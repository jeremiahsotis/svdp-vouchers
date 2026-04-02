<?php
$document_args = isset($document_args) && is_array($document_args) ? $document_args : [];
$document = SVDP_Neighbor_Voucher_Document::get_template_context($voucher ?? [], $document_args);

if (is_wp_error($document)) {
    return;
}

include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/documents/neighbor-voucher.php';
