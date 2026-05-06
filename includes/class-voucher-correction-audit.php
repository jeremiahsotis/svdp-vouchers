<?php
/**
 * Read-only voucher correction audit queries.
 */
class SVDP_Voucher_Correction_Audit {

    /**
     * Get paginated voucher correction audit rows.
     *
     * @param array $args Filter and pagination arguments.
     * @return array
     */
    public static function get_rows($args = []) {
        global $wpdb;

        $args = self::sanitize_args($args);
        $params = [];
        $where_sql = self::build_where_clause($args, $params);
        $limit = (int) $args['per_page'];
        $offset = ((int) $args['page'] - 1) * $limit;

        $corrections_table = $wpdb->prefix . 'svdp_voucher_corrections';
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';

        $sql = "SELECT
                vc.id,
                vc.voucher_id,
                vc.field_name,
                vc.before_value,
                vc.after_value,
                vc.manager_id,
                vc.manager_name_snapshot,
                vc.actor_user_id,
                COALESCE(NULLIF(u.display_name, ''), u.user_login) AS actor_name,
                u.user_login AS actor_user_login,
                vc.reason_id,
                vc.reason_text_snapshot,
                vc.human_summary,
                vc.created_at,
                v.first_name,
                v.last_name,
                v.dob,
                v.status,
                v.voucher_type,
                c.name AS conference_name
            FROM $corrections_table vc
            LEFT JOIN $vouchers_table v ON v.id = vc.voucher_id
            LEFT JOIN $conferences_table c ON c.id = v.conference_id
            LEFT JOIN {$wpdb->users} u ON u.ID = vc.actor_user_id
            $where_sql
            ORDER BY vc.created_at DESC, vc.id DESC
            LIMIT %d OFFSET %d";

        $params[] = $limit;
        $params[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));

        if (!$rows) {
            return [];
        }

