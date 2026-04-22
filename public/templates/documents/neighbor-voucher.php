<?php
if (!isset($delivery_view) || !is_array($delivery_view)) {
    return;
}

$document = is_array($document ?? null) ? $document : [];
$voucher_status = $delivery_view['live_status']['status']['voucher_status'] ?? null;
$redemption = $delivery_view['live_status']['redemption'] ?? [];
if (!is_array($redemption)) {
    $redemption = [];
}
$redemption_total = intval(
    $redemption['total_items_redeemed']
    ?? (($redemption['adults_redeemed'] ?? 0) + ($redemption['children_redeemed'] ?? 0))
);
$coat = $delivery_view['live_status']['coat'] ?? [];
if (!is_array($coat)) {
    $coat = [];
}
$coat_issued = !empty($coat['issued']);
$items = $delivery_view['live_status']['items'] ?? [];
if (!is_array($items)) {
    $items = [];
}
$delivery = $delivery_view['live_status']['delivery'] ?? [];
if (!is_array($delivery)) {
    $delivery = [];
}
$timestamps = $delivery_view['live_status']['timestamps'] ?? [];
if (!is_array($timestamps)) {
    $timestamps = [];
}

$format_date = static function ($date_raw) {
    $date_raw = trim((string) $date_raw);
    if ($date_raw === '') {
        return '';
    }

    try {
        return (new DateTime($date_raw))->format('m/d/Y');
    } catch (Exception $exception) {
        return $date_raw;
    }
};

$format_expiration_date = static function ($created_date) {
    $created_date = trim((string) $created_date);
    if ($created_date === '') {
        return '';
    }

    try {
        $expiration = new DateTime($created_date);
        $expiration->modify('+30 days');
        return $expiration->format('m/d/Y');
    } catch (Exception $exception) {
        return '';
    }
};

$requested_items = is_array($document['requested_items'] ?? null) ? $document['requested_items'] : [];
$delivery_required = (bool) ($delivery['required'] ?? $document['delivery_required'] ?? false);
$delivery_completed = (bool) ($delivery['completed'] ?? false);
$requested_items_total = intval($items['total_items'] ?? $document['requested_items_total'] ?? 0);
$requested_items_total_label = $requested_items_total > 0
    ? SVDP_Voucher_I18n::format_item_count($requested_items_total, $document['language'] ?? 'en')
    : '';
$created_date_display = trim((string) ($document['created_date_display'] ?? ''));
if (!empty($timestamps['created_at'])) {
    $created_date_display = $format_date($timestamps['created_at']);
}

$valid_through_display = trim((string) ($document['valid_through_display'] ?? ''));
if (!empty($timestamps['created_at'])) {
    $valid_through_display = $format_expiration_date($timestamps['created_at']);
}

