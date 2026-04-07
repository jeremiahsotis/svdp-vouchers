<?php
/**
 * Cashier shell fragments and keepalive handling.
 */
class SVDP_Cashier_Shell {

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
        $vouchers = self::filter_vouchers($all_vouchers, $filters);
        $stats = self::build_stats($all_vouchers, $vouchers);

        $html = self::render_template('public/templates/cashier/partials/voucher-list.php', [
            'filters' => $filters,
            'selected_id' => $filters['selected_id'],
            'stats' => $stats,
            'vouchers' => $vouchers,
        ]);

        return self::html_response($html);
    }

    /**
     * Render the cashier detail pane for a single voucher.
     */
    public static function get_voucher_detail_fragment($request) {
        $voucher_id = intval($request['id']);
        $voucher = SVDP_Voucher::get_cashier_voucher($voucher_id);
        $view = sanitize_key((string) $request->get_param('view'));
        $can_mutate_furniture = SVDP_Permissions::user_can_redeem_furniture_vouchers();
        $furniture_catalog_items = [];
        $cancellation_reasons = [];

        if (!$voucher) {
            if ($view === 'neighbor-document') {
                return new WP_Error('voucher_not_found', 'Voucher not found.', ['status' => 404]);
            }

            $html = self::render_template('public/templates/cashier/partials/detail-empty.php', [
                'message' => 'Select a voucher from the list to view its details.',
            ]);

            return self::html_response($html);
        }

        if ($view === 'neighbor-document') {
            return self::get_neighbor_document_response($voucher, $request);
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
     * Email the shared neighbor-facing voucher PDF from the cashier action flow.
     */
    public static function email_neighbor_document($request) {
        $voucher_id = intval($request['id']);
        $voucher = SVDP_Voucher::get_cashier_voucher($voucher_id);

        if (!$voucher) {
            return new WP_Error('voucher_not_found', 'Voucher not found.', ['status' => 404]);
        }

        $language = SVDP_Voucher_I18n::normalize_language($request->get_param('language'));
        $result = SVDP_Neighbor_Voucher_Document::email_for_voucher($voucher, [
            'language' => $language,
        ]);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'voucherId' => (int) $voucher['id'],
            'language' => $result['language'],
            'recipientEmail' => $result['recipient_email'],
            'fileUrl' => $result['file_url'],
        ]);
    }

    /**
     * Fetch reusable neighbor delivery preferences for a voucher.
     */
    public static function get_neighbor_delivery_preferences($request) {
        $voucher_id = intval($request['id']);
        $lookup_key = SVDP_Voucher::get_neighbor_lookup_key_for_voucher_id($voucher_id);

        if ($lookup_key === '') {
            return new WP_Error('voucher_not_found', 'Voucher not found.', ['status' => 404]);
        }

        $preferences = SVDP_Voucher::get_preferences_for_voucher_id($voucher_id);

        return rest_ensure_response([
            'success' => true,
            'voucherId' => $voucher_id,
            'preferences' => SVDP_Neighbor_Delivery_Preferences::normalize_preference_payload($preferences),
        ]);
    }

    /**
     * Save reusable neighbor delivery preferences for a voucher.
     */
    public static function save_neighbor_delivery_preferences($request) {
        $voucher_id = intval($request['id']);
        $lookup_key = SVDP_Voucher::get_neighbor_lookup_key_for_voucher_id($voucher_id);

        if ($lookup_key === '') {
            return new WP_Error('voucher_not_found', 'Voucher not found.', ['status' => 404]);
        }

        $params = self::get_request_data($request);
        $stored_preferences = SVDP_Voucher::upsert_preferences_for_voucher_id($voucher_id, [
            'preferred_language' => sanitize_text_field($params['preferred_language'] ?? ''),
            'is_opted_in' => $params['is_opted_in'] ?? 0,
            'auto_send_enabled' => $params['auto_send_enabled'] ?? 0,
            'email_enabled' => $params['email_enabled'] ?? 0,
            'email_address' => sanitize_email($params['email_address'] ?? ''),
            'sms_enabled' => $params['sms_enabled'] ?? 0,
            'phone_number' => sanitize_text_field($params['phone_number'] ?? ''),
            'notifications_paused' => $params['notifications_paused'] ?? 0,
        ]);

        if ($stored_preferences === false) {
            return new WP_Error(
                'neighbor_delivery_preferences_save_failed',
                'Failed to save neighbor delivery preferences.',
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'voucherId' => $voucher_id,
            'preferences' => SVDP_Neighbor_Delivery_Preferences::normalize_preference_payload($stored_preferences),
        ]);
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
     * Render the shared neighbor-facing voucher document for cashier open/print actions.
     */
    private static function get_neighbor_document_response($voucher, $request) {
        $language = SVDP_Voucher_I18n::normalize_language($request->get_param('language'));
        $html = SVDP_Neighbor_Voucher_Document::render_html($voucher, [
            'language' => $language,
        ]);

        if (is_wp_error($html)) {
            return $html;
        }

        if (rest_sanitize_boolean($request->get_param('auto_print'))) {
            $html = self::inject_auto_print_script($html);
        }

        return self::html_response($html);
    }

    /**
     * Build cashier dashboard metrics.
     */
    private static function build_stats($all_vouchers, $filtered_vouchers) {
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
            'showing' => count($filtered_vouchers),
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
        ];
    }

    /**
     * Merge JSON and form parameters so cashier hooks can accept either transport.
     */
    private static function get_request_data($request) {
        $json_params = $request->get_json_params();
        if (!is_array($json_params)) {
            $json_params = [];
        }

        return array_merge($request->get_params(), $json_params);
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

    /**
     * Trigger the browser print dialog when the cashier opened the document in print mode.
     */
    private static function inject_auto_print_script($html) {
        $script = '<script>window.addEventListener("load",function(){window.print();});</script>';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $script . '</body>', $html, 1);
        }

        return $html . $script;
    }
}
