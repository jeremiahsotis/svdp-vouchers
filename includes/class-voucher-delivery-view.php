<?php
/**
 * Read-only composed voucher delivery view.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Voucher_Delivery_View {

    /**
     * Build the composed delivery view for one voucher ID.
     *
     * @param int $voucher_id Voucher ID.
     * @return array<string, mixed>|null
     */
    public static function build_for_voucher_id($voucher_id) {
        $voucher = SVDP_Voucher::get_cashier_voucher(intval($voucher_id));
        if (!is_array($voucher)) {
            return null;
        }

        $snapshot = SVDP_Voucher_Delivery_Snapshot::get_for_voucher_id($voucher_id);

        return self::build($voucher, $snapshot);
    }

    /**
     * Build the composed delivery view from an existing voucher payload.
     *
     * @param array|object $voucher Voucher payload.
     * @param array|object|null $snapshot Already-fetched snapshot payload.
     * @return array<string, mixed>|null
     */
    public static function build($voucher, $snapshot) {
        $voucher = self::normalize_array_payload($voucher);
        if ($voucher === null) {
            return null;
        }

        $snapshot = self::normalize_array_payload($snapshot);
        $live_status = SVDP_Voucher_Live_Status::build_for_voucher($voucher);

        return [
            'voucher_id' => intval($voucher['id'] ?? 0),
            'snapshot' => [
                'exists' => is_array($snapshot),
                'data' => is_array($snapshot) ? $snapshot : null,
            ],
            'live_status' => $live_status,
        ];
    }

    /**
     * Normalize array/object payloads to arrays.
     *
     * @param mixed $payload Payload value.
     * @return array<string, mixed>|null
     */
    private static function normalize_array_payload($payload) {
        if (is_object($payload)) {
            $payload = (array) $payload;
        }

        if (!is_array($payload)) {
            return null;
        }

        return $payload;
    }
}
