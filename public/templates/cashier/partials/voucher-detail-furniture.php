<?php
$household_total = intval($voucher['adults']) + intval($voucher['children']);
$toggle_prefix = 'voucher-' . intval($voucher['id']);
$item_progress = $voucher['item_progress'] ?? [
    'total' => intval($voucher['voucher_items_count']),
    'requested' => intval($voucher['voucher_items_count']),
    'completed' => 0,
    'cancelled' => 0,
];
$correction_status = $voucher['stored_status'] ?? $voucher['status'];
$can_mutate_furniture = !empty($can_mutate_furniture);
$furniture_catalog_items = is_array($furniture_catalog_items ?? null) ? $furniture_catalog_items : [];
$cancellation_reasons = is_array($cancellation_reasons ?? null) ? $cancellation_reasons : [];
$pricing_copy = SVDP_Voucher_Rules::get_pricing_copy();
$delivery_copy = SVDP_Voucher_Copy::get_delivery_copy();
$document_copy = SVDP_Voucher_Copy::get_document_copy();
$correction_reasons = SVDP_Override_Reason::get_active();
$remaining_items = intval($voucher['remaining_items'] ?? $item_progress['requested']);
$detail_refresh_trigger = $can_mutate_furniture
    ? 'svdp:detail-refresh from:body'
    : 'svdp:detail-refresh from:body, every 30s';
?>
<div
    class="svdp-cashier-detail svdp-cashier-detail-furniture"
    data-current-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
    hx-get="<?php echo esc_url(rest_url('svdp/v1/cashier/vouchers/' . intval($voucher['id']))); ?>"
    hx-trigger="<?php echo esc_attr($detail_refresh_trigger); ?>"
    hx-target="this"
    hx-swap="outerHTML"
