<?php
/**
 * Release C voucher-type capability settings.
 */
class SVDP_Voucher_Type_Settings {

    const DELIVERY_FEE_SETTING_KEY = 'delivery_fee';

    /**
     * Return the supported Release C root voucher types.
     */
    public static function get_root_voucher_types() {
        return ['clothing', 'furniture', 'household_goods'];
    }

    /**
     * Normalize a Release C root voucher type while preserving legacy household handling.
     */
    public static function normalize_voucher_type($voucher_type) {
        return SVDP_Settings::normalize_voucher_type($voucher_type);
    }

    /**
     * Return the delivery fee used for future request snapshots.
     */
    public static function get_delivery_fee() {
        return round((float) SVDP_Settings::get_setting(self::DELIVERY_FEE_SETTING_KEY, '50.00'), 2);
    }

    /**
     * Return configured delivery capability rows keyed by voucher type.
     */
    public static function get_capabilities() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_type_capabilities';

        $rows = $wpdb->get_results("SELECT voucher_type, delivery_available, updated_at, updated_by_user_id FROM $table");
        $capabilities = [];

        foreach (self::get_default_delivery_capabilities() as $voucher_type => $delivery_available) {
            $capabilities[$voucher_type] = [
                'voucher_type' => $voucher_type,
                'delivery_available' => (bool) $delivery_available,
                'updated_at' => null,
                'updated_by_user_id' => null,
            ];
        }

        foreach ((array) $rows as $row) {
            $voucher_type = self::normalize_voucher_type($row->voucher_type);
            if (!in_array($voucher_type, self::get_root_voucher_types(), true)) {
                continue;
            }

            $capabilities[$voucher_type] = [
                'voucher_type' => $voucher_type,
                'delivery_available' => !empty($row->delivery_available),
                'updated_at' => $row->updated_at,
                'updated_by_user_id' => $row->updated_by_user_id !== null ? (int) $row->updated_by_user_id : null,
            ];
        }

        return $capabilities;
    }

    /**
     * Determine whether a voucher type can participate in delivery for future requests.
     */
    public static function is_delivery_available($voucher_type) {
        $voucher_type = self::normalize_voucher_type($voucher_type);
        $capabilities = self::get_capabilities();

        return !empty($capabilities[$voucher_type]['delivery_available']);
    }

    /**
     * Return the selected types that are delivery-eligible at request time.
     */
    public static function get_delivery_eligible_types($voucher_types) {
        $eligible = [];

        foreach (self::normalize_voucher_types($voucher_types) as $voucher_type) {
            if (self::is_delivery_available($voucher_type)) {
                $eligible[] = $voucher_type;
            }
        }

        return $eligible;
    }

    /**
     * Return the default Assistance Needed description for a voucher type.
     */
    public static function get_default_description($voucher_type) {
        $voucher_type = self::normalize_voucher_type($voucher_type);

        $defaults = [
            'clothing' => 'Must redeem in one visit within 30 days of issue date.',
            'furniture' => 'Choose needed furniture items and delivery, if needed. Must redeem in one visit within 30 days of issue date.',
            'household_goods' => 'Choose needed household items. Must redeem in one visit within 30 days of issue date.',
        ];

        return $defaults[$voucher_type] ?? '';
    }

    /**
     * Return the Assistance Needed description setting key for a voucher type.
     */
    public static function get_description_setting_key($voucher_type) {
        $voucher_type = self::normalize_voucher_type($voucher_type);

        if (!in_array($voucher_type, self::get_root_voucher_types(), true)) {
            return '';
        }

        return 'voucher_type_description_' . $voucher_type;
    }

    /**
     * Return the configured Assistance Needed description for a voucher type.
     */
    public static function get_description($voucher_type) {
        $voucher_type = self::normalize_voucher_type($voucher_type);
        $key = self::get_description_setting_key($voucher_type);

        if ($key === '') {
            return '';
        }

        return SVDP_Settings::get_setting($key, self::get_default_description($voucher_type));
    }

    /**
     * Return configured Assistance Needed descriptions keyed by voucher type.
     */
    public static function get_descriptions() {
        $descriptions = [];

        foreach (self::get_root_voucher_types() as $voucher_type) {
            $descriptions[$voucher_type] = self::get_description($voucher_type);
        }

        return $descriptions;
    }

    /**
     * Update the configured Assistance Needed description for a voucher type.
     */
    public static function update_description($voucher_type, $description) {
        $key = self::get_description_setting_key($voucher_type);

        if ($key === '') {
            return new WP_Error('invalid_voucher_type', 'Invalid voucher type.');
        }

        return SVDP_Settings::update_setting($key, sanitize_textarea_field($description), 'textarea');
    }

    /**
     * Normalize a list of root voucher types.
     */
    public static function normalize_voucher_types($voucher_types) {
        if (is_string($voucher_types)) {
            $decoded = json_decode($voucher_types, true);
            $voucher_types = is_array($decoded) ? $decoded : explode(',', $voucher_types);
        }

        if (!is_array($voucher_types)) {
            $voucher_types = [];
        }

        $normalized = [];
        foreach ($voucher_types as $voucher_type) {
            if (is_array($voucher_type)) {
                $voucher_type = $voucher_type['voucherType'] ?? $voucher_type['voucher_type'] ?? '';
            } elseif (is_object($voucher_type)) {
                $voucher_type = $voucher_type->voucherType ?? $voucher_type->voucher_type ?? '';
            }

            $voucher_type = self::normalize_voucher_type($voucher_type);
            if (!in_array($voucher_type, self::get_root_voucher_types(), true) || in_array($voucher_type, $normalized, true)) {
                continue;
            }

            $normalized[] = $voucher_type;
        }

        return $normalized;
    }

    /**
     * Update one delivery capability setting for future requests.
     */
    public static function update_delivery_available($voucher_type, $delivery_available, $actor_user_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_type_capabilities';

        $voucher_type = self::normalize_voucher_type($voucher_type);
        if (!in_array($voucher_type, self::get_root_voucher_types(), true)) {
            return new WP_Error('invalid_voucher_type', 'Invalid voucher type.');
        }

        $actor_user_id = $actor_user_id !== null ? intval($actor_user_id) : get_current_user_id();
        $delivery_available = $delivery_available ? 1 : 0;

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT delivery_available FROM $table WHERE voucher_type = %s",
            $voucher_type
        ));

        if ($existing) {
            $result = $wpdb->update(
                $table,
                [
                    'delivery_available' => $delivery_available,
                    'updated_at' => current_time('mysql'),
                    'updated_by_user_id' => $actor_user_id ?: null,
                ],
                ['voucher_type' => $voucher_type]
            );
        } else {
            $result = $wpdb->insert($table, [
                'voucher_type' => $voucher_type,
                'delivery_available' => $delivery_available,
                'updated_at' => current_time('mysql'),
                'updated_by_user_id' => $actor_user_id ?: null,
            ]);
        }

        if ($result === false) {
            return new WP_Error('database_error', 'Failed to update voucher type delivery setting.');
        }

        return [
            'success' => true,
            'voucher_type' => $voucher_type,
            'before' => $existing ? (bool) $existing->delivery_available : null,
            'after' => (bool) $delivery_available,
            'actor_user_id' => $actor_user_id ?: null,
            'updated_at' => current_time('mysql'),
        ];
    }

    /**
     * Initial Release C delivery defaults.
     */
    private static function get_default_delivery_capabilities() {
        return [
            'clothing' => false,
            'furniture' => true,
            'household_goods' => true,
        ];
    }
}
