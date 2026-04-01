<?php
/**
 * Statement generation and admin reporting.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Statement {

    const BASE_SUBDIR = 'svdp-vouchers/statements';

    /**
     * REST callback: return the first and last day of the previous month.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response
     */
    public static function get_default_range($request) {
        return rest_ensure_response([
            'success' => true,
            'periodStart' => self::get_default_period_range()['periodStart'],
            'periodEnd' => self::get_default_period_range()['periodEnd'],
        ]);
    }

    /**
     * Generate and store one invoice statement for a conference/date range.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function generate_statement($request) {
        global $wpdb;

        $params = self::get_request_data($request);
        $conference_id = intval($params['conferenceId'] ?? $params['conference_id'] ?? 0);
        $period_start = self::sanitize_required_date($params['periodStart'] ?? $params['period_start'] ?? null, 'periodStart');
        if (is_wp_error($period_start)) {
            return $period_start;
        }

        $period_end = self::sanitize_required_date($params['periodEnd'] ?? $params['period_end'] ?? null, 'periodEnd');
        if (is_wp_error($period_end)) {
            return $period_end;
        }

        if ($conference_id <= 0) {
            return new WP_Error('statement_conference_required', 'Select a conference before generating a statement.', ['status' => 400]);
        }

        if ($period_start > $period_end) {
            return new WP_Error('statement_period_invalid', 'The statement start date must be on or before the end date.', ['status' => 400]);
        }

        $conference = SVDP_Conference::get_by_id($conference_id);
        if (!$conference) {
            return new WP_Error('statement_conference_invalid', 'The selected conference could not be found.', ['status' => 404]);
        }

        $invoices = SVDP_Invoice::get_filtered_invoices([
            'conference_id' => $conference_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'statement_status' => 'unstatemented',
        ]);
        if (is_wp_error($invoices)) {
            return $invoices;
        }

        if (empty($invoices)) {
            return new WP_Error('statement_no_invoices', 'No eligible unstatemented invoices were found for that conference and period.', ['status' => 400]);
        }

        $statement_number = self::generate_statement_number();
        if (is_wp_error($statement_number)) {
            return $statement_number;
        }

        $generated_at = current_time('mysql');
        $totals = self::calculate_totals($invoices);
        $html = self::render_template('public/templates/documents/invoice-statement.php', [
            'statement_number' => $statement_number,
            'conference' => $conference,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'generated_at' => $generated_at,
            'invoices' => $invoices,
            'totals' => $totals,
        ]);

        $document = self::store_document($statement_number, $html);
        if (is_wp_error($document)) {
            return $document;
        }

        $statements_table = $wpdb->prefix . 'svdp_invoice_statements';
        $inserted = $wpdb->insert($statements_table, [
            'statement_number' => $statement_number,
            'conference_id' => $conference_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'generated_at' => $generated_at,
            'generated_by_user_id' => get_current_user_id(),
            'stored_file_path' => $document['file_path'],
        ]);

        if ($inserted === false) {
            self::delete_document($document['file_path']);
            return new WP_Error('statement_insert_failed', 'The statement row could not be stored.', ['status' => 500]);
        }

        $statement_id = (int) $wpdb->insert_id;
        $invoice_ids = array_map('intval', wp_list_pluck($invoices, 'id'));
        $placeholders = implode(', ', array_fill(0, count($invoice_ids), '%d'));
        $update_params = array_merge([$statement_id], $invoice_ids);
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}svdp_invoices
             SET statement_id = %d
             WHERE id IN ($placeholders)
               AND statement_id IS NULL",
            $update_params
        ));

        if ($updated === false || intval($updated) !== count($invoice_ids)) {
            self::rollback_generated_statement($statement_id, $document['file_path']);
            return new WP_Error('statement_attach_failed', 'The eligible invoices could not be attached to the generated statement.', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'statementId' => $statement_id,
            'statementNumber' => $statement_number,
            'statementUrl' => $document['url'],
            'periodStart' => $period_start,
            'periodEnd' => $period_end,
            'invoiceCount' => count($invoices),
            'totalAmount' => $totals['total_amount'],
        ]);
    }

    /**
     * REST callback: return one stored statement plus attached invoices.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function get_statement($request) {
        $statement_id = intval($request['id'] ?? 0);
        if ($statement_id <= 0) {
            return new WP_Error('statement_invalid', 'Statement ID is required.', ['status' => 400]);
        }

        $statement = self::get_statement_record($statement_id);
        if (!$statement) {
            return new WP_Error('statement_not_found', 'Statement not found.', ['status' => 404]);
        }

        $invoices = SVDP_Invoice::get_filtered_invoices([
            'statement_id' => $statement_id,
            'statement_status' => 'all',
        ]);
        if (is_wp_error($invoices)) {
            return $invoices;
        }

        return rest_ensure_response([
            'success' => true,
            'statement' => $statement,
            'invoices' => $invoices,
            'meta' => self::calculate_totals($invoices),
        ]);
    }

    /**
     * Return the first and last day of the previous month for UI defaults.
     *
     * @return array
     */
    public static function get_default_period_range() {
        $timezone = wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);

        return [
            'periodStart' => $now->modify('first day of last month')->format('Y-m-d'),
            'periodEnd' => $now->modify('last day of last month')->format('Y-m-d'),
        ];
    }

    /**
     * Fetch recent statements for the admin tab.
     *
     * @param int $limit Max rows to return.
     * @return array
     */
    public static function get_recent_statements($limit = 20) {
        global $wpdb;

        $limit = max(1, intval($limit));
        $statements_table = $wpdb->prefix . 'svdp_invoice_statements';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                s.*,
                c.name AS conference_name,
                COALESCE(invoice_totals.invoice_count, 0) AS invoice_count,
                COALESCE(invoice_totals.total_amount, 0) AS total_amount
             FROM $statements_table s
             LEFT JOIN $conferences_table c ON c.id = s.conference_id
             LEFT JOIN (
                 SELECT
                     statement_id,
                     COUNT(*) AS invoice_count,
                     SUM(amount) AS total_amount
                 FROM {$wpdb->prefix}svdp_invoices
                 WHERE statement_id IS NOT NULL
                 GROUP BY statement_id
             ) AS invoice_totals ON invoice_totals.statement_id = s.id
             ORDER BY s.generated_at DESC, s.id DESC
             LIMIT %d",
            $limit
        ));

        $statements = [];
        foreach ((array) $rows as $row) {
            $statements[] = self::format_statement_row($row);
        }

        return $statements;
    }

    /**
     * Resolve a public URL from one stored relative statement path.
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
     * Fetch one statement record with conference metadata.
     *
     * @param int $statement_id Statement row ID.
     * @return array|null
     */
    private static function get_statement_record($statement_id) {
        global $wpdb;

        $statements_table = $wpdb->prefix . 'svdp_invoice_statements';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT
                s.*,
                c.name AS conference_name,
                COALESCE(invoice_totals.invoice_count, 0) AS invoice_count,
                COALESCE(invoice_totals.total_amount, 0) AS total_amount
             FROM $statements_table s
             LEFT JOIN $conferences_table c ON c.id = s.conference_id
             LEFT JOIN (
                 SELECT
                     statement_id,
                     COUNT(*) AS invoice_count,
                     SUM(amount) AS total_amount
                 FROM {$wpdb->prefix}svdp_invoices
                 WHERE statement_id IS NOT NULL
                 GROUP BY statement_id
             ) AS invoice_totals ON invoice_totals.statement_id = s.id
             WHERE s.id = %d
             LIMIT 1",
            $statement_id
        ));

        if (!$row) {
            return null;
        }

        return self::format_statement_row($row);
    }

    /**
     * Normalize one statement row for UI/API usage.
     *
     * @param object $row Raw statement row.
     * @return array
     */
    private static function format_statement_row($row) {
        $stored_file_path = $row->stored_file_path ?? null;

        return [
            'id' => (int) $row->id,
            'statement_number' => $row->statement_number,
            'conference_id' => (int) $row->conference_id,
            'conference_name' => $row->conference_name ?? '',
            'period_start' => $row->period_start,
            'period_end' => $row->period_end,
            'generated_at' => $row->generated_at,
            'generated_by_user_id' => $row->generated_by_user_id !== null ? (int) $row->generated_by_user_id : null,
            'stored_file_path' => $stored_file_path,
            'stored_file_url' => self::public_url_from_relative_path($stored_file_path),
            'invoice_count' => (int) ($row->invoice_count ?? 0),
            'total_amount' => (float) ($row->total_amount ?? 0),
        ];
    }

    /**
     * Summarize a statement invoice collection.
     *
     * @param array $invoices Normalized invoice rows.
     * @return array
     */
    private static function calculate_totals($invoices) {
        $total_amount = 0.0;

        foreach ((array) $invoices as $invoice) {
            $total_amount += (float) ($invoice['amount'] ?? 0);
        }

        return [
            'invoice_count' => count((array) $invoices),
            'total_amount' => round($total_amount, 2),
        ];
    }

    /**
     * Generate a unique statement number for accounting documents.
     *
     * @return string|WP_Error
     */
    private static function generate_statement_number() {
        global $wpdb;

        $table = $wpdb->prefix . 'svdp_invoice_statements';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $statement_number = 'STM-' . current_time('Ymd-His') . '-' . wp_rand(100, 999);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE statement_number = %s",
                $statement_number
            ));

            if (intval($exists) === 0) {
                return $statement_number;
            }
        }

        return new WP_Error('statement_number_failed', 'A unique statement number could not be generated.', ['status' => 500]);
    }

    /**
     * Remove a partially generated statement and detach invoices.
     *
     * @param int         $statement_id Statement row ID.
     * @param string|null $file_path Stored file path.
     * @return void
     */
    private static function rollback_generated_statement($statement_id, $file_path = null) {
        global $wpdb;

        $statement_id = intval($statement_id);
        if ($statement_id > 0) {
            $wpdb->update(
                $wpdb->prefix . 'svdp_invoices',
                ['statement_id' => null],
                ['statement_id' => $statement_id]
            );

            $wpdb->delete(
                $wpdb->prefix . 'svdp_invoice_statements',
                ['id' => $statement_id]
            );
        }

        self::delete_document($file_path);
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
     * Store rendered statement HTML in the statements documents directory.
     *
     * @param string $statement_number Generated statement number.
     * @param string $html Rendered HTML payload.
     * @return array|WP_Error
     */
    private static function store_document($statement_number, $html) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return new WP_Error('statement_uploads_unavailable', 'The uploads directory is not available for statement storage.', ['status' => 500]);
        }

        $relative_dir = self::BASE_SUBDIR;
        $absolute_dir = trailingslashit($uploads['basedir']) . $relative_dir;

        if (!wp_mkdir_p($absolute_dir)) {
            return new WP_Error('statement_directory_failed', 'The statement storage directory could not be created.', ['status' => 500]);
        }

        $file_name = sanitize_file_name(strtolower($statement_number) . '.html');
        $relative_path = trailingslashit($relative_dir) . $file_name;
        $absolute_path = trailingslashit($absolute_dir) . $file_name;
        $bytes_written = file_put_contents($absolute_path, $html);

        if ($bytes_written === false) {
            return new WP_Error('statement_write_failed', 'The statement file could not be written to storage.', ['status' => 500]);
        }

        return [
            'file_path' => $relative_path,
            'url' => self::public_url_from_relative_path($relative_path),
        ];
    }

    /**
     * Delete one stored statement document file by relative path.
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
     * Merge JSON and form parameters for REST requests.
     *
     * @param WP_REST_Request $request REST request.
     * @return array
     */
    private static function get_request_data($request) {
        $json_params = $request->get_json_params();
        if (!is_array($json_params)) {
            $json_params = [];
        }

        return array_merge($request->get_params(), $json_params);
    }

    /**
     * Validate a required date in YYYY-MM-DD format.
     *
     * @param mixed  $value Raw date value.
     * @param string $field Field label for error responses.
     * @return string|WP_Error
     */
    private static function sanitize_required_date($value, $field) {
        $value = sanitize_text_field((string) $value);
        if ($value === '') {
            return new WP_Error('statement_date_required', sprintf('The %s field is required.', $field), ['status' => 400]);
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value, wp_timezone());
        if (!$date || $date->format('Y-m-d') !== $value) {
            return new WP_Error('statement_date_invalid', sprintf('The %s value must use YYYY-MM-DD format.', $field), ['status' => 400]);
        }

        return $value;
    }

    /**
     * Ensure one stored statement path stays inside the statements subtree.
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
        if (strpos($relative_path, $base_prefix) !== 0) {
            return null;
        }

        return $relative_path;
    }
}
