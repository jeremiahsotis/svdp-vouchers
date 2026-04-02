<?php
$document = SVDP_Neighbor_Voucher_Document::get_template_context($voucher ?? []);

if (is_wp_error($document)) {
    return;
}

include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/documents/neighbor-voucher.php';
