<?php
$cashier_copy = SVDP_Voucher_Copy::get_cashier_copy();
$showing_label = intval($stats['showing']);
if (isset($stats['matching']) && intval($stats['matching']) !== $showing_label) {
    $showing_label = SVDP_Voucher_Copy::format($cashier_copy['showingOfTemplate'], [$showing_label, intval($stats['matching'])]);
}
$remaining_count = max(0, intval($filtered_total ?? 0) - intval($visible_count ?? 0));
$show_more_label = $remaining_count > 0
    ? SVDP_Voucher_Copy::format($cashier_copy['showMoreWithCountTemplate'], [min($remaining_count, SVDP_Cashier_Shell::VISIBLE_INCREMENT)])
    : $cashier_copy['showMore'];
?>
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
        <span class="svdp-stat-value"><?php echo esc_html($showing_label); ?></span>
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
    <?php if (!empty($has_more)): ?>
        <button
            type="button"
            class="svdp-btn svdp-btn-secondary svdp-cashier-show-more"
            data-cashier-show-more
            data-next-visible-count="<?php echo esc_attr($next_visible_count); ?>"
        >
            <?php echo esc_html($show_more_label); ?>
        </button>
    <?php endif; ?>
<?php endif; ?>
