<div class="svdp-voucher-form">

    <h2>Voucher Request Form</h2>
    <p class="svdp-form-intro">Use this form to request a voucher on behalf of a neighbor in need.</p>

    <?php
    $store_hours = SVDP_Settings::get_setting('store_hours', '');
    $redemption_instructions = SVDP_Settings::get_setting('redemption_instructions', '');
    $custom_form_text = !empty($conference) ? ($conference->custom_form_text ?? '') : '';
    $custom_rules_text = !empty($conference) ? ($conference->custom_rules_text ?? '') : '';
    $request_voucher_types = SVDP_Settings::get_public_request_voucher_types();

    if (!empty($conference)) {
        $request_voucher_types = array_values(array_intersect(
            $request_voucher_types,
            SVDP_Settings::normalize_voucher_types($conference->allowed_voucher_types, $request_voucher_types)
        ));
    }

    if (empty($request_voucher_types)) {
        $request_voucher_types = ['clothing'];
    }

    $default_voucher_type = in_array('clothing', $request_voucher_types, true)
        ? 'clothing'
        : $request_voucher_types[0];
    $voucher_type_labels = [
        'clothing' => 'Clothing',
        'furniture' => 'Furniture / Household Goods',
    ];
    $voucher_type_descriptions = [
        'clothing' => 'Keep the current clothing voucher request flow.',
        'furniture' => 'Select requested furniture items, capture delivery needs, and save the estimate range.',
    ];
    $furniture_categories = class_exists('SVDP_Furniture_Catalog')
        ? SVDP_Furniture_Catalog::get_categories()
        : [
            'used_furniture' => 'Used Furniture',
            'handmade_furniture' => 'Handmade Furniture',
            'mattresses_frames' => 'Mattresses & Frames',
            'household_goods' => 'Household Goods',
        ];
    $furniture_category_hints = [
        'used_furniture' => 'Sofas, tables, chairs, and more',
        'handmade_furniture' => 'Built pieces and restored essentials',
        'mattresses_frames' => 'Beds, bunks, frames, and supports',
        'household_goods' => 'Kitchen, bath, and daily-use goods',
    ];
    ?>

    <?php if (!empty($store_hours) || !empty($redemption_instructions)): ?>
    <div class="svdp-instructions">
        <h3>Store Information</h3>
        <?php if (!empty($store_hours)): ?>
            <p><strong>🏪 Hours:</strong> <?php echo esc_html($store_hours); ?></p>
        <?php endif; ?>
        <?php if (!empty($redemption_instructions)): ?>
            <p><strong>ℹ️ Instructions:</strong> <?php echo esc_html($redemption_instructions); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($custom_form_text)): ?>
    <div class="svdp-custom-text">
        <h3><?php echo !empty($conference) ? esc_html($conference->name) : 'Organization'; ?> Information</h3>
        <p><?php echo nl2br(esc_html($custom_form_text)); ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($custom_rules_text)): ?>
    <div class="svdp-custom-rules">
        <h3>Eligibility Requirements</h3>
        <div class="rules-content"><?php echo nl2br(esc_html($custom_rules_text)); ?></div>
    </div>
    <?php endif; ?>

    <form
        id="svdpVoucherForm"
        class="svdp-form"
        data-default-voucher-type="<?php echo esc_attr($default_voucher_type); ?>"
    >
        <h3>Neighbor Information</h3>

        <div class="svdp-form-row">
            <div class="svdp-form-group">
                <label>First Name *</label>
                <input type="text" name="firstName" required>
            </div>

            <div class="svdp-form-group">
                <label>Last Name *</label>
                <input type="text" name="lastName" required>
            </div>
        </div>

        <div class="svdp-form-group svdp-dob-field">
            <label>Date of Birth *</label>
            <input type="date"
                   name="dob"
                   id="svdp-dob-input"
                   class="svdp-date-input"
                   placeholder="MM/DD/YYYY"
                   required>
            <small class="svdp-help-text">Used to track voucher eligibility and ensure appropriate intervals between requests.</small>
        </div>

        <div class="svdp-form-row">
            <div class="svdp-form-group">
                <label>Number of adults (18 and over) in household *</label>
                <input type="number" name="adults" min="0" value="1" required>
                <small class="svdp-help-text">Item allocation is based on household size. Count all adults in the home.</small>
            </div>

            <div class="svdp-form-group">
                <label>Number of children (under 18) in household *</label>
                <input type="number" name="children" min="0" value="0">
                <small class="svdp-help-text">Item allocation is based on household size. Count all children in the home.</small>
            </div>
        </div>

        <h3>Voucher Type</h3>

        <?php if (count($request_voucher_types) === 1): ?>
            <input type="hidden" name="voucherType" value="<?php echo esc_attr($default_voucher_type); ?>">
            <div class="svdp-branch-note">
                <strong><?php echo esc_html($voucher_type_labels[$default_voucher_type] ?? ucfirst($default_voucher_type)); ?></strong>
                <span><?php echo esc_html($voucher_type_descriptions[$default_voucher_type] ?? ''); ?></span>
            </div>
        <?php else: ?>
            <div class="svdp-voucher-type-options" id="svdpVoucherTypeOptions">
                <?php foreach ($request_voucher_types as $voucher_type_option): ?>
                    <label class="svdp-voucher-type-option" data-voucher-type-option="<?php echo esc_attr($voucher_type_option); ?>">
                        <input
                            type="radio"
                            name="voucherType"
                            value="<?php echo esc_attr($voucher_type_option); ?>"
                            <?php checked($voucher_type_option, $default_voucher_type); ?>
                        >
                        <span class="svdp-voucher-type-option-title"><?php echo esc_html($voucher_type_labels[$voucher_type_option] ?? ucfirst($voucher_type_option)); ?></span>
                        <span class="svdp-voucher-type-option-description"><?php echo esc_html($voucher_type_descriptions[$voucher_type_option] ?? ''); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="svdp-form-branch" data-voucher-branch="clothing">
            <div class="svdp-branch-note">
                <strong>Clothing Request</strong>
                <span>Clothing requests keep the current familiar flow and item allowance behavior.</span>
            </div>
        </div>

        <div class="svdp-form-branch" data-voucher-branch="furniture" hidden>
            <div class="svdp-furniture-branch-layout">
                <div class="svdp-furniture-selection-column">
                    <div class="svdp-branch-note">
                        <strong>Furniture Request</strong>
                        <span>Select one or more items by category. Final fulfilled pricing may vary from the estimate shown here.</span>
                    </div>
                    <div class="svdp-furniture-browser">
                        <div class="svdp-form-group svdp-furniture-search-shell">
                            <label for="svdpFurnitureSearch">Search Furniture Catalog</label>
                            <input
                                type="search"
                                id="svdpFurnitureSearch"
                                class="svdp-furniture-search-input"
                                placeholder="Search across all furniture items"
                                autocomplete="off"
                                disabled
                            >
                            <small class="svdp-help-text">Search across all furniture categories. Clearing the search restores category browsing.</small>
                        </div>

                        <div id="svdpFurnitureCatalog" class="svdp-furniture-catalog" data-catalog-loaded="false">
                            <div id="svdpFurnitureCategoryCards" class="svdp-furniture-category-grid">
                                <?php foreach ($furniture_categories as $category_key => $category_label): ?>
                                    <button
                                        type="button"
                                        class="svdp-furniture-category-card"
                                        data-category-card="<?php echo esc_attr($category_key); ?>"
                                        aria-controls="svdpFurnitureCategorySection-<?php echo esc_attr($category_key); ?>"
                                        aria-expanded="false"
                                    >
                                        <span class="svdp-furniture-category-card-copy">
                                            <span class="svdp-furniture-category-card-title"><?php echo esc_html($category_label); ?></span>
                                            <span class="svdp-furniture-category-card-hint">
                                                <?php echo esc_html($furniture_category_hints[$category_key] ?? 'Browse this category'); ?>
                                            </span>
                                        </span>
                                        <span class="svdp-furniture-category-card-meta">
                                            <span class="svdp-furniture-category-card-count" data-category-selected-count="<?php echo esc_attr($category_key); ?>">0 selected</span>
                                            <span class="svdp-furniture-category-card-status" data-category-available-count="<?php echo esc_attr($category_key); ?>">Loading items</span>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div id="svdpFurnitureCategorySections" class="svdp-furniture-category-sections">
                                <?php foreach ($furniture_categories as $category_key => $category_label): ?>
                                    <section
                                        id="svdpFurnitureCategorySection-<?php echo esc_attr($category_key); ?>"
                                        class="svdp-furniture-category-section"
                                        data-category-section="<?php echo esc_attr($category_key); ?>"
                                        hidden
                                    >
                                        <div class="svdp-furniture-category-section-header">
                                            <div class="svdp-furniture-category-section-copy">
                                                <h4><?php echo esc_html($category_label); ?></h4>
                                                <p><?php echo esc_html($furniture_category_hints[$category_key] ?? ''); ?></p>
                                            </div>
                                            <span class="svdp-furniture-category-section-pill" data-category-pill="<?php echo esc_attr($category_key); ?>">Loading...</span>
                                        </div>

                                        <div class="svdp-furniture-category-section-body">
                                            <p class="svdp-furniture-category-placeholder" data-category-placeholder="<?php echo esc_attr($category_key); ?>">
                                                Catalog items for this category will appear here once the shell is hydrated.
                                            </p>
                                        </div>
                                    </section>
                                <?php endforeach; ?>
                            </div>

                            <div id="svdpFurnitureSearchEmpty" class="svdp-empty-state svdp-furniture-search-empty" hidden>
                                <div class="svdp-empty-icon">🔎</div>
                                <div class="svdp-empty-text">No furniture items match that search yet.</div>
                            </div>

                            <div id="svdpFurnitureCatalogLoading" class="svdp-loading">
                                <div class="svdp-spinner"></div>
                                <p>Loading furniture catalog...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <aside id="svdpFurnitureSummary" class="svdp-furniture-summary">
                    <h4>Selected Items Summary</h4>
                    <div class="svdp-summary-row">
                        <span>Selected Items</span>
                        <strong id="svdpSummaryItemCount">0</strong>
                    </div>
                    <div class="svdp-summary-row">
                        <span>Estimated Total</span>
                        <strong id="svdpSummaryTotal">$0.00</strong>
                    </div>
                    <div class="svdp-summary-row">
                        <span>Estimated Conference Portion</span>
                        <strong id="svdpSummaryRequestor">$0.00</strong>
                    </div>

                    <label class="svdp-checkbox-row">
                        <input type="checkbox" name="deliveryRequired" id="svdpDeliveryRequired" value="1">
                        <span>Add Delivery (+$50.00)</span>
                    </label>

                    <div id="svdpDeliveryFeeNote" class="svdp-summary-row svdp-summary-row-delivery">
                        <span>Delivery Fee</span>
                        <strong id="svdpSummaryDeliveryFee">$0.00</strong>
                    </div>

                    <div id="svdpDeliveryAddressFields" class="svdp-delivery-address-fields" hidden>
                        <div class="svdp-form-group">
                            <label>Delivery Address Line 1 *</label>
                            <input type="text" name="deliveryLine1">
                        </div>
                        <div class="svdp-form-group">
                            <label>Delivery Address Line 2</label>
                            <input type="text" name="deliveryLine2">
                        </div>
                        <div class="svdp-form-row">
                            <div class="svdp-form-group">
                                <label>City *</label>
                                <input type="text" name="deliveryCity">
                            </div>
                            <div class="svdp-form-group">
                                <label>State *</label>
                                <input type="text" name="deliveryState" maxlength="50">
                            </div>
                        </div>
                        <div class="svdp-form-group">
                            <label>ZIP Code *</label>
                            <input type="text" name="deliveryZip" maxlength="20">
                        </div>
                    </div>

                    <p class="svdp-help-text">Conference coverage is based on each catalog item. Delivery is added separately when selected.</p>
                </aside>
            </div>
        </div>

        <h3>Requestor Information</h3>

        <?php if (empty($conference)): ?>
        <div class="svdp-form-group">
            <label>Conference or Partner Organization *</label>
            <select name="conference" required>
                <option value="">Select your organization...</option>
                <?php foreach ($conferences as $conf): ?>
                    <?php
                    $allowed_types = SVDP_Settings::normalize_voucher_types(
                        $conf->allowed_voucher_types,
                        $conf->organization_type === 'store' ? ['clothing'] : ['clothing', 'furniture']
                    );
                    ?>
                    <option
                        value="<?php echo esc_attr($conf->slug); ?>"
                        data-allowed-voucher-types="<?php echo esc_attr(wp_json_encode($allowed_types)); ?>"
                    >
                        <?php echo esc_html($conf->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <?php
            $conference_allowed_types = SVDP_Settings::normalize_voucher_types(
                $conference->allowed_voucher_types,
                ['clothing', 'furniture']
            );
            ?>
            <input
                type="hidden"
                name="conference"
                value="<?php echo esc_attr($conference->slug); ?>"
                data-allowed-voucher-types="<?php echo esc_attr(wp_json_encode($conference_allowed_types)); ?>"
            >
            <p><strong>Organization:</strong> <?php echo esc_html($conference->name); ?></p>
        <?php endif; ?>

        <div class="svdp-form-group">
            <label>Your Name *</label>
            <input type="text" name="vincentianName" required>
            <small class="svdp-help-text">Your name as the staff member or volunteer requesting this voucher</small>
        </div>

        <div class="svdp-form-group">
            <label>Your Email Address *</label>
            <input type="email" name="vincentianEmail" required>
            <small class="svdp-help-text">For voucher confirmation and follow-up if needed</small>
        </div>

        <button type="submit" class="svdp-btn svdp-btn-primary">Submit Voucher Request</button>
    </form>

    <div id="svdpFormMessage" class="svdp-message" style="display: none;"></div>

</div>
