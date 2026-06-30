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

        $availability_result = self::validate_selected_types($conference_obj, $child_types);
        if (is_wp_error($availability_result)) {
            return $availability_result;
        }

        $duplicate_result = self::validate_no_recent_duplicates($conference, $identity, $child_types);
        if (is_wp_error($duplicate_result)) {
            return $duplicate_result;
        }

        $line_snapshots = self::build_requested_line_snapshots($child_types, $params);
        if (is_wp_error($line_snapshots)) {
            return $line_snapshots;
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

                if (isset($line_snapshots[$voucher_type])) {
                    $lines_result = self::insert_requested_lines($voucher_result, $voucher_type, $line_snapshots[$voucher_type]);
                    if (is_wp_error($lines_result)) {
                        throw new Exception($lines_result->get_error_message());
                    }
                }
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
            'delivery_requested' => self::is_truthy($params['deliveryRequested'] ?? $params['deliveryRequired'] ?? $params['delivery_requested'] ?? false),
            'delivery_fee' => self::is_truthy($params['deliveryRequested'] ?? $params['deliveryRequired'] ?? $params['delivery_requested'] ?? false)
                ? SVDP_Voucher_Type_Settings::get_delivery_fee()
                : 0.0,
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
     * Validate global and organization voucher-type availability.
     */
    private static function validate_selected_types($conference_obj, $child_types) {
        $global_types = SVDP_Settings::get_public_request_voucher_types();
        $allowed_types = SVDP_Settings::get_conference_allowed_request_voucher_types($conference_obj);

        foreach ($child_types as $voucher_type) {
            if (!in_array($voucher_type, $global_types, true)) {
                return new WP_Error('voucher_type_disabled', self::get_voucher_type_label($voucher_type) . ' is not enabled for new requests.', ['status' => 400]);
            }

            if (!in_array($voucher_type, $allowed_types, true)) {
                return new WP_Error('voucher_type_not_allowed', 'This organization is not configured to request ' . self::get_voucher_type_label($voucher_type) . ' vouchers.', ['status' => 400]);
            }
        }

        return true;
    }

    /**
     * Preserve existing 90-day duplicate rules for each selected child voucher.
     */
    private static function validate_no_recent_duplicates($conference, $identity, $child_types) {
        foreach ($child_types as $voucher_type) {
            $duplicate = SVDP_Voucher::check_duplicate([
                'firstName' => $identity['first_name'],
                'lastName' => $identity['last_name'],
                'dob' => $identity['dob'],
                'conference' => $conference,
                'voucherType' => $voucher_type,
                'createdBy' => $identity['created_by'],
            ]);

            if (is_array($duplicate) && !empty($duplicate['found'])) {
                return new WP_Error(
                    'duplicate_voucher',
                    'A recent ' . self::get_voucher_type_label($voucher_type) . ' voucher already exists for this household.',
                    ['status' => 409, 'duplicate' => $duplicate, 'voucher_type' => $voucher_type]
                );
            }
        }

        return true;
    }

    /**
     * Build requested-line snapshots for selected fulfillment-backed voucher types.
     */
    private static function build_requested_line_snapshots($child_types, $params) {
        $snapshots = [];

        if (in_array('furniture', $child_types, true)) {
            $furniture = self::build_furniture_line_snapshots($params['furnitureItems'] ?? $params['items'] ?? []);
            if (is_wp_error($furniture)) {
                return $furniture;
            }
            $snapshots['furniture'] = $furniture;
        }

        if (in_array('household_goods', $child_types, true)) {
            $requested_categories = self::normalize_household_goods_quantities($params['householdGoodsCategories'] ?? $params['household_goods_categories'] ?? []);
            $household_goods = SVDP_Household_Goods_Catalog::build_request_line_snapshots($requested_categories);
            if (is_wp_error($household_goods)) {
                return $household_goods;
            }
            if (empty($household_goods)) {
                return new WP_Error('household_goods_categories_required', 'Select at least one Household Goods category.', ['status' => 400]);
            }
            $snapshots['household_goods'] = $household_goods;
        }

        return $snapshots;
    }

    /**
     * Convert Furniture catalog selections into shared requested-line snapshots.
     */
    private static function build_furniture_line_snapshots($raw_items) {
        $requested_items = self::normalize_catalog_quantities($raw_items, 'catalogItemId');
        if (empty($requested_items)) {
            return new WP_Error('furniture_items_required', 'Select at least one furniture item.', ['status' => 400]);
        }

        $catalog_rows = SVDP_Furniture_Catalog::get_items_by_ids(array_keys($requested_items), true);
        $catalog_map = [];
        foreach ($catalog_rows as $row) {
            $catalog_map[(int) $row->id] = $row;
        }

        if (count($catalog_map) !== count($requested_items)) {
            return new WP_Error('invalid_furniture_items', 'One or more selected furniture items are no longer available. Please refresh and try again.', ['status' => 400]);
        }

        $category_labels = SVDP_Furniture_Catalog::get_categories();
        $snapshots = [];

        foreach ($catalog_rows as $row) {
            $quantity = (int) ($requested_items[(int) $row->id] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $snapshots[] = [
                'line_type' => 'furniture',
                'source_catalog_id' => (int) $row->id,
                'requested_quantity' => $quantity,
                'requested_name_snapshot' => $row->name,
                'requested_group_snapshot' => $category_labels[$row->category] ?? $row->category,
                'estimated_conference_partner_cost_per_unit_snapshot' => self::calculate_furniture_estimated_unit_cost($row),
                'cashier_guidance_snapshot' => null,
                'sort_order_snapshot' => (int) $row->sort_order,
            ];
        }

        return $snapshots;
    }

    /**
     * Insert requested lines and update child voucher aggregate snapshots.
     */
    private static function insert_requested_lines($voucher_id, $voucher_type, $snapshots) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_requested_lines';
        $total_quantity = 0;
        $estimated_total = 0.0;

        foreach ($snapshots as $snapshot) {
            $quantity = max(1, (int) ($snapshot['requested_quantity'] ?? 1));
            $unit_estimate = isset($snapshot['estimated_conference_partner_cost_per_unit_snapshot'])
                ? (float) $snapshot['estimated_conference_partner_cost_per_unit_snapshot']
                : 0.0;

            $result = $wpdb->insert($table, [
                'voucher_id' => (int) $voucher_id,
                'line_type' => $voucher_type,
                'source_catalog_id' => isset($snapshot['source_catalog_id']) ? (int) $snapshot['source_catalog_id'] : null,
                'requested_name_snapshot' => sanitize_text_field($snapshot['requested_name_snapshot'] ?? ''),
                'requested_group_snapshot' => isset($snapshot['requested_group_snapshot']) ? sanitize_text_field($snapshot['requested_group_snapshot']) : null,
                'requested_quantity' => $quantity,
                'estimated_conference_partner_cost_per_unit_snapshot' => $unit_estimate > 0 ? number_format($unit_estimate, 2, '.', '') : null,
                'cashier_guidance_snapshot' => isset($snapshot['cashier_guidance_snapshot']) ? sanitize_textarea_field($snapshot['cashier_guidance_snapshot']) : null,
                'sort_order_snapshot' => (int) ($snapshot['sort_order_snapshot'] ?? 0),
                'resolution_status' => 'requested',
            ]);

            if ($result === false) {
                return new WP_Error('requested_line_insert_failed', 'Failed to save requested ' . self::get_voucher_type_label($voucher_type) . ' lines.', ['status' => 500]);
            }

            $total_quantity += $quantity;
            $estimated_total += $unit_estimate * $quantity;
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'svdp_vouchers',
            [
                'voucher_items_count' => $total_quantity,
                'voucher_value' => round($estimated_total, 2),
            ],
            ['id' => (int) $voucher_id]
        );

        if ($updated === false) {
            return new WP_Error('voucher_snapshot_update_failed', 'Failed to update child voucher request totals.', ['status' => 500]);
        }

        return true;
    }

    /**
     * Normalize a Furniture or Household Goods quantity payload.
     */
    private static function normalize_catalog_quantities($raw_items, $id_key) {
        if (is_string($raw_items)) {
            $decoded = json_decode($raw_items, true);
            $raw_items = is_array($decoded) ? $decoded : [];
        } elseif (is_object($raw_items)) {
            $raw_items = (array) $raw_items;
        }

        if (!is_array($raw_items)) {
            return [];
        }

        $items = [];
        foreach ($raw_items as $key => $raw_item) {
            if (is_object($raw_item)) {
                $raw_item = (array) $raw_item;
            }

            if (is_array($raw_item)) {
                $id = (int) ($raw_item[$id_key] ?? $raw_item['catalog_item_id'] ?? $raw_item['categoryId'] ?? $raw_item['category_id'] ?? 0);
                $quantity = (int) ($raw_item['quantity'] ?? 0);
            } else {
                $id = (int) $key;
                $quantity = (int) $raw_item;
            }

            if ($id <= 0 || $quantity <= 0) {
                continue;
            }

            if (!isset($items[$id])) {
                $items[$id] = 0;
            }
            $items[$id] += $quantity;
        }

        return $items;
    }

    /**
     * Normalize Household Goods selections into the C2 snapshot helper shape.
     */
    private static function normalize_household_goods_quantities($raw_categories) {
        return self::normalize_catalog_quantities($raw_categories, 'categoryId');
    }

    /**
     * Estimate the Conference / Partner unit cost for a furniture catalog row.
     */
    private static function calculate_furniture_estimated_unit_cost($row) {
        $price = $row->pricing_type === 'fixed'
            ? (float) $row->price_fixed
            : (float) $row->price_max;
        $discount_type = isset($row->discount_type) && $row->discount_type === 'fixed' ? 'fixed' : 'percent';
        $discount_value = isset($row->discount_value) ? max(0.0, (float) $row->discount_value) : 50.0;

        if ($discount_type === 'fixed') {
            return round(min($discount_value, $price), 2);
        }

        return round($price * (min($discount_value, 100.0) / 100), 2);
    }

    /**
     * Human labels for request-group responses.
     */
    private static function get_voucher_type_label($voucher_type) {
        $labels = [
            'clothing' => 'Clothing',
            'furniture' => 'Furniture',
            'household_goods' => 'Household Goods',
        ];

        return $labels[$voucher_type] ?? ucfirst(str_replace('_', ' ', $voucher_type));
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
