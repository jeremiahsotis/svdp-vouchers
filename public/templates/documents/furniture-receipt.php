<?php
$voucher = isset($voucher) && is_object($voucher) ? (array) $voucher : (is_array($voucher ?? null) ? $voucher : []);

if (!isset($delivery_view) || !is_array($delivery_view)) {
    $voucher_id = intval($voucher['id'] ?? 0);
    $delivery_view = $voucher_id > 0 ? SVDP_Voucher_Delivery_View::build_for_voucher_id($voucher_id) : null;

    if (!is_array($delivery_view) && $voucher_id > 0) {
        $delivery_view = SVDP_Voucher_Delivery_View::build($voucher, null);
    }
}

$document_args = isset($document_args) && is_array($document_args) ? $document_args : [];
$document = SVDP_Neighbor_Voucher_Document::get_template_context($voucher, $document_args);

if (is_wp_error($document) || !is_array($delivery_view)) {
    return;
}

include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/documents/neighbor-voucher.php';
