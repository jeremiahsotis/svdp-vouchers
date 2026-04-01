<?php
$period_start_display = date('m/d/Y', strtotime($period_start));
$period_end_display = date('m/d/Y', strtotime($period_end));
$generated_at_display = date('m/d/Y g:i A', strtotime($generated_at));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html($statement_number); ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2933; margin: 32px; }
        h1, h2 { margin: 0 0 12px; }
        .header { display: flex; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
        .meta p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #d9e2ec; padding: 10px 12px; text-align: left; }
        th { background: #f5f7fa; }
        .totals { margin-top: 24px; max-width: 320px; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #d9e2ec; }
        .totals-row strong { font-size: 1.05em; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Conference Statement</h1>
            <p><?php echo esc_html($conference->name ?? 'Unknown Conference'); ?></p>
        </div>
        <div class="meta">
            <p><strong>Statement:</strong> <?php echo esc_html($statement_number); ?></p>
            <p><strong>Period:</strong> <?php echo esc_html($period_start_display . ' - ' . $period_end_display); ?></p>
            <p><strong>Generated:</strong> <?php echo esc_html($generated_at_display); ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Invoice Date</th>
                <th>Voucher ID</th>
                <th>Neighbor</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?php echo esc_html($invoice['invoice_number']); ?></td>
                    <td><?php echo esc_html(date('m/d/Y', strtotime($invoice['invoice_date']))); ?></td>
                    <td><?php echo esc_html($invoice['voucher_id']); ?></td>
                    <td><?php echo esc_html($invoice['neighbor_name'] ?? ''); ?></td>
                    <td>$<?php echo esc_html(number_format((float) $invoice['amount'], 2)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Invoice count</span>
            <span><?php echo esc_html($totals['invoice_count']); ?></span>
        </div>
        <div class="totals-row">
            <strong>Total amount</strong>
            <strong>$<?php echo esc_html(number_format((float) $totals['total_amount'], 2)); ?></strong>
        </div>
    </div>
</body>
</html>
