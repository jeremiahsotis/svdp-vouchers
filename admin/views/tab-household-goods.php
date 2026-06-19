<?php
$can_manage_catalog = SVDP_Permissions::user_can_manage_household_goods_catalog();
$can_manage_limits = SVDP_Permissions::user_can_manage_household_goods_limits();
$can_view_audit = SVDP_Permissions::user_can_view_voucher_configuration_audit();
$browse_groups = SVDP_Household_Goods_Catalog::get_browse_groups();
$active_browse_groups = SVDP_Household_Goods_Catalog::get_browse_groups(false);
$categories = SVDP_Household_Goods_Catalog::get_catalog_categories();
$limits = SVDP_Household_Goods_Catalog::get_limits();
$audit_rows = $can_view_audit ? SVDP_Household_Goods_Catalog::get_configuration_audit(75) : [];
?>

<div class="svdp-household-goods-admin-section">
    <?php if ($can_manage_catalog) : ?>
        <div class="svdp-card">
            <h2>Household Goods Browse Groups</h2>
            <p>Browse groups control the Household Goods filter pills for future requests. Archive groups instead of deleting them so historical snapshots remain stable.</p>

            <div id="svdp-household-goods-group-form" class="svdp-furniture-form">
                <div class="svdp-admin-grid">
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-group-name"><strong>Name</strong></label>
                        <input type="text" id="svdp-hg-group-name" name="name" class="regular-text" placeholder="Kitchen">
                    </div>
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-group-sort-order"><strong>Sort Order</strong></label>
                        <input type="number" id="svdp-hg-group-sort-order" name="sort_order" min="0" value="0" class="small-text">
                    </div>
                </div>
                <button type="button" id="svdp-add-hg-group" class="button button-primary">Add Browse Group</button>
            </div>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th>Active Categories</th>
                        <th>Updated At</th>
                        <th>Updated By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($browse_groups)) : ?>
                        <tr><td colspan="8">No Household Goods browse groups yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($browse_groups as $group) : ?>
                            <?php $is_active = intval($group->active) === 1; ?>
                            <tr class="<?php echo $is_active ? '' : 'inactive'; ?>">
                                <td><?php echo esc_html($group->name); ?></td>
                                <td><?php echo esc_html($group->slug); ?></td>
                                <td><?php echo esc_html($is_active ? 'Active' : 'Archived'); ?></td>
                                <td><?php echo esc_html($group->sort_order); ?></td>
                                <td><?php echo esc_html((int) $group->active_category_count); ?></td>
                                <td><?php echo esc_html($group->updated_at); ?></td>
                                <td><?php echo esc_html($group->updated_by_name ?: 'System'); ?></td>
                                <td>
                                    <div class="svdp-inline-actions">
                                        <button
                                            type="button"
                                            class="button button-small svdp-edit-hg-group"
                                            data-id="<?php echo esc_attr($group->id); ?>"
                                            data-name="<?php echo esc_attr($group->name); ?>"
                                            data-sort-order="<?php echo esc_attr($group->sort_order); ?>"
                                            data-active="<?php echo esc_attr($group->active); ?>"
                                        >Edit</button>
                                        <button
                                            type="button"
                                            class="button button-small svdp-toggle-hg-group-active"
                                            data-id="<?php echo esc_attr($group->id); ?>"
                                            data-active="<?php echo esc_attr($group->active); ?>"
                                        ><?php echo esc_html($is_active ? 'Archive' : 'Restore'); ?></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="svdp-card">
            <h2>Household Goods Catalog</h2>
            <p>Catalog categories are requestable Household Goods needs. Estimated Conference / Partner cost is for projected internal exposure and is not a shopper-facing price.</p>

            <div id="svdp-household-goods-category-form" class="svdp-furniture-form">
                <div class="svdp-admin-grid">
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-category-name"><strong>Category Name</strong></label>
                        <input type="text" id="svdp-hg-category-name" name="name" class="regular-text" placeholder="Bath Towels">
                    </div>
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-category-group"><strong>Browse Group</strong></label>
                        <select id="svdp-hg-category-group" name="browse_group_id">
                            <?php foreach ($active_browse_groups as $group) : ?>
                                <option value="<?php echo esc_attr($group->id); ?>"><?php echo esc_html($group->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-category-cost"><strong>Estimated Conference / Partner Cost Per Unit</strong></label>
                        <input type="number" id="svdp-hg-category-cost" name="estimated_conference_partner_cost_per_unit" min="0" step="0.01" value="0.00" class="small-text">
                    </div>
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-category-quantity-max"><strong>Quantity Maximum</strong></label>
                        <input type="number" id="svdp-hg-category-quantity-max" name="quantity_max" min="0" step="1" value="0" class="small-text">
                        <p class="description">Use 0 for no category-level limit.</p>
                    </div>
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-category-sort-order"><strong>Sort Order</strong></label>
                        <input type="number" id="svdp-hg-category-sort-order" name="sort_order" min="0" value="0" class="small-text">
                    </div>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-hg-category-guidance"><strong>Cashier Guidance</strong></label>
                    <textarea id="svdp-hg-category-guidance" name="cashier_guidance" rows="2" class="large-text"></textarea>
                    <p class="description">Managed staff guidance only. This is not a voucher note and is not shown to Vincentians.</p>
                </div>
                <button type="button" id="svdp-add-hg-category" class="button button-primary">Add Household Goods Category</button>
            </div>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Browse Group</th>
                        <th>Estimated Cost</th>
                        <th>Quantity Maximum</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th>Guidance</th>
                        <th>Updated At</th>
                        <th>Updated By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)) : ?>
                        <tr><td colspan="10">No Household Goods categories yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($categories as $category) : ?>
                            <?php $is_active = intval($category->active) === 1; ?>
                            <tr class="<?php echo $is_active ? '' : 'inactive'; ?>">
                                <td><?php echo esc_html($category->name); ?></td>
                                <td><?php echo esc_html($category->browse_group_name); ?></td>
                                <td><?php echo esc_html('$' . number_format((float) $category->estimated_conference_partner_cost_per_unit, 2)); ?></td>
                                <td><?php echo esc_html((int) $category->quantity_max === 0 ? 'No limit' : (int) $category->quantity_max); ?></td>
                                <td><?php echo esc_html($is_active ? 'Active' : 'Archived'); ?></td>
                                <td><?php echo esc_html($category->sort_order); ?></td>
                                <td><?php echo esc_html($category->cashier_guidance ? 'Yes' : 'No'); ?></td>
                                <td><?php echo esc_html($category->updated_at); ?></td>
                                <td><?php echo esc_html($category->updated_by_name ?: 'System'); ?></td>
                                <td>
                                    <div class="svdp-inline-actions">
                                        <button
                                            type="button"
                                            class="button button-small svdp-edit-hg-category"
                                            data-id="<?php echo esc_attr($category->id); ?>"
                                            data-name="<?php echo esc_attr($category->name); ?>"
                                            data-browse-group-id="<?php echo esc_attr($category->browse_group_id); ?>"
                                            data-cost="<?php echo esc_attr($category->estimated_conference_partner_cost_per_unit); ?>"
                                            data-quantity-max="<?php echo esc_attr($category->quantity_max); ?>"
                                            data-guidance="<?php echo esc_attr($category->cashier_guidance); ?>"
                                            data-sort-order="<?php echo esc_attr($category->sort_order); ?>"
                                            data-active="<?php echo esc_attr($category->active); ?>"
                                        >Edit</button>
                                        <button
                                            type="button"
                                            class="button button-small svdp-toggle-hg-category-active"
                                            data-id="<?php echo esc_attr($category->id); ?>"
                                            data-active="<?php echo esc_attr($category->active); ?>"
                                        ><?php echo esc_html($is_active ? 'Archive' : 'Restore'); ?></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($can_manage_limits) : ?>
        <div class="svdp-card">
            <h2>Household Goods Limits</h2>
            <p>The selected-category limit is fixed at 10. Voucher-wide quantity maximum applies to future Household Goods vouchers only.</p>
            <div id="svdp-household-goods-limits-form" class="svdp-furniture-form">
                <div class="svdp-admin-grid">
                    <div class="svdp-admin-field">
                        <label><strong>Maximum Selected Categories</strong></label>
                        <input type="number" value="<?php echo esc_attr($limits['selected_category_limit']); ?>" class="small-text" disabled>
                    </div>
                    <div class="svdp-admin-field">
                        <label for="svdp-hg-voucher-quantity-max"><strong>Maximum Total Requested Quantity</strong></label>
                        <input type="number" id="svdp-hg-voucher-quantity-max" name="voucher_quantity_max" min="0" step="1" value="<?php echo esc_attr($limits['voucher_quantity_max']); ?>" class="small-text">
                        <p class="description">Use 0 for no voucher-wide limit.</p>
                    </div>
                </div>
                <button type="button" id="svdp-save-hg-limits" class="button button-primary">Save Limits</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($can_view_audit) : ?>
        <div class="svdp-card">
            <h2>Configuration Audit</h2>
            <p class="description">Configuration audit history is read-only.</p>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Changed At</th>
                        <th>Changed By</th>
                        <th>Record</th>
                        <th>Field</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($audit_rows)) : ?>
                        <tr><td colspan="7">No configuration audit rows yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($audit_rows as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row->changed_at); ?></td>
                                <td><?php echo esc_html($row->changed_by_name ?: 'System'); ?></td>
                                <td><?php echo esc_html($row->record_name_snapshot); ?></td>
                                <td><?php echo esc_html($row->field_changed); ?></td>
                                <td><?php echo esc_html($row->before_value); ?></td>
                                <td><?php echo esc_html($row->after_value); ?></td>
                                <td><?php echo esc_html($row->human_summary); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="svdp-edit-hg-group-modal" class="svdp-modal" style="display: none;">
    <div class="svdp-modal-content">
        <h3>Edit Browse Group</h3>
        <input type="hidden" id="svdp-edit-hg-group-id">
        <div id="svdp-edit-household-goods-group-form" class="svdp-furniture-form">
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-group-name"><strong>Name</strong></label>
                    <input type="text" id="svdp-edit-hg-group-name" name="name" class="regular-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-group-sort-order"><strong>Sort Order</strong></label>
                    <input type="number" id="svdp-edit-hg-group-sort-order" name="sort_order" min="0" class="small-text">
                </div>
            </div>
        </div>
        <div class="svdp-inline-actions">
            <button type="button" class="button button-primary" id="svdp-save-hg-group-edit">Save Changes</button>
            <button type="button" class="button svdp-close-modal" data-target="#svdp-edit-hg-group-modal">Cancel</button>
        </div>
    </div>
