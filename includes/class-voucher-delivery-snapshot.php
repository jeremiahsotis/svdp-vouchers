<?php
/**
 * Immutable voucher delivery snapshot storage.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Voucher_Delivery_Snapshot {

    /**
     * Fetch the most recent snapshot for a voucher ID.
     *
     * @param int $voucher_id Voucher ID.
     * @return array<string, mixed>|null
     */
    public static function get_for_voucher_id($voucher_id) {
        return self::get_latest_snapshot_for_voucher($voucher_id);
    }

    /**
     * Create a new immutable delivery snapshot row for a voucher.
     *
     * @param mixed $voucher Voucher ID, voucher row, or voucher-shaped payload containing an ID.
     * @param string $language_code Free-text language code.
     * @param array|object $payload Frozen snapshot payload.
     * @param int|null $created_by_user_id Optional creating user ID.
     * @return array<string, mixed>|false
     */
    public static function create_snapshot($voucher, $language_code, $payload, $created_by_user_id = null) {
        global $wpdb;

        $voucher_id = self::resolve_voucher_id($voucher);
        if ($voucher_id <= 0 || !self::voucher_exists($voucher_id)) {
            return false;
        }

        $language_code = self::sanitize_language_code($language_code);
        if ($language_code === '') {
            return false;
        }

        $prepared_payload = self::prepare_payload_for_storage($payload);
        if ($prepared_payload === null) {
            return false;
        }

        $timestamp = current_time('mysql');
        $result = $wpdb->insert(self::get_table_name(), [
            'voucher_id' => $voucher_id,
            'language_code' => $language_code,
            'payload_json' => $prepared_payload['payload_json'],
            'created_by_user_id' => self::normalize_nullable_user_id($created_by_user_id),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        if ($result === false) {
            return false;
        }

        return self::get_snapshot((int) $wpdb->insert_id);
    }

    /**
     * Fetch one snapshot row by ID.
     *
     * @param int $snapshot_id Snapshot ID.
     * @return array<string, mixed>|null
     */
    public static function get_snapshot($snapshot_id) {
        global $wpdb;

        $snapshot_id = intval($snapshot_id);
        if ($snapshot_id <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM " . self::get_table_name() . "
                 WHERE id = %d
                 LIMIT 1",
                $snapshot_id
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return self::hydrate_snapshot_row($row);
    }

    /**
     * Fetch the most recent snapshot for a voucher, optionally narrowed by language code.
     *
     * @param int $voucher_id Voucher ID.
     * @param string|null $language_code Optional free-text language code.
     * @return array<string, mixed>|null
     */
    public static function get_latest_snapshot_for_voucher($voucher_id, $language_code = null) {
        global $wpdb;

        $voucher_id = intval($voucher_id);
        if ($voucher_id <= 0) {
            return null;
        }

        $table = self::get_table_name();
        if ($language_code !== null) {
            $language_code = self::sanitize_language_code($language_code);
            if ($language_code === '') {
                return null;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                     FROM $table
                     WHERE voucher_id = %d
                       AND language_code = %s
                     ORDER BY created_at DESC, id DESC
                     LIMIT 1",
                    $voucher_id,
                    $language_code
                ),
                ARRAY_A
            );
        } else {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                     FROM $table
                     WHERE voucher_id = %d
                     ORDER BY created_at DESC, id DESC
                     LIMIT 1",
                    $voucher_id
                ),
                ARRAY_A
            );
        }

        if (!is_array($row)) {
            return null;
        }

        return self::hydrate_snapshot_row($row);
    }

    /**
     * Resolve the snapshot table name.
     *
     * @return string
     */
    private static function get_table_name() {
        global $wpdb;

        return $wpdb->prefix . 'svdp_voucher_delivery_snapshots';
    }

    /**
     * Normalize a voucher reference down to a root voucher ID.
     *
     * @param mixed $voucher Voucher reference.
     * @return int
     */
    private static function resolve_voucher_id($voucher) {
        if (is_numeric($voucher)) {
            return intval($voucher);
        }

        if (is_object($voucher)) {
            $voucher = (array) $voucher;
        }

        if (!is_array($voucher)) {
            return 0;
        }

        if (!empty($voucher['voucher_id'])) {
            return intval($voucher['voucher_id']);
        }

        if (!empty($voucher['voucherId'])) {
            return intval($voucher['voucherId']);
        }

        if (!empty($voucher['id'])) {
            return intval($voucher['id']);
        }

        return 0;
    }

    /**
     * Confirm the root voucher row exists before inserting a snapshot.
     *
     * @param int $voucher_id Voucher ID.
     * @return bool
     */
    private static function voucher_exists($voucher_id) {
        global $wpdb;

        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $voucher_id = intval($voucher_id);

        if ($voucher_id <= 0) {
            return false;
        }

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM $vouchers_table
                 WHERE id = %d
                 LIMIT 1",
                $voucher_id
            )
        );

        return !empty($existing_id);
    }

    /**
     * Normalize free-text language storage.
     *
     * @param mixed $language_code Raw language code.
     * @return string
     */
    private static function sanitize_language_code($language_code) {
        return trim(sanitize_text_field((string) $language_code));
    }

    /**
     * Normalize an optional creating user ID.
     *
     * @param mixed $created_by_user_id Raw user ID.
     * @return int|null
     */
    private static function normalize_nullable_user_id($created_by_user_id) {
        $created_by_user_id = intval($created_by_user_id);

        return $created_by_user_id > 0 ? $created_by_user_id : null;
    }

    /**
     * Encode a structured payload as JSON text for immutable storage.
     *
     * @param mixed $payload Raw payload.
     * @return array<string, mixed>|null
     */
    private static function prepare_payload_for_storage($payload) {
        if (is_object($payload)) {
            $payload = (array) $payload;
        }

        if (!is_array($payload)) {
            return null;
        }

        $payload_json = wp_json_encode($payload);
        if (!is_string($payload_json) || $payload_json === '') {
            return null;
        }

        $decoded_payload = json_decode($payload_json, true);
        if (!is_array($decoded_payload) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return [
            'payload_json' => $payload_json,
            'payload' => $decoded_payload,
        ];
    }

    /**
     * Decode the stored payload JSON and normalize scalar fields.
     *
     * @param array<string, mixed> $row Raw database row.
     * @return array<string, mixed>
     */
    private static function hydrate_snapshot_row($row) {
        $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            $payload = null;
        }

        return [
            'id' => intval($row['id'] ?? 0),
            'voucher_id' => intval($row['voucher_id'] ?? 0),
            'language_code' => (string) ($row['language_code'] ?? ''),
            'payload_json' => (string) ($row['payload_json'] ?? ''),
            'payload' => $payload,
            'created_by_user_id' => !empty($row['created_by_user_id']) ? intval($row['created_by_user_id']) : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
