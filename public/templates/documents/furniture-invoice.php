<?php
$invoice_date_display = date('m/d/Y', strtotime($invoice_date));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($invoice_number); ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f3442; margin: 0; padding: 32px; background: #f5f8fa; }
        .sheet { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #d8e4eb; border-radius: 18px; overflow: hidden; }
        .header { padding: 28px 32px; background: #12344d; color: #fff; }
        .header h1 { margin: 0 0 8px; font-size: 30px; }
        .header p { margin: 0; opacity: 0.92; }
        .section { padding: 24px 32px; border-top: 1px solid #e3ebf1; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #5b7282; margin-bottom: 6px; }
        .value { font-size: 16px; color: #12344d; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e3ebf1; text-align: left; vertical-align: top; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: #5b7282; }
        .summary { margin-top: 18px; max-width: 360px; margin-left: auto; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid #e3ebf1; }
        .summary-row strong { color: #12344d; }
        .status-note { color: #5b7282; line-height: 1.5; }
        @media print {
            body { padding: 0; background: #fff; }
            .sheet { border: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <header class="header">
            <h1>Conference Invoice</h1>
            <p><?php echo esc_html($invoice_number); ?></p>
        </header>

        <section class="section">
            <div class="grid">
                <div>
                    <span class="label">Conference</span>
                    <span class="value"><?php echo esc_html($voucher['conference_name']); ?></span>
                </div>
                <div>
                    <span class="label">Invoice Date</span>
                    <span class="value"><?php echo esc_html($invoice_date_display); ?></span>
                </div>
                <div>
                    <span class="label">Neighbor</span>
                    <span class="value"><?php echo esc_html($voucher['first_name'] . ' ' . $voucher['last_name']); ?></span>
                </div>
                <div>
                    <span class="label">Voucher</span>
                    <span class="value">#<?php echo esc_html(intval($voucher['id'])); ?></span>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 style="margin: 0; color: #12344d;">Fulfilled Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Requested Item</th>
                        <th>Outcome</th>
                        <th>Actual Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($voucher['items'] ?? []) as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($item['requested_item_name']); ?></strong><br>
                                <span class="status-note"><?php echo esc_html($item['requested_category_label']); ?></span>
                            </td>
                            <td>
                                <?php if (($item['status'] ?? '') === 'completed'): ?>
                                    <?php if (!empty($item['has_substitution'])): ?>
                                        Fulfilled as <?php echo esc_html($item['substitute_item_name']); ?>
                                    <?php else: ?>
                                        Fulfilled as requested
                                    <?php endif; ?>
                                <?php else: ?>
                                    Cancelled: <?php echo esc_html($item['cancellation_reason_label'] ?: 'No reason recorded'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if (($item['status'] ?? '') === 'completed' && isset($item['actual_price'])) {
                                    echo esc_html('$' . number_format((float) $item['actual_price'], 2));
                                } else {
                                    echo esc_html('$0.00');
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary">
                <div class="summary-row">
                    <span>Actual fulfilled item total</span>
                    <strong><?php echo esc_html('$' . number_format((float) $totals['items_total'], 2)); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Conference share (50%)</span>
                    <strong><?php echo esc_html('$' . number_format((float) $totals['conference_share_total'], 2)); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Delivery fee</span>
                    <strong><?php echo esc_html('$' . number_format((float) $totals['delivery_fee'], 2)); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Total invoice amount</span>
                    <strong><?php echo esc_html('$' . number_format((float) $totals['amount'], 2)); ?></strong>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