>
    <div class="svdp-cashier-detail-header">
        <div>
            <div class="svdp-card-badges">
                <span class="svdp-type-badge svdp-type-furniture"><?php echo esc_html($voucher['voucher_type_label'] ?? 'Furniture'); ?></span>
                <span class="svdp-status-badge svdp-badge-<?php echo esc_attr(strtolower($voucher['status'])); ?>">
                    <?php echo esc_html($voucher['status']); ?>
                </span>
                <span class="svdp-type-badge svdp-type-workflow">
                    <?php echo esc_html($voucher['workflow_status_label'] ?? 'Submitted'); ?>
                </span>
                <span class="svdp-type-badge <?php echo !empty($voucher['delivery_required']) ? 'svdp-type-delivery' : 'svdp-type-pickup'; ?>">
                    <?php echo esc_html(!empty($voucher['delivery_required']) ? $delivery_copy['deliveryLabel'] : $delivery_copy['pickupLabel']); ?>
                </span>
            </div>
            <h2><?php echo esc_html($voucher['first_name'] . ' ' . $voucher['last_name']); ?></h2>
            <p><?php echo esc_html($voucher['conference_name']); ?> • DOB <?php echo esc_html(date('m/d/Y', strtotime($voucher['dob']))); ?></p>
        </div>
    </div>

    <div class="svdp-cashier-detail-grid">
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Household</span>
            <span class="svdp-detail-value"><?php echo esc_html($voucher['adults']); ?> adults, <?php echo esc_html($voucher['children']); ?> children</span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Requested Items</span>
            <span class="svdp-detail-value"><?php echo esc_html(intval($voucher['voucher_items_count'])); ?></span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Created</span>
            <span class="svdp-detail-value"><?php echo esc_html($voucher['voucher_created_date']); ?></span>
        </div>
        <div class="svdp-detail-item">
            <span class="svdp-detail-label">Delivery</span>
            <span class="svdp-detail-value"><?php echo esc_html(!empty($voucher['delivery_required']) ? $delivery_copy['yesLabel'] : $delivery_copy['noLabel']); ?></span>
        </div>
    </div>

    <div class="svdp-cashier-inline-summary">
        <span><?php echo esc_html(intval($item_progress['total'])); ?> items</span>
        <span><?php echo esc_html(intval($item_progress['completed'])); ?> completed</span>
        <span><?php echo esc_html(intval($item_progress['cancelled'])); ?> cancelled</span>
        <span><?php echo esc_html($remaining_items); ?> remaining</span>
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

            <?php if (!empty($voucher['delivery_required'])): ?>
                <div class="svdp-form-row">
                    <div class="svdp-form-group">
                        <label>Delivery Address Line 1</label>
                        <input type="text" name="delivery_address_line_1" value="<?php echo esc_attr($voucher['delivery_address']['line_1'] ?? ''); ?>">
                    </div>
                    <div class="svdp-form-group">
                        <label>Delivery Address Line 2</label>
                        <input type="text" name="delivery_address_line_2" value="<?php echo esc_attr($voucher['delivery_address']['line_2'] ?? ''); ?>">
                    </div>
                </div>

                <div class="svdp-form-row">
                    <div class="svdp-form-group">
                        <label>Delivery City</label>
                        <input type="text" name="delivery_city" value="<?php echo esc_attr($voucher['delivery_address']['city'] ?? ''); ?>">
                    </div>
                    <div class="svdp-form-group">
                        <label>Delivery State</label>
                        <input type="text" name="delivery_state" value="<?php echo esc_attr($voucher['delivery_address']['state'] ?? ''); ?>">
                    </div>
                    <div class="svdp-form-group">
                        <label>Delivery ZIP</label>
                        <input type="text" name="delivery_zip" value="<?php echo esc_attr($voucher['delivery_address']['zip'] ?? ''); ?>">
                    </div>
                </div>
            <?php endif; ?>

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

    <section class="svdp-cashier-info-panel">
        <h3>Request Summary</h3>
        <p><strong><?php echo esc_html($pricing_copy['selectedItemRetailMaximumLabel']); ?>:</strong> <?php echo esc_html($voucher['estimated_total_display']); ?></p>
        <p><strong><?php echo esc_html($pricing_copy['maximumCommitmentLabel']); ?>:</strong> <?php echo esc_html($voucher['estimated_requestor_portion_display']); ?></p>
        <?php if (!empty($voucher['delivery_required'])): ?>
            <p><strong><?php echo esc_html($pricing_copy['deliveryFeeLabel']); ?>:</strong> $<?php echo esc_html(number_format((float) $voucher['delivery_fee'], 2)); ?></p>
        <?php endif; ?>
        <p><strong>Created By:</strong> <?php echo esc_html($voucher['created_by']); ?></p>
        <?php if (!empty($voucher['vincentian_name'])): ?>
            <p><strong>Requester:</strong> <?php echo esc_html($voucher['vincentian_name']); ?><?php if (!empty($voucher['vincentian_email'])): ?> (<?php echo esc_html($voucher['vincentian_email']); ?>)<?php endif; ?></p>
        <?php endif; ?>
    </section>

    <section class="svdp-cashier-info-panel">
        <h3><?php echo esc_html($delivery_copy['deliveryDetailsHeading']); ?></h3>
        <?php if (!empty($voucher['delivery_required']) && !empty($voucher['delivery_address_display'])): ?>
            <p>
                <?php echo esc_html($voucher['delivery_address_display']); ?>
                <?php if (empty($voucher['delivery_address_verified'])): ?>
                    <br><small style="color:#b45309;"><?php echo esc_html($delivery_copy['addressNotVerified']); ?></small>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p><?php echo esc_html($delivery_copy['pickupRequested']); ?></p>
        <?php endif; ?>
    </section>

    <section class="svdp-cashier-info-panel">
        <div class="svdp-cashier-panel-header">
            <div>
                <h3>Item Resolution</h3>
                <p>Resolve each requested item in store-walk order. Complete an item after entering actual price. Photos are optional records.</p>
            </div>
        </div>

        <?php if (empty($voucher['items'])): ?>
            <p>No furniture items were stored on this request.</p>
        <?php else: ?>
            <div class="svdp-furniture-resolution-list">
                <?php foreach ($voucher['items'] as $item): ?>
                    <?php
                    $item_toggle_prefix = $toggle_prefix . '-item-' . intval($item['id']);
                    $status_class = sanitize_html_class('svdp-item-' . strtolower($item['status']));
                    $current_substitution_type = in_array($item['substitution_type'], ['catalog', 'free_text'], true)
                        ? $item['substitution_type']
                        : 'catalog';
                    ?>
                    <article
                        class="svdp-furniture-resolution-card <?php echo esc_attr($status_class); ?>"
                        x-data="{ activeAction: null, substitutionType: '<?php echo esc_attr($current_substitution_type); ?>', pendingPhotoName: '', pendingPhotoPreview: '' }"
                    >
                        <div class="svdp-furniture-request-item-header">
                            <div>
                                <h4><?php echo esc_html($item['requested_item_name']); ?></h4>
                                <p><?php echo esc_html($item['requested_category_label']); ?> • Store-walk order <?php echo esc_html($item['requested_sort_order']); ?></p>
                            </div>
                            <div class="svdp-card-badges">
                                <?php if (!empty($item['has_substitution'])): ?>
                                    <span class="svdp-type-badge svdp-type-substitute"><?php echo esc_html($item['substitution_label']); ?></span>
                                <?php endif; ?>
                                <span class="svdp-status-badge svdp-badge-<?php echo esc_attr(strtolower($item['status'])); ?>">
                                    <?php echo esc_html(ucfirst($item['status'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="svdp-cashier-detail-grid">
                            <div class="svdp-detail-item">
                                <span class="svdp-detail-label">Requested Price</span>
                                <span class="svdp-detail-value"><?php echo esc_html($item['requested_price_display']); ?></span>
                            </div>
                            <div class="svdp-detail-item">
                                <span class="svdp-detail-label">Photos</span>
                                <span class="svdp-detail-value"><?php echo esc_html(intval($item['photo_count'])); ?> uploaded</span>
                            </div>
                            <div class="svdp-detail-item">
                                <span class="svdp-detail-label"><?php echo $item['status'] === 'cancelled' ? 'Cancellation Reason' : 'Actual Price'; ?></span>
                                <span class="svdp-detail-value">
                                    <?php
                                    if ($item['status'] === 'completed' && $item['actual_price_display'] !== '') {
                                        echo esc_html($item['actual_price_display']);
                                    } elseif ($item['status'] === 'cancelled' && $item['cancellation_reason_label'] !== '') {
                                        echo esc_html($item['cancellation_reason_label']);
                                    } else {
                                        echo esc_html('Pending');
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="svdp-detail-item">
                                <span class="svdp-detail-label">Current State</span>
                                <span class="svdp-detail-value">
                                    <?php
                                    if ($item['status'] === 'requested') {
                                        echo esc_html('Needs resolution');
                                    } elseif ($item['status'] === 'completed') {
                                        echo esc_html('Ready for voucher completion');
                                    } else {
                                        echo esc_html('Cancelled');
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($item['has_substitution'])): ?>
                            <div class="svdp-furniture-resolution-note">
                                <strong><?php echo esc_html($item['substitution_label']); ?>:</strong>
                                <?php echo esc_html($item['substitute_item_name']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($item['photos'])): ?>
                            <div class="svdp-furniture-photo-strip">
                                <?php foreach ($item['photos'] as $photo): ?>
                                    <a
                                        class="svdp-furniture-photo"
                                        href="<?php echo esc_url($photo['url']); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <img src="<?php echo esc_url($photo['thumbnail_url']); ?>" alt="<?php echo esc_attr($item['requested_item_name']); ?> photo">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($item['status'] === 'completed' && !empty($item['completion_notes'])): ?>
                            <div class="svdp-furniture-resolution-note">
                                <strong>Completion Notes:</strong> <?php echo esc_html($item['completion_notes']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($item['status'] === 'cancelled' && !empty($item['cancellation_notes'])): ?>
                            <div class="svdp-furniture-resolution-note">
                                <strong>Cancellation Notes:</strong> <?php echo esc_html($item['cancellation_notes']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($can_mutate_furniture && $item['status'] === 'requested'): ?>
                            <div class="svdp-furniture-action-row">
                                <button
                                    type="button"
                                    class="svdp-btn svdp-btn-secondary"
                                    @click="activeAction = activeAction === 'complete' ? null : 'complete'"
                                >
                                    Fulfill Item
                                </button>
                                <button
                                    type="button"
                                    class="svdp-btn svdp-btn-secondary"
                                    @click="activeAction = activeAction === 'substitute' ? null : 'substitute'"
                                >
                                    Substitute
                                </button>
                                <button
                                    type="button"
                                    class="svdp-btn svdp-btn-warning"
                                    @click="activeAction = activeAction === 'cancel' ? null : 'cancel'"
                                >
                                    Cancel Item
                                </button>
                            </div>

                            <div class="svdp-furniture-action-panels">
                                <section class="svdp-furniture-action-panel" x-show="activeAction === 'complete'" x-transition>
                                    <form
                                        class="svdp-form"
                                        data-cashier-action="furniture-photo"
                                        data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
                                        data-item-id="<?php echo esc_attr($item['id']); ?>"
                                        enctype="multipart/form-data"
                                    >
                                        <div class="svdp-form-group">
                                            <label>Add Photo</label>
                                            <input
                                                type="file"
                                                name="photo"
                                                accept="image/*"
                                                @change="
                                                    if (pendingPhotoPreview) { URL.revokeObjectURL(pendingPhotoPreview); }
                                                    pendingPhotoName = $event.target.files.length ? $event.target.files[0].name : '';
                                                    pendingPhotoPreview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : '';
                                                "
                                            >
                                            <small class="svdp-help-text">Photos are optional, and uploaded images are normalized to JPEG, resized, and stored under this voucher item for records.</small>
                                        </div>

                                        <div class="svdp-furniture-upload-preview" x-show="pendingPhotoName">
                                            <div>
                                                <strong x-text="pendingPhotoName"></strong>
                                                <p>Upload this photo to attach it to the item record.</p>
                                            </div>
                                            <template x-if="pendingPhotoPreview">
                                                <img :src="pendingPhotoPreview" alt="Selected furniture photo preview">
                                            </template>
                                        </div>

                                        <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
                                        <button type="submit" class="svdp-btn svdp-btn-secondary">
                                            <?php echo intval($item['photo_count']) > 0 ? 'Add Another Photo' : 'Upload Photo'; ?>
                                        </button>
                                    </form>

                                    <form
                                        class="svdp-form"
                                        data-cashier-action="furniture-complete"
                                        data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
                                        data-item-id="<?php echo esc_attr($item['id']); ?>"
                                        data-photo-count="<?php echo esc_attr(intval($item['photo_count'])); ?>"
                                    >
                                        <div class="svdp-form-group">
                                            <label>Actual Price *</label>
                                            <input type="number" name="actualPrice" min="0.01" step="0.01" inputmode="decimal" required>
                                        </div>

                                        <div class="svdp-form-group">
                                            <label>Completion Notes</label>
                                            <textarea name="completionNotes" rows="3"></textarea>
                                        </div>

                                        <div class="svdp-cashier-inline-summary">
                                            <span>Photos optional: <?php echo esc_html(intval($item['photo_count'])); ?> attached</span>
                                            <span>Actual price required</span>
                                            <?php if (!empty($item['has_substitution'])): ?>
                                                <span><?php echo esc_html($item['substitution_label']); ?> saved</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
                                        <button type="submit" class="svdp-btn svdp-btn-primary">Mark Completed</button>
                                    </form>
                                </section>

                                <section class="svdp-furniture-action-panel" x-show="activeAction === 'substitute'" x-transition>
                                    <form
                                        class="svdp-form"
                                        data-cashier-action="furniture-substitute"
                                        data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
                                        data-item-id="<?php echo esc_attr($item['id']); ?>"
                                    >
                                        <div class="svdp-choice-row">
                                            <label class="svdp-choice-chip">
                                                <input type="radio" name="substitutionType" value="catalog" x-model="substitutionType" <?php checked($current_substitution_type, 'catalog'); ?>>
                                                <span>Catalog Item</span>
                                            </label>
                                            <label class="svdp-choice-chip">
                                                <input type="radio" name="substitutionType" value="free_text" x-model="substitutionType" <?php checked($current_substitution_type, 'free_text'); ?>>
                                                <span>Free Text</span>
                                            </label>
                                        </div>

                                        <div class="svdp-form-group" x-show="substitutionType === 'catalog'">
                                            <label>Substitute From Catalog *</label>
                                            <select name="substituteCatalogItemId">
                                                <option value="">Select a substitute item...</option>
                                                <?php foreach ($furniture_catalog_items as $catalog_item): ?>
                                                    <option value="<?php echo esc_attr(intval($catalog_item->id)); ?>" <?php selected(intval($item['substitute_catalog_item_id']), intval($catalog_item->id)); ?>>
                                                        <?php echo esc_html($catalog_item->name . ' (' . (SVDP_Furniture_Catalog::get_categories()[$catalog_item->category] ?? $catalog_item->category) . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="svdp-form-group" x-show="substitutionType === 'free_text'">
                                            <label>Substitute Item Name *</label>
                                            <input type="text" name="substituteItemName" value="<?php echo esc_attr($current_substitution_type === 'free_text' ? ($item['substitute_item_name'] ?? '') : ''); ?>">
                                        </div>

                                        <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
                                        <button type="submit" class="svdp-btn svdp-btn-primary">Save Substitute</button>
                                    </form>
                                </section>

                                <section class="svdp-furniture-action-panel" x-show="activeAction === 'cancel'" x-transition>
                                    <form
                                        class="svdp-form"
                                        data-cashier-action="furniture-cancel"
                                        data-voucher-id="<?php echo esc_attr($voucher['id']); ?>"
                                        data-item-id="<?php echo esc_attr($item['id']); ?>"
                                    >
                                        <div class="svdp-form-group">
                                            <label>Cancellation Reason *</label>
                                            <select name="cancellationReasonId" required>
                                                <option value="">Select a reason...</option>
                                                <?php foreach ($cancellation_reasons as $reason): ?>
                                                    <option value="<?php echo esc_attr(intval($reason->id)); ?>">
                                                        <?php echo esc_html($reason->reason_text); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="svdp-form-group">
                                            <label>Cancellation Notes</label>
                                            <textarea name="cancellationNotes" rows="3"></textarea>
                                        </div>

                                        <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
                                        <button type="submit" class="svdp-btn svdp-btn-warning">Confirm Cancellation</button>
                                    </form>
                                </section>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($voucher['status'] === 'Redeemed'): ?>
        <section class="svdp-cashier-info-panel">
            <h3><?php echo esc_html($document_copy['completionDocumentsHeading']); ?></h3>
            <?php if (!empty($voucher['furniture_completed_at'])): ?>
                <p><strong>Completed:</strong> <?php echo esc_html(date('m/d/Y g:i A', strtotime($voucher['furniture_completed_at']))); ?></p>
            <?php endif; ?>
            <?php if (!empty($voucher['invoice_number'])): ?>
                <p><strong>Invoice:</strong> <?php echo esc_html($voucher['invoice_number']); ?></p>
            <?php endif; ?>
            <?php if ($voucher['invoice_amount'] !== null): ?>
                <p><strong>Invoice Amount:</strong> $<?php echo esc_html(number_format((float) $voucher['invoice_amount'], 2)); ?></p>
            <?php endif; ?>
            <div class="svdp-document-links">
                <?php if (!empty($voucher['receipt_file_url'])): ?>
                    <a class="svdp-document-link" href="<?php echo esc_url($voucher['receipt_file_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($document_copy['openNeighborReceiptLabel']); ?></a>
                <?php endif; ?>
                <?php if (!empty($voucher['invoice_file_url'])): ?>
                    <a class="svdp-document-link" href="<?php echo esc_url($voucher['invoice_file_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($document_copy['openConferenceInvoiceLabel']); ?></a>
                <?php endif; ?>
            </div>
        </section>
    <?php elseif ($remaining_items === 0 && $can_mutate_furniture): ?>
        <section class="svdp-cashier-info-panel">
            <h3><?php echo esc_html($document_copy['completeVoucherHeading']); ?></h3>
            <p><?php echo esc_html($document_copy['completeVoucherDescription']); ?></p>
            <form class="svdp-form" data-cashier-action="furniture-voucher-complete" data-voucher-id="<?php echo esc_attr($voucher['id']); ?>">
                <div class="svdp-cashier-inline-summary">
                    <span>Items total: <?php echo esc_html(intval($item_progress['total'])); ?></span>
                    <span><?php echo esc_html($pricing_copy['invoiceActualPricingNote']); ?></span>
                    <?php if (!empty($voucher['delivery_required'])): ?>
                        <span><?php echo esc_html($pricing_copy['deliveryFeeIncludedText']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="svdp-inline-error" data-inline-error style="display: none;"></div>
                <button type="submit" class="svdp-btn svdp-btn-primary"><?php echo esc_html($document_copy['completeVoucherButton']); ?></button>
            </form>
        </section>
    <?php elseif ($remaining_items === 0): ?>
        <section class="svdp-cashier-info-panel">
            <h3>Ready For Completion</h3>
            <p>All items are resolved, but this user does not have permission to complete the furniture voucher or generate documents.</p>
        </section>
    <?php elseif (!$can_mutate_furniture): ?>
        <section class="svdp-cashier-info-panel">
            <h3>View Only</h3>
            <p>This user can review furniture outcomes but cannot change photos, pricing, substitutions, cancellations, or completion state.</p>
        </section>
    <?php endif; ?>
</div>
