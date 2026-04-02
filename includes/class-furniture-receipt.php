<?php
/**
 * Backward-compatible wrapper around the shared neighbor voucher document service.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Furniture_Receipt {

    /**
     * Generate and store one neighbor-facing document for a furniture voucher.
     *
     * @param array $voucher Formatted cashier voucher data.
     * @param array $args Optional generation arguments.
     * @return array|WP_Error
     */
    public static function create_for_voucher($voucher, $args = []) {
        $args = is_array($args) ? $args : [];
        $args['file_name'] = !empty($args['file_name']) ? $args['file_name'] : 'neighbor-receipt.html';

        return SVDP_Neighbor_Voucher_Document::create_for_voucher($voucher, $args);
    }

    /**
     * Resolve a public URL from one stored relative receipt path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return string|null
     */
    public static function public_url_from_relative_path($relative_path) {
        return SVDP_Neighbor_Voucher_Document::public_url_from_relative_path($relative_path);
    }

    /**
     * Delete one stored receipt file by relative path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return void
     */
    public static function delete_document($relative_path) {
        SVDP_Neighbor_Voucher_Document::delete_document($relative_path);
    }
}