$delivery_label = SVDP_Voucher_I18n::get_delivery_label($delivery_required, $document['language'] ?? 'en');
$show_delivery_note = $delivery_required;
$copy = is_array($document['copy'] ?? null) ? $document['copy'] : [];
$font_family = trim((string) ($document['font_family'] ?? 'DejaVu Sans, Helvetica, Arial, sans-serif'));
$html_lang = trim((string) ($document['html_lang'] ?? $document['language'] ?? 'en'));
$uppercase_labels = !empty($document['uppercase_labels']);
$is_pdf = !empty($document['is_pdf']);
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr($html_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($copy['document_title'] ?? 'Neighbor Voucher'); ?></title>
    <style>
        body { font-family: <?php echo esc_html($font_family); ?>; color: #1f3442; margin: 0; padding: 32px; background: #f5f8fa; }
        .sheet { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #d8e4eb; border-radius: 18px; overflow: hidden; }
        .header { padding: 28px 32px; background: #0f537d; color: #fff; }
        .header h1 { margin: 0 0 8px; font-size: 30px; }
        .header p { margin: 0; opacity: 0.92; }
        .section { padding: 24px 32px; border-top: 1px solid #e3ebf1; }
        .section-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
        .section-header h2 { margin: 0; color: #12344d; font-size: 22px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .label {
            display: block;
            font-size: 12px;
            color: #5b7282;
            margin-bottom: 6px;
            <?php if ($uppercase_labels): ?>
                text-transform: uppercase;
                letter-spacing: 0.05em;
            <?php else: ?>
                text-transform: none;
                letter-spacing: normal;
                font-weight: 700;
            <?php endif; ?>
        }
        .value { font-size: 16px; color: #12344d; }
        .value-amount { font-size: 24px; font-weight: 700; color: #0f537d; }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eef2f5;
            color: #355062;
            font-size: 12px;
            font-weight: 700;
            <?php if ($uppercase_labels): ?>
                text-transform: uppercase;
                letter-spacing: 0.04em;
            <?php else: ?>
                text-transform: none;
                letter-spacing: normal;
            <?php endif; ?>
        }
        .note { margin: 16px 0 0; padding: 12px 14px; border-radius: 12px; background: #eef7ff; border: 1px solid #d5e8f7; color: #184f71; }
        .item { padding: 16px 18px; border: 1px solid #d8e4eb; border-radius: 14px; margin-bottom: 14px; }
        .item:last-child { margin-bottom: 0; }
        .item-header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .item h3 { margin: 0; font-size: 18px; color: #12344d; }
        .item-count { color: #355062; font-weight: 700; white-space: nowrap; }
        .item p { margin: 8px 0 0; line-height: 1.5; color: #476170; }
        .empty { margin: 0; color: #5b7282; line-height: 1.6; }
        .footer { padding: 20px 32px 28px; color: #5b7282; line-height: 1.6; }
        <?php if ($is_pdf): ?>
            .section-header { display: block; }
            .section-header h2 { margin-bottom: 10px; }
            .grid { display: block; }
            .grid > div { margin-bottom: 14px; }
            .item-header { display: block; }
            .item-count { display: inline-block; margin-top: 8px; }
        <?php endif; ?>
        @media print {
            body { padding: 0; background: #fff; }
            .sheet { border: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div
        class="sheet"
        data-voucher-status="<?php echo esc_attr((string) $voucher_status); ?>"
        data-redemption-total="<?php echo esc_attr((string) $redemption_total); ?>"
        data-coat-issued="<?php echo esc_attr($coat_issued ? '1' : '0'); ?>"
        data-delivery-completed="<?php echo esc_attr($delivery_completed ? '1' : '0'); ?>"
        data-live-items-total="<?php echo esc_attr((string) $requested_items_total); ?>"
    >
        <header class="header">
            <h1><?php echo esc_html($copy['document_heading'] ?? 'Neighbor Voucher'); ?></h1>
            <p><?php echo esc_html($copy['document_intro'] ?? 'Bring this voucher with you when you arrive for pickup or delivery.'); ?></p>
        </header>

        <section class="section">
            <div class="grid">
                <div>
                    <span class="label"><?php echo esc_html($copy['label_neighbor'] ?? 'Neighbor'); ?></span>
                    <span class="value"><?php echo esc_html($document['neighbor_name'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_date_of_birth'] ?? 'Date of Birth'); ?></span>
                    <span class="value"><?php echo esc_html($document['date_of_birth_display'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_conference'] ?? 'Conference'); ?></span>
                    <span class="value"><?php echo esc_html($document['conference_name'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_voucher_type'] ?? 'Voucher Type'); ?></span>
                    <span class="value"><?php echo esc_html($document['voucher_type_label'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_household'] ?? 'Household'); ?></span>
                    <span class="value"><?php echo esc_html($document['household_display'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_delivery'] ?? 'Delivery'); ?></span>
                    <span class="value"><?php echo esc_html($delivery_label); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_created'] ?? 'Created'); ?></span>
                    <span class="value"><?php echo esc_html($created_date_display); ?></span>
                </div>
                <div>
                    <span class="label"><?php echo esc_html($copy['label_valid_through'] ?? 'Valid Through'); ?></span>
                    <span class="value"><?php echo esc_html($valid_through_display); ?></span>
                </div>
            </div>

            <div class="note">
                <span class="label"><?php echo esc_html($copy['label_estimated_amount_approved'] ?? 'Estimated Amount Approved'); ?></span>
                <div class="value value-amount"><?php echo esc_html($document['approved_amount_display'] ?? ''); ?></div>
            </div>

            <?php if ($show_delivery_note): ?>
                <div class="note"><?php echo esc_html($copy['delivery_included_note'] ?? 'Delivery is included with this voucher.'); ?></div>
            <?php endif; ?>
        </section>

        <section class="section">
            <div class="section-header">
                <h2><?php echo esc_html($copy['heading_requested_items'] ?? 'Requested Items'); ?></h2>
                <?php if ($requested_items_total > 0): ?>
                    <span class="pill"><?php echo esc_html($requested_items_total_label); ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($requested_items)): ?>
                <p class="empty"><?php echo esc_html($copy['empty_requested_items'] ?? 'No requested items were recorded for this voucher.'); ?></p>
            <?php else: ?>
                <?php foreach ($requested_items as $item): ?>
                    <article class="item">
                        <div class="item-header">
                            <h3><?php echo esc_html($item['name'] ?? ''); ?></h3>
                            <span class="item-count"><?php echo esc_html($item['quantity_label'] ?? ''); ?></span>
                        </div>

                        <?php if (!empty($item['description'])): ?>
                            <p><?php echo esc_html($item['description']); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <div class="footer">
            <p><?php echo esc_html($copy['footer_note'] ?? 'This neighbor-facing voucher includes the approved amount, delivery status, and requested items only.'); ?></p>
        </div>
    </div>
</body>
</html>
