<?php
/**
 * Pure presenter for live voucher status payloads.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Voucher_Live_Status {

    /**
     * Build the live-status payload for one voucher ID.
     *
     * @param int $voucher_id Voucher ID.
     * @return array<string, mixed>|null
     */
    public static function build_for_voucher_id($voucher_id) {
        $voucher = SVDP_Voucher::get_cashier_voucher(intval($voucher_id));

        if (!is_array($voucher)) {
            return null;
        }

        return self::build_for_voucher($voucher);
    }

    /**
     * Build the live-status payload from an existing voucher payload.
     *
     * @param array|object $voucher Voucher payload.
     * @return array<string, mixed>|null
     */
    public static function build_for_voucher($voucher) {
        $voucher = self::normalize_voucher($voucher);
        if ($voucher === null) {
            return null;
        }

        $voucher_type = SVDP_Voucher::normalize_voucher_type($voucher['voucher_type'] ?? '');

        if ($voucher_type === 'furniture') {
            return self::build_furniture_status($voucher);
        }

        return self::build_clothing_status($voucher);
    }

    /**
     * Build a clothing voucher live-status payload from the existing formatted voucher row.
     *
     * @param array<string, mixed> $voucher Voucher payload.
     * @return array<string, mixed>
     */
    private static function build_clothing_status($voucher) {
        $voucher_id = intval($voucher['id'] ?? 0);
        $voucher_status = self::normalize_status($voucher['status'] ?? '');
        $adults_redeemed = intval($voucher['items_adult_redeemed'] ?? 0);
        $children_redeemed = intval($voucher['items_children_redeemed'] ?? 0);
        $redeemed_at = self::normalize_timestamp($voucher['redeemed_date'] ?? null);
        $coat_status = strtolower(self::normalize_status($voucher['coat_status'] ?? ''));
        $coat_issued = ($coat_status === 'issued') || !empty($voucher['coat_issued_date']);

        return [
            'voucher_id' => $voucher_id,
            'voucher_type' => 'clothing',
            'status' => [
                'voucher_status' => $voucher_status,
                'is_active' => $voucher_status === 'Active',
                'is_redeemed' => $voucher_status === 'Redeemed',
                'is_expired' => $voucher_status === 'Expired',
            ],
            'redemption' => [
                'adults_redeemed' => $adults_redeemed,
                'children_redeemed' => $children_redeemed,
                'total_items_redeemed' => intval($adults_redeemed + $children_redeemed),
            ],
            'coat' => [
                'eligible' => (bool) ($voucher['coat_eligible'] ?? false),
                'issued' => (bool) ($coat_issued || !empty($voucher['coat_issued_date'])),
            ],
            'timestamps' => [
                'created_at' => self::normalize_timestamp($voucher['voucher_created_date'] ?? null),
                'redeemed_at' => $redeemed_at,
            ],
        ];
    }

    /**
     * Build a furniture voucher live-status payload from the existing formatted voucher row.
     *
     * @param array<string, mixed> $voucher Voucher payload.
     * @return array<string, mixed>
     */
    private static function build_furniture_status($voucher) {
        $voucher_id = intval($voucher['id'] ?? 0);
        $voucher_status = self::normalize_status($voucher['status'] ?? '');
        $completed_at = self::normalize_timestamp($voucher['furniture_completed_at'] ?? null);
        $is_completed = $completed_at !== null;
        $items = SVDP_Voucher::get_furniture_voucher_items($voucher_id);

        $total_items = 0;
        $completed_items = 0;
        $cancelled_items = 0;

        foreach ((array) $items as $item) {
            $item = self::normalize_voucher($item);
            if ($item === null) {
                continue;
            }

            $total_items++;
            $item_status = sanitize_key($item['status'] ?? '');

            if ($item_status === 'completed') {
                $completed_items++;
                continue;
            }

            if ($item_status === 'cancelled') {
                $cancelled_items++;
            }
        }

        $remaining_items = max(0, $total_items - $completed_items - $cancelled_items);

        return [
            'voucher_id' => $voucher_id,
            'voucher_type' => 'furniture',
            'status' => [
                'voucher_status' => $voucher_status,
                'completion_state' => $is_completed ? 'completed' : 'not_completed',
                'is_completed' => (bool) $is_completed,
                'is_active' => $voucher_status === 'Active',
            ],
            'delivery' => [
                'required' => (bool) ($voucher['delivery_required'] ?? false),
                'completed' => (bool) (!empty($voucher['delivery_required']) && $is_completed),
            ],
            'items' => [
                'total_items' => intval($total_items),
                'completed_items' => intval($completed_items),
                'remaining_items' => intval($remaining_items),
            ],
            'timestamps' => [
                'created_at' => self::normalize_timestamp($voucher['voucher_created_date'] ?? null),
                'completed_at' => $completed_at,
            ],
        ];
    }

    /**
     * Normalize array/object voucher-shaped payloads.
     *
     * @param mixed $voucher Voucher payload.
     * @return array<string, mixed>|null
     */
    private static function normalize_voucher($voucher) {
        if (is_object($voucher)) {
            $voucher = (array) $voucher;
        }

        if (!is_array($voucher)) {
            return null;
        }

        return $voucher;
    }

    /**
     * Normalize status strings to a stable scalar.
     *
     * @param mixed $status Status value.
     * @return string
     */
    private static function normalize_status($status) {
        if (!is_scalar($status)) {
            return '';
        }

        return trim((string) $status);
    }

    /**
     * Normalize timestamps to either a string or null.
     *
     * @param mixed $timestamp Timestamp value.
     * @return string|null
     */
    private static function normalize_timestamp($timestamp) {
        if (!is_scalar($timestamp)) {
            return null;
        }

        $timestamp = trim((string) $timestamp);

        return $timestamp === '' ? null : $timestamp;
    }

}
