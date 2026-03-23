<?php
$reasons = SVDP_Furniture_Cancellation_Reason::get_all();
?>

<div class="svdp-furniture-admin-section">
    <div class="svdp-card">
        <h2>Furniture Cancellation Reasons</h2>
        <p>Manage the preset reasons that later furniture fulfillment workflows will require when an item is cancelled. Archive old reasons instead of deleting them.</p>

        <div id="svdp-furniture-reason-form" class="svdp-furniture-form">
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-furniture-reason-text"><strong>Reason Text</strong></label>
                    <input type="text" id="svdp-furniture-reason-text" name="reason_text" class="regular-text" placeholder="Item unavailable">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-furniture-reason-order"><strong>Display Order</strong></label>
                    <input type="number" id="svdp-furniture-reason-order" name="display_order" min="0" class="small-text" placeholder="Optional">
                </div>
            </div>

            <p class="description">Leave display order blank to append the new reason to the end of the list.</p>

            <div class="svdp-inline-actions">
                <button type="button" id="svdp-add-furniture-reason" class="button button-primary">Add Cancellation Reason</button>
            </div>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Existing Cancellation Reasons</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Reason Text</th>
                    <th>Display Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reasons)): ?>
                    <tr>
                        <td colspan="4">No cancellation reasons yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reasons as $reason): ?>
                        <?php $is_active = intval($reason->active) === 1; ?>
                        <tr class="<?php echo $is_active ? '' : 'inactive'; ?>">
                            <td><?php echo esc_html($reason->reason_text); ?></td>
                            <td><?php echo esc_html($reason->display_order); ?></td>
                            <td>
                                <span class="<?php echo $is_active ? 'manager-status-active' : 'manager-status-inactive'; ?>">
                                    <?php echo esc_html($is_active ? 'Active' : 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="svdp-inline-actions">
                                    <button
                                        type="button"
                                        class="button button-small svdp-edit-furniture-reason"
                                        data-id="<?php echo esc_attr($reason->id); ?>"
                                        data-text="<?php echo esc_attr($reason->reason_text); ?>"
                                        data-display-order="<?php echo esc_attr($reason->display_order); ?>"
                                        data-active="<?php echo esc_attr($reason->active); ?>"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-small svdp-toggle-furniture-reason-active"
                                        data-id="<?php echo esc_attr($reason->id); ?>"
                                        data-active="<?php echo esc_attr($reason->active); ?>"
                                    >
                                        <?php echo esc_html($is_active ? 'Archive' : 'Restore'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="svdp-edit-furniture-reason-modal" class="svdp-modal" style="display: none;">
    <div class="svdp-modal-content">
        <h3>Edit Cancellation Reason</h3>
        <input type="hidden" id="svdp-edit-furniture-reason-id">

        <div id="svdp-edit-furniture-reason-form" class="svdp-furniture-form">
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-edit-furniture-reason-text"><strong>Reason Text</strong></label>
                    <input type="text" id="svdp-edit-furniture-reason-text" name="reason_text" class="regular-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-furniture-reason-order"><strong>Display Order</strong></label>
                    <input type="number" id="svdp-edit-furniture-reason-order" name="display_order" min="0" class="small-text">
                </div>
            </div>
        </div>

        <div class="svdp-inline-actions">
            <button type="button" class="button button-primary" id="svdp-save-furniture-reason-edit">Save Changes</button>
            <button type="button" class="button svdp-close-modal" data-target="#svdp-edit-furniture-reason-modal">Cancel</button>
        </div>
    </div>
</div>
