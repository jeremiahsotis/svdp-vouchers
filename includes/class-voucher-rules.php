<?php
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Voucher_Rules {

    public static function get_redemption_rule_text() {
        return 'This voucher expires 30 days after issuance. It must be redeemed in one visit; remaining items cannot be saved for a later visit.';
    }

}
