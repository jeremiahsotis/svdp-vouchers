<?php
$household_total = intval($voucher['adults']) + intval($voucher['children']);
$can_mutate_furniture = !empty($can_mutate_furniture);
$unavailable_reasons = is_array($unavailable_reasons ?? null) ? $unavailable_reasons : [];
$summary = is_array($voucher['fulfillment_summary'] ?? null) ? $voucher['fulfillment_summary'] : [
    'requested_units' => 0,
    'fulfilled_units' => 0,
    'unavailable_units' => 0,
    'resolved_units' => 0,
    'actual_total' => 0,
    'estimated_total' => null,
    'ready_to_finalize' => false,
];
$lines = is_array($voucher['fulfillment_lines'] ?? null) ? $voucher['fulfillment_lines'] : [];
$is_mutable = $can_mutate_furniture && ($voucher['cashier_status'] ?? '') === 'ready' && ($voucher['stored_status'] ?? '') === 'Active';
$detail_state_label = $voucher['workflow_status'] === 'completed'
    ? ((intval($summary['unavailable_units']) > 0) ? 'Finalized with Unavailable Items' : 'Finalized')
    : (!empty($summary['ready_to_finalize']) ? 'Ready to Finalize' : 'Fulfillment in Progress');
$delivery_copy = SVDP_Voucher_Copy::get_delivery_copy();
$document_copy = SVDP_Voucher_Copy::get_document_copy();
?>
<div
    class="svdp-cashier-detail svdp-cashier-detail-furniture svdp-shared-fulfillment"
    data-current-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
    hx-get="<?php echo esc_url(rest_url('svdp/v1/cashier/vouchers/' . intval($voucher['id']))); ?>"
    hx-trigger="<?php echo $is_mutable ? 'svdp:detail-refresh from:body' : 'svdp:detail-refresh from:body, every 30s'; ?>"
    hx-target="this"
    hx-swap="outerHTML"
