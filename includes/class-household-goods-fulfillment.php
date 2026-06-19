<?php
/**
 * Shared Release C fulfillment workflow for Furniture and Household Goods.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Household_Goods_Fulfillment {

    /**
     * Check whether this voucher should use the Release C shared fulfillment workspace.
     */
    public static function voucher_uses_shared_fulfillment($voucher_id, $voucher_type = '') {
        $voucher_type = SVDP_Voucher::normalize_voucher_type($voucher_type);
        if ($voucher_type === 'household_goods') {
            return true;
        }

        if ($voucher_type !== 'furniture') {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'svdp_voucher_requested_lines';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE voucher_id = %d",
            intval($voucher_id)
        ));

        return intval($count) > 0;
    }

    /**
     * REST callback: save current fulfillment rows without finalizing.
     */
    public static function save_progress($request) {
        $context = self::get_mutation_context($request['id']);
        if (is_wp_error($context)) {
            return $context;
        }

        $params = self::get_request_data($request);
        $result = self::save_payload($context, $params, false);
        if (is_wp_error($result)) {
            return $result;
        }

        self::audit($context['voucher_id'], 'save_progress', null, $result, 'Fulfillment progress saved for voucher #' . $context['voucher_id'] . '.');

        return rest_ensure_response([
            'success' => true,
            'voucherId' => $context['voucher_id'],
            'summary' => $result['summary'],
            'workflowStatus' => $result['workflow_status'],
        ]);
    }

    /**
     * REST callback: finalize a fully resolved shared fulfillment voucher.
     */
    public static function finalize_voucher($request) {
        $context = self::get_mutation_context($request['id']);
        if (is_wp_error($context)) {
            return $context;
        }

        $params = self::get_request_data($request);
        $result = self::save_payload($context, $params, true);
        if (is_wp_error($result)) {
            return $result;
        }

        if (empty($result['summary']['ready_to_finalize'])) {
            return new WP_Error('fulfillment_unresolved', 'Resolve every requested line before finalizing this voucher.', ['status' => 400]);
        }

        $voucher = SVDP_Voucher::get_cashier_voucher($context['voucher_id']);
        if (!$voucher) {
            return new WP_Error('voucher_not_found', 'Voucher not found.', ['status' => 404]);
        }

        $receipt = SVDP_Furniture_Receipt::create_for_voucher($voucher);
        if (is_wp_error($receipt)) {
            return $receipt;
        }

        $invoice = SVDP_Invoice::create_for_furniture_voucher($voucher);
        if (is_wp_error($invoice)) {
            SVDP_Furniture_Receipt::delete_document($receipt['file_path'] ?? null);
            return $invoice;
        }

        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $furniture_meta_table = $wpdb->prefix . 'svdp_furniture_voucher_meta';
        $finalized_at = current_time('mysql');
        $actor_id = get_current_user_id() ?: null;
        $note = self::sanitize_note($params['finalizationNote'] ?? $params['finalization_note'] ?? '');
        $actual_total = round((float) $result['summary']['actual_total'], 2);

        $update = [
            'status' => 'Redeemed',
            'workflow_status' => 'completed',
            'redeemed_date' => current_time('Y-m-d'),
            'redemption_total_value' => $actual_total,
            'finalized_at' => $finalized_at,
            'finalized_by_user_id' => $actor_id,
            'finalization_note' => $note,
            'finalization_note_by_user_id' => $note !== null ? $actor_id : null,
            'finalization_note_at' => $note !== null ? $finalized_at : null,
            'receipt_file_path' => $receipt['file_path'],
        ];

        $updated = $wpdb->update($vouchers_table, $update, ['id' => $context['voucher_id']]);
        if ($updated === false || intval($updated) < 1) {
            SVDP_Furniture_Receipt::delete_document($receipt['file_path']);
            SVDP_Invoice::delete_invoice($invoice['invoice_id'] ?? 0, $invoice['file_path'] ?? null);
            return new WP_Error('voucher_finalize_failed', 'The voucher documents were generated but the voucher could not be finalized.', ['status' => 500]);
        }

        if ($context['voucher_type'] === 'furniture') {
            $wpdb->update(
                $furniture_meta_table,
                [
                    'completed_at' => $finalized_at,
                    'completed_by_user_id' => $actor_id,
                    'receipt_file_path' => $receipt['file_path'],
                    'invoice_file_path' => $invoice['file_path'],
                ],
                ['voucher_id' => $context['voucher_id']]
            );
        }

        $after = [
            'summary' => $result['summary'],
            'invoice_number' => $invoice['invoice_number'],
            'finalization_note_saved' => $note !== null,
        ];
        self::audit($context['voucher_id'], 'finalize_voucher', null, $after, 'Voucher #' . $context['voucher_id'] . ' finalized with actual total $' . number_format($actual_total, 2) . '.');

        return rest_ensure_response([
            'success' => true,
            'voucherId' => $context['voucher_id'],
            'invoiceNumber' => $invoice['invoice_number'],
            'invoiceAmount' => $invoice['amount'],
            'receiptUrl' => $receipt['url'],
            'invoiceUrl' => $invoice['url'],
        ]);
    }

    /**
     * Fetch active structured unavailable reasons.
     */
    public static function get_unavailable_reasons() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_unavailable_reasons';
        $rows = $wpdb->get_results(
            "SELECT id, reason_text
             FROM $table
             WHERE active = 1
             ORDER BY display_order ASC, reason_text ASC"
        );

        return array_map(function($row) {
            return [
                'id' => (int) $row->id,
                'reason_text' => $row->reason_text,
            ];
        }, $rows ?: []);
    }

    /**
     * Fetch requested lines, fulfillment rows, and calculated summary for one voucher.
     */
    public static function get_fulfillment_state($voucher_id) {
        global $wpdb;
        $lines_table = $wpdb->prefix . 'svdp_voucher_requested_lines';
        $entries_table = $wpdb->prefix . 'svdp_voucher_fulfillment_entries';

        $line_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM $lines_table
             WHERE voucher_id = %d
             ORDER BY sort_order_snapshot ASC, id ASC",
            intval($voucher_id)
        ));

        if (!$line_rows) {
            return [
                'lines' => [],
                'summary' => self::calculate_summary([]),
            ];
        }

        $line_ids = array_map(function($line) {
            return (int) $line->id;
        }, $line_rows);
        $placeholders = implode(', ', array_fill(0, count($line_ids), '%d'));
        $entry_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM $entries_table
             WHERE requested_line_id IN ($placeholders)
             ORDER BY id ASC",
            $line_ids
        ));

        $entries_by_line = [];
        foreach ($entry_rows ?: [] as $entry) {
            $line_id = (int) $entry->requested_line_id;
            if (!isset($entries_by_line[$line_id])) {
                $entries_by_line[$line_id] = [];
            }

            $entries_by_line[$line_id][] = [
                'id' => (int) $entry->id,
                'unit_price' => (float) $entry->unit_price,
                'fulfilled_quantity' => (int) $entry->fulfilled_quantity,
                'line_total' => (float) $entry->line_total,
            ];
        }

        $lines = array_map(function($line) use ($entries_by_line) {
            $entries = $entries_by_line[(int) $line->id] ?? [];
            $fulfilled = array_sum(array_map(function($entry) {
                return (int) $entry['fulfilled_quantity'];
            }, $entries));
            $unavailable = (int) $line->unavailable_quantity;
            $requested = (int) $line->requested_quantity;
            $subtotal = array_sum(array_map(function($entry) {
                return (float) $entry['line_total'];
            }, $entries));

            return [
                'id' => (int) $line->id,
                'voucher_id' => (int) $line->voucher_id,
                'line_type' => $line->line_type,
                'source_catalog_id' => $line->source_catalog_id !== null ? (int) $line->source_catalog_id : null,
                'requested_name' => $line->requested_name_snapshot,
                'requested_group' => $line->requested_group_snapshot,
                'requested_quantity' => $requested,
                'estimated_unit_cost' => $line->estimated_conference_partner_cost_per_unit_snapshot !== null ? (float) $line->estimated_conference_partner_cost_per_unit_snapshot : null,
                'cashier_guidance' => $line->cashier_guidance_snapshot,
                'sort_order' => (int) $line->sort_order_snapshot,
                'unavailable_quantity' => $unavailable,
                'unavailable_reason_id' => $line->unavailable_reason_id !== null ? (int) $line->unavailable_reason_id : null,
                'unavailable_reason_snapshot' => $line->unavailable_reason_snapshot,
                'resolution_status' => $line->resolution_status,
                'entries' => $entries,
                'fulfilled_quantity' => $fulfilled,
                'resolved_quantity' => $fulfilled + $unavailable,
                'remaining_quantity' => max(0, $requested - $fulfilled - $unavailable),
                'subtotal' => round($subtotal, 2),
            ];
        }, $line_rows);

        return [
            'lines' => $lines,
            'summary' => self::calculate_summary($lines),
        ];
    }

    private static function get_mutation_context($voucher_id) {
        if (!SVDP_Permissions::user_can_access_cashier() || !SVDP_Permissions::user_can_redeem_furniture_vouchers()) {
            return new WP_Error('forbidden', 'You do not have permission to fulfill vouchers.', ['status' => 403]);
        }

        $voucher = SVDP_Voucher::get_cashier_voucher(intval($voucher_id));
        if (!$voucher) {
            return new WP_Error('voucher_not_found', 'Voucher not found.', ['status' => 404]);
        }

        $voucher_type = SVDP_Voucher::normalize_voucher_type($voucher['voucher_type'] ?? '');
        if (!in_array($voucher_type, ['furniture', 'household_goods'], true)) {
            return new WP_Error('voucher_type_invalid', 'This action is only available for Furniture and Household Goods vouchers.', ['status' => 400]);
        }

        if (empty($voucher['uses_shared_fulfillment'])) {
            return new WP_Error('shared_fulfillment_missing', 'This voucher does not use the shared fulfillment workspace.', ['status' => 400]);
        }

        if (($voucher['stored_status'] ?? '') === 'Denied' || ($voucher['stored_status'] ?? '') === 'Redeemed') {
            return new WP_Error('voucher_not_mutable', 'This voucher can no longer be changed.', ['status' => 409]);
        }

        if (($voucher['cashier_status'] ?? '') === 'expired') {
            return new WP_Error('voucher_expired', 'Expired vouchers can be viewed but cannot be fulfilled or finalized through the ordinary cashier workflow.', ['status' => 409]);
        }

        $state = self::get_fulfillment_state($voucher['id']);
        if (empty($state['lines'])) {
            return new WP_Error('requested_lines_missing', 'This voucher has no requested lines to fulfill.', ['status' => 400]);
        }

        return [
            'voucher_id' => (int) $voucher['id'],
            'voucher_type' => $voucher_type,
            'voucher' => $voucher,
            'state' => $state,
        ];
    }

    private static function save_payload($context, $params, $require_resolved) {
        $submitted_lines = $params['lines'] ?? [];
        if (is_string($submitted_lines)) {
            $decoded = json_decode(wp_unslash($submitted_lines), true);
            $submitted_lines = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($submitted_lines)) {
            return new WP_Error('fulfillment_payload_invalid', 'Fulfillment rows could not be read.', ['status' => 400]);
        }

        $submitted_by_id = [];
        foreach ($submitted_lines as $line) {
            $line_id = intval($line['lineId'] ?? $line['line_id'] ?? 0);
            if ($line_id > 0) {
                $submitted_by_id[$line_id] = $line;
            }
        }

        global $wpdb;
        $lines_table = $wpdb->prefix . 'svdp_voucher_requested_lines';
        $entries_table = $wpdb->prefix . 'svdp_voucher_fulfillment_entries';
        $reason_map = self::get_reason_map();
        $actor_id = get_current_user_id() ?: null;

        foreach ($context['state']['lines'] as $existing_line) {
            $line_id = (int) $existing_line['id'];
            $line_payload = $submitted_by_id[$line_id] ?? [];
            $requested = (int) $existing_line['requested_quantity'];
            $entries_payload = $line_payload['entries'] ?? [];
            if (!is_array($entries_payload)) {
                $entries_payload = [];
            }

            $entries = [];
            $fulfilled_total = 0;
            foreach ($entries_payload as $entry_payload) {
                $raw_price = $entry_payload['unitPrice'] ?? $entry_payload['unit_price'] ?? '';
                $raw_quantity = $entry_payload['fulfilledQuantity'] ?? $entry_payload['fulfilled_quantity'] ?? 0;
                $quantity = self::sanitize_quantity($raw_quantity, 'Fulfilled quantity');
                if (is_wp_error($quantity)) {
                    return $quantity;
                }

                $price_blank = trim((string) $raw_price) === '';
                if ($quantity === 0 && $price_blank) {
                    continue;
                }

                $price = self::sanitize_price($raw_price);
                if (is_wp_error($price)) {
                    return $price;
                }

                if ($quantity <= 0) {
                    return new WP_Error('fulfilled_quantity_required', 'Fulfilled quantity must be greater than zero when a price row is entered.', ['status' => 400]);
                }

                $line_total = round($price * $quantity, 2);
                $fulfilled_total += $quantity;
                $entries[] = [
                    'requested_line_id' => $line_id,
                    'unit_price' => $price,
                    'fulfilled_quantity' => $quantity,
                    'line_total' => $line_total,
                    'entered_by_user_id' => $actor_id,
                ];
            }

            $unavailable = self::sanitize_quantity($line_payload['unavailableQuantity'] ?? $line_payload['unavailable_quantity'] ?? 0, 'Unavailable quantity');
            if (is_wp_error($unavailable)) {
                return $unavailable;
            }

            if ($fulfilled_total + $unavailable > $requested) {
                return new WP_Error('fulfillment_quantity_exceeded', 'Fulfilled plus unavailable quantity cannot exceed the requested quantity.', ['status' => 400]);
            }

            $reason_id = null;
            $reason_snapshot = null;
            if ($unavailable > 0) {
                $reason_id = intval($line_payload['unavailableReasonId'] ?? $line_payload['unavailable_reason_id'] ?? 0);
                if ($reason_id <= 0 || !isset($reason_map[$reason_id])) {
                    return new WP_Error('unavailable_reason_required', 'Select a structured unavailable reason for every unavailable quantity.', ['status' => 400]);
                }
                $reason_snapshot = $reason_map[$reason_id];
            }

            if ($require_resolved && ($fulfilled_total + $unavailable) !== $requested) {
                return new WP_Error('fulfillment_line_unresolved', 'Resolve every requested line before finalizing this voucher.', ['status' => 400]);
            }

            $resolution_status = self::resolve_status($requested, $fulfilled_total, $unavailable);

            $wpdb->delete($entries_table, ['requested_line_id' => $line_id]);
            foreach ($entries as $entry) {
                $wpdb->insert($entries_table, $entry);
            }

            $wpdb->update(
                $lines_table,
                [
                    'unavailable_quantity' => $unavailable,
                    'unavailable_reason_id' => $reason_id,
                    'unavailable_reason_snapshot' => $reason_snapshot,
                    'resolution_status' => $resolution_status,
                ],
                ['id' => $line_id]
            );
        }

        $state = self::get_fulfillment_state($context['voucher_id']);
        $workflow_status = $state['summary']['ready_to_finalize'] ? 'ready_for_completion' : 'in_progress';
        $wpdb->update(
            $wpdb->prefix . 'svdp_vouchers',
            ['workflow_status' => $workflow_status],
            ['id' => $context['voucher_id']]
        );

        return [
            'lines' => $state['lines'],
            'summary' => $state['summary'],
            'workflow_status' => $workflow_status,
        ];
    }

    private static function calculate_summary($lines) {
        $requested = 0;
        $fulfilled = 0;
        $unavailable = 0;
        $actual_total = 0.0;
        $estimated_total = 0.0;
        $has_estimate = false;

        foreach ($lines as $line) {
            $requested += (int) $line['requested_quantity'];
            $fulfilled += (int) $line['fulfilled_quantity'];
            $unavailable += (int) $line['unavailable_quantity'];
            $actual_total += (float) $line['subtotal'];
            if ($line['estimated_unit_cost'] !== null) {
                $has_estimate = true;
                $estimated_total += (float) $line['estimated_unit_cost'] * (int) $line['requested_quantity'];
            }
        }

        $resolved = $fulfilled + $unavailable;

        return [
            'requested_units' => $requested,
            'fulfilled_units' => $fulfilled,
            'unavailable_units' => $unavailable,
            'resolved_units' => $resolved,
            'actual_total' => round($actual_total, 2),
            'estimated_total' => $has_estimate ? round($estimated_total, 2) : null,
            'ready_to_finalize' => $requested > 0 && $resolved === $requested,
        ];
    }

    private static function get_reason_map() {
        $map = [];
        foreach (self::get_unavailable_reasons() as $reason) {
            $map[(int) $reason['id']] = $reason['reason_text'];
        }
        return $map;
    }

    private static function sanitize_quantity($value, $label) {
        if ($value === '' || $value === null) {
            return 0;
        }

        if (!is_numeric($value) || (int) $value != (float) $value) {
            return new WP_Error('quantity_invalid', $label . ' must be a non-negative whole number.', ['status' => 400]);
        }

        $quantity = (int) $value;
        if ($quantity < 0) {
            return new WP_Error('quantity_negative', $label . ' cannot be negative.', ['status' => 400]);
        }

        return $quantity;
    }

    private static function sanitize_price($value) {
        if (trim((string) $value) === '' || !is_numeric($value)) {
            return new WP_Error('unit_price_invalid', 'Price Each must be a valid amount greater than zero.', ['status' => 400]);
        }

        $price = round((float) $value, 2);
        if ($price <= 0) {
            return new WP_Error('unit_price_invalid', 'Price Each must be greater than zero.', ['status' => 400]);
        }

        return $price;
    }

    private static function sanitize_note($value) {
        $note = sanitize_textarea_field((string) $value);
        return $note === '' ? null : $note;
    }

    private static function resolve_status($requested, $fulfilled, $unavailable) {
        if (($fulfilled + $unavailable) < $requested) {
            return ($fulfilled + $unavailable) > 0 ? 'partially_fulfilled' : 'requested';
        }

        if ($unavailable >= $requested && $fulfilled === 0) {
            return 'unavailable';
        }

        return 'resolved';
    }

    private static function audit($voucher_id, $event_type, $before, $after, $summary) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'svdp_voucher_fulfillment_audit', [
            'voucher_id' => intval($voucher_id),
            'event_type' => sanitize_key($event_type),
            'before_value' => $before === null ? null : wp_json_encode($before),
            'after_value' => $after === null ? null : wp_json_encode($after),
            'actor_user_id' => get_current_user_id() ?: null,
            'human_summary' => sanitize_text_field($summary),
            'created_at' => current_time('mysql'),
        ]);
    }

    private static function get_request_data($request) {
        $json_params = $request->get_json_params();
        if (!is_array($json_params)) {
            $json_params = [];
        }

        return array_merge($request->get_params(), $json_params);
    }
}
