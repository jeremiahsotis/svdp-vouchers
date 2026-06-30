<?php
/**
 * Settings management
 */
class SVDP_Settings {

    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value or default
     */
    public static function get_setting($key, $default = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_settings';

        $setting = $wpdb->get_row($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s",
            $key
        ));

        if ($setting) {
            return $setting->setting_value;
        }

        return $default;
    }

    /**
     * Update a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Setting type (text, decimal, textarea)
     * @return bool Success
     */
    public static function update_setting($key, $value, $type = 'text') {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_settings';

        // Check if setting exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE setting_key = %s",
            $key
        ));

        if ($exists) {
            // Update existing setting
            $result = $wpdb->update(
                $table,
                [
                    'setting_value' => $value,
                    'setting_type' => $type,
                    'updated_at' => current_time('mysql')
                ],
                ['setting_key' => $key]
            );

            return $result !== false;
        } else {
            // Insert new setting
            $result = $wpdb->insert(
                $table,
                [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_type' => $type
                ]
            );

            return $result !== false;
        }
    }

    /**
     * Get all settings as an associative array
     *
     * @return array Settings array [key => value]
     */
    public static function get_all_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_settings';

        $settings = $wpdb->get_results("SELECT setting_key, setting_value FROM $table");

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $setting->setting_value;
        }

        return $result;
    }

    /**
     * Get item values (adult and child)
     *
     * @return array ['adult' => float, 'child' => float]
     */
    public static function get_item_values() {
        return [
            'adult' => (float) self::get_setting('adult_item_value', 5.00),
            'child' => (float) self::get_setting('child_item_value', 3.00)
        ];
    }

    /**
     * Normalize a single voucher type to the locked root types.
     *
     * @param string $voucher_type Raw voucher type.
     * @return string Normalized voucher type or empty string when unsupported.
     */
    public static function normalize_voucher_type($voucher_type) {
        $voucher_type = strtolower(trim((string) $voucher_type));

        if ($voucher_type === '' || $voucher_type === 'regular') {
            return 'clothing';
        }

        if ($voucher_type === 'household') {
            return 'furniture';
        }

        if ($voucher_type === 'clothing' || $voucher_type === 'furniture' || $voucher_type === 'household_goods') {
            return $voucher_type;
        }

        return '';
    }

    /**
     * Normalize a voucher type list from JSON, CSV, or array input.
     *
     * @param mixed $types Raw voucher type collection.
     * @param array $default Default types when input is empty.
     * @return array
     */
    public static function normalize_voucher_types($types, $default = ['clothing']) {
        if (is_string($types)) {
            $decoded = json_decode($types, true);
            if (is_array($decoded)) {
                $types = $decoded;
            } else {
                $types = array_map('trim', explode(',', $types));
            }
        }

        if (!is_array($types)) {
            $types = [];
        }

        $normalized = [];
        foreach ($types as $type) {
            $normalized_type = self::normalize_voucher_type($type);
            if ($normalized_type === '' || in_array($normalized_type, $normalized, true)) {
                continue;
            }

            $normalized[] = $normalized_type;
        }

        if (!empty($normalized)) {
            return $normalized;
        }

        $fallback = [];
        foreach ((array) $default as $type) {
            $normalized_type = self::normalize_voucher_type($type);
            if ($normalized_type === '' || in_array($normalized_type, $fallback, true)) {
                continue;
            }

            $fallback[] = $normalized_type;
        }

        return !empty($fallback) ? $fallback : ['clothing'];
    }

    /**
     * Serialize a voucher type list into the settings CSV format.
     *
     * @param mixed $types Raw voucher type collection.
     * @param array $default Default types when input is empty.
     * @return string
     */
    public static function serialize_voucher_types($types, $default = ['clothing']) {
        return implode(',', self::normalize_voucher_types($types, $default));
    }

    /**
     * Encode a voucher type list into the conference JSON format.
     *
     * @param mixed $types Raw voucher type collection.
     * @param array $default Default types when input is empty.
     * @return string
     */
    public static function encode_voucher_types($types, $default = ['clothing']) {
        return wp_json_encode(self::normalize_voucher_types($types, $default));
    }

    /**
     * Get available voucher types
     *
     * @return array Array of voucher type strings
     */
    public static function get_available_voucher_types() {
        $raw_types = self::get_setting('available_voucher_types', 'clothing,furniture,household_goods');
        $normalized = self::normalize_voucher_types($raw_types, ['clothing', 'furniture', 'household_goods']);

        if (self::is_legacy_two_type_default($raw_types)) {
            $normalized[] = 'household_goods';
            $normalized = array_values(array_unique($normalized));
        }

        return $normalized;
    }

    /**
     * Public request form can expose the globally available root voucher types.
     *
     * @return array
     */
    public static function get_public_request_voucher_types() {
        return self::normalize_voucher_types(
            self::get_available_voucher_types(),
            ['clothing', 'furniture', 'household_goods']
        );
    }

    /**
     * Resolve Conference/Organization voucher types for new public requests.
     *
     * Existing non-store rows saved with the old default of clothing+furniture are
     * treated as Release C's three-type default for future requests.
     *
     * @param object $conference Conference row.
     * @return array
     */
    public static function get_conference_allowed_request_voucher_types($conference) {
        $is_store = !empty($conference->organization_type) && $conference->organization_type === 'store';
        $default = $is_store ? ['clothing'] : ['clothing', 'furniture', 'household_goods'];
        $raw_types = $conference->allowed_voucher_types ?? '';
        $normalized = self::normalize_voucher_types($raw_types, $default);

        if (!$is_store && self::is_legacy_two_type_default($raw_types)) {
            $normalized[] = 'household_goods';
            $normalized = array_values(array_unique($normalized));
        }

        return $normalized;
    }

    /**
     * Detect the old non-store default before Household Goods existed.
     *
     * @param mixed $raw_types Raw stored type list.
     * @return bool
     */
    private static function is_legacy_two_type_default($raw_types) {
        if (!is_string($raw_types)) {
            return false;
        }

        $trimmed = trim($raw_types);
        if ($trimmed === 'clothing,furniture') {
            return true;
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            return false;
        }

        $normalized = self::normalize_voucher_types($decoded, []);
        sort($normalized);

        return $normalized === ['clothing', 'furniture'];
    }

    /**
     * Get store hours
     *
     * @return string Store hours text
     */
    public static function get_store_hours() {
        return self::get_setting('store_hours', 'Monday-Friday 9am-5pm');
    }

    /**
     * Get redemption instructions
     *
     * @return string Redemption instructions text
     */
    public static function get_redemption_instructions() {
        return self::get_setting('redemption_instructions', 'Neighbors should visit the store and provide their first name, last name, and date of birth at the counter.');
    }
}
