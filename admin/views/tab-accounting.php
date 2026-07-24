<?php
global $wpdb;
$organizations = SVDP_Conference::get_all(false);
$batches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}svdp_accounting_batches ORDER BY id DESC LIMIT 25");
$fields = [
    'bookkeeping_email' => 'Bookkeeping Email',
    'quickbooks_ar_account' => 'QuickBooks Accounts Receivable Account',
    'quickbooks_income_account' => 'QuickBooks Income Account',
    'quickbooks_voucher_item' => 'QuickBooks Voucher Service Item',
    'quickbooks_delivery_item' => 'QuickBooks Delivery Service Item',
];
?>
<div class="svdp-accounting-admin-section">
<h2>Accounting Settings</h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="svdp_save_accounting_settings"><?php wp_nonce_field('svdp_save_accounting_settings'); ?>
<table class="form-table"><?php foreach ($fields as $key => $label): ?><tr><th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input class="regular-text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(SVDP_Settings::get_setting($key, '')); ?>" <?php echo $key === 'bookkeeping_email' ? 'type="email"' : 'type="text"'; ?>></td></tr><?php endforeach; ?></table>
<?php submit_button('Save Accounting Settings'); ?></form>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="svdp_run_accounting_cycle"><?php wp_nonce_field('svdp_run_accounting_cycle'); ?><?php submit_button('Run Due Monthly Cycle Now', 'secondary'); ?></form>

<h2>Organization Billing Mappings</h2>
<?php foreach ($organizations as $organization): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:grid;grid-template-columns:2fr 2fr 2fr auto;gap:10px;align-items:end;margin:10px 0">
<input type="hidden" name="action" value="svdp_save_accounting_organization"><input type="hidden" name="conference_id" value="<?php echo esc_attr($organization->id); ?>"><?php wp_nonce_field('svdp_save_accounting_organization'); ?>
<strong><?php echo esc_html($organization->name); ?></strong><label>Billing Email<input type="email" name="billing_email" value="<?php echo esc_attr($organization->billing_email); ?>"></label><label>QuickBooks Customer Name<input type="text" name="quickbooks_customer_name" value="<?php echo esc_attr($organization->quickbooks_customer_name); ?>"></label><button class="button">Save</button></form><?php endforeach; ?>

<h2>Recent Accounting Batches</h2>
<table class="widefat striped"><thead><tr><th>Batch</th><th>Cutoff</th><th>Status</th><th>Statements</th><th>Invoices</th><th>Total</th><th>Email</th><th>Actions</th></tr></thead><tbody>
<?php if (!$batches): ?><tr><td colspan="8">No accounting batches yet.</td></tr><?php endif; foreach ($batches as $batch): ?><tr><td><?php echo esc_html($batch->batch_key); ?></td><td><?php echo esc_html($batch->cutoff_date); ?></td><td><?php echo esc_html($batch->status); ?></td><td><?php echo esc_html($batch->statement_count); ?></td><td><?php echo esc_html($batch->invoice_count); ?></td><td>$<?php echo esc_html(number_format((float)$batch->total_amount,2)); ?></td><td><?php echo esc_html($batch->email_status); ?></td><td>
<?php if ($batch->iif_file_path): ?><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=svdp_download_accounting_file&batch_id=' . $batch->id . '&type=iif'), 'svdp_download_accounting_file_' . $batch->id . '_iif')); ?>">IIF</a> <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=svdp_download_accounting_file&batch_id=' . $batch->id . '&type=manifest'), 'svdp_download_accounting_file_' . $batch->id . '_manifest')); ?>">Manifest</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline"><input type="hidden" name="action" value="svdp_email_accounting_batch"><input type="hidden" name="batch_id" value="<?php echo esc_attr($batch->id); ?>"><?php wp_nonce_field('svdp_email_accounting_batch'); ?><button class="button">Re-email</button></form><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div>
