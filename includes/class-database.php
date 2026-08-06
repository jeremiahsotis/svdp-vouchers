<?php
/**
 * Database setup and schema.
 */
class SVDP_Database {

    const SCHEMA_VERSION = '14';

    /**
     * Run idempotent schema upgrades for the plugin.
     */
    public static function maybe_upgrade() {
        $current_version = get_option('svdp_vouchers_schema_version', '');
        $needs_v13_backfill = version_compare((string) $current_version, '13', '<');
        if ($current_version === self::SCHEMA_VERSION && self::has_current_schema()) {
            return;
        }

        global $wpdb;
        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $needs_v2_backfill = !self::column_exists($conferences_table, 'organization_type');

        self::create_tables();

        if ($needs_v2_backfill) {
            self::run_v2_backfill();
        }

        self::normalize_slice_two_data();
        self::normalize_furniture_coverage_data();
        self::normalize_unified_priced_catalog_data($needs_v13_backfill);

        update_option('svdp_vouchers_schema_version', self::SCHEMA_VERSION);
    }

    /**
     * Confirm the current schema version also has the latest required columns.
     *
     * This protects long-running installs if the stored schema version is current
     * but one or more furniture columns were not added successfully.
     *
     * @return bool
     */
    private static function has_current_schema() {
        global $wpdb;

        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $catalog_items_table = $wpdb->prefix . 'svdp_catalog_items';
        $furniture_categories_table = $wpdb->prefix . 'svdp_furniture_categories';
        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';
        $managers_table = $wpdb->prefix . 'svdp_managers';
        $override_audit_table = $wpdb->prefix . 'svdp_override_audit';
        $voucher_corrections_table = $wpdb->prefix . 'svdp_voucher_corrections';
        $request_groups_table = $wpdb->prefix . 'svdp_voucher_request_groups';
        $request_group_delivery_table = $wpdb->prefix . 'svdp_voucher_request_group_delivery';
        $voucher_type_capabilities_table = $wpdb->prefix . 'svdp_voucher_type_capabilities';
        $household_goods_browse_groups_table = $wpdb->prefix . 'svdp_household_goods_browse_groups';
        $household_goods_catalog_table = $wpdb->prefix . 'svdp_household_goods_catalog';
        $configuration_audit_table = $wpdb->prefix . 'svdp_configuration_audit';
        $requested_lines_table = $wpdb->prefix . 'svdp_voucher_requested_lines';
        $fulfillment_entries_table = $wpdb->prefix . 'svdp_voucher_fulfillment_entries';
        $unavailable_reasons_table = $wpdb->prefix . 'svdp_unavailable_reasons';
        $fulfillment_audit_table = $wpdb->prefix . 'svdp_voucher_fulfillment_audit';
        $statements_table = $wpdb->prefix . 'svdp_invoice_statements';
        $batches_table = $wpdb->prefix . 'svdp_accounting_batches';
        $accounting_audit_table = $wpdb->prefix . 'svdp_accounting_audit';

        if (!self::table_exists($vouchers_table) || !self::table_exists($catalog_items_table) || !self::table_exists($furniture_categories_table) || !self::table_exists($voucher_items_table) || !self::table_exists($managers_table) || !self::table_exists($override_audit_table) || !self::table_exists($voucher_corrections_table) || !self::table_exists($request_groups_table) || !self::table_exists($request_group_delivery_table) || !self::table_exists($voucher_type_capabilities_table) || !self::table_exists($household_goods_browse_groups_table) || !self::table_exists($household_goods_catalog_table) || !self::table_exists($configuration_audit_table) || !self::table_exists($requested_lines_table) || !self::table_exists($fulfillment_entries_table) || !self::table_exists($unavailable_reasons_table) || !self::table_exists($fulfillment_audit_table) || !self::table_exists($batches_table) || !self::table_exists($accounting_audit_table)) {
            return false;
        }

        return self::column_exists($vouchers_table, 'delivery_lat')
            && self::column_exists($vouchers_table, 'request_group_id')
            && self::column_exists($vouchers_table, 'finalized_at')
            && self::column_exists($vouchers_table, 'finalized_by_user_id')
            && self::column_exists($vouchers_table, 'finalization_note')
            && self::column_exists($vouchers_table, 'finalization_note_by_user_id')
            && self::column_exists($vouchers_table, 'finalization_note_at')
            && self::column_exists($vouchers_table, 'receipt_file_path')
            && self::column_exists($vouchers_table, 'delivery_lng')
            && self::column_exists($vouchers_table, 'delivery_verified')
            && self::column_exists($vouchers_table, 'delivery_verification_source')
            && self::column_exists($vouchers_table, 'delivery_verification_confidence')
            && self::column_exists($vouchers_table, 'delivery_normalized_address')
            && self::column_exists($catalog_items_table, 'show_price_as_max')
            && self::column_exists($catalog_items_table, 'discount_type')
            && self::column_exists($catalog_items_table, 'discount_value')
            && self::column_exists($voucher_items_table, 'discount_type_snapshot')
            && self::column_exists($voucher_items_table, 'discount_value_snapshot')
            && self::column_exists($voucher_items_table, 'conference_share_amount')
            && self::column_exists($voucher_items_table, 'store_share_amount')
            && self::column_exists($managers_table, 'failed_attempts')
            && self::column_exists($managers_table, 'locked_until')
            && self::column_exists($managers_table, 'last_used_at')
            && self::column_exists($voucher_corrections_table, 'human_summary')
            && self::column_exists($request_group_delivery_table, 'selected_voucher_types_snapshot')
            && self::column_exists($household_goods_browse_groups_table, 'updated_by_user_id')
            && self::column_exists($household_goods_catalog_table, 'cashier_guidance')
            && self::column_exists($household_goods_catalog_table, 'pricing_type')
            && self::column_exists($household_goods_catalog_table, 'show_price_as_max')
            && self::column_exists($requested_lines_table, 'requested_pricing_type_snapshot')
            && self::column_exists($requested_lines_table, 'selected_category_limit_snapshot')
            && self::column_exists($configuration_audit_table, 'human_summary')
            && self::column_exists($statements_table, 'pdf_file_path')
            && self::column_exists($statements_table, 'accounting_batch_id');
    }

