<?php
$voucher_type = $voucher['voucher_type'] ?? 'clothing';

if ($voucher_type === 'furniture') {
    include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/cashier/partials/voucher-detail-furniture.php';
    return;
}

$household_total = intval($voucher['adults']) + intval($voucher['children']);
$items_per_person = $voucher['created_by'] === 'Cashier' ? 3 : 7;
$max_adult_items = intval($voucher['adults']) * $items_per_person;
$max_child_items = intval($voucher['children']) * $items_per_person;
$max_total_items = !empty($voucher['voucher_items_count']) ? intval($voucher['voucher_items_count']) : ($household_total * $items_per_person);
$toggle_prefix = 'voucher-' . intval($voucher['id']);
$correction_status = $voucher['stored_status'] ?? $voucher['status'];
$show_redeem_panel = $voucher['status'] === 'Active';
$show_coat_panel = in_array($voucher['status'], ['Active', 'Redeemed'], true)
    && $voucher['coat_eligible']
    && $voucher['coat_status'] !== 'Issued';
$coat_copy = SVDP_Voucher_Copy::get_coat_copy();
$correction_reasons = SVDP_Override_Reason::get_active();
?>
<div
    class="svdp-cashier-detail"
    data-current-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
    hx-get="<?php echo esc_url(rest_url('svdp/v1/cashier/vouchers/' . intval($voucher['id']))); ?>"
    hx-trigger="svdp:detail-refresh from:body, every 30s"
    hx-target="this"
    hx-swap="outerHTML"