>
    <div class="svdp-cashier-detail-header">
        <div>
            <div class="svdp-detail-status-line">
                <span class="svdp-card-status-icon"><?php echo esc_html($voucher['cashier_status_icon']); ?></span>
                <span class="svdp-card-status-label"><?php echo esc_html($voucher['cashier_status_label']); ?></span>
                <span class="svdp-card-status-date"><?php echo esc_html($voucher['cashier_status_date_label']); ?></span>
            </div>
            <h2><?php echo esc_html($voucher['first_name'] . ' ' . $voucher['last_name']); ?></h2>
            <p><?php echo esc_html($voucher['voucher_type_label']); ?> • <?php echo esc_html($voucher['conference_name']); ?> • DOB <?php echo esc_html(date('m/d/Y', strtotime($voucher['dob']))); ?></p>
        </div>
    </div>

    <div class="svdp-cashier-detail-grid">
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Household</span>
            <span class="svdp-detail-value"><?php echo esc_html($voucher['adults']); ?> adults, <?php echo esc_html($voucher['children']); ?> children</span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Requested Units</span>
            <span class="svdp-detail-value"><?php echo esc_html(intval($summary['requested_units'])); ?></span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Redeem By</span>
            <span class="svdp-detail-value"><?php echo esc_html(date('m/d/Y', strtotime($voucher['expiration_date']))); ?></span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Delivery</span>
            <span class="svdp-detail-value"><?php echo esc_html(!empty($voucher['delivery_required']) ? $delivery_copy['yesLabel'] : $delivery_copy['noLabel']); ?></span>
        </div>
    </div>

    <div class="svdp-cashier-inline-summary">
        <span><?php echo esc_html($detail_state_label); ?></span>
        <span>Resolved: <?php echo esc_html(intval($summary['resolved_units'])); ?> of <?php echo esc_html(intval($summary['requested_units'])); ?></span>
        <span>Actual Redemption Total: $<?php echo esc_html(number_format((float) $summary['actual_total'], 2)); ?></span>
    </div>

    <?php if (!empty($voucher['request_group_id'])): ?>
        <section class="svdp-cashier-info-panel">
            <h3>Request Group</h3>
            <p>Part of Request Group RG-<?php echo esc_html(intval($voucher['request_group_id'])); ?>. Each voucher in the group remains independently redeemable and expirable.</p>
        </section>
    <?php endif; ?>

    <?php if (!empty($voucher['delivery_required']) || !empty($voucher['delivery_address_display'])): ?>
        <section class="svdp-cashier-info-panel">
            <h3><?php echo esc_html($delivery_copy['deliveryDetailsHeading']); ?></h3>
            <?php if (!empty($voucher['delivery_required']) && !empty($voucher['delivery_address_display'])): ?>
                <p><?php echo esc_html($voucher['delivery_address_display']); ?></p>
                <?php if (empty($voucher['delivery_address_verified'])): ?>
                    <p><small style="color:#7a5200;"><?php echo esc_html($delivery_copy['addressNotVerified']); ?></small></p>
                <?php endif; ?>
            <?php else: ?>
                <p><?php echo esc_html($delivery_copy['pickupRequested']); ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if (empty($lines)): ?>
        <section class="svdp-cashier-info-panel">
            <h3>Fulfillment Workspace</h3>
            <p>No requested lines were stored on this voucher.</p>
        </section>
    <?php else: ?>
        <form
            class="svdp-form svdp-shared-fulfillment-form"
            data-cashier-action="shared-fulfillment"
            data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
            data-readonly="<?php echo $is_mutable ? '0' : '1'; ?>"
        >
            <section class="svdp-cashier-info-panel">
                <div class="svdp-cashier-panel-header">
                    <div>
                        <h3>Fulfillment Workspace</h3>
                        <p>Enter fulfilled quantities, price rows, and unavailable quantities for this voucher.</p>
                    </div>
                </div>

                <div class="svdp-fulfillment-lines">
                    <?php foreach ($lines as $line): ?>
                        <article
                            class="svdp-fulfillment-line"
                            data-fulfillment-line
                            data-line-id="<?php echo esc_attr($line['id']); ?>"
                            data-requested-quantity="<?php echo esc_attr($line['requested_quantity']); ?>"
                        >
                            <div class="svdp-furniture-request-item-header">
                                <div>
                                    <h4><?php echo esc_html($line['requested_name']); ?></h4>
                                    <p>
                                        <?php echo esc_html($line['requested_group'] ?: $voucher['voucher_type_label']); ?>
                                        <?php if (!empty($line['cashier_guidance'])): ?>
                                            • <?php echo esc_html($line['cashier_guidance']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="svdp-card-badges">
                                    <span class="svdp-type-badge">Requested: <?php echo esc_html(intval($line['requested_quantity'])); ?></span>
                                    <span class="svdp-type-badge svdp-type-workflow" data-line-status><?php echo esc_html(ucfirst(str_replace('_', ' ', $line['resolution_status']))); ?></span>
                                </div>
                            </div>

                            <div class="svdp-fulfillment-row-head" aria-hidden="true">
                                <span>Price Each</span>
                                <span>Quantity</span>
                                <span>Line Total</span>
                                <span></span>
                            </div>

                            <div class="svdp-fulfillment-entries" data-fulfillment-entries>
                                <?php
                                $entries = !empty($line['entries']) ? $line['entries'] : [['unit_price' => '', 'fulfilled_quantity' => '', 'line_total' => 0]];
                                foreach ($entries as $entry):
                                ?>
                                    <div class="svdp-fulfillment-entry" data-fulfillment-entry>
                                        <label>
                                            <span>Price Each</span>
                                            <input type="number" inputmode="decimal" min="0.01" step="0.01" data-unit-price value="<?php echo esc_attr($entry['unit_price'] !== '' ? number_format((float) $entry['unit_price'], 2, '.', '') : ''); ?>" <?php disabled(!$is_mutable); ?>>
                                        </label>
                                        <label>
                                            <span>Fulfilled Quantity</span>
                                            <input type="number" min="0" step="1" data-fulfilled-quantity value="<?php echo esc_attr($entry['fulfilled_quantity']); ?>" <?php disabled(!$is_mutable); ?>>
                                        </label>
                                        <div class="svdp-line-total" data-line-total>$<?php echo esc_html(number_format((float) ($entry['line_total'] ?? 0), 2)); ?></div>
                                        <button type="button" class="svdp-btn svdp-btn-secondary svdp-remove-row" data-remove-fulfillment-row <?php disabled(!$is_mutable); ?>>Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($is_mutable): ?>
                                <button type="button" class="svdp-btn svdp-btn-secondary" data-add-fulfillment-row>Add another price</button>
                            <?php endif; ?>

                            <div class="svdp-form-row svdp-unavailable-row">
                                <div class="svdp-form-group">
                                    <label>Item Unavailable</label>
                                    <input type="number" min="0" step="1" data-unavailable-quantity value="<?php echo esc_attr(intval($line['unavailable_quantity'])); ?>" <?php disabled(!$is_mutable); ?>>
                                </div>
                                <div class="svdp-form-group" data-unavailable-reason-wrap>
                                    <label>Unavailable Reason</label>
                                    <select data-unavailable-reason <?php disabled(!$is_mutable); ?>>
                                        <option value="">Select a reason...</option>
                                        <?php foreach ($unavailable_reasons as $reason): ?>
                                            <option value="<?php echo esc_attr(intval($reason['id'])); ?>" <?php selected(intval($line['unavailable_reason_id']), intval($reason['id'])); ?>>
                                                <?php echo esc_html($reason['reason_text']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="svdp-cashier-inline-summary">
                                <span data-line-resolved>Resolved: <?php echo esc_html(intval($line['resolved_quantity'])); ?> of <?php echo esc_html(intval($line['requested_quantity'])); ?></span>
                                <span data-line-subtotal>Subtotal: $<?php echo esc_html(number_format((float) $line['subtotal'], 2)); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="svdp-cashier-info-panel svdp-fulfillment-summary-panel">
                <h3>Fulfillment Summary</h3>
                <div class="svdp-cashier-detail-grid">
                    <div class="svdp-detail-item">
                        <span class="svdp-detail-label">Requested Units</span>
                        <span class="svdp-detail-value" data-summary-requested><?php echo esc_html(intval($summary['requested_units'])); ?></span>
                    </div>
                    <div class="svdp-detail-item">
                        <span class="svdp-detail-label">Fulfilled Units</span>
                        <span class="svdp-detail-value" data-summary-fulfilled><?php echo esc_html(intval($summary['fulfilled_units'])); ?></span>
                    </div>
                    <div class="svdp-detail-item">
                        <span class="svdp-detail-label">Unavailable Units</span>
                        <span class="svdp-detail-value" data-summary-unavailable><?php echo esc_html(intval($summary['unavailable_units'])); ?></span>
                    </div>
                    <div class="svdp-detail-item">
                        <span class="svdp-detail-label">Actual Redemption Total</span>
                        <span class="svdp-detail-value" data-summary-actual-total>$<?php echo esc_html(number_format((float) $summary['actual_total'], 2)); ?></span>
                    </div>
                    <?php if ($summary['estimated_total'] !== null): ?>
                        <div class="svdp-detail-item">
                            <span class="svdp-detail-label">Estimated Conference / Partner Cost</span>
                            <span class="svdp-detail-value">$<?php echo esc_html(number_format((float) $summary['estimated_total'], 2)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="svdp-detail-item">
                        <span class="svdp-detail-label">Readiness</span>
                        <span class="svdp-detail-value" data-summary-readiness><?php echo !empty($summary['ready_to_finalize']) ? 'Ready to Finalize' : 'Unresolved'; ?></span>
                    </div>
                </div>

                <?php if (($voucher['cashier_status'] ?? '') === 'expired'): ?>
                    <div class="svdp-inline-error" style="display: block;">Expired vouchers can be viewed but cannot be fulfilled or finalized through the ordinary cashier workflow.</div>
                <?php elseif (!$can_mutate_furniture): ?>
                    <div class="svdp-inline-error" style="display: block;">This user can review fulfillment details but cannot save or finalize this voucher.</div>
                <?php elseif (($voucher['cashier_status'] ?? '') === 'redeemed'): ?>
                    <div class="svdp-document-links">
                        <?php if (!empty($voucher['receipt_file_url'])): ?>
                            <a class="svdp-document-link" href="<?php echo esc_url($voucher['receipt_file_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($document_copy['openNeighborReceiptLabel']); ?></a>
                        <?php endif; ?>
                        <?php if (!empty($voucher['invoice_file_url'])): ?>
                            <a class="svdp-document-link" href="<?php echo esc_url($voucher['invoice_file_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($document_copy['openConferenceInvoiceLabel']); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($voucher['finalization_note'])): ?>
                        <div class="svdp-furniture-resolution-note">
                            <strong>Internal Finalization Note:</strong> <?php echo esc_html($voucher['finalization_note']); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="svdp-form-group">
                        <label>Optional Internal Finalization Note</label>
                        <textarea name="finalizationNote" rows="3" data-finalization-note></textarea>
                        <small class="svdp-help-text">Visible only to authorized store and program users.</small>
                    </div>

                    <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
                    <div class="svdp-fulfillment-actions">
                        <button type="submit" class="svdp-btn svdp-btn-secondary" data-fulfillment-submit="save">Save Progress</button>
                        <button type="submit" class="svdp-btn svdp-btn-primary" data-fulfillment-submit="finalize" <?php disabled(empty($summary['ready_to_finalize'])); ?>>Finalize Voucher</button>
                    </div>
                <?php endif; ?>
            </section>
        </form>
    <?php endif; ?>
</div>
