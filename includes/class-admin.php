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
        add_action('wp_ajax_svdp_add_furniture_category', [$this, 'ajax_add_furniture_category']);
        add_action('wp_ajax_svdp_update_furniture_category', [$this, 'ajax_update_furniture_category']);
        add_action('wp_ajax_svdp_add_furniture_cancellation_reason', [$this, 'ajax_add_furniture_cancellation_reason']);
        add_action('wp_ajax_svdp_update_furniture_cancellation_reason', [$this, 'ajax_update_furniture_cancellation_reason']);
        add_action('wp_ajax_svdp_set_furniture_cancellation_reason_active', [$this, 'ajax_set_furniture_cancellation_reason_active']);
        add_action('wp_ajax_svdp_add_household_goods_browse_group', [$this, 'ajax_add_household_goods_browse_group']);
        add_action('wp_ajax_svdp_update_household_goods_browse_group', [$this, 'ajax_update_household_goods_browse_group']);
        add_action('wp_ajax_svdp_set_household_goods_browse_group_active', [$this, 'ajax_set_household_goods_browse_group_active']);
        add_action('wp_ajax_svdp_add_household_goods_category', [$this, 'ajax_add_household_goods_category']);
        add_action('wp_ajax_svdp_update_household_goods_category', [$this, 'ajax_update_household_goods_category']);
        add_action('wp_ajax_svdp_set_household_goods_category_active', [$this, 'ajax_set_household_goods_category_active']);
        add_action('wp_ajax_svdp_update_household_goods_limits', [$this, 'ajax_update_household_goods_limits']);

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
        add_action('admin_post_svdp_save_accounting_settings', [$this, 'save_accounting_settings']);
        add_action('admin_post_svdp_save_accounting_organization', [$this, 'save_accounting_organization']);
        add_action('admin_post_svdp_run_accounting_cycle', [$this, 'run_accounting_cycle']);
        add_action('admin_post_svdp_email_accounting_batch', [$this, 'email_accounting_batch']);
        add_action('admin_post_svdp_download_accounting_file', [$this, 'download_accounting_file']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('SVdP Vouchers', 'svdp-vouchers'),
            __('SVdP Vouchers', 'svdp-vouchers'),
            SVDP_VOUCHERS_ACCOUNTING_CAP,
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

        wp_enqueue_style('svdp-vouchers-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/css/admin.css', [], $this->get_asset_version('admin/css/admin.css'));
        wp_enqueue_script('svdp-vouchers-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], $this->get_asset_version('admin/js/admin.js'), true);
        wp_enqueue_script('svdp-furniture-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/furniture-admin.js', ['jquery'], $this->get_asset_version('admin/js/furniture-admin.js'), true);
        wp_enqueue_script('svdp-household-goods-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/household-goods-admin.js', ['jquery'], $this->get_asset_version('admin/js/household-goods-admin.js'), true);
        wp_enqueue_script('svdp-accounting-admin', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/accounting-admin.js', ['jquery', 'svdp-vouchers-admin'], $this->get_asset_version('admin/js/accounting-admin.js'), true);
        wp_enqueue_script('svdp-managers', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/managers.js', ['jquery'], $this->get_asset_version('admin/js/managers.js'), true);
        wp_enqueue_script('svdp-override-reasons', SVDP_VOUCHERS_PLUGIN_URL . 'admin/js/override-reasons.js', ['jquery', 'jquery-ui-sortable'], $this->get_asset_version('admin/js/override-reasons.js'), true);

        wp_localize_script('svdp-vouchers-admin', 'svdpAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('svdp_admin_nonce'),
            'restUrl' => esc_url_raw(rest_url('svdp/v1/')),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Return a cache-busting version for plugin assets.
     *
     * @param string $relative_path Asset path relative to the plugin root.
     * @return int|string
     */
    private function get_asset_version($relative_path) {
        $asset_path = SVDP_VOUCHERS_PLUGIN_DIR . ltrim($relative_path, '/');

        return file_exists($asset_path) ? filemtime($asset_path) : SVDP_VOUCHERS_VERSION;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!SVDP_Permissions::user_can_manage_accounting()) {
            wp_die(esc_html__('You do not have permission to access SVdP accounting.', 'svdp-vouchers'));
        }
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'analytics';
        if (!SVDP_Permissions::user_can_manage_plugin() && !in_array($active_tab, ['analytics', 'invoices', 'statements', 'accounting'], true)) {
            $active_tab = 'analytics';
        }
        include SVDP_VOUCHERS_PLUGIN_DIR . 'admin/views/admin-page.php';
    }

    public function save_accounting_settings() {
        $this->require_accounting_post('svdp_save_accounting_settings');
        $keys = ['bookkeeping_email', 'quickbooks_ar_account', 'quickbooks_income_account', 'quickbooks_voucher_item', 'quickbooks_delivery_item'];
        foreach ($keys as $key) {
            $before = SVDP_Settings::get_setting($key, '');
            $value = $key === 'bookkeeping_email' ? sanitize_email($_POST[$key] ?? '') : sanitize_text_field($_POST[$key] ?? '');
            SVDP_Settings::update_setting($key, $value);
            SVDP_Accounting::audit('accounting_setting_changed', 'setting', null, sprintf('Accounting setting %s was updated.', $key), $before, $value);
        }
        wp_safe_redirect(admin_url('admin.php?page=svdp-vouchers&tab=accounting&updated=1'));
        exit;
    }

    public function save_accounting_organization() {
        $this->require_accounting_post('svdp_save_accounting_organization');
        $id = absint($_POST['conference_id'] ?? 0);
        $before = SVDP_Conference::get_by_id($id);
        SVDP_Conference::update($id, [
            'billing_email' => sanitize_email($_POST['billing_email'] ?? ''),
            'quickbooks_customer_name' => sanitize_text_field($_POST['quickbooks_customer_name'] ?? ''),
        ]);
        SVDP_Accounting::audit('organization_accounting_changed', 'organization', $id, sprintf('Billing and QuickBooks mappings were updated for organization #%d.', $id), $before, SVDP_Conference::get_by_id($id));
        wp_safe_redirect(admin_url('admin.php?page=svdp-vouchers&tab=accounting&updated=1'));
        exit;
    }

    public function run_accounting_cycle() {
        $this->require_accounting_post('svdp_run_accounting_cycle');
        SVDP_Accounting::run_monthly('manual', get_current_user_id());
        wp_safe_redirect(admin_url('admin.php?page=svdp-vouchers&tab=accounting&ran=1'));
        exit;
    }

    public function email_accounting_batch() {
        $this->require_accounting_post('svdp_email_accounting_batch');
        SVDP_Accounting::email_export(absint($_POST['batch_id'] ?? 0));
        wp_safe_redirect(admin_url('admin.php?page=svdp-vouchers&tab=accounting'));
        exit;
    }

    public function download_accounting_file() {
        if (!SVDP_Permissions::user_can_manage_accounting()) {
            wp_die('Permission denied.', 403);
        }
        $batch_id = absint($_GET['batch_id'] ?? 0);
        $type = sanitize_key($_GET['type'] ?? '');
        check_admin_referer('svdp_download_accounting_file_' . $batch_id . '_' . $type);
        global $wpdb;
        $column = $type === 'manifest' ? 'manifest_file_path' : 'iif_file_path';
        $relative = $wpdb->get_var($wpdb->prepare("SELECT $column FROM {$wpdb->prefix}svdp_accounting_batches WHERE id=%d", $batch_id));
        $uploads = wp_upload_dir();
        $absolute = $relative ? trailingslashit($uploads['basedir']) . ltrim($relative, '/') : '';
        if (!$absolute || !file_exists($absolute)) {
            wp_die('Accounting file not found.', 404);
        }
        SVDP_Accounting::audit('accounting_file_downloaded', 'batch', $batch_id, sprintf('An authorized user downloaded the %s file for accounting batch #%d.', $type, $batch_id));
        nocache_headers();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($absolute) . '"');
        readfile($absolute);
        exit;
    }

    private function require_accounting_post($nonce_action) {
        if (!SVDP_Permissions::user_can_manage_accounting()) {
            wp_die('Permission denied.', 403);
        }
        check_admin_referer($nonce_action);
    }
    
    /**
     * AJAX: Add conference
     */
    public function ajax_add_conference() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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
        
        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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
        
        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
            wp_send_json_error('Permission denied');
        }

        $available_voucher_types = SVDP_Settings::serialize_voucher_types(
            sanitize_text_field(wp_unslash($_POST['available_voucher_types'] ?? '')),
            ['clothing', 'furniture', 'household_goods']
        );

        // Sanitize and save each setting
        $settings = [
            'adult_item_value' => ['value' => sanitize_text_field(wp_unslash($_POST['adult_item_value'] ?? '5.00')), 'type' => 'decimal'],
            'child_item_value' => ['value' => sanitize_text_field(wp_unslash($_POST['child_item_value'] ?? '3.00')), 'type' => 'decimal'],
            'store_hours' => ['value' => sanitize_text_field(wp_unslash($_POST['store_hours'] ?? '')), 'type' => 'text'],
            'redemption_instructions' => ['value' => sanitize_textarea_field(wp_unslash($_POST['redemption_instructions'] ?? '')), 'type' => 'textarea'],
            'available_voucher_types' => ['value' => $available_voucher_types, 'type' => 'text'],
        ];

        if (class_exists('SVDP_Voucher_Type_Settings')) {
            foreach (SVDP_Voucher_Type_Settings::get_root_voucher_types() as $voucher_type) {
                $description_key = SVDP_Voucher_Type_Settings::get_description_setting_key($voucher_type);
                $posted_description_key = 'voucher_type_description_' . $voucher_type;
                $settings[$description_key] = [
                    'value' => sanitize_textarea_field(wp_unslash($_POST[$posted_description_key] ?? SVDP_Voucher_Type_Settings::get_default_description($voucher_type))),
                    'type' => 'textarea',
                ];
            }
        }

        $success = true;
        foreach ($settings as $key => $setting) {
            if (!SVDP_Settings::update_setting($key, $setting['value'], $setting['type'])) {
                $success = false;
                break;
            }
        }

        if ($success && class_exists('SVDP_Voucher_Type_Settings')) {
            foreach (SVDP_Voucher_Type_Settings::get_root_voucher_types() as $voucher_type) {
                $posted_delivery_key = 'voucher_type_delivery_' . $voucher_type;
                $result = SVDP_Voucher_Type_Settings::update_delivery_available(
                    $voucher_type,
                    !empty($_POST[$posted_delivery_key]),
                    get_current_user_id()
                );

                if (is_wp_error($result)) {
                    $success = false;
                    break;
                }
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
            wp_send_json_error('Permission denied');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $voucher_types = isset($_POST['voucher_types']) ? wp_unslash($_POST['voucher_types']) : '';

        if ($id <= 0) {
            wp_send_json_error('Invalid organization ID');
        }

        $updated = SVDP_Conference::update($id, ['allowed_voucher_types' => $voucher_types]);

        if ($updated !== false) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!SVDP_Permissions::user_can_manage_accounting()) {
            wp_send_json_error('Permission denied');
        }

        $filters = isset($_POST['filters']) ? wp_unslash($_POST['filters']) : [];
        $data = SVDP_Analytics::get_dashboard_data($filters);
        if (is_wp_error($data)) {
            wp_send_json_error($data->get_error_message());
        }

        wp_send_json_success($data);
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

    public function ajax_add_furniture_category() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');
        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) wp_send_json_error('Permission denied');
        $result = SVDP_Furniture_Catalog::create_category($_POST);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        wp_send_json_success(['id' => $result, 'message' => 'Furniture category created.']);
    }

    public function ajax_update_furniture_category() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');
        if (!SVDP_Permissions::user_can_manage_furniture_catalog()) wp_send_json_error('Permission denied');
        $result = SVDP_Furniture_Catalog::update_category((int) ($_POST['id'] ?? 0), $_POST);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        wp_send_json_success('Furniture category updated.');
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
     * AJAX: Add Household Goods browse group.
     */
    public function ajax_add_household_goods_browse_group() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $result = SVDP_Household_Goods_Catalog::create_browse_group($_POST);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['id' => $result, 'message' => 'Browse group created.']);
    }

    /**
     * AJAX: Update Household Goods browse group.
     */
    public function ajax_update_household_goods_browse_group() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $result = SVDP_Household_Goods_Catalog::update_browse_group($id, $_POST);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Browse group updated.');
    }

    /**
     * AJAX: Archive or restore Household Goods browse group.
     */
    public function ajax_set_household_goods_browse_group_active() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        $result = SVDP_Household_Goods_Catalog::set_browse_group_active($id, $active);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($active ? 'Browse group restored.' : 'Browse group archived.');
    }

    /**
     * AJAX: Add Household Goods category.
     */
    public function ajax_add_household_goods_category() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $result = SVDP_Household_Goods_Catalog::create_category($_POST);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['id' => $result, 'message' => 'Household Goods category created.']);
    }

    /**
     * AJAX: Update Household Goods category.
     */
    public function ajax_update_household_goods_category() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $result = SVDP_Household_Goods_Catalog::update_category($id, $_POST);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Household Goods category updated.');
    }

    /**
     * AJAX: Archive or restore Household Goods category.
     */
    public function ajax_set_household_goods_category_active() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_catalog()) {
            wp_send_json_error('Permission denied');
        }

        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        $result = SVDP_Household_Goods_Catalog::set_category_active($id, $active);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($active ? 'Household Goods category restored.' : 'Household Goods category archived.');
    }

    /**
     * AJAX: Update Household Goods limits.
     */
    public function ajax_update_household_goods_limits() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!SVDP_Permissions::user_can_manage_household_goods_limits()) {
            wp_send_json_error('Permission denied');
        }

        $result = SVDP_Household_Goods_Catalog::update_selected_category_limit($_POST['selected_category_limit'] ?? '');
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        $result = SVDP_Household_Goods_Catalog::update_voucher_quantity_max($_POST['voucher_quantity_max'] ?? '');
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success('Household Goods limits updated.');
    }

    /**
     * Export vouchers to CSV
     */
    public function export_vouchers() {
        check_admin_referer('svdp_export', 'svdp_export_nonce');
        
        if (!SVDP_Permissions::user_can_manage_accounting()) {
            wp_die('Permission denied');
        }

        $filters = [
            'date_range' => sanitize_text_field(wp_unslash($_POST['filter_date_range'] ?? 'mtd')),
            'start_date' => sanitize_text_field(wp_unslash($_POST['filter_start_date'] ?? '')),
            'end_date' => sanitize_text_field(wp_unslash($_POST['filter_end_date'] ?? '')),
            'org_type' => sanitize_text_field(wp_unslash($_POST['filter_org_type'] ?? 'all')),
            'org_id' => sanitize_text_field(wp_unslash($_POST['filter_org_id'] ?? 'all')),
            'voucher_type' => sanitize_text_field(wp_unslash($_POST['filter_voucher_type'] ?? 'all')),
        ];

        $vouchers = SVDP_Analytics::get_export_rows($filters);
        if (is_wp_error($vouchers)) {
            wp_die(esc_html($vouchers->get_error_message()));
        }
        
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
                SVDP_Analytics::format_organization_type($voucher->organization_type),
                SVDP_Analytics::format_voucher_type($voucher->voucher_type),
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
            wp_send_json_error('Permission denied');
        }

        $name = sanitize_text_field($_POST['name']);

        if (empty($name)) {
            wp_send_json_error('Manager name is required');
        }

        $manual_code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : null;
        $result = SVDP_Manager::create($name, $manual_code);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(isset($result['error']) ? $result['error'] : 'Failed to create manager');
        }
    }

    /**
     * AJAX: Get all managers
     */
    public function ajax_get_managers() {
        check_ajax_referer('svdp_admin_nonce', 'nonce');

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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

        if (!current_user_can(SVDP_VOUCHERS_ADMIN_CAP)) {
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
