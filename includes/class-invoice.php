<?php
/**
 * Stored conference invoice generation and admin listing.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Invoice {

    /**
     * Generate and store one conference invoice for a completed furniture voucher.
     *
     * @param array $voucher Formatted cashier voucher data.
     * @return array|WP_Error
     */
    public static function create_for_furniture_voucher($voucher) {
        global $wpdb;

        $voucher_id = intval($voucher['id'] ?? 0);
        $conference_id = intval($voucher['conference_id'] ?? 0);
        if ($voucher_id <= 0 || $conference_id <= 0) {
            return new WP_Error('invoice_voucher_invalid', 'Invoice generation requires a valid furniture voucher and conference.', ['status' => 400]);
        }

        $existing_invoice = self::get_by_voucher_id($voucher_id);
        if ($existing_invoice) {
            return new WP_Error('invoice_exists', 'An invoice already exists for this furniture voucher.', ['status' => 409]);
        }

        $invoice_number = self::generate_invoice_number($voucher_id);
        $invoice_date = current_time('Y-m-d');
        $totals = self::calculate_totals($voucher);
        $html = self::render_template('public/templates/documents/furniture-invoice.php', [
            'voucher' => $voucher,
            'invoice_number' => $invoice_number,
            'invoice_date' => $invoice_date,
            'totals' => $totals,
        ]);

        $document = self::store_document($voucher_id, 'conference-invoice.html', $html);
        if (is_wp_error($document)) {
            return $document;
        }

        $table = $wpdb->prefix . 'svdp_invoices';
        $inserted = $wpdb->insert($table, [
            'voucher_id' => $voucher_id,
            'conference_id' => $conference_id,
            'invoice_number' => $invoice_number,
            'invoice_date' => $invoice_date,
            'amount' => $totals['amount'],
            'delivery_fee' => $totals['delivery_fee'],
            'items_total' => $totals['items_total'],
            'conference_share_total' => $totals['conference_share_total'],
            'stored_file_path' => $document['file_path'],
        ]);

        if ($inserted === false) {
            self::delete_document($document['file_path']);
            return new WP_Error('invoice_insert_failed', 'The invoice row could not be stored.', ['status' => 500]);
        }

        return [
            'invoice_id' => (int) $wpdb->insert_id,
            'invoice_number' => $invoice_number,
            'invoice_date' => $invoice_date,
            'amount' => $totals['amount'],
            'delivery_fee' => $totals['delivery_fee'],
            'items_total' => $totals['items_total'],
            'conference_share_total' => $totals['conference_share_total'],
            'file_path' => $document['file_path'],
            'url' => $document['url'],
        ];
    }

    /**
     * REST callback: list filtered invoices for admin reporting.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function get_admin_invoices($request) {
        $filters = self::normalize_admin_filters($request->get_params());
        if (is_wp_error($filters)) {
            return $filters;
        }

        $invoices = self::get_filtered_invoices($filters);
        if (is_wp_error($invoices)) {
            return $invoices;
        }

        $total_amount = 0.0;
        foreach ($invoices as $invoice) {
            $total_amount += (float) $invoice['amount'];
        }

        return rest_ensure_response([
            'success' => true,
            'filters' => [
                'conferenceId' => $filters['conference_id'] > 0 ? $filters['conference_id'] : null,
                'periodStart' => $filters['period_start'],
                'periodEnd' => $filters['period_end'],
                'statementStatus' => $filters['statement_status'],
            ],
            'invoices' => $invoices,
            'meta' => [
                'count' => count($invoices),
                'totalAmount' => round($total_amount, 2),
            ],
        ]);
    }

    /**
     * Query filtered invoices for admin reporting or statement generation.
     *
     * @param array $filters Filter arguments.
     * @return array|WP_Error
     */
    public static function get_filtered_invoices($filters = []) {
        $filters = self::normalize_admin_filters($filters);
        if (is_wp_error($filters)) {
            return $filters;
        }

        $rows = self::query_filtered_invoices($filters);
        $formatted = [];

        foreach ($rows as $row) {
            $formatted[] = self::format_admin_invoice($row);
        }

        return $formatted;
    }

    /**
     * Fetch the stored invoice row for one voucher if it exists.
     *
     * @param int $voucher_id Root voucher ID.
     * @return object|null
     */
    public static function get_by_voucher_id($voucher_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'svdp_invoices';
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT *
             FROM $table
             WHERE voucher_id = %d
             ORDER BY id DESC
             LIMIT 1",
            intval($voucher_id)
        ));

        if (!$invoice) {
            return null;
        }

        $invoice->stored_file_url = self::public_url_from_relative_path($invoice->stored_file_path);
        return $invoice;
    }

    /**
     * Delete one stored invoice row and its file.
     *
     * @param int         $invoice_id Invoice row ID.
     * @param string|null $file_path Stored relative file path.
     * @return void
     */
    public static function delete_invoice($invoice_id, $file_path = null) {
        global $wpdb;

        if ($file_path !== null) {
            self::delete_document($file_path);
        }

        if (intval($invoice_id) > 0) {
            $wpdb->delete($wpdb->prefix . 'svdp_invoices', ['id' => intval($invoice_id)]);
        }
    }

    /**
     * Resolve a public URL from one stored relative invoice path.
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
     * Build one normalized invoice payload for admin reporting.
     *
     * @param object $invoice Raw invoice row.
     * @return array
     */
    private static function format_admin_invoice($invoice) {
        $stored_file_path = $invoice->stored_file_path ?? null;
        $neighbor_name = trim(
            sanitize_text_field((string) ($invoice->first_name ?? '')) . ' ' .
            sanitize_text_field((string) ($invoice->last_name ?? ''))
        );

        return [
            'id' => (int) $invoice->id,
            'voucher_id' => (int) $invoice->voucher_id,
            'conference_id' => (int) $invoice->conference_id,
            'conference_name' => $invoice->conference_name ?? '',
            'neighbor_name' => $neighbor_name !== '' ? $neighbor_name : null,
            'voucher_type' => $invoice->voucher_type ?? null,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date,
            'amount' => (float) $invoice->amount,
            'delivery_fee' => (float) $invoice->delivery_fee,
            'items_total' => (float) $invoice->items_total,
            'conference_share_total' => (float) $invoice->conference_share_total,
            'statement_id' => $invoice->statement_id !== null ? (int) $invoice->statement_id : null,
            'statement_number' => $invoice->statement_number ?? null,
            'statement_period_start' => $invoice->statement_period_start ?? null,
            'statement_period_end' => $invoice->statement_period_end ?? null,
            'stored_file_path' => $stored_file_path,
            'stored_file_url' => self::public_url_from_relative_path($stored_file_path),
        ];
    }

    /**
     * Query invoice rows using the admin filter set.
     *
     * @param array $filters Normalized filters.
     * @return array
     */
    private static function query_filtered_invoices($filters) {
        global $wpdb;

        $invoices_table = $wpdb->prefix . 'svdp_invoices';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $statements_table = $wpdb->prefix . 'svdp_invoice_statements';
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';

        $sql = "
            SELECT
                i.*,
                c.name AS conference_name,
                s.statement_number,
                s.period_start AS statement_period_start,
                s.period_end AS statement_period_end,
                v.first_name,
                v.last_name,
                v.voucher_type
            FROM $invoices_table i
            LEFT JOIN $conferences_table c ON c.id = i.conference_id
            LEFT JOIN $statements_table s ON s.id = i.statement_id
            LEFT JOIN $vouchers_table v ON v.id = i.voucher_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($filters['conference_id'] > 0) {
            $sql .= " AND i.conference_id = %d";
            $params[] = $filters['conference_id'];
        }

        if ($filters['statement_id'] > 0) {
            $sql .= " AND i.statement_id = %d";
            $params[] = $filters['statement_id'];
        }

        if ($filters['period_start'] !== null) {
            $sql .= " AND i.invoice_date >= %s";
            $params[] = $filters['period_start'];
        }

        if ($filters['period_end'] !== null) {
            $sql .= " AND i.invoice_date <= %s";
            $params[] = $filters['period_end'];
        }

        if ($filters['statement_status'] === 'unstatemented') {
            $sql .= " AND i.statement_id IS NULL";
        } elseif ($filters['statement_status'] === 'statemented') {
            $sql .= " AND i.statement_id IS NOT NULL";
        }

        $sql .= " ORDER BY i.invoice_date DESC, i.id DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Normalize admin invoice filters from REST or PHP input.
     *
     * @param array $raw_filters Raw filter collection.
     * @return array|WP_Error
     */
    private static function normalize_admin_filters($raw_filters) {
        if (!is_array($raw_filters)) {
            $raw_filters = [];
        }

        $conference_id = intval($raw_filters['conferenceId'] ?? $raw_filters['conference_id'] ?? 0);
        $statement_id = intval($raw_filters['statementId'] ?? $raw_filters['statement_id'] ?? 0);
        $period_start = self::sanitize_filter_date($raw_filters['periodStart'] ?? $raw_filters['period_start'] ?? null, 'periodStart');
        if (is_wp_error($period_start)) {
            return $period_start;
        }

        $period_end = self::sanitize_filter_date($raw_filters['periodEnd'] ?? $raw_filters['period_end'] ?? null, 'periodEnd');
        if (is_wp_error($period_end)) {
            return $period_end;
        }

        $statement_status = strtolower(trim((string) ($raw_filters['statementStatus'] ?? $raw_filters['statement_status'] ?? 'all')));
        if ($statement_status === '') {
            $statement_status = 'all';
        }

        $allowed_statement_statuses = ['all', 'unstatemented', 'statemented'];
        if (!in_array($statement_status, $allowed_statement_statuses, true)) {
            return new WP_Error('invalid_statement_status', 'Select a valid statement status filter.', ['status' => 400]);
        }

        if ($period_start !== null && $period_end !== null && $period_start > $period_end) {
            return new WP_Error('invalid_period_range', 'The start date must be on or before the end date.', ['status' => 400]);
        }

        return [
            'conference_id' => max(0, $conference_id),
            'statement_id' => max(0, $statement_id),
            'period_start' => $period_start,
            'period_end' => $period_end,
            'statement_status' => $statement_status,
        ];
    }

    /**
     * Validate a filter date in YYYY-MM-DD format.
     *
     * @param mixed  $value Raw date value.
     * @param string $field Field label for error responses.
     * @return string|null|WP_Error
     */
    private static function sanitize_filter_date($value, $field) {
        if ($value === null || $value === '') {
            return null;
        }

        $value = sanitize_text_field((string) $value);
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value, wp_timezone());

        if (!$date || $date->format('Y-m-d') !== $value) {
            return new WP_Error('invalid_date', sprintf('The %s value must use YYYY-MM-DD format.', $field), ['status' => 400]);
        }

        return $value;
    }

    /**
     * Delete one stored invoice document file by relative path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return void
     */
    private static function delete_document($relative_path) {
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
     * Compute invoice totals from completed furniture item outcomes.
     *
     * @param array $voucher Formatted cashier voucher data.
     * @return array
     */
    private static function calculate_totals($voucher) {
        $items_total = 0.0;

        foreach ((array) ($voucher['items'] ?? []) as $item) {
            if (($item['status'] ?? '') === 'completed' && isset($item['actual_price'])) {
                $items_total += (float) $item['actual_price'];
            }
        }

        $items_total = round($items_total, 2);
        $conference_share_total = round($items_total * 0.5, 2);
        $delivery_fee = !empty($voucher['delivery_required']) ? round((float) ($voucher['delivery_fee'] ?? 0), 2) : 0.0;
        $amount = round($conference_share_total + $delivery_fee, 2);

        return [
            'items_total' => $items_total,
            'conference_share_total' => $conference_share_total,
            'delivery_fee' => $delivery_fee,
            'amount' => $amount,
        ];
    }

    /**
     * Generate a stable unique invoice number per voucher.
     *
     * @param int $voucher_id Root voucher ID.
     * @return string
     */
    private static function generate_invoice_number($voucher_id) {
        return 'INV-' . current_time('Ymd') . '-' . str_pad((string) intval($voucher_id), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Render a document template inside the plugin.
     *
     * @param string $relative_path Template path relative to plugin dir.
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
     * Store rendered invoice HTML in the voucher documents directory.
     *
     * @param int    $voucher_id Root voucher ID.
     * @param string $file_name Target file name.
     * @param string $html Rendered HTML payload.
     * @return array|WP_Error
     */
    private static function store_document($voucher_id, $file_name, $html) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return new WP_Error('invoice_uploads_unavailable', 'The uploads directory is not available for invoice storage.', ['status' => 500]);
        }

        $relative_dir = trailingslashit(SVDP_Furniture_Photo_Storage::BASE_SUBDIR . '/' . intval($voucher_id)) . 'documents';
        $absolute_dir = trailingslashit($uploads['basedir']) . $relative_dir;

        if (!wp_mkdir_p($absolute_dir)) {
            return new WP_Error('invoice_directory_failed', 'The invoice storage directory could not be created.', ['status' => 500]);
        }

        $relative_path = trailingslashit($relative_dir) . sanitize_file_name($file_name);
        $absolute_path = trailingslashit($absolute_dir) . sanitize_file_name($file_name);
        $bytes_written = file_put_contents($absolute_path, $html);

        if ($bytes_written === false) {
            return new WP_Error('invoice_write_failed', 'The invoice file could not be written to storage.', ['status' => 500]);
        }

        return [
            'file_path' => $relative_path,
            'url' => self::public_url_from_relative_path($relative_path),
        ];
    }

    /**
     * Ensure one stored invoice path stays inside the voucher documents subtree.
     *
     * @param mixed $relative_path Stored relative uploads path.
     * @return string|null
     */
    private static function normalize_managed_relative_path($relative_path) {
        $relative_path = ltrim(wp_normalize_path((string) $relative_path), '/');
        if ($relative_path === '' || strpos($relative_path, '../') !== false) {
            return null;
        }

        $base_prefix = trailingslashit(SVDP_Furniture_Photo_Storage::BASE_SUBDIR);
        if (strpos($relative_path, $base_prefix) !== 0 || strpos($relative_path, '/documents/') === false) {
            return null;
        }

        return $relative_path;
    }
}
