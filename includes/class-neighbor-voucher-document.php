<?php
/**
 * Shared neighbor-facing voucher document rendering and storage.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Neighbor_Voucher_Document {

    /**
     * Base uploads subdirectory for voucher-owned files.
     */
    const BASE_SUBDIR = 'svdp-vouchers';

    /**
     * Shared template path for neighbor-facing voucher documents.
     */
    const TEMPLATE_RELATIVE_PATH = 'public/templates/documents/neighbor-voucher.php';

    /**
     * Default stored file name for one rendered voucher document.
     */
    const DEFAULT_FILE_NAME = 'neighbor-voucher.html';

    /**
     * Generate and store one shared neighbor-facing voucher document.
     *
     * @param array|int $voucher Formatted cashier voucher array or voucher ID.
     * @param array     $args Optional generation arguments.
     * @return array|WP_Error
     */
    public static function create_for_voucher($voucher, $args = []) {
        $voucher = self::normalize_voucher_payload($voucher);
        if (is_wp_error($voucher)) {
            return $voucher;
        }

        $voucher_id = intval($voucher['id'] ?? 0);
        if ($voucher_id <= 0) {
            return new WP_Error('neighbor_voucher_invalid', 'Neighbor voucher generation requires a valid voucher.', ['status' => 400]);
        }

        $html = self::render_html($voucher, $args);
        if (is_wp_error($html)) {
            return $html;
        }

        $file_name = !empty($args['file_name'])
            ? sanitize_file_name($args['file_name'])
            : self::DEFAULT_FILE_NAME;

        return self::store_document($voucher_id, $file_name, $html);
    }

    /**
     * Render the shared neighbor-facing voucher HTML.
     *
     * @param array|int $voucher Formatted cashier voucher array or voucher ID.
     * @param array     $args Optional rendering arguments.
     * @return string|WP_Error
     */
    public static function render_html($voucher, $args = []) {
        $document = self::get_template_context($voucher, $args);
        if (is_wp_error($document)) {
            return $document;
        }

        return self::render_template(self::TEMPLATE_RELATIVE_PATH, [
            'document' => $document,
        ]);
    }

    /**
     * Build the shared template context for one voucher.
     *
     * @param array|int $voucher Formatted cashier voucher array or voucher ID.
     * @param array     $args Optional rendering arguments.
     * @return array|WP_Error
     */
    public static function get_template_context($voucher, $args = []) {
        $voucher = self::normalize_voucher_payload($voucher);
        if (is_wp_error($voucher)) {
            return $voucher;
        }

        $voucher_type = SVDP_Voucher::normalize_voucher_type($voucher['voucher_type'] ?? 'clothing');
        $requested_items = $voucher_type === 'furniture'
            ? self::build_furniture_requested_items($voucher)
            : self::build_clothing_requested_items($voucher);
        $requested_items_total = array_reduce($requested_items, function($carry, $item) {
            return $carry + intval($item['quantity'] ?? 0);
        }, 0);

        return [
            'language' => sanitize_key($args['language'] ?? 'en'),
            'voucher_id' => (int) $voucher['id'],
            'voucher_type' => $voucher_type,
            'voucher_type_label' => sanitize_text_field($voucher['voucher_type_label'] ?? ucfirst($voucher_type)),
            'neighbor_name' => self::format_neighbor_name($voucher),
            'date_of_birth_display' => self::format_date($voucher['dob'] ?? null),
            'conference_name' => sanitize_text_field((string) ($voucher['conference_name'] ?? '')),
            'household_display' => self::format_household_display($voucher),
            'created_date_display' => self::format_date($voucher['voucher_created_date'] ?? null),
            'valid_through_display' => self::format_expiration_date($voucher['voucher_created_date'] ?? null),
            'approved_amount_display' => self::build_approved_amount_display($voucher),
            'delivery_required' => !empty($voucher['delivery_required']),
            'delivery_label' => !empty($voucher['delivery_required']) ? 'Included' : 'Not included',
            'requested_items' => $requested_items,
            'requested_items_total' => $requested_items_total,
            'requested_items_total_label' => self::format_item_count($requested_items_total),
        ];
    }

    /**
     * Resolve a public URL from one stored relative document path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return string|null
     */
    public static function public_url_from_relative_path($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return null;
        }

        $uploads = wp_upload_dir();
        return trailingslashit($uploads['baseurl']) . $relative_path;
    }

    /**
     * Delete one stored document file by relative path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return void
     */
    public static function delete_document($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return;
        }

        $uploads = wp_upload_dir();
        $absolute_path = trailingslashit($uploads['basedir']) . $relative_path;
        if (file_exists($absolute_path)) {
            wp_delete_file($absolute_path);
        }
    }

    /**
     * Normalize one voucher payload to the shared cashier array shape.
     *
     * @param array|int|object $voucher Voucher payload or voucher ID.
     * @return array|WP_Error
     */
    private static function normalize_voucher_payload($voucher) {
        if (is_numeric($voucher)) {
            $voucher = SVDP_Voucher::get_cashier_voucher(intval($voucher));
        } elseif (is_object($voucher)) {
            $voucher = (array) $voucher;
        }

        if (!is_array($voucher)) {
            return new WP_Error('neighbor_voucher_invalid', 'Neighbor voucher rendering requires a formatted voucher payload.', ['status' => 400]);
        }

        if (intval($voucher['id'] ?? 0) <= 0) {
            return new WP_Error('neighbor_voucher_invalid', 'Neighbor voucher rendering requires a valid voucher ID.', ['status' => 400]);
        }

        return $voucher;
    }

    /**
     * Collapse clothing voucher data into one neighbor-facing requested item list.
     *
     * @param array $voucher Formatted cashier voucher.
     * @return array
     */
    private static function build_clothing_requested_items($voucher) {
        $item_count = max(0, intval($voucher['voucher_items_count'] ?? 0));
        if ($item_count <= 0) {
            return [];
        }

        return [[
            'name' => 'Clothing voucher items',
            'quantity' => $item_count,
            'quantity_label' => self::format_item_count($item_count),
            'description' => self::format_household_display($voucher),
        ]];
    }

    /**
     * Collapse furniture voucher snapshots into grouped neighbor-facing items.
     *
     * @param array $voucher Formatted cashier voucher.
     * @return array
     */
    private static function build_furniture_requested_items($voucher) {
        $grouped_items = [];

        foreach ((array) ($voucher['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item_name = sanitize_text_field((string) ($item['requested_item_name'] ?? ''));
            if ($item_name === '') {
                continue;
            }

            $category_label = sanitize_text_field((string) ($item['requested_category_label'] ?? ''));
            $group_key = strtolower($item_name . '|' . $category_label);

            if (!isset($grouped_items[$group_key])) {
                $grouped_items[$group_key] = [
                    'name' => $item_name,
                    'quantity' => 0,
                    'quantity_label' => '',
                    'description' => $category_label !== '' ? $category_label : null,
                ];
            }

            $grouped_items[$group_key]['quantity']++;
        }

        if (empty($grouped_items)) {
            $item_count = max(0, intval($voucher['voucher_items_count'] ?? 0));
            if ($item_count <= 0) {
                return [];
            }

            return [[
                'name' => 'Requested furniture items',
                'quantity' => $item_count,
                'quantity_label' => self::format_item_count($item_count),
                'description' => null,
            ]];
        }

        foreach ($grouped_items as &$item) {
            $item['quantity_label'] = self::format_item_count($item['quantity']);
        }
        unset($item);

        return array_values($grouped_items);
    }

    /**
     * Format one neighbor-facing approved amount display.
     *
     * @param array $voucher Formatted cashier voucher.
     * @return string
     */
    private static function build_approved_amount_display($voucher) {
        $voucher_type = SVDP_Voucher::normalize_voucher_type($voucher['voucher_type'] ?? 'clothing');

        if ($voucher_type === 'furniture') {
            $amount_min = self::to_nullable_float($voucher['estimated_requestor_portion_min'] ?? null);
            $amount_max = self::to_nullable_float($voucher['estimated_requestor_portion_max'] ?? null);

            if ($amount_min !== null || $amount_max !== null) {
                return self::format_money_range($amount_min, $amount_max);
            }
        }

        return self::format_money((float) ($voucher['voucher_value'] ?? 0));
    }

    /**
     * Build the printable household summary text.
     *
     * @param array $voucher Formatted cashier voucher.
     * @return string
     */
    private static function format_household_display($voucher) {
        $adults = max(0, intval($voucher['adults'] ?? 0));
        $children = max(0, intval($voucher['children'] ?? 0));

        return sprintf(
            '%d adult%s, %d child%s',
            $adults,
            $adults === 1 ? '' : 's',
            $children,
            $children === 1 ? '' : 'ren'
        );
    }

    /**
     * Format the neighbor name from the voucher payload.
     *
     * @param array $voucher Formatted cashier voucher.
     * @return string
     */
    private static function format_neighbor_name($voucher) {
        return trim(
            sanitize_text_field((string) ($voucher['first_name'] ?? '')) . ' ' .
            sanitize_text_field((string) ($voucher['last_name'] ?? ''))
        );
    }

    /**
     * Format one calendar date for the document.
     *
     * @param string|null $date_raw Raw date string.
     * @return string
     */
    private static function format_date($date_raw) {
        $date_raw = trim((string) $date_raw);
        if ($date_raw === '') {
            return '';
        }

        try {
            return (new DateTime($date_raw))->format('m/d/Y');
        } catch (Exception $exception) {
            return $date_raw;
        }
    }

    /**
     * Format the default 30-day expiration date for one voucher.
     *
     * @param string|null $created_date Raw creation date.
     * @return string
     */
    private static function format_expiration_date($created_date) {
        $created_date = trim((string) $created_date);
        if ($created_date === '') {
            return '';
        }

        try {
            $expiration = new DateTime($created_date);
            $expiration->modify('+30 days');
            return $expiration->format('m/d/Y');
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * Format a fixed money amount.
     *
     * @param float $amount Currency amount.
     * @return string
     */
    private static function format_money($amount) {
        return '$' . number_format((float) $amount, 2);
    }

    /**
     * Format a money range or one fixed amount.
     *
     * @param float|null $min Minimum amount.
     * @param float|null $max Maximum amount.
     * @return string
     */
    private static function format_money_range($min, $max) {
        $min = $min !== null ? (float) $min : 0.0;
        $max = $max !== null ? (float) $max : $min;

        if (abs($max - $min) < 0.01) {
            return self::format_money($min);
        }

        return self::format_money($min) . ' - ' . self::format_money($max);
    }

    /**
     * Format one item count label.
     *
     * @param int $count Item count.
     * @return string
     */
    private static function format_item_count($count) {
        $count = max(0, intval($count));
        return sprintf('%d item%s', $count, $count === 1 ? '' : 's');
    }

    /**
     * Normalize one float-ish value while preserving nulls.
     *
     * @param mixed $value Input value.
     * @return float|null
     */
    private static function to_nullable_float($value) {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Render one plugin template with extracted variables.
     *
     * @param string $relative_path Template path relative to the plugin root.
     * @param array  $vars Template variables.
     * @return string
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
     * Store one rendered document under the voucher-specific uploads directory.
     *
     * @param int    $voucher_id Root voucher ID.
     * @param string $file_name Target file name.
     * @param string $html Rendered HTML payload.
     * @return array|WP_Error
     */
    private static function store_document($voucher_id, $file_name, $html) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return new WP_Error('neighbor_voucher_uploads_unavailable', 'The uploads directory is not available for neighbor voucher storage.', ['status' => 500]);
        }

        $relative_dir = trailingslashit(self::BASE_SUBDIR . '/' . intval($voucher_id)) . 'documents';
        $absolute_dir = trailingslashit($uploads['basedir']) . $relative_dir;

        if (!wp_mkdir_p($absolute_dir)) {
            return new WP_Error('neighbor_voucher_directory_failed', 'The neighbor voucher storage directory could not be created.', ['status' => 500]);
        }

        $sanitized_file_name = sanitize_file_name($file_name);
        $relative_path = trailingslashit($relative_dir) . $sanitized_file_name;
        $absolute_path = trailingslashit($absolute_dir) . $sanitized_file_name;
        $bytes_written = file_put_contents($absolute_path, $html);

        if ($bytes_written === false) {
            return new WP_Error('neighbor_voucher_write_failed', 'The neighbor voucher file could not be written to storage.', ['status' => 500]);
        }

        return [
            'file_path' => $relative_path,
            'url' => self::public_url_from_relative_path($relative_path),
        ];
    }

    /**
     * Ensure one stored document path stays inside the managed voucher subtree.
     *
     * @param mixed $relative_path Stored relative uploads path.
     * @return string|null
     */
    private static function normalize_managed_relative_path($relative_path) {
        $relative_path = ltrim(wp_normalize_path((string) $relative_path), '/');
        if ($relative_path === '' || strpos($relative_path, '../') !== false) {
            return null;
        }

        $base_prefix = trailingslashit(self::BASE_SUBDIR);
        if (strpos($relative_path, $base_prefix) !== 0 || strpos($relative_path, '/documents/') === false) {
            return null;
        }

        return $relative_path;
    }
}