>
    <div class="svdp-cashier-detail-header">
        <div>
            <h2><?php echo esc_html($voucher['first_name'] . ' ' . $voucher['last_name']); ?></h2>
            <p><?php echo esc_html($voucher['conference_name']); ?> • DOB <?php echo esc_html(date('m/d/Y', strtotime($voucher['dob']))); ?></p>
        </div>
        <span class="svdp-status-badge svdp-badge-<?php echo esc_attr(strtolower($voucher['status'])); ?>">
            <?php echo esc_html($voucher['status']); ?>
        </span>
    </div>

    <div class="svdp-cashier-detail-grid">
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Household</span>
            <span class="svdp-detail-value"><?php echo esc_html($voucher['adults']); ?> adults, <?php echo esc_html($voucher['children']); ?> children</span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Items Allowed</span>
            <span class="svdp-detail-value"><?php echo esc_html($max_total_items); ?></span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Created</span>
            <span class="svdp-detail-value"><?php echo esc_html($voucher['voucher_created_date']); ?></span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Created By</span>
            <span class="svdp-detail-value"><?php echo esc_html($voucher['created_by']); ?></span>
        </div>
    </div>

    <?php if (!empty($voucher['override_note'])): ?>
        <div class="svdp-coat-info not-eligible">
            Override Note: <?php echo esc_html($voucher['override_note']); ?>
        </div>
    <?php endif; ?>

    <section class="svdp-cashier-info-panel">
        <div class="svdp-cashier-panel-header">
            <div>
                <h3>Correct Voucher</h3>
                <p>Update voucher fields with manager authorization and an audit reason.</p>
            </div>
            <button type="button" class="svdp-btn svdp-btn-secondary" @click="$store.cashier.activePanel = $store.cashier.activePanel === 'correct-<?php echo esc_attr($toggle_prefix); ?>' ? null : 'correct-<?php echo esc_attr($toggle_prefix); ?>'">
                Correct Voucher
            </button>
        </div>

        <form
            class="svdp-form"
            data-cashier-action="voucher-correct"
            data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
            x-show="$store.cashier.activePanel === 'correct-<?php echo esc_attr($toggle_prefix); ?>'"
            x-transition
        >
            <div class="svdp-form-row">
                <div class="svdp-form-group">
                    <label>Adults *</label>
                    <input type="number" name="adults" min="0" value="<?php echo esc_attr($voucher['adults']); ?>" required>
                </div>
                <div class="svdp-form-group">
                    <label>Children *</label>
                    <input type="number" name="children" min="0" value="<?php echo esc_attr($voucher['children']); ?>" required>
                </div>
            </div>

            <div class="svdp-form-row">
                <div class="svdp-form-group">
                    <label>Date of Birth *</label>
                    <input type="date" name="dob" value="<?php echo esc_attr($voucher['dob']); ?>" required>
                </div>
                <div class="svdp-form-group">
                    <label>Voucher Created Date *</label>
                    <input type="date" name="voucher_created_date" value="<?php echo esc_attr($voucher['voucher_created_date']); ?>" required>
                </div>
            </div>

            <div class="svdp-form-row">
                <div class="svdp-form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <?php foreach (['Active', 'Redeemed', 'Expired'] as $status_option): ?>
                            <option value="<?php echo esc_attr($status_option); ?>" <?php selected($correction_status, $status_option); ?>>
                                <?php echo esc_html($status_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-form-group">
                    <label>Reason *</label>
                    <select name="reasonId" required>
                        <option value="">Select a reason...</option>
                        <?php foreach ($correction_reasons as $reason): ?>
                            <option value="<?php echo esc_attr(intval($reason->id)); ?>">
                                <?php echo esc_html($reason->reason_text); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="svdp-form-row">
                <div class="svdp-form-group">
                    <label>Manager Name *</label>
                    <input type="text" name="managerName" autocomplete="off" required>
                </div>
                <div class="svdp-form-group">
                    <label>Manager Code *</label>
                    <input type="text" name="managerCode" maxlength="4" autocomplete="off" required>
                </div>
            </div>

            <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
            <button type="submit" class="svdp-btn svdp-btn-primary">Submit Correction</button>
        </form>
    </section>

    <section class="svdp-cashier-info-panel">
        <h3>Recent Corrections</h3>
        <?php if (!empty($voucher['recent_corrections'])): ?>
            <?php foreach ($voucher['recent_corrections'] as $correction): ?>
                <p>
                    <?php echo esc_html($correction['human_summary']); ?>
                    <br><small><?php echo esc_html(date('m/d/Y g:i A', strtotime($correction['created_at']))); ?></small>
                </p>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No recent corrections.</p>
        <?php endif; ?>
    </section>

    <?php if ($voucher['status'] === 'Redeemed'): ?>
        <div class="svdp-cashier-info-panel">
            <h3>Redemption Summary</h3>
            <p>Redeemed on <?php echo esc_html($voucher['redeemed_date']); ?>.</p>
            <p>
                Items: <?php echo esc_html(intval($voucher['items_adult_redeemed']) + intval($voucher['items_children_redeemed'])); ?>
                (<?php echo esc_html($voucher['items_adult_redeemed']); ?> adult, <?php echo esc_html($voucher['items_children_redeemed']); ?> child)
            </p>
            <?php if ($voucher['redemption_total_value'] !== null): ?>
                <p>Estimated value: $<?php echo esc_html(number_format((float) $voucher['redemption_total_value'], 2)); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($voucher['coat_status'] === 'Issued' && !empty($voucher['coat_issued_date'])): ?>
        <div class="svdp-coat-info issued">
            <?php echo esc_html(SVDP_Voucher_Copy::format($coat_copy['coatsIssuedTemplate'], [$voucher['coat_adults_issued'], $voucher['coat_children_issued'], $voucher['coat_issued_date']])); ?>
        </div>
    <?php elseif (!$voucher['coat_eligible']): ?>
        <div class="svdp-coat-info not-eligible">
            <?php echo esc_html(SVDP_Voucher_Copy::format($coat_copy['notEligibleUntilTemplate'], [$voucher['coat_eligible_after']])); ?>
        </div>
    <?php else: ?>
        <div class="svdp-coat-info eligible"><?php echo esc_html($coat_copy['availableMessage']); ?></div>
    <?php endif; ?>

    <?php if ($show_redeem_panel || $show_coat_panel): ?>
        <div class="svdp-cashier-action-stack">
            <?php if ($show_redeem_panel): ?>
                <section class="svdp-cashier-info-panel">
                    <div class="svdp-cashier-panel-header">
                        <div>
                            <h3>Redeem Voucher</h3>
                            <p>Keep the same clothing checkout rules while staying inside the live shell.</p>
                        </div>
                        <button type="button" class="svdp-btn svdp-btn-secondary" @click="$store.cashier.activePanel = $store.cashier.activePanel === 'redeem-<?php echo esc_attr($toggle_prefix); ?>' ? null : 'redeem-<?php echo esc_attr($toggle_prefix); ?>'">
                            Enter Items
                        </button>
                    </div>

                    <form
                        class="svdp-form"
                        data-cashier-action="redeem"
                        data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
                        data-max-total="<?php echo esc_attr($max_total_items); ?>"
                        x-show="$store.cashier.activePanel === 'redeem-<?php echo esc_attr($toggle_prefix); ?>'"
                        x-transition
                    >
                        <div class="svdp-form-row">
                            <div class="svdp-form-group">
                                <label>Adult Items Provided *</label>
                                <input type="number" name="items_adult" min="0" max="<?php echo esc_attr($max_adult_items); ?>" value="0" required>
                                <small class="svdp-help-text">Maximum: <?php echo esc_html($max_adult_items); ?> adult items</small>
                            </div>
                            <div class="svdp-form-group">
                                <label>Child Items Provided *</label>
                                <input type="number" name="items_children" min="0" max="<?php echo esc_attr($max_child_items); ?>" value="0" required>
                                <small class="svdp-help-text">Maximum: <?php echo esc_html($max_child_items); ?> child items</small>
                            </div>
                        </div>

                        <div class="svdp-cashier-inline-summary">
                            <span>Maximum total items: <?php echo esc_html($max_total_items); ?></span>
                            <span data-redemption-total>Current total: 0</span>
                            <span data-redemption-value>Estimated value: $0.00</span>
                        </div>

                        <div class="svdp-inline-error" data-inline-error style="display: none;"></div>

                        <button type="submit" class="svdp-btn svdp-btn-primary">Mark as Redeemed</button>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($show_coat_panel): ?>
                <section class="svdp-cashier-info-panel">
                    <div class="svdp-cashier-panel-header">
                        <div>
                            <h3><?php echo esc_html($coat_copy['issueWinterCoatsHeading']); ?></h3>
                            <p><?php echo esc_html($coat_copy['issueWinterCoatsDescription']); ?></p>
                        </div>
                        <button type="button" class="svdp-btn svdp-btn-secondary" @click="$store.cashier.activePanel = $store.cashier.activePanel === 'coat-<?php echo esc_attr($toggle_prefix); ?>' ? null : 'coat-<?php echo esc_attr($toggle_prefix); ?>'">
                            <?php echo esc_html($coat_copy['issueCoatButton']); ?>
                        </button>
                    </div>

                    <form
                        class="svdp-form"
                        data-cashier-action="coat"
                        data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
                        x-show="$store.cashier.activePanel === 'coat-<?php echo esc_attr($toggle_prefix); ?>'"
                        x-transition
                    >
                        <div class="svdp-form-row">
                            <div class="svdp-form-group">
                                <label><?php echo esc_html($coat_copy['adultCoatsLabel']); ?></label>
                                <input type="number" name="adults" min="0" max="<?php echo esc_attr($voucher['adults']); ?>" value="<?php echo esc_attr($voucher['adults']); ?>" required>
                                <small class="svdp-help-text"><?php echo esc_html(SVDP_Voucher_Copy::format($coat_copy['adultCoatsMaximumTemplate'], [$voucher['adults']])); ?></small>
                            </div>
                            <div class="svdp-form-group">
                                <label><?php echo esc_html($coat_copy['childCoatsLabel']); ?></label>
                                <input type="number" name="children" min="0" max="<?php echo esc_attr($voucher['children']); ?>" value="<?php echo esc_attr($voucher['children']); ?>" required>
                                <small class="svdp-help-text"><?php echo esc_html(SVDP_Voucher_Copy::format($coat_copy['childCoatsMaximumTemplate'], [$voucher['children']])); ?></small>
                            </div>
                        </div>

                        <div class="svdp-cashier-inline-summary">
                            <span data-coat-total><?php echo esc_html(SVDP_Voucher_Copy::format($coat_copy['totalCoatsTemplate'], [$household_total])); ?></span>
                        </div>

                        <div class="svdp-inline-error" data-inline-error style="display: none;"></div>

                        <button type="submit" class="svdp-btn svdp-btn-primary"><?php echo esc_html($coat_copy['issueCoatsButton']); ?></button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($voucher['status'] === 'Expired'): ?>
        <div class="svdp-cashier-info-panel">
            <h3>Voucher Expired</h3>
            <p>This clothing voucher is outside the 30-day active window and is now read-only.</p>
        </div>
    <?php endif; ?>
</div>
