<?php
$catalog_items = SVDP_Furniture_Catalog::get_all();
$categories = SVDP_Furniture_Catalog::get_categories();
$pricing_types = SVDP_Furniture_Catalog::get_pricing_types();
?>

<div class="svdp-furniture-admin-section">
    <div class="svdp-card">
        <h2>Furniture Catalog</h2>
        <p>Manage the catalog rows that will power later furniture request and fulfillment flows. Archive items instead of deleting them so historical voucher snapshots can remain stable.</p>

        <div id="svdp-furniture-catalog-form" class="svdp-furniture-form">
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-name"><strong>Item Name</strong></label>
                    <input type="text" id="svdp-catalog-name" name="name" class="regular-text" placeholder="Sofa">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-category"><strong>Category</strong></label>
                    <select id="svdp-catalog-category" name="category">
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-pricing-type"><strong>Pricing Type</strong></label>
                    <select id="svdp-catalog-pricing-type" name="pricing_type">
                        <?php foreach ($pricing_types as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-sort-order"><strong>Sort Order</strong></label>
                    <input type="number" id="svdp-catalog-sort-order" name="sort_order" min="0" value="0" class="small-text">
                </div>
            </div>

            <div class="svdp-admin-grid svdp-pricing-fields" data-pricing-fields="range">
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-price-min"><strong>Minimum Price</strong></label>
                    <input type="number" id="svdp-catalog-price-min" name="price_min" min="0" step="0.01" class="small-text" placeholder="25.00">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-price-max"><strong>Maximum Price</strong></label>
                    <input type="number" id="svdp-catalog-price-max" name="price_max" min="0" step="0.01" class="small-text" placeholder="75.00">
                </div>
            </div>

            <div class="svdp-admin-grid svdp-pricing-fields" data-pricing-fields="fixed" style="display: none;">
                <div class="svdp-admin-field">
                    <label for="svdp-catalog-price-fixed"><strong>Fixed Price</strong></label>
                    <input type="number" id="svdp-catalog-price-fixed" name="price_fixed" min="0" step="0.01" class="small-text" placeholder="50.00">
                </div>
            </div>

            <p class="description">Use range pricing for used furniture and household goods. Use fixed pricing for handmade furniture and mattresses/frames.</p>

            <div class="svdp-inline-actions">
                <button type="button" id="svdp-add-catalog-item" class="button button-primary">Add Catalog Item</button>
            </div>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Existing Catalog Items</h2>
        <p class="description">Inactive items stay in the database and can be restored later.</p>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Pricing</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($catalog_items)): ?>
                    <tr>
                        <td colspan="6">No catalog items yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($catalog_items as $item): ?>
                        <?php
                        $is_active = intval($item->active) === 1;
                        $pricing_label = $item->pricing_type === 'fixed'
                            ? '$' . number_format((float) $item->price_fixed, 2)
                            : '$' . number_format((float) $item->price_min, 2) . ' - $' . number_format((float) $item->price_max, 2);
                        ?>
                        <tr class="<?php echo $is_active ? '' : 'inactive'; ?>">
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php echo esc_html($categories[$item->category] ?? $item->category); ?></td>
                            <td><?php echo esc_html(ucfirst($item->pricing_type) . ': ' . $pricing_label); ?></td>
                            <td><?php echo esc_html($item->sort_order); ?></td>
                            <td>
                                <span class="<?php echo $is_active ? 'manager-status-active' : 'manager-status-inactive'; ?>">
                                    <?php echo esc_html($is_active ? 'Active' : 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="svdp-inline-actions">
                                    <button
                                        type="button"
                                        class="button button-small svdp-edit-furniture-item"
                                        data-id="<?php echo esc_attr($item->id); ?>"
                                        data-name="<?php echo esc_attr($item->name); ?>"
                                        data-category="<?php echo esc_attr($item->category); ?>"
                                        data-pricing-type="<?php echo esc_attr($item->pricing_type); ?>"
                                        data-price-min="<?php echo esc_attr($item->price_min); ?>"
                                        data-price-max="<?php echo esc_attr($item->price_max); ?>"
                                        data-price-fixed="<?php echo esc_attr($item->price_fixed); ?>"
                                        data-sort-order="<?php echo esc_attr($item->sort_order); ?>"
                                        data-active="<?php echo esc_attr($item->active); ?>"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-small svdp-toggle-furniture-item-active"
                                        data-id="<?php echo esc_attr($item->id); ?>"
                                        data-active="<?php echo esc_attr($item->active); ?>"
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

<div id="svdp-edit-furniture-catalog-modal" class="svdp-modal" style="display: none;">
    <div class="svdp-modal-content">
        <h3>Edit Catalog Item</h3>
        <input type="hidden" id="svdp-edit-catalog-id">

        <div id="svdp-edit-furniture-catalog-form" class="svdp-furniture-form">
            <div class="svdp-admin-grid">
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-name"><strong>Item Name</strong></label>
                    <input type="text" id="svdp-edit-catalog-name" name="name" class="regular-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-category"><strong>Category</strong></label>
                    <select id="svdp-edit-catalog-category" name="category">
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-pricing-type"><strong>Pricing Type</strong></label>
                    <select id="svdp-edit-catalog-pricing-type" name="pricing_type">
                        <?php foreach ($pricing_types as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-sort-order"><strong>Sort Order</strong></label>
                    <input type="number" id="svdp-edit-catalog-sort-order" name="sort_order" min="0" class="small-text">
                </div>
            </div>

            <div class="svdp-admin-grid svdp-pricing-fields" data-pricing-fields="range">
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-price-min"><strong>Minimum Price</strong></label>
                    <input type="number" id="svdp-edit-catalog-price-min" name="price_min" min="0" step="0.01" class="small-text">
                </div>
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-price-max"><strong>Maximum Price</strong></label>
                    <input type="number" id="svdp-edit-catalog-price-max" name="price_max" min="0" step="0.01" class="small-text">
                </div>
            </div>

            <div class="svdp-admin-grid svdp-pricing-fields" data-pricing-fields="fixed" style="display: none;">
                <div class="svdp-admin-field">
                    <label for="svdp-edit-catalog-price-fixed"><strong>Fixed Price</strong></label>
                    <input type="number" id="svdp-edit-catalog-price-fixed" name="price_fixed" min="0" step="0.01" class="small-text">
                </div>
            </div>
        </div>

        <div class="svdp-inline-actions">
            <button type="button" class="button button-primary" id="svdp-save-furniture-catalog-edit">Save Changes</button>
            <button type="button" class="button svdp-close-modal" data-target="#svdp-edit-furniture-catalog-modal">Cancel</button>
        </div>
    </div>
</div>
