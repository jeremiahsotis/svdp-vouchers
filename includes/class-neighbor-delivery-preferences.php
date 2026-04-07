<?php
/**
 * Neighbor delivery preferences storage.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Neighbor_Delivery_Preferences {

    /**
     * Build a deterministic lookup key from neighbor identity fields.
     *
     * @param string $first_name First name.
     * @param string $last_name Last name.
     * @param string $dob Date of birth.
     * @return string
     */
    public static function build_lookup_key($first_name, $last_name, $dob) {
        $identity = self::normalize_identity_fields($first_name, $last_name, $dob);

        if ($identity['lookup_first_name'] === '' || $identity['lookup_last_name'] === '' || $identity['lookup_dob'] === '') {
            return '';
        }

        return sha1(implode('|', [
            $identity['lookup_first_name'],
            $identity['lookup_last_name'],
            $identity['lookup_dob'],
        ]));
    }

    /**
     * Normalize identity values for storage and lookup-key generation.
     *
     * @param string $first_name First name.
     * @param string $last_name Last name.
     * @param string $dob Date of birth.
     * @return array<string, string>
     */
    public static function normalize_identity_fields($first_name, $last_name, $dob) {
        $normalized_dob = self::normalize_dob($dob);

        return [
            'first_name' => self::normalize_text($first_name),
            'last_name' => self::normalize_text($last_name),
            'dob' => $normalized_dob,
            'lookup_first_name' => self::normalize_lookup_fragment($first_name),
            'lookup_last_name' => self::normalize_lookup_fragment($last_name),
            'lookup_dob' => self::normalize_lookup_fragment($normalized_dob),
        ];
    }

    /**
     * Retrieve preferences by deterministic lookup key.
     *
     * @param string $lookup_key Lookup key hash.
     * @return array<string, mixed>|null
     */
    public static function get_by_lookup_key($lookup_key) {
        global $wpdb;

        $lookup_key = self::normalize_lookup_key($lookup_key);
        if ($lookup_key === '') {
            return null;
        }

        $table = self::get_table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE neighbor_lookup_key = %s LIMIT 1",
                $lookup_key
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Retrieve preferences by neighbor identity fields.
     *
     * @param string $first_name First name.
     * @param string $last_name Last name.
     * @param string $dob Date of birth.
     * @return array<string, mixed>|null
     */
    public static function get_by_identity($first_name, $last_name, $dob) {
        $lookup_key = self::build_lookup_key($first_name, $last_name, $dob);
        if ($lookup_key === '') {
            return null;
        }

        return self::get_by_lookup_key($lookup_key);
    }

    /**
     * Create or update a delivery preferences record keyed by neighbor identity.
     *
     * @param array<string, mixed> $data Preference data.
     * @return array<string, mixed>|false
     */
    public static function upsert_preferences($data) {
        global $wpdb;

        if (!is_array($data)) {
            return false;
        }

        $identity = self::normalize_identity_fields(
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['dob'] ?? ''
        );

        $lookup_key = self::build_lookup_key(
            $identity['first_name'],
            $identity['last_name'],
            $identity['dob']
        );

        if ($lookup_key === '' || $identity['dob'] === '') {
            return false;
        }

        $timestamp = current_time('mysql');
        $record = [
            'neighbor_lookup_key' => $lookup_key,
            'first_name' => $identity['first_name'],
            'last_name' => $identity['last_name'],
            'dob' => $identity['dob'],
            'preferred_language' => self::sanitize_nullable_language($data['preferred_language'] ?? null),
            'is_opted_in' => self::normalize_boolean($data['is_opted_in'] ?? 0),
            'auto_send_enabled' => self::normalize_boolean($data['auto_send_enabled'] ?? 0),
            'email_enabled' => self::normalize_boolean($data['email_enabled'] ?? 0),
            'email_address' => self::sanitize_nullable_email($data['email_address'] ?? null),
            'sms_enabled' => self::normalize_boolean($data['sms_enabled'] ?? 0),
            'phone_number' => self::sanitize_nullable_phone($data['phone_number'] ?? null),
            'notifications_paused' => self::normalize_boolean($data['notifications_paused'] ?? 0),
            'updated_at' => $timestamp,
        ];

        $table = self::get_table_name();
        $existing = self::get_by_lookup_key($lookup_key);

        if ($existing) {
            $result = $wpdb->update(
                $table,
                $record,
                ['neighbor_lookup_key' => $lookup_key]
            );

            if ($result === false) {
                return false;
            }

            return self::get_by_lookup_key($lookup_key);
        }

        $record['created_at'] = $timestamp;

        $result = $wpdb->insert($table, $record);
        if (!$result) {
            return false;
        }

        return self::get_by_lookup_key($lookup_key);
    }

    /**
     * Create or update preferences using explicit identity fields as the source of truth.
     *
     * @param string              $first_name First name.
     * @param string              $last_name Last name.
     * @param string              $dob Date of birth.
     * @param array<string, mixed> $data Preference data.
     * @return array<string, mixed>|false
     */
    public static function upsert_preferences_for_identity($first_name, $last_name, $dob, $data = []) {
        if (!is_array($data)) {
            return false;
        }

        $identity = self::normalize_identity_fields($first_name, $last_name, $dob);
        if ($identity['first_name'] === '' || $identity['last_name'] === '' || $identity['dob'] === '') {
            return false;
        }

        $data['first_name'] = $identity['first_name'];
        $data['last_name'] = $identity['last_name'];
        $data['dob'] = $identity['dob'];

        return self::upsert_preferences($data);
    }

    /**
     * Normalize a stored preferences row for cashier/backend consumers.
     *
     * @param array|object|null $preferences Stored preferences row.
     * @return array<string, mixed>|null
     */
    public static function normalize_preference_payload($preferences) {
        if (is_object($preferences)) {
            $preferences = (array) $preferences;
        }

        if (!is_array($preferences)) {
            return null;
        }

        $preferred_language = self::normalize_lookup_fragment($preferences['preferred_language'] ?? '');
        $email_address = self::sanitize_nullable_email($preferences['email_address'] ?? null);
        $phone_number = self::sanitize_nullable_phone($preferences['phone_number'] ?? null);

        return [
            'preferred_language' => $preferred_language !== '' ? $preferred_language : null,
            'is_opted_in' => !empty($preferences['is_opted_in']),
            'auto_send_enabled' => !empty($preferences['auto_send_enabled']),
            'email_enabled' => !empty($preferences['email_enabled']),
            'email_address' => $email_address,
            'sms_enabled' => !empty($preferences['sms_enabled']),
            'phone_number' => $phone_number,
            'notifications_paused' => !empty($preferences['notifications_paused']),
        ];
    }

    /**
     * Resolve the preferences table name.
     *
     * @return string
     */
    private static function get_table_name() {
        global $wpdb;

        return $wpdb->prefix . 'svdp_neighbor_delivery_preferences';
    }

    /**
     * Normalize a text field for storage.
     *
     * @param mixed $value Raw input.
     * @return string
     */
    private static function normalize_text($value) {
        $value = sanitize_text_field((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    /**
     * Normalize a field fragment for lookup-key generation.
     *
     * @param mixed $value Raw input.
     * @return string
     */
    private static function normalize_lookup_fragment($value) {
        return strtolower(self::normalize_text($value));
    }

    /**
     * Normalize lookup-key input.
     *
     * @param mixed $lookup_key Raw lookup key.
     * @return string
     */
    private static function normalize_lookup_key($lookup_key) {
        $lookup_key = strtolower(trim((string) $lookup_key));

        if (preg_match('/^[a-f0-9]{40}$/', $lookup_key)) {
            return $lookup_key;
        }

        return '';
    }

    /**
     * Normalize date of birth to Y-m-d.
     *
     * @param mixed $dob Raw date.
     * @return string
     */
    private static function normalize_dob($dob) {
        $dob = self::normalize_text($dob);
        if ($dob === '') {
            return '';
        }

        $date = DateTime::createFromFormat('Y-m-d', $dob);
        $errors = DateTime::getLastErrors();
        $has_parse_errors = is_array($errors)
            && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);

        if ($date instanceof DateTime && !$has_parse_errors) {
            return $date->format('Y-m-d');
        }

        $timestamp = strtotime($dob);
        if ($timestamp === false) {
            return '';
        }

        return gmdate('Y-m-d', $timestamp);
    }

    /**
     * Normalize a boolean-like input to 0 or 1.
     *
     * @param mixed $value Raw input.
     * @return int
     */
    private static function normalize_boolean($value) {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /**
     * Normalize an optional language value.
     *
     * @param mixed $value Raw input.
     * @return string|null
     */
    private static function sanitize_nullable_language($value) {
        $value = self::normalize_lookup_fragment($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Normalize an optional email value.
     *
     * @param mixed $value Raw input.
     * @return string|null
     */
    private static function sanitize_nullable_email($value) {
        $email = sanitize_email((string) $value);

        return $email !== '' ? $email : null;
    }

    /**
     * Normalize an optional phone value.
     *
     * @param mixed $value Raw input.
     * @return string|null
     */
    private static function sanitize_nullable_phone($value) {
        $phone = self::normalize_text($value);

        return $phone !== '' ? $phone : null;
    }
}