</div>

<div id="svdp-edit-hg-category-modal" class="svdp-modal" style="display: none;">
    <div class="svdp-modal-content">
        <h3>Edit Household Goods Category</h3>
        <input type="hidden" id="svdp-edit-hg-category-id">
        <div id="svdp-edit-household-goods-category-form" class="svdp-furniture-form">
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-category-name"><strong>Category Name</strong></label>
                    <input type="text" id="svdp-edit-hg-category-name" name="name" class="regular-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-category-group"><strong>Browse Group</strong></label>
                    <select id="svdp-edit-hg-category-group" name="browse_group_id">
                        <?php foreach ($active_browse_groups as $group) : ?>
                            <option value="<?php echo esc_attr($group->id); ?>"><?php echo esc_html($group->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-category-cost"><strong>Estimated Conference / Partner Cost Per Unit</strong></label>
                    <input type="number" id="svdp-edit-hg-category-cost" name="estimated_conference_partner_cost_per_unit" min="0" step="0.01" class="small-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-category-quantity-max"><strong>Quantity Maximum</strong></label>
                    <input type="number" id="svdp-edit-hg-category-quantity-max" name="quantity_max" min="0" step="1" class="small-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-hg-category-sort-order"><strong>Sort Order</strong></label>
                    <input type="number" id="svdp-edit-hg-category-sort-order" name="sort_order" min="0" class="small-text">
                </div>
            </div>
            <div class="svdp-admin-field">
                <label for="svdp-edit-hg-category-guidance"><strong>Cashier Guidance</strong></label>
                <textarea id="svdp-edit-hg-category-guidance" name="cashier_guidance" rows="2" class="large-text"></textarea>
            </div>
        </div>
        <div class="svdp-inline-actions">
            <button type="button" class="button button-primary" id="svdp-save-hg-category-edit">Save Changes</button>
            <button type="button" class="button svdp-close-modal" data-target="#svdp-edit-hg-category-modal">Cancel</button>
        </div>
    </div>
</div>
