<?php
$is_selected = intval($selected_id) === intval($voucher['id']);
$household_total = intval($voucher['adults']) + intval($voucher['children']);
$status_class = strtolower($voucher['status']);
$voucher_type = $voucher['voucher_type'] ?? 'clothing';
$is_furniture = $voucher_type === 'furniture';
$detail_url = rest_url('svdp/v1/cashier/vouchers/' . intval($voucher['id']));
$item_progress = $voucher['item_progress'] ?? null;
$remaining_items = intval($voucher['remaining_items'] ?? ($item_progress['requested'] ?? 0));
?>
<button
    type="button"
    class="svdp-cashier-list-card svdp-card-<?php echo esc_attr($status_class); ?><?php echo $is_selected ? ' is-selected' : ''; ?><?php echo $is_furniture ? ' svdp-card-furniture' : ''; ?>"
    data-voucher-card
    data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
    hx-get="<?php echo esc_url($detail_url); ?>"
    hx-target="#svdpCashierDetailPanel"
    hx-swap="innerHTML"
>
    <div class="svdp-cashier-list-card-header">
        <div>
            <div class="svdp-card-name"><?php echo esc_html($voucher['first_name'] . ' ' . $voucher['last_name']); ?></div>
            <div class="svdp-card-subtitle"><?php echo esc_html($voucher['conference_name']); ?></div>
        </div>
        <div class="svdp-card-badges">
            <span class="svdp-type-badge svdp-type-<?php echo esc_attr($voucher_type); ?>">
                <?php echo esc_html($voucher['voucher_type_label'] ?? ucfirst($voucher_type)); ?>
            </span>
            <?php if ($is_furniture): ?>
                <span class="svdp-type-badge svdp-type-workflow">
                    <?php echo esc_html($voucher['workflow_status_label'] ?? 'Submitted'); ?>
                </span>
            <?php endif; ?>
            <span class="svdp-status-badge svdp-badge-<?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($voucher['status']); ?>
            </span>
        </div>
    </div>

    <?php if ($is_furniture): ?>
        <div class="svdp-cashier-list-card-grid">
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">DOB</span>
                <span class="svdp-detail-value"><?php echo esc_html(date('m/d/Y', strtotime($voucher['dob']))); ?></span>
            </div>
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">Household</span>
                <span class="svdp-detail-value"><?php echo esc_html($household_total); ?> people</span>
            </div>
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">Delivery</span>
                <span class="svdp-detail-value"><?php echo esc_html(!empty($voucher['delivery_required']) ? 'Yes' : 'No'); ?></span>
            </div>
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">Items</span>
                <span class="svdp-detail-value"><?php echo esc_html(intval($voucher['voucher_items_count'])); ?> requested</span>
            </div>
        </div>

        <?php if ($item_progress): ?>
            <div class="svdp-cashier-inline-summary">
                <span>
                    <?php
                    echo esc_html(
                        intval($item_progress['total']) . ' items • ' .
                        intval($item_progress['completed']) . ' completed • ' .
                        intval($item_progress['cancelled']) . ' cancelled • ' .
                        $remaining_items . ' remaining'
                    );
                    ?>
                </span>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="svdp-cashier-list-card-grid">
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">DOB</span>
                <span class="svdp-detail-value"><?php echo esc_html(date('m/d/Y', strtotime($voucher['dob']))); ?></span>
            </div>
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">Household</span>
                <span class="svdp-detail-value"><?php echo esc_html($household_total); ?> people</span>
            </div>
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">Created</span>
                <span class="svdp-detail-value"><?php echo esc_html($voucher['voucher_created_date']); ?></span>
            </div>
            <div class="svdp-detail-item">
                <span class="svdp-detail-label">Coat</span>
                <span class="svdp-detail-value">
                    <?php
                    if ($voucher['coat_status'] === 'Issued') {
                        echo esc_html('Issued');
                    } elseif ($voucher['coat_eligible']) {
                        echo esc_html('Available');
                    } else {
                        echo esc_html('Not Eligible');
                    }
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</button>
