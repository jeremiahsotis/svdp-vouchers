<?php
/**
 * Cashier shell fragments and keepalive handling.
 */
class SVDP_Cashier_Shell {
    const DEFAULT_VISIBLE_COUNT = 5;
    const VISIBLE_INCREMENT = 5;

    /**
     * Keep cashier sessions warm while the shell stays open.
     */
    public static function ping($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_authenticated', 'You must be logged in.', ['status' => 401]);
        }

        wp_set_auth_cookie(get_current_user_id(), true, is_ssl());

        return rest_ensure_response([
            'authenticated' => true,
            'nonce' => wp_create_nonce('wp_rest'),
            'timestamp' => current_time('mysql'),
        ]);
    }

    /**
     * Render the cashier list pane.
     */
    public static function get_vouchers_fragment($request) {
        $filters = self::get_filters($request);
        $all_vouchers = SVDP_Voucher::get_cashier_vouchers();
        $filtered_vouchers = self::filter_vouchers($all_vouchers, $filters);
        $filtered_total = count($filtered_vouchers);
        $visible_count = min($filters['visible_count'], $filtered_total);
        $vouchers = array_slice($filtered_vouchers, 0, $filters['visible_count']);
        $stats = self::build_stats($all_vouchers, $filtered_vouchers, $visible_count);
        $has_more = $filtered_total > $filters['visible_count'];

        $html = self::render_template('public/templates/cashier/partials/voucher-list.php', [
            'filters' => $filters,
            'selected_id' => $filters['selected_id'],
            'stats' => $stats,
            'vouchers' => $vouchers,
            'filtered_total' => $filtered_total,
            'visible_count' => $visible_count,
            'has_more' => $has_more,
            'next_visible_count' => min($filtered_total, $filters['visible_count'] + self::VISIBLE_INCREMENT),
        ]);

        return self::html_response($html);
    }

    /**
     * Render the cashier detail pane for a single voucher.
     */
    public static function get_voucher_detail_fragment($request) {
        $voucher_id = intval($request['id']);
        $voucher = SVDP_Voucher::get_cashier_voucher($voucher_id);
        $can_mutate_furniture = SVDP_Permissions::user_can_redeem_furniture_vouchers();
        $furniture_catalog_items = [];
        $cancellation_reasons = [];

        if (!$voucher) {
            $html = self::render_template('public/templates/cashier/partials/detail-empty.php', [
                'message' => SVDP_Voucher_Copy::get_cashier_message('emptyDetailMessage'),
            ]);

            return self::html_response($html);
        }

        if (($voucher['voucher_type'] ?? 'clothing') === 'furniture') {
            $furniture_catalog_items = SVDP_Furniture_Catalog::get_all(false);
            $cancellation_reasons = SVDP_Furniture_Cancellation_Reason::get_all(false);
        }

        $html = self::render_template('public/templates/cashier/partials/voucher-detail.php', [
            'voucher' => $voucher,
            'can_mutate_furniture' => $can_mutate_furniture,
            'furniture_catalog_items' => $furniture_catalog_items,
            'cancellation_reasons' => $cancellation_reasons,
        ]);

        return self::html_response($html);
    }

    /**
     * Apply list filters in PHP so the fragment can stay server rendered.
     */
    private static function filter_vouchers($vouchers, $filters) {
        $filtered = array_filter($vouchers, function($voucher) use ($filters) {
            if (!empty($filters['search'])) {
                $searchable = strtolower(implode(' ', [
                    $voucher['first_name'],
                    $voucher['last_name'],
                    $voucher['dob'],
                    $voucher['conference_name'],
                ]));

                if (strpos($searchable, strtolower($filters['search'])) === false) {
                    return false;
                }
            }

            if ($filters['filter'] === 'active' && $voucher['status'] !== 'Active') {
                return false;
            }

            if ($filters['filter'] === 'redeemed' && $voucher['status'] !== 'Redeemed') {
                return false;
            }

            if ($filters['filter'] === 'expired' && $voucher['status'] !== 'Expired') {
                return false;
            }

            if ($filters['filter'] === 'coat-available' && !$voucher['coat_eligible']) {
                return false;
            }

            return true;
        });

        usort($filtered, function($left, $right) use ($filters) {
            switch ($filters['sort']) {
                case 'date-asc':
                    return strcmp($left['voucher_created_date'], $right['voucher_created_date']);

                case 'name-asc':
                    return strcmp(
                        strtolower($left['first_name'] . ' ' . $left['last_name']),
                        strtolower($right['first_name'] . ' ' . $right['last_name'])
                    );

                case 'name-desc':
                    return strcmp(
                        strtolower($right['first_name'] . ' ' . $right['last_name']),
                        strtolower($left['first_name'] . ' ' . $left['last_name'])
                    );

                case 'date-desc':
                default:
                    return strcmp($right['voucher_created_date'], $left['voucher_created_date']);
            }
        });

        return array_values($filtered);
    }

    /**
     * Build cashier dashboard metrics.
     */
    private static function build_stats($all_vouchers, $filtered_vouchers, $visible_count) {
        $today = current_time('Y-m-d');

        return [
            'active' => count(array_filter($all_vouchers, function($voucher) {
                return $voucher['status'] === 'Active';
            })),
            'redeemed_today' => count(array_filter($all_vouchers, function($voucher) use ($today) {
                return $voucher['status'] === 'Redeemed' && $voucher['redeemed_date'] === $today;
            })),
            'coat_available' => count(array_filter($all_vouchers, function($voucher) {
                return $voucher['coat_eligible'] && $voucher['coat_status'] !== 'Issued';
            })),
            'showing' => $visible_count,
            'matching' => count($filtered_vouchers),
        ];
    }

    /**
     * Sanitize query-string filters for HTMX requests.
     */
    private static function get_filters($request) {
        return [
            'search' => sanitize_text_field($request->get_param('search') ?: ''),
            'filter' => sanitize_text_field($request->get_param('filter') ?: 'all'),
            'sort' => sanitize_text_field($request->get_param('sort') ?: 'date-desc'),
            'selected_id' => intval($request->get_param('selected_id')),
            'visible_count' => self::sanitize_visible_count($request->get_param('visible_count')),
        ];
    }

    /**
     * Keep the list display count bounded while preserving Show More state.
     */
    private static function sanitize_visible_count($visible_count) {
        $visible_count = intval($visible_count);

        if ($visible_count < self::DEFAULT_VISIBLE_COUNT) {
            return self::DEFAULT_VISIBLE_COUNT;
        }

        return min($visible_count, 500);
    }

    /**
     * Render a cashier partial.
     */
    private static function render_template($relative_path, $vars = []) {
        $template_path = SVDP_VOUCHERS_PLUGIN_DIR . ltrim($relative_path, '/');

        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Return an HTML fragment response for HTMX.
     */
    private static function html_response($html) {
        return new WP_REST_Response($html, 200, [
            'Content-Type' => 'text/html; charset=' . get_option('blog_charset'),
        ]);
    }
}
