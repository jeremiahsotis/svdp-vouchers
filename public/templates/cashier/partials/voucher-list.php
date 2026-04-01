<div class="svdp-cashier-stats">
    <div class="svdp-stat-item">
        <span class="svdp-stat-label">Active Vouchers</span>
        <span class="svdp-stat-value"><?php echo esc_html($stats['active']); ?></span>
    </div>
    <div class="svdp-stat-item">
        <span class="svdp-stat-label">Redeemed Today</span>
        <span class="svdp-stat-value"><?php echo esc_html($stats['redeemed_today']); ?></span>
    </div>
    <div class="svdp-stat-item">
        <span class="svdp-stat-label">Coats Available</span>
        <span class="svdp-stat-value"><?php echo esc_html($stats['coat_available']); ?></span>
    </div>
    <div class="svdp-stat-item">
        <span class="svdp-stat-label">Showing</span>
        <span class="svdp-stat-value"><?php echo esc_html($stats['showing']); ?></span>
    </div>
</div>

<?php if (empty($vouchers)): ?>
    <div class="svdp-empty-state">
        <div class="svdp-empty-icon">📋</div>
        <div class="svdp-empty-text">No vouchers match the current filters.</div>
    </div>
<?php else: ?>
    <div class="svdp-cashier-voucher-list">
        <?php foreach ($vouchers as $voucher): ?>
            <?php include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/cashier/partials/voucher-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
