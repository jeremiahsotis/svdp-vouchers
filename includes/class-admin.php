<?php
/**
 * Admin functionality
 */
class SVDP_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_svdp_add_conference', [$this, 'ajax_add_conference']);
        add_action('wp_ajax_svdp_delete_conference', [$this, 'ajax_delete_conference']);
        add_action('wp_ajax_svdp_update_conference', [$this, 'ajax_update_conference']);
        add_action('wp_ajax_svdp_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_svdp_update_voucher_types', [$this, 'ajax_update_voucher_types']);
        add_action('wp_ajax_svdp_get_custom_text', [$this, 'ajax_get_custom_text']);
        add_action('wp_ajax_svdp_save_custom_text', [$this, 'ajax_save_custom_text']);
        add_action('wp_ajax_svdp_apply_analytics_filters', [$this, 'ajax_apply_analytics_filters']);
        add_action('wp_ajax_svdp_add_furniture_catalog_item', [$this, 'ajax_add_furniture_catalog_item']);
        add_action('wp_ajax_svdp_update_furniture_catalog_item', [$this, 'ajax_update_furniture_catalog_item']);
        add_action('wp_ajax_svdp_set_furniture_catalog_item_active', [$this, 'ajax_set_furniture_catalog_item_active']);
        add_action('wp_ajax_svdp_add_furniture_cancellation_reason', [$this, 'ajax_add_furniture_cancellation_reason']);
        add_action('wp_ajax_svdp_update_furniture_cancellation_reason', [$this, 'ajax_update_furniture_cancellation_reason']);
        add_action('wp_ajax_svdp_set_furniture_cancellation_reason_active', [$this, 'ajax_set_furniture_cancellation_reason_active']);

        // Manager AJAX
        add_action('wp_ajax_svdp_add_manager', [$this, 'ajax_add_manager']);
        add_action('wp_ajax_svdp_get_managers', [$this, 'ajax_get_managers']);
        add_action('wp_ajax_svdp_deactivate_manager', [$this, 'ajax_deactivate_manager']);
        add_action('wp_ajax_svdp_regenerate_code', [$this, 'ajax_regenerate_code']);

        // Reason AJAX
        add_action('wp_ajax_svdp_add_reason', [$this, 'ajax_add_reason']);
        add_action('wp_ajax_svdp_get_reasons', [$this, 'ajax_get_reasons']);
        add_action('wp_ajax_svdp_update_reason', [$this, 'ajax_update_reason']);
        add_action('wp_ajax_svdp_delete_reason', [$this, 'ajax_delete_reason']);
        add_action('wp_ajax_svdp_reorder_reasons', [$this, 'ajax_reorder_reasons']);

        // Export handler
        add_action('admin_post_svdp_export_vouchers', [$this, 'export_vouchers']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('SVdP Vouchers', 'svdp-vouchers'),
            __('SVdP Vouchers', 'svdp-vouchers'),
            'manage_options',
            'svdp-vouchers',
            [$this, 'render_admin_page'],
            'dashicons-tickets-alt',
            30
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Settings are now managed via SVDP_Settings class and database table
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_svdp-vouchers') {
            return;
        }

        wp_enqueue_style('svdp-vouchers-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/css/admin.css', [], SVDP_VOUCHERS_VERSION);
        wp_enqueue_script('svdp-vouchers-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], SVDP_VOUCHERS_VERSION, true);
        wp_enqueue_script('svdp-furniture-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/furniture-admin.js', ['jquery'], SVDP_VOUCHERS_VERSION, true);
        wp_enqueue_script('svdp-accounting-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/accounting-admin.js', ['jquery', 'svdp-vouchers-admin'], SVDP_VOUCHERS_VERSION, true);
        wp_enqueue_script('svdp-managers', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/managers.js', ['jquery'], SVDP_VOUCHERS_VERSION, true);
        wp_enqueue_script('svdp-override-reasons', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/override-reasons.js', ['jquery', 'jquery-ui-sortable'], SVDP_VOUCHERS_VERSION, true);

        wp_localize_script('svdp-vouchers-admin', 'svdpAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('svdp_admin_nonce'),
            'restUrl' => esc_url_raw(rest_url('svdp/v1/')),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'conferences';
        include SVDP_VOUCHERS_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
    
    /**
     * AJAX: Add conference
     */
    public function ajax_add_conference() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $org_type = sanitize_text_field($_POST['organization_type'] ?? 'conference');
        $eligibility_days = intval($_POST['eligibility_days'] ?? 90);
        $regular_items = intval($_POST['regular_items'] ?? 7);

        if (empty($name)) {
            wp_send_json_error('Organization name is required');
        }

        $id = SVDP_Conference::create($name, $slug, 0, $org_type, $eligibility_days, $regular_items);

        if ($id) {
            wp_send_json_success([
                'message' => 'Organization added successfully',
                'conference' => SVDP_Conference::get_by_id($id),
            ]);
        } else {
            wp_send_json_error('Failed to add conference');
        }
    }
    
    /**
     * AJAX: Delete conference
     */
    public function ajax_delete_conference() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $id = intval($_POST['id']);
        
        if (SVDP_Conference::delete($id)) {
            wp_send_json_success('Conference deleted successfully');
        } else {
            wp_send_json_error('Failed to delete conference');
        }
    }
    
    /**
     * AJAX: Update conference
     */
    public function ajax_update_conference() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $id = intval($_POST['id']);
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['slug']),
            'notification_email' => sanitize_email($_POST['notification_email']),
            'eligibility_days' => intval($_POST['eligibility_days']),
            'items_per_person' => intval($_POST['items_per_person']),
        ];
        
        if (SVDP_Conference::update($id, $data)) {
            wp_send_json_success('Conference updated successfully');
        } else {
            wp_send_json_error('Failed to update conference');
        }
    }

    /**
     * Save plugin settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $available_voucher_types = SVDP_Settings::serialize_voucher_types(
            sanitize_text_field($_POST['available_voucher_types']),
            ['clothing', 'furniture']
        );

        // Sanitize and save each setting
        $settings = [
            'adult_item_value' => ['value' => sanitize_text_field($_POST['adult_item_value']), 'type' => 'decimal'],
            'child_item_value' => ['value' => sanitize_text_field($_POST['child_item_value']), 'type' => 'decimal'],
            'store_hours' => ['value' => sanitize_text_field($_POST['store_hours']), 'type' => 'text'],
            'redemption_instructions' => ['value' => sanitize_textarea_field($_POST['redemption_instructions']), 'type' => 'textarea'],
            'available_voucher_types' => ['value' => $available_voucher_types, 'type' => 'text'],
        ];

        $success = true;
        foreach ($settings as $key => $setting) {
            if (!SVDP_Settings::update_setting($key, $setting['value'], $setting['type'])) {
                $success = false;
                break;
            }
        }

        if ($success) {
            wp_send_json_success('Settings saved successfully');
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }

    /**
     * AJAX: Update organization voucher types
     */
    public function ajax_update_voucher_types() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $voucher_types = $_POST['voucher_types']; // Already JSON string

        if (SVDP_Conference::update($id, ['allowed_voucher_types' => $voucher_types])) {
            wp_send_json_success('Voucher types updated successfully');
        } else {
            wp_send_json_error('Failed to update voucher types');
        }
    }

    /**
     * AJAX: Get organization custom text
     */
    public function ajax_get_custom_text() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $conference = SVDP_Conference::get_by_id($id);

        if ($conference) {
            wp_send_json_success([
                'custom_form_text' => $conference->custom_form_text ?? '',
                'custom_rules_text' => $conference->custom_rules_text ?? ''
            ]);
        } else {
            wp_send_json_error('Organization not found');
        }
    }

    /**
     * AJAX: Save organization custom text
     */
    public function ajax_save_custom_text() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $data = [
            'custom_form_text' => sanitize_textarea_field($_POST['custom_form_text']),
            'custom_rules_text' => sanitize_textarea_field($_POST['custom_rules_text'])
        ];

        if (SVDP_Conference::update($id, $data)) {
            wp_send_json_success('Custom text saved successfully');
        } else {
            wp_send_json_error('Failed to save custom text');
        }
    }

    /**
     * AJAX: Apply analytics filters and return filtered data
     */
    public function ajax_apply_analytics_filters() {
        check_ajax_referer('svdp_analytics_filters', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $filters = $_POST['filters'];

        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';

        // Build WHERE clauses based on filters
        $where_clauses = ["v.status != 'Denied'"];

        // Date range filter
        if ($filters['date_range'] !== 'all') {
            if ($filters['date_range'] === 'custom') {
                $start_date = sanitize_text_field($filters['start_date']);
                $end_date = sanitize_text_field($filters['end_date']);
                $where_clauses[] = $wpdb->prepare("v.voucher_created_date BETWEEN %s AND %s", $start_date, $end_date);
            } else {
                $days = intval($filters['date_range']);
                if ($filters['date_range'] === 'ytd') {
                    $year_start = date('Y-01-01');
                    $where_clauses[] = $wpdb->prepare("v.voucher_created_date >= %s", $year_start);
                } else {
                    $cutoff_date = date('Y-m-d', strtotime("-{$days} days"));
                    $where_clauses[] = $wpdb->prepare("v.voucher_created_date >= %s", $cutoff_date);
                }
            }
        }

        // Organization type filter
        if ($filters['org_type'] !== 'all') {
            $org_type = sanitize_text_field($filters['org_type']);
            $where_clauses[] = $wpdb->prepare("c.organization_type = %s", $org_type);
        }

        // Specific organization filter
        if ($filters['org_id'] !== 'all') {
            $org_id = intval($filters['org_id']);
            $where_clauses[] = $wpdb->prepare("v.conference_id = %d", $org_id);
        }

        // Voucher type filter
        if ($filters['voucher_type'] !== 'all') {
            $voucher_type = sanitize_text_field($filters['voucher_type']);
            $where_clauses[] = $wpdb->prepare("v.voucher_type = %s", $voucher_type);
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Get filtered stats
        $stats = [
            'total_vouchers' => 0,
            'redeemed_vouchers' => 0,
            'items_redeemed' => 0,
            'redemption_value' => 0,
            'organizations' => []
        ];

        // Overall totals
        $totals = $wpdb->get_row("
            SELECT COUNT(*) as total_vouchers,
                   SUM(CASE WHEN v.status = 'Redeemed' THEN 1 ELSE 0 END) as redeemed_vouchers,
                   SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.items_adult_redeemed, 0) + COALESCE(v.items_children_redeemed, 0) ELSE 0 END) as items_redeemed,
                   SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.redemption_total_value, 0) ELSE 0 END) as redemption_value
            FROM {$vouchers_table} v
            LEFT JOIN {$conferences_table} c ON v.conference_id = c.id
            WHERE {$where_sql}
        ");

        $stats['total_vouchers'] = intval($totals->total_vouchers);
        $stats['redeemed_vouchers'] = intval($totals->redeemed_vouchers);
        $stats['items_redeemed'] = intval($totals->items_redeemed);
        $stats['redemption_value'] = floatval($totals->redemption_value);

        // Per-organization stats
        $org_stats = $wpdb->get_results("
            SELECT c.name,
                   c.organization_type,
                   COUNT(v.id) as vouchers_issued,
                   SUM(CASE WHEN v.status = 'Redeemed' THEN 1 ELSE 0 END) as vouchers_redeemed,
                   SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.items_adult_redeemed, 0) + COALESCE(v.items_children_redeemed, 0) ELSE 0 END) as items_redeemed,
                   SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.redemption_total_value, 0) ELSE 0 END) as redemption_value
            FROM {$conferences_table} c
            LEFT JOIN {$vouchers_table} v ON c.id = v.conference_id AND {$where_sql}
            WHERE c.active = 1
            GROUP BY c.id, c.name, c.organization_type
            HAVING vouchers_issued > 0
            ORDER BY vouchers_issued DESC
        ");

        $stats['organizations'] = $org_stats;

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Add furniture catalog item.
     */
    public function ajax_add_furniture_catalog_item() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $result = SVDP_Furniture_Catalog::create($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'id' => $result,
            'message' => 'Catalog item created.',
        ]);
    }

    /**
     * AJAX: Update furniture catalog item.
     */
    public function ajax_update_furniture_catalog_item() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $result = SVDP_Furniture_Catalog::update($id, $_POST);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Catalog item updated.');
    }

    /**
     * AJAX: Archive or restore furniture catalog item.
     */
    public function ajax_set_furniture_catalog_item_active() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        $result = SVDP_Furniture_Catalog::set_active($id, $active);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($active ? 'Catalog item restored.' : 'Catalog item archived.');
    }

    /**
     * AJAX: Add furniture cancellation reason.
     */
    public function ajax_add_furniture_cancellation_reason() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $result = SVDP_Furniture_Cancellation_Reason::create($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'id' => $result,
            'message' => 'Furniture cancellation reason created.',
        ]);
    }

    /**
     * AJAX: Update furniture cancellation reason.
     */
    public function ajax_update_furniture_cancellation_reason() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $result = SVDP_Furniture_Cancellation_Reason::update($id, $_POST);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Furniture cancellation reason updated.');
    }

    /**
     * AJAX: Archive or restore furniture cancellation reason.
     */
    public function ajax_set_furniture_cancellation_reason_active() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        $result = SVDP_Furniture_Cancellation_Reason::set_active($id, $active);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($active ? 'Furniture cancellation reason restored.' : 'Furniture cancellation reason archived.');
    }

    /**
     * Export vouchers to CSV
     */
    public function export_vouchers() {
        check_admin_referer('svdp_export', 'svdp_export_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        
        // Build filters from analytics filters (if set) or legacy date range
        $where_clauses = ['1=1'];

        // Check if analytics filters are set
        $filter_date_range = isset($_POST['filter_date_range']) ? sanitize_text_field($_POST['filter_date_range']) : sanitize_text_field($_POST['date_range']);

        // Date filter
        if ($filter_date_range === 'custom') {
            $start_date = isset($_POST['filter_start_date']) ? sanitize_text_field($_POST['filter_start_date']) : sanitize_text_field($_POST['start_date']);
            $end_date = isset($_POST['filter_end_date']) ? sanitize_text_field($_POST['filter_end_date']) : sanitize_text_field($_POST['end_date']);
            $where_clauses[] = $wpdb->prepare("v.voucher_created_date BETWEEN %s AND %s", $start_date, $end_date);
        } elseif ($filter_date_range !== 'all') {
            if ($filter_date_range === 'ytd') {
                $start_date = date('Y-01-01');
            } else {
                $start_date = date('Y-m-d', strtotime('-' . intval($filter_date_range) . ' days'));
            }
            $where_clauses[] = $wpdb->prepare("v.voucher_created_date >= %s", $start_date);
        }

        // Organization type filter
        if (isset($_POST['filter_org_type']) && $_POST['filter_org_type'] !== 'all') {
            $org_type = sanitize_text_field($_POST['filter_org_type']);
            $where_clauses[] = $wpdb->prepare("c.organization_type = %s", $org_type);
        }

        // Specific organization filter
        if (isset($_POST['filter_org_id']) && $_POST['filter_org_id'] !== 'all') {
            $org_id = intval($_POST['filter_org_id']);
            $where_clauses[] = $wpdb->prepare("v.conference_id = %d", $org_id);
        }

        // Voucher type filter
        if (isset($_POST['filter_voucher_type']) && $_POST['filter_voucher_type'] !== 'all') {
            $voucher_type = sanitize_text_field($_POST['filter_voucher_type']);
            $where_clauses[] = $wpdb->prepare("v.voucher_type = %s", $voucher_type);
        }

        // Status filter (include denied checkbox)
        if (!isset($_POST['include_denied'])) {
            $where_clauses[] = "v.status != 'Denied'";
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        // Get vouchers
        $vouchers = $wpdb->get_results("
            SELECT
                v.id,
                v.first_name,
                v.last_name,
                v.dob,
                v.adults,
                v.children,
                (v.adults + v.children) as household_size,
                v.voucher_value,
                c.name as conference,
                c.organization_type,
                COALESCE(v.voucher_type, 'clothing') as voucher_type,
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
            WHERE {$where_sql}
            ORDER BY v.voucher_created_date DESC
        ");
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=svdp-vouchers-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, [
            'ID',
            'First Name',
            'Last Name',
            'Date of Birth',
            'Adults',
            'Children',
            'Household Size',
            'Voucher Value',
            'Conference/Partner',
            'Organization Type',
            'Voucher Type',
            'Vincentian Name',
            'Vincentian Email',
            'Created By',
            'Voucher Date',
            'Status',
            'Redeemed Date',
            'Items Adult Redeemed',
            'Items Children Redeemed',
            'Total Items Redeemed',
            'Redemption Value',
            'Coat Status',
            'Coat Issued Date',
            'Override Note',
            'Override Manager',
            'Override Reason',
            'Created At'
        ]);
        
        // Add data
        foreach ($vouchers as $voucher) {
            fputcsv($output, [
                $voucher->id,
                $voucher->first_name,
                $voucher->last_name,
                $voucher->dob,
                $voucher->adults,
                $voucher->children,
                $voucher->household_size,
                $voucher->voucher_value,
                $voucher->conference,
                ucfirst($voucher->organization_type),
                ucfirst($voucher->voucher_type),
                $voucher->vincentian_name,
                $voucher->vincentian_email,
                $voucher->created_by,
                $voucher->voucher_created_date,
                $voucher->status,
                $voucher->redeemed_date,
                $voucher->items_adult_redeemed,
                $voucher->items_children_redeemed,
                $voucher->total_items_redeemed,
                number_format($voucher->redemption_total_value, 2),
                $voucher->coat_status,
                $voucher->coat_issued_date,
                $voucher->override_note,
                $voucher->manager_name,
                $voucher->override_reason,
                $voucher->created_at
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * AJAX: Add manager
     */
    public function ajax_add_manager() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $name = sanitize_text_field($_POST['name']);

        if (empty($name)) {
            wp_send_json_error('Manager name is required');
        }

        $result = SVDP_Manager::create($name);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to create manager');
        }
    }

    /**
     * AJAX: Get all managers
     */
    public function ajax_get_managers() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $managers = SVDP_Manager::get_all();
        wp_send_json_success($managers);
    }

    /**
     * AJAX: Deactivate manager
     */
    public function ajax_deactivate_manager() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $result = SVDP_Manager::deactivate($id);

        if ($result !== false) {
            wp_send_json_success('Manager deactivated');
        } else {
            wp_send_json_error('Failed to deactivate manager');
        }
    }

    /**
     * AJAX: Regenerate manager code
     */
    public function ajax_regenerate_code() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $result = SVDP_Manager::regenerate_code($id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to regenerate code');
        }
    }

    /**
     * AJAX: Add override reason
     */
    public function ajax_add_reason() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $reason_text = sanitize_text_field($_POST['reason_text']);

        if (empty($reason_text)) {
            wp_send_json_error('Reason text is required');
        }

        $id = SVDP_Override_Reason::create($reason_text);

        if ($id) {
            wp_send_json_success('Reason added successfully');
        } else {
            wp_send_json_error('Failed to add reason');
        }
    }

    /**
     * AJAX: Get all reasons
     */
    public function ajax_get_reasons() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $reasons = SVDP_Override_Reason::get_all();
        wp_send_json_success($reasons);
    }

    /**
     * AJAX: Update reason
     */
    public function ajax_update_reason() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $reason_text = sanitize_text_field($_POST['reason_text']);

        $result = SVDP_Override_Reason::update($id, $reason_text);

        if ($result !== false) {
            wp_send_json_success('Reason updated');
        } else {
            wp_send_json_error('Failed to update reason');
        }
    }

    /**
     * AJAX: Delete reason
     */
    public function ajax_delete_reason() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id']);
        $result = SVDP_Override_Reason::delete($id);

        if ($result !== false) {
            wp_send_json_success('Reason deleted');
        } else {
            wp_send_json_error('Failed to delete reason');
        }
    }

    /**
     * AJAX: Reorder reasons
     */
    public function ajax_reorder_reasons() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $order = $_POST['order']; // Array of IDs in new order

        if (!is_array($order)) {
            wp_send_json_error('Invalid order data');
        }

        $result = SVDP_Override_Reason::reorder($order);

        if ($result) {
            wp_send_json_success('Order updated');
        } else {
            wp_send_json_error('Failed to update order');
        }
    }
}