    /**
     * Create database tables.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $vouchers_sql = "CREATE TABLE $vouchers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_group_id bigint(20) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            dob date NOT NULL,
            adults int(11) NOT NULL DEFAULT 0,
            children int(11) NOT NULL DEFAULT 0,
            conference_id bigint(20) NOT NULL,
            vincentian_name varchar(200) DEFAULT NULL,
            vincentian_email varchar(200) DEFAULT NULL,
            created_by varchar(50) NOT NULL,
            voucher_type varchar(32) NOT NULL DEFAULT 'clothing',
            voucher_created_date date NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'Active',
            workflow_status varchar(32) NOT NULL DEFAULT 'submitted',
            redeemed_date date DEFAULT NULL,
            override_note text DEFAULT NULL,
            voucher_value decimal(10,2) NOT NULL DEFAULT 0,
            voucher_items_count int(11) DEFAULT NULL,
            items_adult_redeemed int(11) DEFAULT 0,
            items_children_redeemed int(11) DEFAULT 0,
            redemption_total_value decimal(10,2) DEFAULT NULL,
            finalized_at datetime DEFAULT NULL,
            finalized_by_user_id bigint(20) DEFAULT NULL,
            finalization_note text DEFAULT NULL,
            finalization_note_by_user_id bigint(20) DEFAULT NULL,
            finalization_note_at datetime DEFAULT NULL,
            receipt_file_path varchar(500) DEFAULT NULL,
            denial_reason text DEFAULT NULL,
            delivery_lat decimal(10,7) DEFAULT NULL,
            delivery_lng decimal(10,7) DEFAULT NULL,
            delivery_verified tinyint(1) NOT NULL DEFAULT 0,
            delivery_verification_source varchar(100) DEFAULT NULL,
            delivery_verification_confidence decimal(5,4) DEFAULT NULL,
            delivery_normalized_address varchar(500) DEFAULT NULL,
            coat_status varchar(50) DEFAULT 'Available',
            coat_issued_date date DEFAULT NULL,
            coat_adults_issued int(11) DEFAULT NULL,
            coat_children_issued int(11) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_svdp_request_group_voucher_type (request_group_id, voucher_type),
            KEY request_group_id (request_group_id),
            KEY first_name (first_name),
            KEY last_name (last_name),
            KEY dob (dob),
            KEY conference_id (conference_id),
            KEY status (status),
            KEY voucher_type (voucher_type),
            KEY workflow_status (workflow_status),
            KEY voucher_created_date (voucher_created_date),
            KEY coat_issued_date (coat_issued_date)
        ) $charset_collate;";

        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $conferences_sql = "CREATE TABLE $conferences_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            is_emergency tinyint(1) NOT NULL DEFAULT 0,
            organization_type varchar(50) DEFAULT 'conference',
            eligibility_days int(11) DEFAULT 90,
            emergency_affects_eligibility tinyint(1) DEFAULT 0,
            regular_items_per_person int(11) DEFAULT 7,
            emergency_items_per_person int(11) DEFAULT 3,
            form_enabled tinyint(1) DEFAULT 1,
            active tinyint(1) NOT NULL DEFAULT 1,
            notification_email varchar(200) DEFAULT NULL,
            billing_email varchar(200) DEFAULT NULL,
            quickbooks_customer_name varchar(200) DEFAULT NULL,
            custom_form_text text DEFAULT NULL,
            custom_rules_text text DEFAULT NULL,
            allowed_voucher_types text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY active (active),
            KEY organization_type (organization_type)
        ) $charset_collate;";

        $settings_table = $wpdb->prefix . 'svdp_settings';
        $settings_sql = "CREATE TABLE $settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text,
            setting_type varchar(50) DEFAULT 'text',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        dbDelta($vouchers_sql);
        dbDelta($conferences_sql);
        dbDelta($settings_sql);

        self::create_furniture_tables();
        self::create_release_c_foundation_tables();
        self::create_household_goods_tables();
        self::create_release_c_fulfillment_tables();
        self::create_managers_table();
        self::create_override_reasons_table();
        self::create_override_audit_table();
        self::create_voucher_corrections_table();
        self::add_voucher_correction_human_summary_column();
        self::add_override_columns();
        self::add_address_verification_columns();
        self::add_request_group_columns();
        self::add_release_c_finalization_columns();

        self::insert_default_conferences();
        self::insert_default_settings();
        self::seed_voucher_type_capabilities();
        self::seed_household_goods_catalog();
        self::seed_unavailable_reasons();
    }

    /**
     * Insert default conferences.
     */
    private static function insert_default_conferences() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $conferences = [
            ['name' => 'Emergency', 'slug' => 'emergency', 'is_emergency' => 1],
            ['name' => 'Cathedral of the Immaculate Conception', 'slug' => 'cathedral-immaculate-conception', 'is_emergency' => 0],
            ['name' => 'Catholic Charities', 'slug' => 'catholic-charities', 'is_emergency' => 0],
            ['name' => 'Our Lady of Good Hope', 'slug' => 'our-lady-good-hope', 'is_emergency' => 0],
            ['name' => 'Queen of Angels', 'slug' => 'queen-of-angels', 'is_emergency' => 0],
            ['name' => 'Sacred Heart – Warsaw', 'slug' => 'sacred-heart-warsaw', 'is_emergency' => 0],
            ['name' => 'St Charles Borromeo', 'slug' => 'st-charles-borromeo', 'is_emergency' => 0],
            ['name' => 'St Elizabeth Ann Seton', 'slug' => 'st-elizabeth-ann-seton', 'is_emergency' => 0],
            ['name' => 'St John the Baptist', 'slug' => 'st-john-baptist', 'is_emergency' => 0],
            ['name' => 'St John – St Patrick', 'slug' => 'st-john-st-patrick', 'is_emergency' => 0],
            ['name' => 'St Joseph', 'slug' => 'st-joseph', 'is_emergency' => 0],
            ['name' => 'St Jude', 'slug' => 'st-jude', 'is_emergency' => 0],
            ['name' => 'St Louis Besancon', 'slug' => 'st-louis-besancon', 'is_emergency' => 0],
            ['name' => 'St Mary – Fort Wayne', 'slug' => 'st-mary-fort-wayne', 'is_emergency' => 0],
            ['name' => 'St Therese', 'slug' => 'st-therese', 'is_emergency' => 0],
            ['name' => 'St Vincent de Paul', 'slug' => 'st-vincent-de-paul', 'is_emergency' => 0],
        ];

