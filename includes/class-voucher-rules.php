<?php
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Voucher_Rules {

    public static function get_redemption_rule_text() {
        return SVDP_Voucher_Copy::get_redemption_rule_text();
    }

    public static function get_selected_item_retail_maximum_label() {
        return 'Selected item retail maximum';
    }

    public static function get_maximum_conference_commitment_label() {
        return 'Maximum Conference commitment';
    }

    public static function get_total_maximum_conference_commitment_label() {
        return 'Total maximum Conference commitment';
    }

    public static function get_delivery_fee_label() {
        return 'Delivery fee';
    }

    public static function get_conference_cost_label() {
        return 'Conference cost';
    }

    public static function get_pricing_explanation_text() {
        return 'The amount shown is the maximum Conference cost for this voucher. Final fulfilled pricing may be lower based on the items chosen.';
    }

    public static function get_furniture_pricing_rule_text() {
        return 'Most items are calculated at 50% of the retail prices shown. Mattress/Frame Bundles use the exact price shown.';
    }

    public static function get_approval_not_submitted_text() {
        return 'This voucher is not submitted until you approve the maximum Conference commitment.';
    }

    public static function get_actual_fulfilled_item_total_label() {
        return 'Actual fulfilled item total';
    }

    public static function get_conference_share_label() {
        return 'Conference share (50%)';
    }

    public static function get_total_invoice_amount_label() {
        return 'Total invoice amount';
    }

    public static function get_invoice_actual_pricing_note() {
        return 'Conference invoice uses actual fulfilled prices x 50%';
    }

    public static function get_delivery_fee_included_text() {
        return 'Delivery fee included';
    }

    public static function get_pricing_copy() {
        return [
            'selectedItemRetailMaximumLabel' => self::get_selected_item_retail_maximum_label(),
            'maximumCommitmentLabel' => self::get_maximum_conference_commitment_label(),
            'totalMaximumCommitmentLabel' => self::get_total_maximum_conference_commitment_label(),
            'deliveryFeeLabel' => self::get_delivery_fee_label(),
            'conferenceCostLabel' => self::get_conference_cost_label(),
            'pricingExplanation' => self::get_pricing_explanation_text(),
            'pricingRule' => self::get_furniture_pricing_rule_text(),
            'approvalNotSubmittedText' => self::get_approval_not_submitted_text(),
            'actualFulfilledItemTotalLabel' => self::get_actual_fulfilled_item_total_label(),
            'conferenceShareLabel' => self::get_conference_share_label(),
            'totalInvoiceAmountLabel' => self::get_total_invoice_amount_label(),
            'invoiceActualPricingNote' => self::get_invoice_actual_pricing_note(),
            'deliveryFeeIncludedText' => self::get_delivery_fee_included_text(),
        ];
    }

    public static function get_client_copy_payload() {
        $copy = SVDP_Voucher_Copy::get_client_copy_payload();
        $copy['pricing'] = self::get_pricing_copy();
        $copy['rules'] = [
            'redemptionRuleText' => self::get_redemption_rule_text(),
        ];

        return $copy;
    }

}
