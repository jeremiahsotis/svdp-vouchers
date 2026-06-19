<?php
/**
 * Release C request-group creation service.
 */
class SVDP_Voucher_Request_Group {

    /**
     * Create one request group with one to three child voucher rows atomically.
     *
     * This backend service is intentionally not wired to the public builder in C1.
     *
     * @param array|WP_REST_Request $request Request payload or REST-style request.
     * @return array|WP_Error
     */
    public static function create($request) {
        $params = self::get_request_data($request);
        $child_types = self::extract_child_voucher_types($params);

        if (is_wp_error($child_types)) {
            return $child_types;
        }

        $conference = sanitize_text_field($params['conference'] ?? '');
        $conference_obj = SVDP_Conference::get_by_slug($conference);

        if (!$conference_obj) {
            global $wpdb;
            $conference_obj = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}svdp_conferences WHERE name = %s OR id = %d",
                $conference,
                intval($conference)
            ));
        }

        if (!$conference_obj) {
            return new WP_Error('invalid_conference', 'Conference not found.', ['status' => 400]);
        }

        $identity = [
            'first_name' => sanitize_text_field($params['firstName'] ?? $params['first_name'] ?? ''),
            'last_name' => sanitize_text_field($params['lastName'] ?? $params['last_name'] ?? ''),
            'dob' => sanitize_text_field($params['dob'] ?? ''),
            'adults' => intval($params['adults'] ?? 0),
            'children' => intval($params['children'] ?? 0),
            'requestor_name' => sanitize_text_field($params['requestorName'] ?? $params['vincentianName'] ?? $params['requestor_name'] ?? ''),
            'requestor_email' => sanitize_email($params['requestorEmail'] ?? $params['vincentianEmail'] ?? $params['requestor_email'] ?? ''),
            'created_by' => sanitize_text_field($params['createdBy'] ?? $params['created_by'] ?? ($conference_obj->is_emergency ? 'Cashier' : 'Vincentian')),
        ];

        if ($identity['first_name'] === '' || $identity['last_name'] === '' || $identity['dob'] === '') {
            return new WP_Error('missing_household_identity', 'First name, last name, and date of birth are required.', ['status' => 400]);
        }

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $group_id = null;
        $voucher_ids = [];

        try {
            $group_result = self::insert_request_group($conference_obj, $identity);
            if (is_wp_error($group_result)) {
                throw new Exception($group_result->get_error_message());
            }

            $group_id = $group_result;

            foreach ($child_types as $voucher_type) {
                $voucher_result = self::insert_child_voucher($group_id, $conference_obj, $identity, $voucher_type);
                if (is_wp_error($voucher_result)) {
                    throw new Exception($voucher_result->get_error_message());
                }

                $voucher_ids[$voucher_type] = $voucher_result;
            }

            $delivery_result = self::insert_group_delivery_snapshot($group_id, $child_types, $params);
            if (is_wp_error($delivery_result)) {
                throw new Exception($delivery_result->get_error_message());
            }

            $wpdb->query('COMMIT');
        } catch (Exception $exception) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('request_group_create_failed', $exception->getMessage(), ['status' => 500]);
        }

        return [
            'success' => true,
            'request_group_id' => $group_id,
            'voucher_ids' => $voucher_ids,
            'selected_voucher_types' => $child_types,
            'delivery_eligible_voucher_types' => SVDP_Voucher_Type_Settings::get_delivery_eligible_types($child_types),
        ];
    }

    /**
     * Insert the shared request snapshot.
     */
    private static function insert_request_group($conference_obj, $identity) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_request_groups';

        $result = $wpdb->insert($table, [
            'conference_id' => (int) $conference_obj->id,
            'first_name' => $identity['first_name'],
            'last_name' => $identity['last_name'],
            'dob' => $identity['dob'],
            'adults' => $identity['adults'],
            'children' => $identity['children'],
            'requestor_name' => $identity['requestor_name'] !== '' ? $identity['requestor_name'] : null,
            'requestor_email' => $identity['requestor_email'] !== '' ? $identity['requestor_email'] : null,
            'created_by' => $identity['created_by'],
            'submitted_at' => current_time('mysql'),
        ]);

        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create voucher request group.');
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Insert a child voucher root row linked to the request group.
     */
    private static function insert_child_voucher($group_id, $conference_obj, $identity, $voucher_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_vouchers';

        $household_size = $identity['adults'] + $identity['children'];
        $voucher_value = 0.0;
        $voucher_items_count = null;

        if ($voucher_type === 'clothing') {
            $voucher_value = $household_size * (!empty($conference_obj->is_emergency) ? 10 : 20);
            $items_per_person = !empty($conference_obj->is_emergency)
                ? ($conference_obj->emergency_items_per_person ?? 3)
                : ($conference_obj->regular_items_per_person ?? 7);
            $voucher_items_count = $household_size * intval($items_per_person);
        }

        $result = $wpdb->insert($table, [
            'request_group_id' => $group_id,
            'first_name' => $identity['first_name'],
            'last_name' => $identity['last_name'],
            'dob' => $identity['dob'],
            'adults' => $identity['adults'],
            'children' => $identity['children'],
            'conference_id' => (int) $conference_obj->id,
            'vincentian_name' => $identity['requestor_name'] !== '' ? $identity['requestor_name'] : null,
            'vincentian_email' => $identity['requestor_email'] !== '' ? $identity['requestor_email'] : null,
            'created_by' => $identity['created_by'],
            'voucher_created_date' => current_time('Y-m-d'),
            'voucher_value' => $voucher_value,
            'voucher_type' => $voucher_type,
            'voucher_items_count' => $voucher_items_count,
        ]);

        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create child voucher.');
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Insert the single group-level delivery snapshot.
     */
    private static function insert_group_delivery_snapshot($group_id, $selected_types, $params) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_request_group_delivery';
        $eligible_types = SVDP_Voucher_Type_Settings::get_delivery_eligible_types($selected_types);
        $delivery_requested = self::is_truthy($params['deliveryRequested'] ?? $params['deliveryRequired'] ?? $params['delivery_requested'] ?? false);

        if ($delivery_requested && empty($eligible_types)) {
            return new WP_Error('delivery_not_available', 'Delivery is not available for the selected voucher types.', ['status' => 400]);
        }

        $address = self::sanitize_delivery_address($params['deliveryAddress'] ?? $params['delivery_address'] ?? []);
        if ($delivery_requested && ($address['line_1'] === '' || $address['city'] === '' || $address['state'] === '' || $address['zip'] === '')) {
            return new WP_Error('delivery_address_required', 'Delivery address line 1, city, state, and ZIP code are required when delivery is selected.', ['status' => 400]);
        }

        $verification = self::sanitize_verification($params);

        $result = $wpdb->insert($table, [
            'request_group_id' => $group_id,
            'delivery_requested' => $delivery_requested ? 1 : 0,
            'delivery_fee_snapshot' => $delivery_requested ? SVDP_Voucher_Type_Settings::get_delivery_fee() : 0.00,
            'eligible_voucher_types_snapshot' => wp_json_encode(array_values($eligible_types)),
            'selected_voucher_types_snapshot' => wp_json_encode(array_values($selected_types)),
            'address_line_1' => $delivery_requested ? $address['line_1'] : null,
            'address_line_2' => $delivery_requested && $address['line_2'] !== '' ? $address['line_2'] : null,
            'city' => $delivery_requested ? $address['city'] : null,
            'state' => $delivery_requested ? $address['state'] : null,
            'zip' => $delivery_requested ? $address['zip'] : null,
            'lat' => $delivery_requested ? $verification['lat'] : null,
            'lng' => $delivery_requested ? $verification['lng'] : null,
            'verified' => $delivery_requested ? $verification['verified'] : 0,
            'verification_source' => $delivery_requested ? $verification['source'] : null,
            'verification_confidence' => $delivery_requested ? $verification['confidence'] : null,
            'normalized_address' => $delivery_requested ? $verification['normalized_address'] : null,
        ]);

        if ($result === false) {
            return new WP_Error('database_error', 'Failed to save request-group delivery snapshot.');
        }

        return true;
    }

    /**
     * Extract one to three unique child voucher types from the request payload.
     */
    private static function extract_child_voucher_types($params) {
        $raw_types = $params['voucherTypes'] ?? $params['voucher_types'] ?? null;

        if ($raw_types === null && isset($params['childVouchers'])) {
            $raw_types = $params['childVouchers'];
        }

        if ($raw_types === null && isset($params['child_vouchers'])) {
            $raw_types = $params['child_vouchers'];
        }

        if ($raw_types === null && isset($params['voucherType'])) {
            $raw_types = [$params['voucherType']];
        }

        $types = SVDP_Voucher_Type_Settings::normalize_voucher_types($raw_types);

        if (count($types) < 1 || count($types) > 3) {
            return new WP_Error('invalid_voucher_type_count', 'A request group must include one, two, or three voucher types.', ['status' => 400]);
        }

        return $types;
    }

    /**
     * Merge JSON and form parameters when a WP_REST_Request is passed.
     */
    private static function get_request_data($request) {
        if (is_array($request)) {
            return $request;
        }

        if (is_object($request) && method_exists($request, 'get_json_params')) {
            $json_params = $request->get_json_params();
            if (!is_array($json_params)) {
                $json_params = [];
            }

            return array_merge($request->get_params(), $json_params);
        }

        return [];
    }

    /**
     * Sanitize delivery address fields.
     */
    private static function sanitize_delivery_address($raw_address) {
        if (is_string($raw_address)) {
            $decoded = json_decode($raw_address, true);
            $raw_address = is_array($decoded) ? $decoded : [];
        } elseif (is_object($raw_address)) {
            $raw_address = (array) $raw_address;
        } elseif (!is_array($raw_address)) {
            $raw_address = [];
        }

        return [
            'line_1' => sanitize_text_field($raw_address['line1'] ?? $raw_address['line_1'] ?? ''),
            'line_2' => sanitize_text_field($raw_address['line2'] ?? $raw_address['line_2'] ?? ''),
            'city' => sanitize_text_field($raw_address['city'] ?? ''),
            'state' => sanitize_text_field($raw_address['state'] ?? ''),
            'zip' => sanitize_text_field($raw_address['zip'] ?? ''),
        ];
    }

    /**
     * Sanitize optional address verification snapshot fields.
     */
    private static function sanitize_verification($params) {
        $lat = $params['deliveryLat'] ?? $params['delivery_lat'] ?? null;
        $lng = $params['deliveryLng'] ?? $params['delivery_lng'] ?? null;
        $lat = is_numeric($lat) ? (float) $lat : null;
        $lng = is_numeric($lng) ? (float) $lng : null;

        if ($lat !== null && ($lat < -90 || $lat > 90)) {
            $lat = null;
        }

        if ($lng !== null && ($lng < -180 || $lng > 180)) {
            $lng = null;
        }

        $verified = self::is_truthy($params['deliveryVerified'] ?? $params['delivery_verified'] ?? false);
        if ($lat === null || $lng === null) {
            $verified = false;
        }

        $source = sanitize_text_field($params['deliveryVerificationSource'] ?? $params['delivery_verification_source'] ?? '');
        $confidence = $params['deliveryVerificationConfidence'] ?? $params['delivery_verification_confidence'] ?? null;
        $confidence = is_numeric($confidence) ? max(0.0, min(1.0, round((float) $confidence, 4))) : null;
        $normalized = sanitize_text_field($params['deliveryNormalized'] ?? $params['deliveryNormalizedAddress'] ?? $params['delivery_normalized_address'] ?? '');

        return [
            'lat' => $lat,
            'lng' => $lng,
            'verified' => $verified ? 1 : 0,
            'source' => $verified && $source !== '' ? $source : null,
            'confidence' => $verified ? $confidence : null,
            'normalized_address' => $normalized !== '' ? substr($normalized, 0, 500) : null,
        ];
    }

    /**
     * Normalize boolean-ish request values.
     */
    private static function is_truthy($value) {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