        foreach ($conferences as $conference) {
            $wpdb->insert($table, $conference);
        }
    }

    /**
     * Insert default settings.
     */
    private static function insert_default_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_settings';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $settings = [
            ['setting_key' => 'adult_item_value', 'setting_value' => '5.00', 'setting_type' => 'decimal'],
            ['setting_key' => 'child_item_value', 'setting_value' => '3.00', 'setting_type' => 'decimal'],
            ['setting_key' => 'store_hours', 'setting_value' => 'Monday-Friday 9am-5pm', 'setting_type' => 'text'],
            ['setting_key' => 'redemption_instructions', 'setting_value' => 'Neighbors should visit the store and provide their first name, last name, and date of birth at the counter.', 'setting_type' => 'textarea'],
            ['setting_key' => 'available_voucher_types', 'setting_value' => 'clothing,furniture', 'setting_type' => 'text'],
            ['setting_key' => 'household_goods_voucher_quantity_max', 'setting_value' => '0', 'setting_type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            $wpdb->insert($table, $setting);
        }
    }

    /**
     * Migrate database to version 2.
     */
    public static function migrate_to_v2() {
        global $wpdb;

        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        if (self::column_exists($conferences_table, 'organization_type')) {
            return;
        }

        self::create_tables();
        self::run_v2_backfill();
    }

    /**
     * Backfill version 2 data after the schema is present.
     */
    private static function run_v2_backfill() {
        global $wpdb;

        $conferences_table = $wpdb->prefix . 'svdp_conferences';
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';

        $wpdb->query("UPDATE $conferences_table SET organization_type = 'conference' WHERE organization_type IS NULL");
        $wpdb->query("UPDATE $conferences_table SET eligibility_days = 90 WHERE eligibility_days IS NULL");
        $wpdb->query("UPDATE $conferences_table SET regular_items_per_person = 7 WHERE regular_items_per_person IS NULL");
        $wpdb->query("UPDATE $conferences_table SET emergency_items_per_person = 3 WHERE emergency_items_per_person IS NULL");
        $wpdb->query("UPDATE $conferences_table SET form_enabled = 1 WHERE form_enabled IS NULL");
        $wpdb->query("UPDATE $conferences_table SET emergency_affects_eligibility = 0 WHERE emergency_affects_eligibility IS NULL");

        $wpdb->query("UPDATE $vouchers_table SET voucher_type = 'clothing' WHERE voucher_type IS NULL OR voucher_type = '' OR voucher_type = 'regular'");
        $wpdb->query("UPDATE $vouchers_table SET items_adult_redeemed = 0 WHERE items_adult_redeemed IS NULL");
        $wpdb->query("UPDATE $vouchers_table SET items_children_redeemed = 0 WHERE items_children_redeemed IS NULL");

        $wpdb->query("UPDATE $conferences_table SET organization_type = 'store' WHERE slug = 'emergency' AND organization_type = 'conference'");
        $wpdb->query("UPDATE $conferences_table SET allowed_voucher_types = '[\"clothing\",\"furniture\"]' WHERE organization_type != 'store' AND (allowed_voucher_types IS NULL OR allowed_voucher_types = '')");
        $wpdb->query("UPDATE $conferences_table SET allowed_voucher_types = '[\"clothing\"]' WHERE organization_type = 'store' AND (allowed_voucher_types IS NULL OR allowed_voucher_types = '')");

        self::insert_default_settings();
    }

    /**
     * Create furniture support tables from the schema handoff.
     */
    private static function create_furniture_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $furniture_categories_table = $wpdb->prefix . 'svdp_furniture_categories';
        $furniture_categories_sql = "CREATE TABLE $furniture_categories_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL,
            name varchar(255) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            updated_by_user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY idx_svdp_furniture_category_active (active),
            KEY idx_svdp_furniture_category_sort (sort_order)
        ) $charset_collate;";

        $catalog_items_table = $wpdb->prefix . 'svdp_catalog_items';
        $catalog_items_sql = "CREATE TABLE $catalog_items_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            pricing_type varchar(20) NOT NULL,
            price_min decimal(10,2) DEFAULT NULL,
            price_max decimal(10,2) DEFAULT NULL,
            price_fixed decimal(10,2) DEFAULT NULL,
            show_price_as_max tinyint(1) NOT NULL DEFAULT 1,
            discount_type varchar(20) NOT NULL DEFAULT 'percent',
            discount_value decimal(10,2) NOT NULL DEFAULT 50.00,
            sort_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            allow_substitution tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_catalog_category (category),
            KEY idx_svdp_catalog_active (active),
            KEY idx_svdp_catalog_sort (sort_order)
        ) $charset_collate;";

        $furniture_meta_table = $wpdb->prefix . 'svdp_furniture_voucher_meta';
        $furniture_meta_sql = "CREATE TABLE $furniture_meta_table (
            voucher_id bigint(20) NOT NULL,
            delivery_required tinyint(1) NOT NULL DEFAULT 0,
            delivery_address_line_1 varchar(255) DEFAULT NULL,
            delivery_address_line_2 varchar(255) DEFAULT NULL,
            delivery_city varchar(100) DEFAULT NULL,
            delivery_state varchar(50) DEFAULT NULL,
            delivery_zip varchar(20) DEFAULT NULL,
            delivery_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            estimated_total_min decimal(10,2) DEFAULT NULL,
            estimated_total_max decimal(10,2) DEFAULT NULL,
            estimated_requestor_portion_min decimal(10,2) DEFAULT NULL,
            estimated_requestor_portion_max decimal(10,2) DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            completed_by_user_id bigint(20) DEFAULT NULL,
            receipt_file_path varchar(500) DEFAULT NULL,
            invoice_file_path varchar(500) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (voucher_id),
            KEY idx_svdp_furniture_completed_at (completed_at)
        ) $charset_collate;";

        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';
        $voucher_items_sql = "CREATE TABLE $voucher_items_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id bigint(20) NOT NULL,
            catalog_item_id bigint(20) DEFAULT NULL,
            requested_item_name_snapshot varchar(255) NOT NULL,
            requested_category_snapshot varchar(50) NOT NULL,
            requested_pricing_type_snapshot varchar(20) NOT NULL,
            requested_price_min_snapshot decimal(10,2) DEFAULT NULL,
            requested_price_max_snapshot decimal(10,2) DEFAULT NULL,
            requested_price_fixed_snapshot decimal(10,2) DEFAULT NULL,
            discount_type_snapshot varchar(20) DEFAULT NULL,
            discount_value_snapshot decimal(10,2) DEFAULT NULL,
            conference_share_amount decimal(10,2) DEFAULT NULL,
            store_share_amount decimal(10,2) DEFAULT NULL,
            requested_sort_order_snapshot int(11) NOT NULL DEFAULT 0,
            substitution_type varchar(20) NOT NULL DEFAULT 'none',
            substitute_catalog_item_id bigint(20) DEFAULT NULL,
            substitute_item_name varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'requested',
            actual_price decimal(10,2) DEFAULT NULL,
            completion_notes text DEFAULT NULL,
            cancellation_reason_id bigint(20) DEFAULT NULL,
            cancellation_notes text DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            completed_by_user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_voucher_items_voucher (voucher_id),
            KEY idx_svdp_voucher_items_status (status),
            KEY idx_svdp_voucher_items_sort (requested_sort_order_snapshot)
        ) $charset_collate;";

        $voucher_item_photos_table = $wpdb->prefix . 'svdp_voucher_item_photos';
        $voucher_item_photos_sql = "CREATE TABLE $voucher_item_photos_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_item_id bigint(20) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_name varchar(255) NOT NULL,
            mime_type varchar(100) NOT NULL,
            file_size bigint(20) NOT NULL,
            image_width int(11) DEFAULT NULL,
            image_height int(11) DEFAULT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            uploaded_by_user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_item_photos_item (voucher_item_id)
        ) $charset_collate;";

        $cancellation_reasons_table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';
        $cancellation_reasons_sql = "CREATE TABLE $cancellation_reasons_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reason_text varchar(255) NOT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_cancel_reasons_active (active),
            KEY idx_svdp_cancel_reasons_order (display_order)
        ) $charset_collate;";

        $invoices_table = $wpdb->prefix . 'svdp_invoices';
        $invoices_sql = "CREATE TABLE $invoices_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id bigint(20) NOT NULL,
            conference_id bigint(20) NOT NULL,
            invoice_number varchar(100) NOT NULL,
            invoice_date date NOT NULL,
            amount decimal(10,2) NOT NULL,
            delivery_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            items_total decimal(10,2) NOT NULL DEFAULT 0.00,
            conference_share_total decimal(10,2) NOT NULL DEFAULT 0.00,
            statement_id bigint(20) DEFAULT NULL,
            stored_file_path varchar(500) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_svdp_invoice_number (invoice_number),
            KEY idx_svdp_invoice_voucher (voucher_id),
            KEY idx_svdp_invoice_conference (conference_id),
            KEY idx_svdp_invoice_statement (statement_id),
            KEY idx_svdp_invoice_date (invoice_date)
        ) $charset_collate;";

        $invoice_statements_table = $wpdb->prefix . 'svdp_invoice_statements';
        $invoice_statements_sql = "CREATE TABLE $invoice_statements_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            statement_number varchar(100) NOT NULL,
            conference_id bigint(20) NOT NULL,
            period_start date NOT NULL,
            period_end date NOT NULL,
            generated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            generated_by_user_id bigint(20) DEFAULT NULL,
            stored_file_path varchar(500) DEFAULT NULL,
            pdf_file_path varchar(500) DEFAULT NULL,
            email_to_snapshot varchar(200) DEFAULT NULL,
            email_status varchar(32) NOT NULL DEFAULT 'not_sent',
            email_attempts int(11) NOT NULL DEFAULT 0,
            email_last_attempt_at datetime DEFAULT NULL,
            email_sent_at datetime DEFAULT NULL,
            email_last_error text DEFAULT NULL,
            accounting_batch_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_svdp_statement_number (statement_number),
            KEY idx_svdp_statement_conference (conference_id),
            KEY idx_svdp_statement_period (period_start, period_end)
        ) $charset_collate;";

        $accounting_batches_table = $wpdb->prefix . 'svdp_accounting_batches';
        $accounting_batches_sql = "CREATE TABLE $accounting_batches_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            batch_key varchar(100) NOT NULL,
            batch_type varchar(20) NOT NULL DEFAULT 'monthly',
            cutoff_date date NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'processing',
            trigger_source varchar(32) NOT NULL DEFAULT 'cron',
            triggered_by_user_id bigint(20) DEFAULT NULL,
            iif_file_path varchar(500) DEFAULT NULL,
            manifest_file_path varchar(500) DEFAULT NULL,
            statement_count int(11) NOT NULL DEFAULT 0,
            invoice_count int(11) NOT NULL DEFAULT 0,
            total_amount decimal(12,2) NOT NULL DEFAULT 0.00,
            bookkeeping_email_snapshot varchar(200) DEFAULT NULL,
            email_status varchar(32) NOT NULL DEFAULT 'not_sent',
            email_attempts int(11) NOT NULL DEFAULT 0,
            email_sent_at datetime DEFAULT NULL,
            email_last_error text DEFAULT NULL,
            started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_svdp_accounting_batch_key (batch_key),
            KEY idx_svdp_accounting_batch_status (status)
        ) $charset_collate;";

        $accounting_audit_table = $wpdb->prefix . 'svdp_accounting_audit';
        $accounting_audit_sql = "CREATE TABLE $accounting_audit_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(80) NOT NULL,
            decision varchar(10) NOT NULL DEFAULT 'allow',
            resource_type varchar(50) NOT NULL,
            resource_id bigint(20) DEFAULT NULL,
            actor_user_id bigint(20) DEFAULT NULL,
            actor_source varchar(32) NOT NULL DEFAULT 'user',
            before_value longtext DEFAULT NULL,
            after_value longtext DEFAULT NULL,
            error_message text DEFAULT NULL,
            human_summary text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_accounting_audit_resource (resource_type, resource_id),
            KEY idx_svdp_accounting_audit_created (created_at)
        ) $charset_collate;";

        dbDelta($furniture_categories_sql);
        dbDelta($catalog_items_sql);
        dbDelta($furniture_meta_sql);
        dbDelta($voucher_items_sql);
        dbDelta($voucher_item_photos_sql);
        dbDelta($cancellation_reasons_sql);
        dbDelta($invoices_sql);
        dbDelta($invoice_statements_sql);
        dbDelta($accounting_batches_sql);
        dbDelta($accounting_audit_sql);
    }

    /**
     * Normalize root voucher types and settings for the furniture-ready schema.
     */
    private static function normalize_slice_two_data() {
        global $wpdb;

        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        if (self::table_exists($vouchers_table)) {
            if (!self::column_exists($vouchers_table, 'workflow_status')) {
                $wpdb->query("ALTER TABLE $vouchers_table ADD COLUMN workflow_status varchar(32) NOT NULL DEFAULT 'submitted' AFTER status");
            }

            $wpdb->query("ALTER TABLE $vouchers_table MODIFY COLUMN voucher_type varchar(32) NOT NULL DEFAULT 'clothing'");
            $wpdb->query("ALTER TABLE $vouchers_table MODIFY COLUMN workflow_status varchar(32) NOT NULL DEFAULT 'submitted'");
            $wpdb->query("UPDATE $vouchers_table SET voucher_type = 'clothing' WHERE voucher_type IS NULL OR voucher_type = '' OR voucher_type = 'regular'");
            $wpdb->query("UPDATE $vouchers_table SET voucher_type = 'furniture' WHERE voucher_type = 'household'");
            $wpdb->query("UPDATE $vouchers_table SET workflow_status = 'submitted' WHERE workflow_status IS NULL OR workflow_status = ''");
        }

        self::normalize_conference_voucher_types();
        self::normalize_available_voucher_types_setting();
    }

    /**
     * Normalize conference voucher type JSON so furniture and household share one root type.
     */
    private static function normalize_conference_voucher_types() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';

        if (!self::table_exists($table)) {
            return;
        }

        $conferences = $wpdb->get_results("SELECT id, organization_type, allowed_voucher_types FROM $table");
        foreach ($conferences as $conference) {
            $default_types = $conference->organization_type === 'store'
                ? ['clothing']
                : ['clothing', 'furniture'];

            $normalized_types = SVDP_Settings::encode_voucher_types($conference->allowed_voucher_types, $default_types);

            $wpdb->update(
                $table,
                ['allowed_voucher_types' => $normalized_types],
                ['id' => $conference->id]
            );
        }
    }

    /**
     * Normalize the available voucher type setting into root types only.
     */
    private static function normalize_available_voucher_types_setting() {
        if (!self::table_exists($GLOBALS['wpdb']->prefix . 'svdp_settings')) {
            return;
        }

        $normalized_types = SVDP_Settings::serialize_voucher_types(
            SVDP_Settings::get_setting('available_voucher_types', 'clothing,furniture'),
            ['clothing', 'furniture']
        );

        SVDP_Settings::update_setting('available_voucher_types', $normalized_types, 'text');
    }

    /**
     * Create Release C request-group and voucher-type capability tables.
     */
    private static function create_release_c_foundation_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $request_groups_table = $wpdb->prefix . 'svdp_voucher_request_groups';
        $request_groups_sql = "CREATE TABLE $request_groups_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conference_id bigint(20) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            dob date NOT NULL,
            adults int(11) NOT NULL DEFAULT 0,
            children int(11) NOT NULL DEFAULT 0,
            requestor_name varchar(200) DEFAULT NULL,
            requestor_email varchar(200) DEFAULT NULL,
            created_by varchar(50) NOT NULL DEFAULT 'Vincentian',
            submitted_at datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_request_groups_conference (conference_id),
            KEY idx_svdp_request_groups_identity (last_name, first_name, dob),
            KEY idx_svdp_request_groups_submitted (submitted_at)
        ) $charset_collate;";

        $request_group_delivery_table = $wpdb->prefix . 'svdp_voucher_request_group_delivery';
        $request_group_delivery_sql = "CREATE TABLE $request_group_delivery_table (
            request_group_id bigint(20) NOT NULL,
            delivery_requested tinyint(1) NOT NULL DEFAULT 0,
            delivery_fee_snapshot decimal(10,2) NOT NULL DEFAULT 0.00,
            eligible_voucher_types_snapshot text DEFAULT NULL,
            selected_voucher_types_snapshot text DEFAULT NULL,
            address_line_1 varchar(255) DEFAULT NULL,
            address_line_2 varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            state varchar(50) DEFAULT NULL,
            zip varchar(20) DEFAULT NULL,
            lat decimal(10,7) DEFAULT NULL,
            lng decimal(10,7) DEFAULT NULL,
            verified tinyint(1) NOT NULL DEFAULT 0,
            verification_source varchar(100) DEFAULT NULL,
            verification_confidence decimal(5,4) DEFAULT NULL,
            normalized_address varchar(500) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (request_group_id)
        ) $charset_collate;";

        $voucher_type_capabilities_table = $wpdb->prefix . 'svdp_voucher_type_capabilities';
        $voucher_type_capabilities_sql = "CREATE TABLE $voucher_type_capabilities_table (
            voucher_type varchar(32) NOT NULL,
            delivery_available tinyint(1) NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by_user_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (voucher_type),
            KEY idx_svdp_voucher_type_delivery (delivery_available)
        ) $charset_collate;";

        dbDelta($request_groups_sql);
        dbDelta($request_group_delivery_sql);
        dbDelta($voucher_type_capabilities_sql);
    }

    /**
     * Create Release C Household Goods catalog and configuration audit tables.
     */
    private static function create_household_goods_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $browse_groups_table = $wpdb->prefix . 'svdp_household_goods_browse_groups';
        $browse_groups_sql = "CREATE TABLE $browse_groups_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            updated_by_user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY idx_svdp_hg_groups_active (active),
            KEY idx_svdp_hg_groups_sort (sort_order)
        ) $charset_collate;";

        $catalog_table = $wpdb->prefix . 'svdp_household_goods_catalog';
        $catalog_sql = "CREATE TABLE $catalog_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            browse_group_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            estimated_conference_partner_cost_per_unit decimal(10,2) NOT NULL DEFAULT 0.00,
            pricing_type varchar(20) NOT NULL DEFAULT 'fixed',
            price_min decimal(10,2) DEFAULT NULL,
            price_max decimal(10,2) DEFAULT NULL,
            price_fixed decimal(10,2) DEFAULT NULL,
            show_price_as_max tinyint(1) NOT NULL DEFAULT 1,
            discount_type varchar(20) NOT NULL DEFAULT 'percent',
            discount_value decimal(10,2) NOT NULL DEFAULT 50.00,
            quantity_max int(11) NOT NULL DEFAULT 0,
            cashier_guidance text DEFAULT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            updated_by_user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_hg_catalog_group (browse_group_id),
            KEY idx_svdp_hg_catalog_active (active),
            KEY idx_svdp_hg_catalog_sort (browse_group_id, sort_order),
            KEY idx_svdp_hg_catalog_name (name)
        ) $charset_collate;";

        $configuration_audit_table = $wpdb->prefix . 'svdp_configuration_audit';
        $configuration_audit_sql = "CREATE TABLE $configuration_audit_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            configuration_area varchar(100) NOT NULL,
            record_type varchar(100) NOT NULL,
            record_identifier varchar(200) NOT NULL,
            record_name_snapshot varchar(255) NOT NULL,
            field_changed varchar(100) NOT NULL,
            before_value text DEFAULT NULL,
            after_value text DEFAULT NULL,
            changed_by_user_id bigint(20) DEFAULT NULL,
            changed_at datetime NOT NULL,
            human_summary text NOT NULL,
            PRIMARY KEY (id),
            KEY idx_svdp_config_audit_area (configuration_area),
            KEY idx_svdp_config_audit_record (record_type, record_identifier),
            KEY idx_svdp_config_audit_changed (changed_at)
        ) $charset_collate;";

        dbDelta($browse_groups_sql);
        dbDelta($catalog_sql);
        dbDelta($configuration_audit_sql);
    }

    /**
     * Create shared Release C fulfillment tables for Furniture and Household Goods.
     */
    private static function create_release_c_fulfillment_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $requested_lines_table = $wpdb->prefix . 'svdp_voucher_requested_lines';
        $requested_lines_sql = "CREATE TABLE $requested_lines_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id bigint(20) NOT NULL,
            line_type varchar(32) NOT NULL,
            source_catalog_id bigint(20) DEFAULT NULL,
            requested_name_snapshot varchar(255) NOT NULL,
            requested_group_snapshot varchar(255) DEFAULT NULL,
            requested_quantity int(11) NOT NULL DEFAULT 1,
            estimated_conference_partner_cost_per_unit_snapshot decimal(10,2) DEFAULT NULL,
            requested_pricing_type_snapshot varchar(20) DEFAULT NULL,
            requested_price_min_snapshot decimal(10,2) DEFAULT NULL,
            requested_price_max_snapshot decimal(10,2) DEFAULT NULL,
            requested_price_fixed_snapshot decimal(10,2) DEFAULT NULL,
            show_price_as_max_snapshot tinyint(1) DEFAULT NULL,
            discount_type_snapshot varchar(20) DEFAULT NULL,
            discount_value_snapshot decimal(10,2) DEFAULT NULL,
            quantity_max_snapshot int(11) DEFAULT NULL,
            selected_category_limit_snapshot int(11) DEFAULT NULL,
            voucher_quantity_max_snapshot int(11) DEFAULT NULL,
            cashier_guidance_snapshot text DEFAULT NULL,
            sort_order_snapshot int(11) NOT NULL DEFAULT 0,
            unavailable_quantity int(11) NOT NULL DEFAULT 0,
            unavailable_reason_id bigint(20) DEFAULT NULL,
            unavailable_reason_snapshot varchar(255) DEFAULT NULL,
            resolution_status varchar(32) NOT NULL DEFAULT 'requested',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_requested_lines_voucher (voucher_id),
            KEY idx_svdp_requested_lines_type (line_type),
            KEY idx_svdp_requested_lines_sort (voucher_id, sort_order_snapshot)
        ) $charset_collate;";

        $fulfillment_entries_table = $wpdb->prefix . 'svdp_voucher_fulfillment_entries';
        $fulfillment_entries_sql = "CREATE TABLE $fulfillment_entries_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            requested_line_id bigint(20) NOT NULL,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            fulfilled_quantity int(11) NOT NULL DEFAULT 0,
            line_total decimal(10,2) NOT NULL DEFAULT 0.00,
            entered_by_user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_fulfillment_entries_line (requested_line_id),
            KEY idx_svdp_fulfillment_entries_actor (entered_by_user_id)
        ) $charset_collate;";

        $unavailable_reasons_table = $wpdb->prefix . 'svdp_unavailable_reasons';
        $unavailable_reasons_sql = "CREATE TABLE $unavailable_reasons_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reason_text varchar(255) NOT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_svdp_unavailable_reasons_active (active),
            KEY idx_svdp_unavailable_reasons_order (display_order)
        ) $charset_collate;";

        $fulfillment_audit_table = $wpdb->prefix . 'svdp_voucher_fulfillment_audit';
        $fulfillment_audit_sql = "CREATE TABLE $fulfillment_audit_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id bigint(20) NOT NULL,
            event_type varchar(100) NOT NULL,
            before_value longtext DEFAULT NULL,
            after_value longtext DEFAULT NULL,
            actor_user_id bigint(20) DEFAULT NULL,
            human_summary text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_svdp_fulfillment_audit_voucher (voucher_id),
            KEY idx_svdp_fulfillment_audit_event (event_type),
            KEY idx_svdp_fulfillment_audit_created (created_at)
        ) $charset_collate;";

        dbDelta($requested_lines_sql);
        dbDelta($fulfillment_entries_sql);
        dbDelta($unavailable_reasons_sql);
        dbDelta($fulfillment_audit_sql);
    }

    /**
     * Seed starter Household Goods browse groups and catalog categories.
     */
    private static function seed_household_goods_catalog() {
        global $wpdb;
        $groups_table = $wpdb->prefix . 'svdp_household_goods_browse_groups';
        $catalog_table = $wpdb->prefix . 'svdp_household_goods_catalog';

        if (!self::table_exists($groups_table) || !self::table_exists($catalog_table)) {
            return;
        }

        $group_count = intval($wpdb->get_var("SELECT COUNT(*) FROM $groups_table"));
        if ($group_count > 0) {
            return;
        }

        $groups = [
            'Kitchen' => [
                ['Pots & Pans', '10.00', 0],
                ['Plates & Bowls', '4.00', 12],
                ['Cups & Glasses', '3.00', 12],
                ['Silverware', '5.00', 2],
                ['Cooking Utensils', '5.00', 4],
            ],
            'Bed & Bath' => [
                ['Bedding', '12.00', 4],
                ['Pillows', '5.00', 4],
                ['Bath Towels', '4.00', 6],
                ['Washcloths', '2.00', 8],
            ],
            'Window Coverings' => [
                ['Curtains', '8.00', 6],
                ['Curtain Rods', '5.00', 6],
            ],
            'Cleaning & Home Basics' => [
                ['Laundry Basket', '5.00', 2],
                ['Trash Can', '6.00', 2],
                ['Broom', '5.00', 1],
                ['Mop', '6.00', 1],
            ],
            'Small Appliances' => [
                ['Toaster', '10.00', 1],
                ['Coffee Maker', '12.00', 1],
            ],
            'Storage & Organization' => [
                ['Storage Bins', '6.00', 6],
            ],
        ];

        $group_order = 0;
        foreach ($groups as $group_name => $categories) {
            $wpdb->insert($groups_table, [
                'name' => $group_name,
                'slug' => sanitize_title($group_name),
                'sort_order' => $group_order,
                'active' => 1,
                'updated_by_user_id' => get_current_user_id() ?: null,
            ]);

            $group_id = intval($wpdb->insert_id);
            $category_order = 0;
            foreach ($categories as $category) {
                $wpdb->insert($catalog_table, [
                    'browse_group_id' => $group_id,
                    'name' => $category[0],
                    'slug' => sanitize_title($category[0]),
                    'estimated_conference_partner_cost_per_unit' => $category[1],
                    'quantity_max' => $category[2],
                    'cashier_guidance' => '',
                    'sort_order' => $category_order,
                    'active' => 1,
                    'updated_by_user_id' => get_current_user_id() ?: null,
                ]);
                $category_order++;
            }

            $group_order++;
        }

        if (class_exists('SVDP_Settings')) {
            SVDP_Settings::update_setting('household_goods_voucher_quantity_max', '0', 'integer');
        }
    }

    /**
     * Seed unavailable reasons retained for historical/admin compatibility.
     */
    private static function seed_unavailable_reasons() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_unavailable_reasons';

        if (!self::table_exists($table)) {
            return;
        }

        $count = intval($wpdb->get_var("SELECT COUNT(*) FROM $table"));
        if ($count > 0) {
            return;
        }

        $reasons = [
            'Not currently in stock',
            'Item condition not suitable',
            'Item could not be located',
            'Other approved operational reason',
        ];

        foreach ($reasons as $index => $reason) {
            $wpdb->insert($table, [
                'reason_text' => $reason,
                'display_order' => $index,
                'active' => 1,
            ]);
        }
    }

    /**
     * Add request-group linkage to voucher rows on existing installs.
     */
    private static function add_request_group_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        if (!self::table_exists($table)) {
            return;
        }

        if (!self::column_exists($table, 'request_group_id')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN request_group_id bigint(20) DEFAULT NULL AFTER id");
        }

        if (!self::index_exists($table, 'request_group_id')) {
            $wpdb->query("ALTER TABLE $table ADD KEY request_group_id (request_group_id)");
        }

        if (!self::index_exists($table, 'uniq_svdp_request_group_voucher_type')) {
            $wpdb->query("ALTER TABLE $table ADD UNIQUE KEY uniq_svdp_request_group_voucher_type (request_group_id, voucher_type)");
        }
    }

    /**
     * Add voucher-level finalization fields for shared Release C fulfillment.
     */
    private static function add_release_c_finalization_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        if (!self::table_exists($table)) {
            return;
        }

        if (!self::column_exists($table, 'finalized_at')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN finalized_at datetime DEFAULT NULL AFTER redemption_total_value");
        }

        if (!self::column_exists($table, 'finalized_by_user_id')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN finalized_by_user_id bigint(20) DEFAULT NULL AFTER finalized_at");
        }

        if (!self::column_exists($table, 'finalization_note')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN finalization_note text DEFAULT NULL AFTER finalized_by_user_id");
        }

        if (!self::column_exists($table, 'finalization_note_by_user_id')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN finalization_note_by_user_id bigint(20) DEFAULT NULL AFTER finalization_note");
        }

        if (!self::column_exists($table, 'finalization_note_at')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN finalization_note_at datetime DEFAULT NULL AFTER finalization_note_by_user_id");
        }

        if (!self::column_exists($table, 'receipt_file_path')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN receipt_file_path varchar(500) DEFAULT NULL AFTER finalization_note_at");
        }
    }

    /**
     * Seed initial Release C delivery capability defaults.
     */
    private static function seed_voucher_type_capabilities() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_type_capabilities';

        if (!self::table_exists($table)) {
            return;
        }

        $defaults = [
            'clothing' => 0,
            'furniture' => 1,
            'household_goods' => 1,
        ];

        foreach ($defaults as $voucher_type => $delivery_available) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE voucher_type = %s",
                $voucher_type
            ));

            if ($exists) {
                continue;
            }

            $wpdb->insert($table, [
                'voucher_type' => $voucher_type,
                'delivery_available' => $delivery_available,
                'updated_at' => current_time('mysql'),
                'updated_by_user_id' => get_current_user_id() ?: null,
            ]);
        }
    }

    /**
     * Normalize catalog coverage fields and voucher-item snapshots for furniture requests.
     */
    private static function normalize_furniture_coverage_data() {
        global $wpdb;

        $catalog_items_table = $wpdb->prefix . 'svdp_catalog_items';
        if (self::table_exists($catalog_items_table)) {
            if (!self::column_exists($catalog_items_table, 'show_price_as_max')) {
                $wpdb->query("ALTER TABLE $catalog_items_table ADD COLUMN show_price_as_max tinyint(1) NOT NULL DEFAULT 1 AFTER price_fixed");
            }

            if (!self::column_exists($catalog_items_table, 'discount_type')) {
                $wpdb->query("ALTER TABLE $catalog_items_table ADD COLUMN discount_type varchar(20) NOT NULL DEFAULT 'percent' AFTER price_fixed");
            }

            if (!self::column_exists($catalog_items_table, 'discount_value')) {
                $wpdb->query("ALTER TABLE $catalog_items_table ADD COLUMN discount_value decimal(10,2) NOT NULL DEFAULT 50.00 AFTER discount_type");
            }

            $wpdb->query("ALTER TABLE $catalog_items_table MODIFY COLUMN show_price_as_max tinyint(1) NOT NULL DEFAULT 1");
            $wpdb->query("ALTER TABLE $catalog_items_table MODIFY COLUMN discount_type varchar(20) NOT NULL DEFAULT 'percent'");
            $wpdb->query("ALTER TABLE $catalog_items_table MODIFY COLUMN discount_value decimal(10,2) NOT NULL DEFAULT 50.00");
            $wpdb->query("UPDATE $catalog_items_table SET show_price_as_max = 0 WHERE show_price_as_max IS NULL");
            $wpdb->query("UPDATE $catalog_items_table SET discount_type = 'percent' WHERE discount_type IS NULL OR discount_type = '' OR discount_type NOT IN ('percent', 'fixed')");
            $wpdb->query("UPDATE $catalog_items_table SET discount_value = 50.00 WHERE discount_value IS NULL OR discount_value < 0");
            $wpdb->query("UPDATE $catalog_items_table SET discount_value = 50.00 WHERE discount_type = 'percent' AND discount_value > 100");
        }

        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';
        if (!self::table_exists($voucher_items_table)) {
            return;
        }

        if (!self::column_exists($voucher_items_table, 'discount_type_snapshot')) {
            $wpdb->query("ALTER TABLE $voucher_items_table ADD COLUMN discount_type_snapshot varchar(20) DEFAULT NULL AFTER requested_price_fixed_snapshot");
        }

        if (!self::column_exists($voucher_items_table, 'discount_value_snapshot')) {
            $wpdb->query("ALTER TABLE $voucher_items_table ADD COLUMN discount_value_snapshot decimal(10,2) DEFAULT NULL AFTER discount_type_snapshot");
        }

        if (!self::column_exists($voucher_items_table, 'conference_share_amount')) {
            $wpdb->query("ALTER TABLE $voucher_items_table ADD COLUMN conference_share_amount decimal(10,2) DEFAULT NULL AFTER discount_value_snapshot");
        }

        if (!self::column_exists($voucher_items_table, 'store_share_amount')) {
            $wpdb->query("ALTER TABLE $voucher_items_table ADD COLUMN store_share_amount decimal(10,2) DEFAULT NULL AFTER conference_share_amount");
        }

        $wpdb->query("ALTER TABLE $voucher_items_table MODIFY COLUMN discount_type_snapshot varchar(20) DEFAULT NULL");
        $wpdb->query("ALTER TABLE $voucher_items_table MODIFY COLUMN discount_value_snapshot decimal(10,2) DEFAULT NULL");
        $wpdb->query("ALTER TABLE $voucher_items_table MODIFY COLUMN conference_share_amount decimal(10,2) DEFAULT NULL");
        $wpdb->query("ALTER TABLE $voucher_items_table MODIFY COLUMN store_share_amount decimal(10,2) DEFAULT NULL");

        $wpdb->query("UPDATE $voucher_items_table SET discount_type_snapshot = 'percent' WHERE discount_type_snapshot IS NULL OR discount_type_snapshot = '' OR discount_type_snapshot NOT IN ('percent', 'fixed')");
        $wpdb->query("UPDATE $voucher_items_table SET discount_value_snapshot = 50.00 WHERE discount_value_snapshot IS NULL OR discount_value_snapshot < 0");
        $wpdb->query("UPDATE $voucher_items_table SET discount_value_snapshot = 50.00 WHERE discount_type_snapshot = 'percent' AND discount_value_snapshot > 100");
    }

    /** Normalize additive schema-13 catalog data without rewriting issued snapshots. */
    private static function normalize_unified_priced_catalog_data($run_v13_backfill = false) {
        global $wpdb;

        $categories = $wpdb->prefix . 'svdp_furniture_categories';
        $items = $wpdb->prefix . 'svdp_catalog_items';
        if (self::table_exists($categories)) {
            $seed = [
                ['used_furniture', 'Used Furniture', 10],
                ['handmade_furniture', 'Handmade Furniture', 20],
                ['mattresses_frames', 'Mattresses & Frames', 30],
                ['household_goods', 'Household Goods', 40],
            ];
            foreach ($seed as $category) {
                if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM $categories WHERE slug = %s", $category[0]))) {
                    $active = 1;
                    if ($category[0] === 'household_goods') {
                        $active = (int) $wpdb->get_var("SELECT COUNT(*) FROM $items WHERE category = 'household_goods' AND active = 1") > 0 ? 1 : 0;
                    }
                    $wpdb->insert($categories, ['slug' => $category[0], 'name' => $category[1], 'sort_order' => $category[2], 'active' => $active]);
                }
            }
        }

        if ($run_v13_backfill && self::table_exists($items)) {
            $wpdb->query("UPDATE $items SET show_price_as_max = 1 WHERE pricing_type = 'range'");
        }

        $hg = $wpdb->prefix . 'svdp_household_goods_catalog';
        if (self::table_exists($hg)) {
            $wpdb->query("UPDATE $hg SET pricing_type = 'fixed', price_fixed = estimated_conference_partner_cost_per_unit, show_price_as_max = 1, discount_type = 'percent', discount_value = 50.00 WHERE price_fixed IS NULL AND price_min IS NULL AND price_max IS NULL");
            $wpdb->query("UPDATE $hg SET estimated_conference_partner_cost_per_unit = ROUND(price_fixed * 0.50, 2) WHERE pricing_type = 'fixed' AND price_fixed IS NOT NULL AND discount_type = 'percent' AND discount_value = 50.00");
        }

        if (SVDP_Settings::get_setting('household_goods_selected_category_limit', null) === null) {
            SVDP_Settings::update_setting('household_goods_selected_category_limit', '0', 'integer');
        }
    }

    /**
     * Create managers table for override system.
     */
    public static function create_managers_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            code_hash varchar(255) NOT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            failed_attempts int(11) NOT NULL DEFAULT 0,
            locked_until datetime DEFAULT NULL,
            last_used_at datetime DEFAULT NULL,
            created_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY active (active),
            KEY locked_until (locked_until)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create override audit table.
     */
    public static function create_override_audit_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_override_audit';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id bigint(20) DEFAULT NULL,
            manager_id bigint(20) DEFAULT NULL,
            manager_name_snapshot varchar(200) DEFAULT NULL,
            actor_user_id bigint(20) DEFAULT NULL,
            success tinyint(1) NOT NULL DEFAULT 0,
            reason_id bigint(20) DEFAULT NULL,
            reason_text_snapshot varchar(255) DEFAULT NULL,
            context varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY manager_id (manager_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create voucher correction audit table.
     */
    public static function create_voucher_corrections_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_corrections';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id bigint(20) NOT NULL,
            field_name varchar(100) NOT NULL,
            before_value text NULL,
            after_value text NULL,
            actor_user_id bigint(20) NULL,
            manager_id bigint(20) NULL,
            manager_name_snapshot varchar(200) NULL,
            reason_id bigint(20) NULL,
            reason_text_snapshot varchar(255) NULL,
            human_summary text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY voucher_id (voucher_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add human-readable voucher correction summaries to existing installs.
     */
    public static function add_voucher_correction_human_summary_column() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_corrections';

        if (!self::table_exists($table)) {
            return;
        }

        if (!self::column_exists($table, 'human_summary')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN human_summary text DEFAULT NULL AFTER reason_text_snapshot");
        }
    }

    /**
     * Create override reasons table.
     */
    public static function create_override_reasons_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_override_reasons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reason_text varchar(255) NOT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY active_order (active, display_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        self::insert_default_reasons();
    }

    /**
     * Insert default override reasons.
     */
    private static function insert_default_reasons() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_override_reasons';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $defaults = [
            'Urgent family emergency',
            'Recent disaster or fire',
            'Medical emergency',
            'Housing crisis/eviction',
            'Other special circumstance'
        ];

        foreach ($defaults as $index => $reason) {
            $wpdb->insert($table, [
                'reason_text' => $reason,
                'display_order' => $index,
                'active' => 1
            ]);
        }
    }

    /**
     * Add manager_id and reason_id columns to vouchers table.
     */
    public static function add_override_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        if (!self::column_exists($table, 'manager_id')) {
            $wpdb->query("ALTER TABLE $table
                ADD COLUMN manager_id bigint(20) DEFAULT NULL AFTER override_note,
                ADD COLUMN reason_id bigint(20) DEFAULT NULL AFTER manager_id");

            $wpdb->query("ALTER TABLE $table
                ADD KEY manager_id (manager_id),
                ADD KEY reason_id (reason_id)");
        }
    }

    /**
     * Add optional address verification columns to vouchers table.
     */
    public static function add_address_verification_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        if (!self::table_exists($table)) {
            return;
        }

        if (!self::column_exists($table, 'delivery_lat')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN delivery_lat decimal(10,7) DEFAULT NULL AFTER denial_reason");
        }

        if (!self::column_exists($table, 'delivery_lng')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN delivery_lng decimal(10,7) DEFAULT NULL AFTER delivery_lat");
        }

        if (!self::column_exists($table, 'delivery_verified')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN delivery_verified tinyint(1) NOT NULL DEFAULT 0 AFTER delivery_lng");
        }

        if (!self::column_exists($table, 'delivery_verification_source')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN delivery_verification_source varchar(100) DEFAULT NULL AFTER delivery_verified");
        }

        if (!self::column_exists($table, 'delivery_verification_confidence')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN delivery_verification_confidence decimal(5,4) DEFAULT NULL AFTER delivery_verification_source");
        }

        if (!self::column_exists($table, 'delivery_normalized_address')) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN delivery_normalized_address varchar(500) DEFAULT NULL AFTER delivery_verification_confidence");
        }
    }

    /**
     * Check whether a table exists.
     *
     * @param string $table Fully-qualified table name.
     * @return bool
     */
    private static function table_exists($table) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
    }

    /**
     * Check whether a column exists on a table.
     *
     * @param string $table Fully-qualified table name.
     * @param string $column Column name.
     * @return bool
     */
    private static function column_exists($table, $column) {
        global $wpdb;

        if (!self::table_exists($table)) {
            return false;
        }

        $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $table LIKE %s", $column));
        return !empty($column_exists);
    }

    /**
     * Check whether an index exists on a table.
     *
     * @param string $table Fully-qualified table name.
     * @param string $index Index name.
     * @return bool
     */
    private static function index_exists($table, $index) {
        global $wpdb;

        if (!self::table_exists($table)) {
            return false;
        }

        $rows = $wpdb->get_results($wpdb->prepare("SHOW INDEX FROM $table WHERE Key_name = %s", $index));
        return !empty($rows);
    }
}
