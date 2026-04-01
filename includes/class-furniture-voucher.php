<?php
/**
 * Furniture voucher fulfillment workflows.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Furniture_Voucher {

    /**
     * Fetch enriched item rows for one furniture voucher cashier detail view.
     *
     * @param int $voucher_id Root voucher ID.
     * @return array
     */
    public static function get_voucher_items($voucher_id) {
        global $wpdb;

        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM $voucher_items_table
             WHERE voucher_id = %d
             ORDER BY requested_sort_order_snapshot ASC, id ASC",
            intval($voucher_id)
        ));

        if (!$rows) {
            return [];
        }

        $categories = SVDP_Furniture_Catalog::get_categories();
        $item_ids = array_values(array_map(function($row) {
            return intval($row->id);
        }, $rows));
        $reason_ids = array_values(array_filter(array_map(function($row) {
            return intval($row->cancellation_reason_id);
        }, $rows)));
        $substitute_catalog_item_ids = array_values(array_filter(array_map(function($row) {
            return intval($row->substitute_catalog_item_id);
        }, $rows)));

        $photo_map = self::get_photo_map($item_ids);
        $reason_map = self::get_reason_map($reason_ids);
        $catalog_name_map = self::get_catalog_name_map($substitute_catalog_item_ids);

        return array_map(function($row) use ($categories, $photo_map, $reason_map, $catalog_name_map) {
            $price_min = $row->requested_price_min_snapshot !== null ? (float) $row->requested_price_min_snapshot : null;
            $price_max = $row->requested_price_max_snapshot !== null ? (float) $row->requested_price_max_snapshot : null;
            $price_fixed = $row->requested_price_fixed_snapshot !== null ? (float) $row->requested_price_fixed_snapshot : null;
            $photo_rows = $photo_map[(int) $row->id] ?? [];
            $substitute_name = sanitize_text_field((string) ($row->substitute_item_name ?? ''));

            if ($substitute_name === '' && !empty($row->substitute_catalog_item_id)) {
                $substitute_name = $catalog_name_map[(int) $row->substitute_catalog_item_id] ?? '';
            }

            $substitution_type = sanitize_key($row->substitution_type ?? 'none');
            $has_substitution = in_array($substitution_type, ['catalog', 'free_text'], true) && $substitute_name !== '';

            return [
                'id' => (int) $row->id,
                'requested_item_name' => $row->requested_item_name_snapshot,
                'requested_category' => $row->requested_category_snapshot,
                'requested_category_label' => $categories[$row->requested_category_snapshot] ?? $row->requested_category_snapshot,
                'requested_pricing_type' => $row->requested_pricing_type_snapshot,
                'requested_price_min' => $price_min,
                'requested_price_max' => $price_max,
                'requested_price_fixed' => $price_fixed,
                'requested_price_display' => self::format_money_range(
                    $price_min,
                    $price_max,
                    $price_fixed,
                    $row->requested_pricing_type_snapshot
                ),
                'requested_sort_order' => (int) $row->requested_sort_order_snapshot,
                'status' => sanitize_key($row->status),
                'substitution_type' => $substitution_type,
                'substitute_catalog_item_id' => !empty($row->substitute_catalog_item_id) ? (int) $row->substitute_catalog_item_id : null,
                'substitute_item_name' => $substitute_name,
                'has_substitution' => $has_substitution,
                'substitution_label' => self::get_substitution_label($substitution_type),
                'actual_price' => $row->actual_price !== null ? (float) $row->actual_price : null,
                'actual_price_display' => $row->actual_price !== null ? '$' . number_format((float) $row->actual_price, 2) : '',
                'completion_notes' => $row->completion_notes,
                'cancellation_reason_id' => !empty($row->cancellation_reason_id) ? (int) $row->cancellation_reason_id : null,
                'cancellation_reason_label' => !empty($row->cancellation_reason_id)
                    ? ($reason_map[(int) $row->cancellation_reason_id] ?? '')
                    : '',
                'cancellation_notes' => $row->cancellation_notes,
                'completed_at' => $row->completed_at,
                'photo_count' => count($photo_rows),
                'photos' => $photo_rows,
            ];
        }, $rows);
    }

    /**
     * Attach one normalized photo to a requested furniture item.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function upload_item_photo($request) {
        $context = self::get_mutation_context($request['id'], $request['item_id']);
        if (is_wp_error($context)) {
            return $context;
        }

        if ($context['item']->status !== 'requested') {
            return new WP_Error('item_not_mutable', 'Only requested items can receive new photos.', ['status' => 409]);
        }

        $files = $request->get_file_params();
        $file = $files['photo'] ?? ($_FILES['photo'] ?? null);
        $stored_photo = SVDP_Furniture_Photo_Storage::store_uploaded_photo($context['voucher_id'], $context['item_id'], $file);
        if (is_wp_error($stored_photo)) {
            return $stored_photo;
        }

        global $wpdb;
        $photos_table = $wpdb->prefix . 'svdp_voucher_item_photos';
        $next_sort_order = self::get_next_photo_sort_order($context['item_id']);

        $inserted = $wpdb->insert($photos_table, [
            'voucher_item_id' => $context['item_id'],
            'file_path' => $stored_photo['file_path'],
            'file_name' => $stored_photo['file_name'],
            'mime_type' => $stored_photo['mime_type'],
            'file_size' => intval($stored_photo['file_size']),
            'image_width' => intval($stored_photo['image_width']),
            'image_height' => intval($stored_photo['image_height']),
            'sort_order' => $next_sort_order,
            'uploaded_by_user_id' => get_current_user_id(),
        ]);

        if ($inserted === false) {
            SVDP_Furniture_Photo_Storage::delete_relative_path($stored_photo['file_path']);
            return new WP_Error('photo_insert_failed', 'The photo upload succeeded but could not be attached to the item.', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'itemId' => $context['item_id'],
            'photoCount' => self::get_item_photo_count($context['item_id']),
        ]);
    }

    /**
     * Record a substitution on the existing voucher item row.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function substitute_item($request) {
        $context = self::get_mutation_context($request['id'], $request['item_id']);
        if (is_wp_error($context)) {
            return $context;
        }

        if ($context['item']->status !== 'requested') {
            return new WP_Error('item_not_mutable', 'Only requested items can be substituted.', ['status' => 409]);
        }

        $params = self::get_request_data($request);
        $substitution_type = sanitize_key($params['substitutionType'] ?? $params['substitution_type'] ?? '');
        $substitute_catalog_item_id = null;
        $substitute_item_name = null;

        if ($substitution_type === 'catalog') {
            $substitute_catalog_item_id = intval($params['substituteCatalogItemId'] ?? $params['substitute_catalog_item_id'] ?? 0);
            if ($substitute_catalog_item_id <= 0) {
                return new WP_Error('substitute_catalog_required', 'Choose a catalog item to use as the substitute.', ['status' => 400]);
            }

            $catalog_item = SVDP_Furniture_Catalog::get_by_id($substitute_catalog_item_id);
            if (!$catalog_item || empty($catalog_item->active)) {
                return new WP_Error('substitute_catalog_invalid', 'The selected substitute catalog item is not available.', ['status' => 400]);
            }

            $substitute_item_name = sanitize_text_field($catalog_item->name);
        } elseif ($substitution_type === 'free_text') {
            $substitute_item_name = sanitize_text_field($params['substituteItemName'] ?? $params['substitute_item_name'] ?? '');
            if ($substitute_item_name === '') {
                return new WP_Error('substitute_name_required', 'Enter a substitute item name.', ['status' => 400]);
            }
        } else {
            return new WP_Error('substitution_type_invalid', 'Choose either a catalog substitute or a free-text substitute.', ['status' => 400]);
        }

        global $wpdb;
        $items_table = $wpdb->prefix . 'svdp_voucher_items';
        $updated = $wpdb->update(
            $items_table,
            [
                'substitution_type' => $substitution_type,
                'substitute_catalog_item_id' => $substitute_catalog_item_id,
                'substitute_item_name' => $substitute_item_name,
                'cancellation_reason_id' => null,
                'cancellation_notes' => null,
            ],
            ['id' => $context['item_id']]
        );

        if ($updated === false) {
            return new WP_Error('substitute_update_failed', 'The substitute item could not be saved.', ['status' => 500]);
        }

        $workflow_status = self::sync_voucher_workflow_status($context['voucher_id']);

        return rest_ensure_response([
            'success' => true,
            'itemId' => $context['item_id'],
            'workflowStatus' => $workflow_status,
        ]);
    }

    /**
     * Mark one requested furniture item completed.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function complete_item($request) {
        $context = self::get_mutation_context($request['id'], $request['item_id']);
        if (is_wp_error($context)) {
            return $context;
        }

        if ($context['item']->status !== 'requested') {
            return new WP_Error('item_not_mutable', 'Only requested items can be completed.', ['status' => 409]);
        }

        $params = self::get_request_data($request);
        $actual_price = self::sanitize_price($params['actualPrice'] ?? $params['actual_price'] ?? null);
        if (is_wp_error($actual_price)) {
            return $actual_price;
        }

        $photo_count = self::get_item_photo_count($context['item_id']);
        if ($photo_count < 1) {
            return new WP_Error('photo_required_for_completion', 'Upload at least one photo before completing this item.', ['status' => 400]);
        }

        global $wpdb;
        $items_table = $wpdb->prefix . 'svdp_voucher_items';
        $updated = $wpdb->update(
            $items_table,
            [
                'status' => 'completed',
                'actual_price' => $actual_price,
                'completion_notes' => self::sanitize_optional_textarea($params['completionNotes'] ?? $params['completion_notes'] ?? ''),
                'cancellation_reason_id' => null,
                'cancellation_notes' => null,
                'completed_at' => current_time('mysql'),
                'completed_by_user_id' => get_current_user_id(),
            ],
            ['id' => $context['item_id']]
        );

        if ($updated === false) {
            return new WP_Error('item_complete_failed', 'The item could not be marked completed.', ['status' => 500]);
        }

        $workflow_status = self::sync_voucher_workflow_status($context['voucher_id']);

        return rest_ensure_response([
            'success' => true,
            'itemId' => $context['item_id'],
            'workflowStatus' => $workflow_status,
        ]);
    }

    /**
     * Cancel one requested furniture item using a preset reason.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function cancel_item($request) {
        $context = self::get_mutation_context($request['id'], $request['item_id']);
        if (is_wp_error($context)) {
            return $context;
        }

        if ($context['item']->status !== 'requested') {
            return new WP_Error('item_not_mutable', 'Only requested items can be cancelled.', ['status' => 409]);
        }

        $params = self::get_request_data($request);
        $reason_id = intval($params['cancellationReasonId'] ?? $params['cancellation_reason_id'] ?? 0);
        if ($reason_id <= 0) {
            return new WP_Error('cancellation_reason_required', 'Choose a cancellation reason before cancelling this item.', ['status' => 400]);
        }

        $reason = SVDP_Furniture_Cancellation_Reason::get_by_id($reason_id);
        if (!$reason || empty($reason->active)) {
            return new WP_Error('cancellation_reason_invalid', 'The selected cancellation reason is no longer available.', ['status' => 400]);
        }

        global $wpdb;
        $items_table = $wpdb->prefix . 'svdp_voucher_items';
        $updated = $wpdb->update(
            $items_table,
            [
                'status' => 'cancelled',
                'substitution_type' => 'none',
                'substitute_catalog_item_id' => null,
                'substitute_item_name' => null,
                'actual_price' => null,
                'completion_notes' => null,
                'cancellation_reason_id' => $reason_id,
                'cancellation_notes' => self::sanitize_optional_textarea($params['cancellationNotes'] ?? $params['cancellation_notes'] ?? ''),
                'completed_at' => current_time('mysql'),
                'completed_by_user_id' => get_current_user_id(),
            ],
            ['id' => $context['item_id']]
        );

        if ($updated === false) {
            return new WP_Error('item_cancel_failed', 'The item could not be cancelled.', ['status' => 500]);
        }

        $workflow_status = self::sync_voucher_workflow_status($context['voucher_id']);

        return rest_ensure_response([
            'success' => true,
            'itemId' => $context['item_id'],
            'workflowStatus' => $workflow_status,
        ]);
    }

    /**
     * Complete a furniture voucher once all items are resolved and generate documents.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error
     */
    public static function complete_voucher($request) {
        $context = self::get_completion_context($request['id']);
        if (is_wp_error($context)) {
            return $context;
        }

        $voucher = SVDP_Voucher::get_cashier_voucher($context['voucher_id']);
        if (!$voucher) {
            return new WP_Error('voucher_not_found', 'Furniture voucher not found.', ['status' => 404]);
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
        $completed_at = current_time('mysql');
        $completed_by_user_id = get_current_user_id();

        $meta_updated = $wpdb->update(
            $furniture_meta_table,
            [
                'completed_at' => $completed_at,
                'completed_by_user_id' => $completed_by_user_id,
                'receipt_file_path' => $receipt['file_path'],
                'invoice_file_path' => $invoice['file_path'],
            ],
            ['voucher_id' => $context['voucher_id']]
        );

        if ($meta_updated === false || intval($meta_updated) < 1) {
            self::cleanup_failed_voucher_completion($context['voucher_id'], $receipt['file_path'], $invoice);
            return new WP_Error('voucher_complete_meta_failed', 'The furniture voucher documents were generated but could not be linked to the voucher.', ['status' => 500]);
        }

        $voucher_updated = $wpdb->update(
            $vouchers_table,
            [
                'status' => 'Redeemed',
                'workflow_status' => 'completed',
                'redeemed_date' => current_time('Y-m-d'),
            ],
            ['id' => $context['voucher_id']]
        );

        if ($voucher_updated === false || intval($voucher_updated) < 1) {
            self::cleanup_failed_voucher_completion($context['voucher_id'], $receipt['file_path'], $invoice);
            return new WP_Error('voucher_complete_status_failed', 'The furniture voucher documents were generated but the voucher could not be marked completed.', ['status' => 500]);
        }

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
     * Resolve one voucher/item pair and verify furniture mutation permissions.
     *
     * @param int $voucher_id Root voucher ID.
     * @param int $item_id Voucher item ID.
     * @return array|WP_Error
     */
    private static function get_mutation_context($voucher_id, $item_id) {
        if (!SVDP_Permissions::user_can_access_cashier() || !SVDP_Permissions::user_can_redeem_furniture_vouchers()) {
            return new WP_Error('forbidden', 'You do not have permission to modify furniture vouchers.', ['status' => 403]);
        }

        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';

        $voucher_id = intval($voucher_id);
        $item_id = intval($item_id);

        $voucher = $wpdb->get_row($wpdb->prepare(
            "SELECT id, voucher_type, status
             FROM $vouchers_table
             WHERE id = %d
             LIMIT 1",
            $voucher_id
        ));

        if (!$voucher) {
            return new WP_Error('voucher_not_found', 'Furniture voucher not found.', ['status' => 404]);
        }

        if (!SVDP_Voucher::is_furniture_voucher_type($voucher->voucher_type)) {
            return new WP_Error('voucher_type_invalid', 'This action is only available for furniture vouchers.', ['status' => 400]);
        }

        if ($voucher->status === 'Denied' || $voucher->status === 'Redeemed') {
            return new WP_Error('voucher_not_mutable', 'This furniture voucher can no longer be changed.', ['status' => 409]);
        }

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT *
             FROM $voucher_items_table
             WHERE id = %d
               AND voucher_id = %d
             LIMIT 1",
            $item_id,
            $voucher_id
        ));

        if (!$item) {
            return new WP_Error('voucher_item_not_found', 'Furniture item not found on this voucher.', ['status' => 404]);
        }

        return [
            'voucher' => $voucher,
            'voucher_id' => $voucher_id,
            'item' => $item,
            'item_id' => $item_id,
        ];
    }

    /**
     * Resolve one voucher context for completion checks.
     *
     * @param int $voucher_id Root voucher ID.
     * @return array|WP_Error
     */
    private static function get_completion_context($voucher_id) {
        if (!SVDP_Permissions::user_can_access_cashier() || !SVDP_Permissions::user_can_redeem_furniture_vouchers()) {
            return new WP_Error('forbidden', 'You do not have permission to complete furniture vouchers.', ['status' => 403]);
        }

        global $wpdb;
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';
        $furniture_meta_table = $wpdb->prefix . 'svdp_furniture_voucher_meta';
        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';

        $voucher_id = intval($voucher_id);
        $voucher = $wpdb->get_row($wpdb->prepare(
            "SELECT v.id, v.voucher_type, v.status, fm.voucher_id AS furniture_meta_voucher_id
             FROM $vouchers_table v
             LEFT JOIN $furniture_meta_table fm ON fm.voucher_id = v.id
             WHERE v.id = %d
             LIMIT 1",
            $voucher_id
        ));

        if (!$voucher) {
            return new WP_Error('voucher_not_found', 'Furniture voucher not found.', ['status' => 404]);
        }

        if (!SVDP_Voucher::is_furniture_voucher_type($voucher->voucher_type)) {
            return new WP_Error('voucher_type_invalid', 'This action is only available for furniture vouchers.', ['status' => 400]);
        }

        if ($voucher->status === 'Denied' || $voucher->status === 'Redeemed') {
            return new WP_Error('voucher_not_mutable', 'This furniture voucher has already been completed.', ['status' => 409]);
        }

        if (empty($voucher->furniture_meta_voucher_id)) {
            return new WP_Error('voucher_meta_missing', 'Furniture voucher details are missing for this record.', ['status' => 500]);
        }

        $counts = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) AS requested,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
             FROM $voucher_items_table
             WHERE voucher_id = %d",
            $voucher_id
        ));

        $total = intval($counts->total ?? 0);
        $requested = intval($counts->requested ?? 0);

        if ($total < 1) {
            return new WP_Error('voucher_items_missing', 'This furniture voucher does not contain any items to complete.', ['status' => 400]);
        }

        if ($requested > 0) {
            return new WP_Error('voucher_unresolved_items', 'Resolve all requested furniture items before completing the voucher.', ['status' => 400]);
        }

        return [
            'voucher_id' => $voucher_id,
            'total' => $total,
            'requested' => $requested,
        ];
    }

    /**
     * Fetch photo rows keyed by voucher item ID.
     *
     * @param array $item_ids Voucher item IDs.
     * @return array
     */
    private static function get_photo_map($item_ids) {
        global $wpdb;

        $item_ids = array_values(array_filter(array_map('intval', (array) $item_ids)));
        if (empty($item_ids)) {
            return [];
        }

        $photos_table = $wpdb->prefix . 'svdp_voucher_item_photos';
        $placeholders = implode(', ', array_fill(0, count($item_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM $photos_table
             WHERE voucher_item_id IN ($placeholders)
             ORDER BY sort_order ASC, id ASC",
            $item_ids
        ));

        if (!$rows) {
            return [];
        }

        $map = [];

        foreach ($rows as $row) {
            $urls = SVDP_Furniture_Photo_Storage::build_public_urls($row->file_path);
            $item_id = intval($row->voucher_item_id);

            if (!isset($map[$item_id])) {
                $map[$item_id] = [];
            }

            $map[$item_id][] = [
                'id' => (int) $row->id,
                'file_name' => $row->file_name,
                'url' => $urls['url'],
                'thumbnail_url' => $urls['thumbnail_url'],
                'mime_type' => $row->mime_type,
                'file_size' => (int) $row->file_size,
                'image_width' => $row->image_width !== null ? (int) $row->image_width : null,
                'image_height' => $row->image_height !== null ? (int) $row->image_height : null,
                'sort_order' => (int) $row->sort_order,
            ];
        }

        return $map;
    }

    /**
     * Fetch cancellation reason labels keyed by ID.
     *
     * @param array $reason_ids Reason IDs.
     * @return array
     */
    private static function get_reason_map($reason_ids) {
        global $wpdb;

        $reason_ids = array_values(array_filter(array_map('intval', (array) $reason_ids)));
        if (empty($reason_ids)) {
            return [];
        }

        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';
        $placeholders = implode(', ', array_fill(0, count($reason_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, reason_text
             FROM $table
             WHERE id IN ($placeholders)",
            $reason_ids
        ));

        if (!$rows) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = $row->reason_text;
        }

        return $map;
    }

    /**
     * Fetch substitute catalog item names keyed by ID.
     *
     * @param array $catalog_item_ids Catalog item IDs.
     * @return array
     */
    private static function get_catalog_name_map($catalog_item_ids) {
        $catalog_rows = SVDP_Furniture_Catalog::get_items_by_ids($catalog_item_ids, false);
        if (!$catalog_rows) {
            return [];
        }

        $map = [];
        foreach ($catalog_rows as $catalog_row) {
            $map[(int) $catalog_row->id] = $catalog_row->name;
        }

        return $map;
    }

    /**
     * Count photos attached to one voucher item.
     *
     * @param int $item_id Voucher item ID.
     * @return int
     */
    private static function get_item_photo_count($item_id) {
        global $wpdb;

        $photos_table = $wpdb->prefix . 'svdp_voucher_item_photos';
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM $photos_table
             WHERE voucher_item_id = %d",
            intval($item_id)
        ));
    }

    /**
     * Compute the next sort order for a newly attached photo.
     *
     * @param int $item_id Voucher item ID.
     * @return int
     */
    private static function get_next_photo_sort_order($item_id) {
        global $wpdb;

        $photos_table = $wpdb->prefix . 'svdp_voucher_item_photos';
        $max_sort_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(sort_order)
             FROM $photos_table
             WHERE voucher_item_id = %d",
            intval($item_id)
        ));

        return $max_sort_order === null ? 0 : intval($max_sort_order) + 1;
    }

    /**
     * Synchronize the furniture workflow badge stored on the root voucher row.
     *
     * @param int $voucher_id Root voucher ID.
     * @return string
     */
    private static function sync_voucher_workflow_status($voucher_id) {
        global $wpdb;

        $voucher_items_table = $wpdb->prefix . 'svdp_voucher_items';
        $vouchers_table = $wpdb->prefix . 'svdp_vouchers';

        $counts = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) AS requested,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
             FROM $voucher_items_table
             WHERE voucher_id = %d",
            intval($voucher_id)
        ));

        $total = intval($counts->total ?? 0);
        $requested = intval($counts->requested ?? 0);
        $completed = intval($counts->completed ?? 0);
        $cancelled = intval($counts->cancelled ?? 0);

        $workflow_status = 'submitted';
        if ($total > 0 && $requested === 0) {
            $workflow_status = 'ready_for_completion';
        } elseif (($completed + $cancelled) > 0) {
            $workflow_status = 'in_progress';
        }

        $wpdb->update(
            $vouchers_table,
            ['workflow_status' => $workflow_status],
            ['id' => intval($voucher_id)]
        );

        return $workflow_status;
    }

    /**
     * Roll back completion side effects when one of the final writes fails.
     *
     * @param int        $voucher_id Root voucher ID.
     * @param string     $receipt_file_path Stored receipt path.
     * @param array|null $invoice Invoice result payload.
     * @return void
     */
    private static function cleanup_failed_voucher_completion($voucher_id, $receipt_file_path, $invoice = null) {
        global $wpdb;

        SVDP_Furniture_Receipt::delete_document($receipt_file_path);

        if (is_array($invoice)) {
            SVDP_Invoice::delete_invoice($invoice['invoice_id'] ?? 0, $invoice['file_path'] ?? null);
        }

        $wpdb->update(
            $wpdb->prefix . 'svdp_furniture_voucher_meta',
            [
                'completed_at' => null,
                'completed_by_user_id' => null,
                'receipt_file_path' => null,
                'invoice_file_path' => null,
            ],
            ['voucher_id' => intval($voucher_id)]
        );
    }

    /**
     * Merge JSON and form parameters for REST and HTMX posts.
     *
     * @param WP_REST_Request $request REST request.
     * @return array
     */
    private static function get_request_data($request) {
        $json_params = $request->get_json_params();
        if (!is_array($json_params)) {
            $json_params = [];
        }

        return array_merge($request->get_params(), $json_params);
    }

    /**
     * Normalize a positive decimal item price.
     *
     * @param mixed $value Raw input.
     * @return float|WP_Error
     */
    private static function sanitize_price($value) {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null) {
            return new WP_Error('actual_price_required', 'Enter the actual fulfilled price before completing this item.', ['status' => 400]);
        }

        if (!is_numeric($value)) {
            return new WP_Error('actual_price_invalid', 'Enter a valid actual price.', ['status' => 400]);
        }

        $price = round((float) $value, 2);
        if ($price <= 0) {
            return new WP_Error('actual_price_invalid', 'Actual price must be greater than zero.', ['status' => 400]);
        }

        return $price;
    }

    /**
     * Sanitize optional textarea content while preserving empty values as null.
     *
     * @param mixed $value Raw textarea input.
     * @return string|null
     */
    private static function sanitize_optional_textarea($value) {
        $value = sanitize_textarea_field((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Format a requested price as fixed or range text.
     *
     * @param float|null  $min Minimum price.
     * @param float|null  $max Maximum price.
     * @param float|null  $fixed Fixed price.
     * @param string|null $pricing_type Pricing type.
     * @return string
     */
    private static function format_money_range($min, $max, $fixed = null, $pricing_type = 'range') {
        if ($pricing_type === 'fixed' && $fixed !== null) {
            return '$' . number_format((float) $fixed, 2);
        }

        $min = $min !== null ? (float) $min : 0.0;
        $max = $max !== null ? (float) $max : $min;

        if (abs($max - $min) < 0.01) {
            return '$' . number_format($min, 2);
        }

        return '$' . number_format($min, 2) . ' - $' . number_format($max, 2);
    }

    /**
     * Convert a substitution type into a cashier-facing label.
     *
     * @param string $substitution_type Normalized substitution type.
     * @return string
     */
    private static function get_substitution_label($substitution_type) {
        if ($substitution_type === 'catalog') {
            return 'Catalog Substitute';
        }

        if ($substitution_type === 'free_text') {
            return 'Custom Substitute';
        }

        return '';
    }
}
