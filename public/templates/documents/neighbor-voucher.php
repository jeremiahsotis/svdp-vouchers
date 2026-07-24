<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo esc_html('Voucher #' . $document['voucher_number']); ?></title>
    <style>
        @page { size: letter; margin: .6in; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font: 16px/1.45 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        header { display:flex; justify-content:space-between; gap:24px; border-bottom:3px solid #1f5fbf; padding-bottom:16px; }
        h1,h2 { margin:0 0 8px; } h1 { font-size:28px; } h2 { margin-top:24px; font-size:20px; }
        .notice { background:#eaf2ff; border:1px solid #9bbcf0; border-radius:10px; padding:10px 14px; font-weight:700; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; margin-top:20px; }
        .label { color:#5d6878; font-size:13px; text-transform:uppercase; letter-spacing:.04em; }
        .value { font-weight:700; }
        table { width:100%; border-collapse:collapse; } th,td { text-align:left; border-bottom:1px solid #d9e1ec; padding:10px 8px; }
        th:last-child,td:last-child { width:90px; text-align:center; } tr { break-inside:avoid; }
        .actions { margin-top:24px; } button { padding:10px 18px; font:inherit; font-weight:700; }
        @media print { .actions { display:none; } }
    </style>
</head>
<body>
<header>
    <div><h1>Neighbor Voucher</h1><div>Voucher #<?php echo esc_html($document['voucher_number']); ?> · <?php echo esc_html($document['voucher_type']); ?></div></div>
    <div class="notice">Neighbor Copy — No pricing shown</div>
</header>
<div class="grid">
    <?php
    $fields = [
        'Neighbor' => $document['neighbor_name'], 'Date of Birth' => $document['dob'],
        'Organization' => $document['organization'], 'Status' => $document['status'],
        'Issued' => $document['issued_date'], 'Expires' => $document['expiration_date'],
        'Redeemed' => $document['redeemed_date'], 'Vincentian' => $document['vincentian_name'],
        'Vincentian Email' => $document['vincentian_email'],
    ];
    foreach ($fields as $label => $value): if ($value === '') continue; ?>
        <div><div class="label"><?php echo esc_html($label); ?></div><div class="value"><?php echo esc_html($value); ?></div></div>
    <?php endforeach; ?>
</div>
<h2>Approved Items</h2>
<table><thead><tr><th>Item</th><th>Quantity</th></tr></thead><tbody>
<?php foreach ($document['items'] as $item): ?>
    <tr><td><strong><?php echo esc_html($item['name']); ?></strong><?php if ($item['category']): ?> <em>(<?php echo esc_html($item['category']); ?>)</em><?php endif; ?></td><td><?php echo esc_html($item['quantity']); ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<div class="actions"><button type="button" onclick="window.print()">Print Voucher</button></div>
<script>window.addEventListener('load',function(){window.print();});</script>
</body>
</html>