        return array_map([self::class, 'format_row'], $rows);
    }

    /**
     * Count voucher correction audit rows matching filters.
     *
     * @param array $args Filter arguments.
     * @return int
     */
    public static function count_rows($args = []) {
        global $wpdb;

        $args = self::sanitize_args($args);
        $params = [];
        $where_sql = self::build_where_clause($args, $params);

        $corrections_table = $wpdb->prefix . 'svdp_voucher_corrections';
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';

        $sql = "SELECT COUNT(*)
            FROM $corrections_table vc
            LEFT JOIN $vouchers_table v ON v.id = vc.voucher_id
            LEFT JOIN $conferences_table c ON c.id = v.conference_id
            LEFT JOIN {$wpdb->users} u ON u.ID = vc.actor_user_id
            $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get fields approved for voucher correction audit filtering.
     *
     * @return array
     */
    public static function get_allowed_fields() {
        return [
            'adults',
            'children',
            'dob',
            'status',
            'voucher_created_date',
            'delivery_address_line_1',
            'delivery_address_line_2',
            'delivery_city',
            'delivery_state',
            'delivery_zip',
        ];
    }

    /**
     * Sanitize filters and pagination input.
     *
     * @param array $args Raw arguments.
     * @return array
     */
    public static function sanitize_args($args = []) {
        $args = is_array($args) ? $args : [];
        $allowed_fields = self::get_allowed_fields();
        $field_name = isset($args['field_name']) ? sanitize_key($args['field_name']) : '';

        if (!in_array($field_name, $allowed_fields, true)) {
            $field_name = '';
        }

        return [
            'voucher_id' => isset($args['voucher_id']) ? max(0, (int) $args['voucher_id']) : 0,
            'neighbor' => isset($args['neighbor']) ? sanitize_text_field((string) $args['neighbor']) : '',
            'field_name' => $field_name,
            'manager' => isset($args['manager']) ? sanitize_text_field((string) $args['manager']) : '',
            'actor' => isset($args['actor']) ? sanitize_text_field((string) $args['actor']) : '',
            'reason' => isset($args['reason']) ? sanitize_text_field((string) $args['reason']) : '',
            'date_from' => self::sanitize_date($args['date_from'] ?? ''),
            'date_to' => self::sanitize_date($args['date_to'] ?? ''),
            'page' => isset($args['page']) ? max(1, (int) $args['page']) : 1,
            'per_page' => isset($args['per_page']) ? min(100, max(1, (int) $args['per_page'])) : 25,
        ];
    }

    /**
     * Build a prepared WHERE clause for audit filters.
     *
     * @param array $args Sanitized arguments.
     * @param array $params Prepared SQL parameters.
     * @return string
     */
    private static function build_where_clause($args, &$params) {
        global $wpdb;

        $where = ['1=1'];

        if (!empty($args['voucher_id'])) {
            $where[] = 'vc.voucher_id = %d';
            $params[] = (int) $args['voucher_id'];
        }

        if ($args['neighbor'] !== '') {
            $like = '%' . $wpdb->esc_like($args['neighbor']) . '%';
            $where[] = "(v.first_name LIKE %s OR v.last_name LIKE %s OR CONCAT_WS(' ', v.first_name, v.last_name) LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($args['field_name'] !== '') {
            $where[] = 'vc.field_name = %s';
            $params[] = $args['field_name'];
        }

        if ($args['manager'] !== '') {
            $where[] = 'vc.manager_name_snapshot LIKE %s';
            $params[] = '%' . $wpdb->esc_like($args['manager']) . '%';
        }

        if ($args['actor'] !== '') {
            $like = '%' . $wpdb->esc_like($args['actor']) . '%';
            $where[] = '(u.display_name LIKE %s OR u.user_login LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        if ($args['reason'] !== '') {
            $where[] = 'vc.reason_text_snapshot LIKE %s';
            $params[] = '%' . $wpdb->esc_like($args['reason']) . '%';
        }

        if ($args['date_from'] !== '') {
            $where[] = 'vc.created_at >= %s';
            $params[] = $args['date_from'] . ' 00:00:00';
        }

        if ($args['date_to'] !== '') {
            $where[] = 'vc.created_at <= %s';
            $params[] = $args['date_to'] . ' 23:59:59';
        }

        return 'WHERE ' . implode(' AND ', $where);
    }

    /**
     * Format a database row for admin display.
     *
     * @param object $row Raw database row.
     * @return array
     */
    private static function format_row($row) {
        $neighbor_name = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? ''));
        $summary = (string) ($row->human_summary ?? '');

        if ($summary === '') {
            $summary = sprintf(
                'Voucher #%d: %s changed.',
                (int) $row->voucher_id,
                (string) $row->field_name
            );
        }

        return [
            'id' => (int) $row->id,
            'voucher_id' => (int) $row->voucher_id,
            'field_name' => (string) $row->field_name,
            'before_value' => maybe_unserialize($row->before_value),
            'after_value' => maybe_unserialize($row->after_value),
            'manager_id' => $row->manager_id !== null ? (int) $row->manager_id : null,
            'manager_name_snapshot' => (string) ($row->manager_name_snapshot ?? ''),
            'actor_user_id' => $row->actor_user_id !== null ? (int) $row->actor_user_id : null,
            'actor_name' => (string) ($row->actor_name ?? ''),
            'actor_user_login' => (string) ($row->actor_user_login ?? ''),
            'reason_id' => $row->reason_id !== null ? (int) $row->reason_id : null,
            'reason_text_snapshot' => (string) ($row->reason_text_snapshot ?? ''),
            'human_summary' => $summary,
            'created_at' => (string) $row->created_at,
            'first_name' => (string) ($row->first_name ?? ''),
            'last_name' => (string) ($row->last_name ?? ''),
            'neighbor_name' => $neighbor_name,
            'dob' => (string) ($row->dob ?? ''),
            'status' => (string) ($row->status ?? ''),
            'voucher_type' => (string) ($row->voucher_type ?? ''),
            'conference_name' => (string) ($row->conference_name ?? ''),
        ];
    }

    /**
     * Validate an ISO date string.
     *
     * @param string $date Raw date.
     * @return string
     */
    private static function sanitize_date($date) {
        $date = sanitize_text_field((string) $date);

        if ($date === '') {
            return '';
        }

        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            return '';
        }

        return $date;
    }
}
