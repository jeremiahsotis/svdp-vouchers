<?php
/**
 * Shared Analytics query service.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Analytics {

    public static function get_default_filters() {
        return [
            'date_range' => 'mtd',
            'start_date' => '',
            'end_date' => '',
            'org_type' => 'all',
            'org_id' => 'all',
            'voucher_type' => 'all',
        ];
    }

    public static function normalize_filters($raw_filters = []) {
        $defaults = self::get_default_filters();
        $filters = array_merge($defaults, is_array($raw_filters) ? $raw_filters : []);
        $allowed_ranges = ['mtd', 'all', '30', '90', 'ytd', 'custom'];
        $allowed_org_types = ['all', 'conference', 'partner', 'store'];

        $filters['date_range'] = sanitize_key($filters['date_range']);
        if (!in_array($filters['date_range'], $allowed_ranges, true)) {
            $filters['date_range'] = 'mtd';
        }

        $filters['org_type'] = sanitize_key($filters['org_type']);
        if (!in_array($filters['org_type'], $allowed_org_types, true)) {
            $filters['org_type'] = 'all';
        }

        $filters['org_id'] = $filters['org_id'] === 'all' ? 'all' : (string) max(0, intval($filters['org_id']));
        if ($filters['org_id'] === '0') {
            $filters['org_id'] = 'all';
        }

        $filters['voucher_type'] = sanitize_key($filters['voucher_type']);
        if ($filters['voucher_type'] !== 'all') {
            $normalized_type = SVDP_Settings::normalize_voucher_type($filters['voucher_type']);
            $filters['voucher_type'] = $normalized_type ?: 'all';
        }

        $filters['start_date'] = self::sanitize_date($filters['start_date']);
        $filters['end_date'] = self::sanitize_date($filters['end_date']);

        if ($filters['date_range'] === 'custom' && (!$filters['start_date'] || !$filters['end_date'])) {
            return new WP_Error('analytics_dates_required', 'Please select both start and end dates for the custom range.');
        }

        if ($filters['date_range'] === 'custom' && $filters['start_date'] > $filters['end_date']) {
            return new WP_Error('analytics_dates_invalid', 'The custom start date must be on or before the end date.');
        }

        return $filters;
    }

    public static function get_organizations() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';

        return $wpdb->get_results(
            "SELECT id, name, organization_type
             FROM $table
             WHERE active = 1
             ORDER BY organization_type ASC, name ASC"
        );
    }

    public static function get_voucher_type_options() {
        $options = [];
        foreach (SVDP_Settings::get_available_voucher_types() as $type) {
            $options[$type] = self::format_voucher_type($type);
        }

        return $options;
    }

    public static function get_dashboard_data($raw_filters = []) {
        $filters = self::normalize_filters($raw_filters);
        if (is_wp_error($filters)) {
            return $filters;
        }

        return [
            'filters' => $filters,
            'date_label' => self::date_label($filters),
            'overview' => self::get_status_overview($filters),
            'period_overview' => self::get_status_overview($filters),
            'community_impact' => self::get_community_impact($filters),
            'winter_coats' => self::get_winter_coats($filters),
            'organizations' => self::get_organization_performance($filters),
            'denied' => self::get_denied_vouchers($filters),
            'overrides' => self::get_override_statistics($filters),
        ];
    }

    public static function get_export_rows($raw_filters = []) {
        $filters = self::normalize_filters($raw_filters);
        if (is_wp_error($filters)) {
            return $filters;
        }

        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $where = self::build_where($filters, 'v', 'voucher_created_date');

        return $wpdb->get_results(self::prepare(
            "SELECT
                v.id,
                v.first_name,
                v.last_name,
                v.dob,
                v.adults,
                v.children,
                (COALESCE(v.adults, 0) + COALESCE(v.children, 0)) as household_size,
                v.voucher_value,
                c.name as conference,
                c.organization_type,
                " . self::voucher_type_expr('v') . " as voucher_type,
                v.vincentian_name,
                v.vincentian_email,
                v.created_by,
                v.voucher_created_date,
                v.status,
                v.redeemed_date,
                COALESCE(v.items_adult_redeemed, 0) as items_adult_redeemed,
                COALESCE(v.items_children_redeemed, 0) as items_children_redeemed,
                (COALESCE(v.items_adult_redeemed, 0) + COALESCE(v.items_children_redeemed, 0)) as total_items_redeemed,
                COALESCE(v.redemption_total_value, 0) as redemption_total_value,
                v.coat_status,
                v.coat_issued_date,
                v.override_note,
                m.name as manager_name,
                r.reason_text as override_reason,
                v.created_at
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            LEFT JOIN {$wpdb->prefix}svdp_managers m ON v.manager_id = m.id
            LEFT JOIN {$wpdb->prefix}svdp_override_reasons r ON v.reason_id = r.id
            WHERE {$where['sql']}
            ORDER BY v.voucher_created_date DESC, v.id DESC",
            $where['params']
        ));
    }

    public static function format_voucher_type($type) {
        $labels = [
            'clothing' => 'Clothing',
            'furniture' => 'Furniture',
            'household_goods' => 'Household Goods',
        ];

        return $labels[$type] ?? ucwords(str_replace('_', ' ', (string) $type));
    }

    public static function format_organization_type($type) {
        $labels = [
            'conference' => 'Conference',
            'partner' => 'Partner',
            'store' => 'Store',
        ];

        return $labels[$type] ?? ucwords(str_replace('_', ' ', (string) $type));
    }

    private static function get_status_overview($filters) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $where = self::build_where($filters, 'v', 'voucher_created_date');
        $expiration_cutoff = self::expiration_cutoff_date();

        $row = $wpdb->get_row(self::prepare(
            "SELECT
                COUNT(v.id) as total_vouchers,
                SUM(CASE WHEN v.status = 'Redeemed' THEN 1 ELSE 0 END) as total_redeemed,
                SUM(CASE WHEN v.status = 'Active' AND v.voucher_created_date >= %s THEN 1 ELSE 0 END) as currently_active,
                SUM(CASE WHEN v.status = 'Expired' OR (v.status = 'Active' AND v.voucher_created_date < %s) THEN 1 ELSE 0 END) as total_expired,
                SUM(CASE WHEN v.status = 'Denied' THEN 1 ELSE 0 END) as total_denied
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$where['sql']}",
            array_merge([$expiration_cutoff, $expiration_cutoff], $where['params'])
        ));

        return [
            'total_vouchers' => (int) ($row->total_vouchers ?? 0),
            'total_redeemed' => (int) ($row->total_redeemed ?? 0),
            'currently_active' => (int) ($row->currently_active ?? 0),
            'total_expired' => (int) ($row->total_expired ?? 0),
            'total_denied' => (int) ($row->total_denied ?? 0),
        ];
    }

    private static function get_community_impact($filters) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $invoices_table = $wpdb->prefix . 'svdp_invoices';
        $where = self::build_where($filters, 'v', 'voucher_created_date', ["v.status != 'Denied'"]);

        $total = $wpdb->get_row(self::prepare(
            "SELECT
                COALESCE(SUM(COALESCE(v.adults, 0)), 0) as adults,
                COALESCE(SUM(COALESCE(v.children, 0)), 0) as children,
                COALESCE(SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.redemption_total_value, i.amount, 0) ELSE 0 END), 0) as total_value,
                COALESCE(SUM(CASE WHEN v.status = 'Redeemed' THEN " . self::items_provided_expr('v', 'i') . " ELSE 0 END), 0) as total_items
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             LEFT JOIN $invoices_table i ON i.voucher_id = v.id
             WHERE {$where['sql']}",
            $where['params']
        ));

        $breakdown = $wpdb->get_results(self::prepare(
            "SELECT
                " . self::voucher_type_expr('v') . " as voucher_type,
                COALESCE(SUM(COALESCE(v.adults, 0)), 0) as adults,
                COALESCE(SUM(COALESCE(v.children, 0)), 0) as children,
                COALESCE(SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.redemption_total_value, i.amount, 0) ELSE 0 END), 0) as total_value,
                COALESCE(SUM(CASE WHEN v.status = 'Redeemed' THEN " . self::items_provided_expr('v', 'i') . " ELSE 0 END), 0) as total_items
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             LEFT JOIN $invoices_table i ON i.voucher_id = v.id
             WHERE {$where['sql']}
             GROUP BY voucher_type
             ORDER BY voucher_type ASC",
            $where['params']
        ));

        return [
            'total' => self::format_impact_row($total),
            'by_type' => array_map([__CLASS__, 'format_impact_row'], $breakdown ?: []),
        ];
    }

    private static function get_winter_coats($filters) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $where = self::build_where($filters, 'v', 'coat_issued_date', ["v.coat_status = 'Issued'"]);

        $row = $wpdb->get_row(self::prepare(
            "SELECT
                COALESCE(SUM(COALESCE(v.coat_adults_issued, 0)), 0) as adults,
                COALESCE(SUM(COALESCE(v.coat_children_issued, 0)), 0) as children
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$where['sql']}",
            $where['params']
        ));

        $adults = (int) ($row->adults ?? 0);
        $children = (int) ($row->children ?? 0);

        return [
            'adults' => $adults,
            'children' => $children,
            'total' => $adults + $children,
        ];
    }

    private static function get_organization_performance($filters) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $invoices_table = $wpdb->prefix . 'svdp_invoices';
        $where = self::build_where($filters, 'v', 'voucher_created_date', ["v.status != 'Denied'"]);

        $rows = $wpdb->get_results(self::prepare(
            "SELECT
                c.name,
                c.organization_type,
                COUNT(v.id) as vouchers_issued,
                SUM(CASE WHEN v.status = 'Redeemed' THEN 1 ELSE 0 END) as vouchers_redeemed,
                SUM(CASE WHEN v.status = 'Redeemed' THEN " . self::items_provided_expr('v', 'i') . " ELSE 0 END) as items_redeemed,
                SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.redemption_total_value, i.amount, 0) ELSE 0 END) as redemption_value
             FROM $conferences_table c
             LEFT JOIN $vouchers_table v ON c.id = v.conference_id AND {$where['sql']}
             LEFT JOIN $invoices_table i ON i.voucher_id = v.id
             WHERE c.active = 1
             GROUP BY c.id, c.name, c.organization_type
             HAVING vouchers_issued > 0
             ORDER BY vouchers_issued DESC, c.name ASC",
            $where['params']
        ));

        return array_map(function($row) {
            return [
                'name' => (string) $row->name,
                'organization_type' => (string) $row->organization_type,
                'organization_type_label' => self::format_organization_type($row->organization_type),
                'vouchers_issued' => (int) $row->vouchers_issued,
                'vouchers_redeemed' => (int) $row->vouchers_redeemed,
                'items_redeemed' => (int) $row->items_redeemed,
                'redemption_value' => (float) $row->redemption_value,
            ];
        }, $rows ?: []);
    }

    private static function get_denied_vouchers($filters) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $where = self::build_where($filters, 'v', 'voucher_created_date', ["v.status = 'Denied'"]);

        $summary = $wpdb->get_var(self::prepare(
            "SELECT COUNT(v.id)
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$where['sql']}",
            $where['params']
        ));

        $by_org = $wpdb->get_results(self::prepare(
            "SELECT COALESCE(c.name, 'Unknown') as name, COUNT(v.id) as denied_count
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$where['sql']}
             GROUP BY c.id, c.name
             ORDER BY denied_count DESC, name ASC
             LIMIT 10",
            $where['params']
        ));

        $recent = $wpdb->get_results(self::prepare(
            "SELECT
                v.first_name,
                v.last_name,
                v.dob,
                v.vincentian_name,
                v.created_by,
                v.voucher_created_date,
                v.denial_reason,
                " . self::voucher_type_expr('v') . " as voucher_type,
                c.name as conference_name
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$where['sql']}
             ORDER BY v.created_at DESC, v.id DESC
             LIMIT 20",
            $where['params']
        ));

        return [
            'total' => (int) $summary,
            'by_organization' => array_map(function($row) {
                return [
                    'name' => (string) $row->name,
                    'denied_count' => (int) $row->denied_count,
                ];
            }, $by_org ?: []),
            'recent' => array_map(function($row) {
                return [
                    'name' => trim((string) $row->first_name . ' ' . (string) $row->last_name),
                    'dob' => (string) $row->dob,
                    'conference_name' => (string) $row->conference_name,
                    'voucher_type' => (string) $row->voucher_type,
                    'voucher_type_label' => self::format_voucher_type($row->voucher_type),
                    'requested_by' => (string) ($row->vincentian_name ?: $row->created_by),
                    'voucher_created_date' => (string) $row->voucher_created_date,
                    'denial_reason' => (string) $row->denial_reason,
                ];
            }, $recent ?: []),
        ];
    }

    private static function get_override_statistics($filters) {
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $managers_table = $wpdb->prefix . 'svdp_managers';
        $reasons_table = $wpdb->prefix . 'svdp_override_reasons';
        $base_where = self::build_where($filters, 'v', 'voucher_created_date');
        $override_where = self::build_where($filters, 'v', 'voucher_created_date', ['v.manager_id IS NOT NULL']);
        $reason_where = self::build_where($filters, 'v', 'voucher_created_date', ['v.reason_id IS NOT NULL']);

        $total_vouchers = (int) $wpdb->get_var(self::prepare(
            "SELECT COUNT(v.id)
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$base_where['sql']}",
            $base_where['params']
        ));

        $override_count = (int) $wpdb->get_var(self::prepare(
            "SELECT COUNT(v.id)
             FROM $vouchers_table v
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$override_where['sql']}",
            $override_where['params']
        ));

        $by_manager = $wpdb->get_results(self::prepare(
            "SELECT m.name as manager_name, COUNT(v.id) as override_count
             FROM $vouchers_table v
             INNER JOIN $managers_table m ON v.manager_id = m.id
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$override_where['sql']}
             GROUP BY m.id, m.name
             ORDER BY override_count DESC, m.name ASC",
            $override_where['params']
        ));

        $by_reason = $wpdb->get_results(self::prepare(
            "SELECT r.reason_text, COUNT(v.id) as override_count
             FROM $vouchers_table v
             INNER JOIN $reasons_table r ON v.reason_id = r.id
             LEFT JOIN $conferences_table c ON v.conference_id = c.id
             WHERE {$reason_where['sql']}
             GROUP BY r.id, r.reason_text
             ORDER BY override_count DESC, r.reason_text ASC",
            $reason_where['params']
        ));

        return [
            'total_vouchers' => $total_vouchers,
            'override_count' => $override_count,
            'override_percentage' => $total_vouchers > 0 ? round(($override_count / $total_vouchers) * 100, 1) : 0.0,
            'by_manager' => array_map(function($row) {
                return [
                    'manager_name' => (string) $row->manager_name,
                    'override_count' => (int) $row->override_count,
                ];
            }, $by_manager ?: []),
            'by_reason' => array_map(function($row) {
                return [
                    'reason_text' => (string) $row->reason_text,
                    'override_count' => (int) $row->override_count,
                ];
            }, $by_reason ?: []),
        ];
    }

    private static function format_impact_row($row) {
        $adults = (int) ($row->adults ?? 0);
        $children = (int) ($row->children ?? 0);
        $voucher_type = isset($row->voucher_type) ? (string) $row->voucher_type : null;

        return [
            'voucher_type' => $voucher_type,
            'voucher_type_label' => $voucher_type !== null ? self::format_voucher_type($voucher_type) : null,
            'adults' => $adults,
            'children' => $children,
            'people_served' => $adults + $children,
            'total_value' => (float) ($row->total_value ?? 0),
            'total_items' => (int) ($row->total_items ?? 0),
        ];
    }

    private static function build_where($filters, $alias, $date_column, $extra_clauses = []) {
        $clauses = array_values($extra_clauses);
        $params = [];
        $date_range = self::date_bounds($filters);

        if ($date_range !== null) {
            $clauses[] = "$alias.$date_column BETWEEN %s AND %s";
            $params[] = $date_range['start'];
            $params[] = $date_range['end'];
        }

        if ($filters['org_type'] !== 'all') {
            $clauses[] = 'c.organization_type = %s';
            $params[] = $filters['org_type'];
        }

        if ($filters['org_id'] !== 'all') {
            $clauses[] = "$alias.conference_id = %d";
            $params[] = (int) $filters['org_id'];
        }

        if ($filters['voucher_type'] !== 'all') {
            $clauses[] = self::voucher_type_expr($alias) . ' = %s';
            $params[] = $filters['voucher_type'];
        }

        return [
            'sql' => !empty($clauses) ? implode(' AND ', $clauses) : '1=1',
            'params' => $params,
        ];
    }

    private static function date_bounds($filters) {
        $today = wp_date('Y-m-d', current_time('timestamp'));
        $timestamp = current_time('timestamp');

        switch ($filters['date_range']) {
            case 'all':
                return null;
            case 'custom':
                return [
                    'start' => $filters['start_date'],
                    'end' => $filters['end_date'],
                ];
            case '30':
                return [
                    'start' => wp_date('Y-m-d', strtotime('-30 days', $timestamp)),
                    'end' => $today,
                ];
            case '90':
                return [
                    'start' => wp_date('Y-m-d', strtotime('-90 days', $timestamp)),
                    'end' => $today,
                ];
            case 'ytd':
                return [
                    'start' => wp_date('Y-01-01', $timestamp),
                    'end' => $today,
                ];
            case 'mtd':
            default:
                return [
                    'start' => wp_date('Y-m-01', $timestamp),
                    'end' => $today,
                ];
        }
    }

    private static function date_label($filters) {
        $bounds = self::date_bounds($filters);
        if ($bounds === null) {
            return 'All Time';
        }

        $labels = [
            'mtd' => 'Month to Date',
            '30' => 'Last 30 Days',
            '90' => 'Last 90 Days',
            'ytd' => 'Year to Date',
            'custom' => 'Custom Range',
        ];

        return ($labels[$filters['date_range']] ?? 'Selected Range') . ' (' . $bounds['start'] . ' to ' . $bounds['end'] . ')';
    }

    private static function expiration_cutoff_date() {
        $days = (int) apply_filters('svdp_vouchers_expiration_days', 30);
        return wp_date('Y-m-d', strtotime('-' . $days . ' days', current_time('timestamp')));
    }

    private static function voucher_type_expr($alias) {
        return "CASE WHEN $alias.voucher_type IS NULL OR $alias.voucher_type = '' OR $alias.voucher_type = 'regular' THEN 'clothing' WHEN $alias.voucher_type = 'household' THEN 'furniture' ELSE $alias.voucher_type END";
    }

    private static function items_provided_expr($voucher_alias, $invoice_alias) {
        return "CASE WHEN " . self::voucher_type_expr($voucher_alias) . " IN ('furniture', 'household_goods') THEN COALESCE($invoice_alias.items_total, COALESCE($voucher_alias.items_adult_redeemed, 0) + COALESCE($voucher_alias.items_children_redeemed, 0)) ELSE COALESCE($voucher_alias.items_adult_redeemed, 0) + COALESCE($voucher_alias.items_children_redeemed, 0) END";
    }

    private static function sanitize_date($value) {
        $value = sanitize_text_field((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function prepare($sql, $params = []) {
        global $wpdb;
        if (empty($params)) {
            return $sql;
        }

        return $wpdb->prepare($sql, $params);
    }
}
