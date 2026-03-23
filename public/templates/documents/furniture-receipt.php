<?php
$delivery_required = !empty($voucher['delivery_required']);
$completed_date = !empty($voucher['furniture_completed_at']) ? $voucher['furniture_completed_at'] : current_time('mysql');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Furniture Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f3442; margin: 0; padding: 32px; background: #f5f8fa; }
        .sheet { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #d8e4eb; border-radius: 18px; overflow: hidden; }
        .header { padding: 28px 32px; background: #0f537d; color: #fff; }
        .header h1 { margin: 0 0 8px; font-size: 30px; }
        .header p { margin: 0; opacity: 0.9; }
        .section { padding: 24px 32px; border-top: 1px solid #e3ebf1; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #5b7282; margin-bottom: 6px; }
        .value { font-size: 16px; color: #12344d; }
        .item { padding: 16px 18px; border: 1px solid #d8e4eb; border-radius: 14px; margin-bottom: 14px; }
        .item h3 { margin: 0 0 6px; font-size: 18px; color: #12344d; }
        .item p { margin: 4px 0; line-height: 1.5; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; background: #eef2f5; color: #355062; }
        .badge-completed { background: #dff4e7; color: #10643d; }
        .badge-cancelled { background: #fff1d6; color: #7a5200; }
        .note { margin-top: 8px; padding: 10px 12px; border-radius: 10px; background: #f7fafc; border: 1px solid #e3ebf1; }
        .footer { padding: 20px 32px 28px; color: #5b7282; line-height: 1.6; }
        @media print {
            body { padding: 0; background: #fff; }
            .sheet { border: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <header class="header">
            <h1>Furniture Voucher Receipt</h1>
            <p>Neighbor copy. No prices are shown on this receipt.</p>
        </header>

        <section class="section">
            <div class="grid">
                <div>
                    <span class="label">Neighbor</span>
                    <span class="value"><?php echo esc_html($voucher['first_name'] . ' ' . $voucher['last_name']); ?></span>
                </div>
                <div>
                    <span class="label">Date of Birth</span>
                    <span class="value"><?php echo esc_html(date('m/d/Y', strtotime($voucher['dob']))); ?></span>
                </div>
                <div>
                    <span class="label">Conference</span>
                    <span class="value"><?php echo esc_html($voucher['conference_name']); ?></span>
                </div>
                <div>
                    <span class="label">Completed</span>
                    <span class="value"><?php echo esc_html(date('m/d/Y g:i A', strtotime($completed_date))); ?></span>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 style="margin: 0 0 12px; color: #12344d;">Delivery</h2>
            <?php if ($delivery_required && !empty($voucher['delivery_address_display'])): ?>
                <p style="margin: 0; line-height: 1.6;">Delivery requested to <?php echo esc_html($voucher['delivery_address_display']); ?>.</p>
            <?php else: ?>
                <p style="margin: 0; line-height: 1.6;">Pickup requested. No delivery address was provided.</p>
            <?php endif; ?>
        </section>

        <section class="section">
            <h2 style="margin: 0 0 16px; color: #12344d;">Item Outcomes</h2>
            <?php foreach ((array) ($voucher['items'] ?? []) as $item): ?>
                <article class="item">
                    <div style="display: flex; justify-content: space-between; gap: 12px; align-items: flex-start;">
                        <div>
                            <h3><?php echo esc_html($item['requested_item_name']); ?></h3>
                            <p><?php echo esc_html($item['requested_category_label']); ?></p>
                        </div>
                        <span class="badge <?php echo ($item['status'] ?? '') === 'completed' ? 'badge-completed' : 'badge-cancelled'; ?>">
                            <?php echo esc_html(ucfirst($item['status'] ?? 'requested')); ?>
                        </span>
                    </div>

                    <?php if (($item['status'] ?? '') === 'completed'): ?>
                        <p>
                            <?php if (!empty($item['has_substitution'])): ?>
                                Fulfilled as <strong><?php echo esc_html($item['substitute_item_name']); ?></strong>.
                            <?php else: ?>
                                Fulfilled as requested.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($item['completion_notes'])): ?>
                            <div class="note">
                                <strong>Notes:</strong> <?php echo esc_html($item['completion_notes']); ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif (($item['status'] ?? '') === 'cancelled'): ?>
                        <p>Cancelled: <?php echo esc_html($item['cancellation_reason_label'] ?: 'No reason recorded'); ?>.</p>
                        <?php if (!empty($item['cancellation_notes'])): ?>
                            <div class="note">
                                <strong>Notes:</strong> <?php echo esc_html($item['cancellation_notes']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="footer">
            <p>Keep this receipt for your records. Store staff can use it to confirm what was fulfilled, substituted, or cancelled on this furniture voucher.</p>
        </div>
    </div>
</body>
</html>
