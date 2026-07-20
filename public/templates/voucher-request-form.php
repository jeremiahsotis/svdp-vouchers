<?php
$store_hours = SVDP_Settings::get_setting('store_hours', '');
$redemption_instructions = SVDP_Settings::get_setting('redemption_instructions', '');
$custom_form_text = !empty($conference) ? ($conference->custom_form_text ?? '') : '';
$custom_rules_text = !empty($conference) ? ($conference->custom_rules_text ?? '') : '';
$request_voucher_types = SVDP_Settings::get_public_request_voucher_types();
$root_voucher_types = class_exists('SVDP_Voucher_Type_Settings')
    ? SVDP_Voucher_Type_Settings::get_root_voucher_types()
    : ['clothing', 'furniture', 'household_goods'];
$request_voucher_types = array_values(array_intersect($root_voucher_types, $request_voucher_types));
$pricing_copy = SVDP_Voucher_Rules::get_pricing_copy();
$delivery_capabilities = class_exists('SVDP_Voucher_Type_Settings')
    ? SVDP_Voucher_Type_Settings::get_capabilities()
    : [];

if (!empty($conference)) {
    $request_voucher_types = array_values(array_intersect(
        $request_voucher_types,
        SVDP_Settings::get_conference_allowed_request_voucher_types($conference)
    ));
}

if (empty($request_voucher_types)) {
    $request_voucher_types = ['clothing'];
}

$voucher_type_labels = [
    'clothing' => 'Clothing Voucher',
    'furniture' => 'Furniture Voucher',
    'household_goods' => 'Household Goods Voucher',
];
$voucher_type_descriptions = class_exists('SVDP_Voucher_Type_Settings')
    ? SVDP_Voucher_Type_Settings::get_descriptions()
    : [
        'clothing' => 'Must redeem in one visit within 30 days of issue date.',
        'furniture' => 'Choose needed furniture items and delivery, if needed. Must redeem in one visit within 30 days of issue date.',
        'household_goods' => 'Choose needed household items. Must redeem in one visit within 30 days of issue date.',
    ];
$delivery_capability_flags = [];
foreach ($root_voucher_types as $voucher_type) {
    $delivery_capability_flags[$voucher_type] = !empty($delivery_capabilities[$voucher_type]['delivery_available']);
}
$furniture_categories = class_exists('SVDP_Furniture_Catalog') ? SVDP_Furniture_Catalog::get_categories() : [];
$furniture_category_hints = [
    'used_furniture' => 'Sofas, tables, chairs, and more',
    'handmade_furniture' => 'Built pieces and restored essentials',
    'mattresses_frames' => 'Beds, bunks, frames, and supports',
    'household_goods' => 'Legacy furniture-voucher household goods',
];

$organization_type = !empty($conference) ? ($conference->organization_type ?? 'conference') : 'conference';
$requestor_entity_label = $organization_type === 'partner' ? 'Partner' : 'Conference';
$requestor_name_label = $organization_type === 'partner' ? 'Partner Representative Name' : 'Conference Member / Vincentian Name';
$requestor_email_label = $organization_type === 'partner' ? 'Partner Representative Email' : 'Conference Member / Vincentian Email';
?>

<div class="svdp-voucher-form svdp-assisted-builder">

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
        class="svdp-form svdp-builder-form"
        data-available-voucher-types="<?php echo esc_attr(wp_json_encode($request_voucher_types)); ?>"
        data-delivery-capabilities="<?php echo esc_attr(wp_json_encode($delivery_capability_flags)); ?>"
        data-requestor-entity-label="<?php echo esc_attr($requestor_entity_label); ?>"
        data-requestor-name-label="<?php echo esc_attr($requestor_name_label); ?>"
        data-requestor-email-label="<?php echo esc_attr($requestor_email_label); ?>"
        data-store-hours="<?php echo esc_attr($store_hours); ?>"
        data-redemption-instructions="<?php echo esc_attr($redemption_instructions); ?>"
    >
        <div class="svdp-builder-shell">
            <main class="svdp-builder-main">
                <div class="svdp-builder-progress" aria-label="Request progress">
                    <div class="svdp-builder-progress-meta">
                        <strong id="svdpStepCounter"></strong>
                        <span id="svdpStepTitle"></span>
                    </div>
                    <ol id="svdpBuilderSteps" class="svdp-builder-steps"></ol>
                </div>

                <section class="svdp-builder-step" data-step-panel="assistance">
                    <h3>What assistance is needed?</h3>
                    <p class="svdp-step-lede">Select all voucher types needed for this household.</p>
                    <div class="svdp-assistance-grid" id="svdpAssistanceOptions">
                        <?php foreach ($request_voucher_types as $voucher_type): ?>
                            <?php
                            $delivery_available = !empty($delivery_capability_flags[$voucher_type]);
                            $description = $voucher_type_descriptions[$voucher_type] ?? '';
                            ?>
                            <button
                                type="button"
                                class="svdp-assistance-card"
                                data-voucher-type-option="<?php echo esc_attr($voucher_type); ?>"
                                aria-pressed="false"
                            >
                                <span class="svdp-selected-indicator" aria-hidden="true">✓</span>
                                <span class="svdp-selected-text">Selected</span>
                                <strong><?php echo esc_html($voucher_type_labels[$voucher_type] ?? ucfirst($voucher_type)); ?></strong>
                                <span><?php echo esc_html($description); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="svdp-inline-error" data-error-for="assistance"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="household" hidden>
                    <h3>Household Information</h3>
                    <div class="svdp-form-row">
                        <div class="svdp-form-group">
                            <label for="svdpFirstName">First Name *</label>
                            <input id="svdpFirstName" type="text" name="firstName" autocomplete="given-name">
                        </div>
                        <div class="svdp-form-group">
                            <label for="svdpLastName">Last Name *</label>
                            <input id="svdpLastName" type="text" name="lastName" autocomplete="family-name">
                        </div>
                    </div>
                    <div class="svdp-form-group svdp-dob-field">
                        <label for="svdp-dob-input">Date of Birth *</label>
                        <input type="date" name="dob" id="svdp-dob-input" class="svdp-date-input" placeholder="MM/DD/YYYY">
                        <small class="svdp-help-text">Used to track voucher eligibility and intervals between requests.</small>
                    </div>
                    <div class="svdp-form-row">
                        <div class="svdp-form-group">
                            <label for="svdpAdults">Adults *</label>
                            <input id="svdpAdults" type="number" name="adults" min="0" value="1">
                        </div>
                        <div class="svdp-form-group">
                            <label for="svdpChildren">Children *</label>
                            <input id="svdpChildren" type="number" name="children" min="0" value="0">
                        </div>
                    </div>
                    <p id="svdpHouseholdCount" class="svdp-count-note">Household size: 1 person</p>
                    <div class="svdp-inline-error" data-error-for="household"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="clothing" hidden>
                    <h3>Clothing Voucher</h3>
                    <div class="svdp-branch-note">
                        <strong>Clothing Voucher selected</strong>
                        <span><?php echo esc_html(SVDP_Voucher_Rules::get_redemption_rule_text()); ?></span>
                    </div>
                    <p class="svdp-voucher-rule-note">The voucher expires 30 days after it is created and is redeemed in one visit.</p>
                </section>

                <section class="svdp-builder-step" data-step-panel="furniture" hidden>
                    <h3>Choose Furniture Items</h3>
                    <p class="svdp-step-lede">Select needed furniture items and quantities.</p>
                    <div class="svdp-catalog-search-shell">
                        <label for="svdpFurnitureSearch">Search furniture</label>
                        <input type="search" id="svdpFurnitureSearch" class="svdp-catalog-search-input" autocomplete="off" disabled>
                        <small class="svdp-help-text">Search by item name or category.</small>
                    </div>
                    <div class="svdp-pill-scroll" data-pill-scroll="furniture">
                        <button type="button" class="svdp-pill-arrow" data-pill-arrow="left" aria-label="Scroll furniture categories left" hidden>‹</button>
                        <div id="svdpFurniturePills" class="svdp-filter-pills" tabindex="0" aria-label="Furniture categories"></div>
                        <button type="button" class="svdp-pill-arrow" data-pill-arrow="right" aria-label="Scroll furniture categories right" hidden>›</button>
                    </div>
                    <div id="svdpFurnitureCatalog" class="svdp-catalog-list" data-catalog-loaded="false">
                        <div id="svdpFurnitureCatalogLoading" class="svdp-loading">
                            <div class="svdp-spinner"></div>
                            <p>Loading furniture catalog...</p>
                        </div>
                    </div>
                    <p class="svdp-summary-policy-note svdp-light-policy"><?php echo esc_html($pricing_copy['pricingExplanation']); ?></p>
                    <div class="svdp-inline-error" data-error-for="furniture"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="household_goods" hidden>
                    <h3>Household Goods</h3>
                    <p class="svdp-step-lede">Select Household Goods categories and quantities.</p>
                    <div class="svdp-catalog-search-shell">
                        <label for="svdpHouseholdGoodsSearch">Search household goods</label>
                        <input type="search" id="svdpHouseholdGoodsSearch" class="svdp-catalog-search-input" autocomplete="off" disabled>
                        <small class="svdp-help-text">Search bedding, curtains, pots and pans, towels, and more.</small>
                    </div>
                    <div class="svdp-pill-scroll" data-pill-scroll="household_goods">
                        <button type="button" class="svdp-pill-arrow" data-pill-arrow="left" aria-label="Scroll Household Goods groups left" hidden>‹</button>
                        <div id="svdpHouseholdGoodsPills" class="svdp-filter-pills" tabindex="0" aria-label="Household Goods browse groups"></div>
                        <button type="button" class="svdp-pill-arrow" data-pill-arrow="right" aria-label="Scroll Household Goods groups right" hidden>›</button>
                    </div>
                    <div class="svdp-count-strip">
                        <span id="svdpHouseholdGoodsCategoryCount">Selected categories: 0 of 10</span>
                        <span id="svdpHouseholdGoodsUnitCount">Requested units: 0</span>
                    </div>
                    <div id="svdpHouseholdGoodsCatalog" class="svdp-catalog-list" data-catalog-loaded="false">
                        <div id="svdpHouseholdGoodsLoading" class="svdp-loading">
                            <div class="svdp-spinner"></div>
                            <p>Loading Household Goods catalog...</p>
                        </div>
                    </div>
                    <div class="svdp-inline-error" data-error-for="household_goods"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="delivery" hidden>
                    <h3>Delivery</h3>
                    <p id="svdpDeliveryEligibleText" class="svdp-step-lede"></p>
                    <input type="checkbox" name="deliveryRequired" id="svdpDeliveryRequired" value="1" hidden>
                    <div class="svdp-delivery-choice-grid">
                        <button type="button" class="svdp-choice-card is-selected" data-delivery-choice="none" aria-pressed="true">
                            <span class="svdp-choice-card-icon" aria-hidden="true">🚫</span>
                            <strong>No delivery needed</strong>
                            <span>The neighbor will visit the store.</span>
                        </button>
                        <button type="button" class="svdp-choice-card" data-delivery-choice="needed" aria-pressed="false">
                            <span class="svdp-choice-card-icon" aria-hidden="true">🚚</span>
                            <strong>Delivery needed</strong>
                            <span id="svdpDeliveryFeeChoice">Delivery: $<?php echo esc_html(number_format((float) SVDP_Voucher_Type_Settings::get_delivery_fee(), 2)); ?></span>
                        </button>
                    </div>
                    <div id="svdpDeliveryAddressFields" class="svdp-delivery-address-fields" hidden>
                        <div class="svdp-form-group">
                            <label for="svdpDeliveryLine1">Delivery Address Line 1 *</label>
                            <input id="svdpDeliveryLine1" type="text" name="deliveryLine1">
                        </div>
                        <div class="svdp-form-group">
                            <label for="svdpDeliveryLine2">Delivery Address Line 2</label>
                            <input id="svdpDeliveryLine2" type="text" name="deliveryLine2">
                        </div>
                        <div class="svdp-form-row">
                            <div class="svdp-form-group">
                                <label for="svdpDeliveryCity">City *</label>
                                <input id="svdpDeliveryCity" type="text" name="deliveryCity">
                            </div>
                            <div class="svdp-form-group">
                                <label for="svdpDeliveryState">State *</label>
                                <input id="svdpDeliveryState" type="text" name="deliveryState" maxlength="50">
                            </div>
                        </div>
                        <div class="svdp-form-group">
                            <label for="svdpDeliveryZip">ZIP Code *</label>
                            <input id="svdpDeliveryZip" type="text" name="deliveryZip" maxlength="20">
                        </div>
                        <input type="hidden" name="deliveryLat" id="svdpDeliveryLat">
                        <input type="hidden" name="deliveryLng" id="svdpDeliveryLng">
                        <input type="hidden" name="deliveryVerified" id="svdpDeliveryVerified" value="0">
                        <input type="hidden" name="deliveryNormalized" id="svdpDeliveryNormalized">
                    </div>
                    <div class="svdp-inline-error" data-error-for="delivery"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="requestor" hidden>
                    <h3><?php echo esc_html($requestor_entity_label); ?> Requestor</h3>
                    <?php if (empty($conference)): ?>
                    <div class="svdp-form-group">
                        <label for="svdpConference">Conference or Partner Organization *</label>
                        <select id="svdpConference" name="conference">
                            <option value="">Select your organization...</option>
                            <?php foreach ($conferences as $conf): ?>
                                <?php
                                $allowed_types = SVDP_Settings::get_conference_allowed_request_voucher_types($conf);
                                ?>
                                <option
                                    value="<?php echo esc_attr($conf->slug); ?>"
                                    data-allowed-voucher-types="<?php echo esc_attr(wp_json_encode($allowed_types)); ?>"
                                    data-organization-type="<?php echo esc_attr($conf->organization_type ?? 'conference'); ?>"
                                >
                                    <?php echo esc_html($conf->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <?php
                        $conference_allowed_types = SVDP_Settings::get_conference_allowed_request_voucher_types($conference);
                        ?>
                        <input
                            type="hidden"
                            name="conference"
                            value="<?php echo esc_attr($conference->slug); ?>"
                            data-allowed-voucher-types="<?php echo esc_attr(wp_json_encode($conference_allowed_types)); ?>"
                            data-organization-type="<?php echo esc_attr($organization_type); ?>"
                            data-organization-name="<?php echo esc_attr($conference->name); ?>"
                        >
                        <p><strong>Organization:</strong> <?php echo esc_html($conference->name); ?></p>
                    <?php endif; ?>

                    <div class="svdp-form-group">
                        <label for="svdpVincentianName" id="svdpRequestorNameLabel"><?php echo esc_html($requestor_name_label); ?> *</label>
                        <input id="svdpVincentianName" type="text" name="vincentianName">
                    </div>
                    <div class="svdp-form-group">
                        <label for="svdpVincentianEmail" id="svdpRequestorEmailLabel"><?php echo esc_html($requestor_email_label); ?> *</label>
                        <input id="svdpVincentianEmail" type="email" name="vincentianEmail">
                    </div>
                    <div class="svdp-inline-error" data-error-for="requestor"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="review" hidden>
                    <h3>Review and Submit</h3>
                    <p class="svdp-step-intro">Review the information below before submitting. Use Edit if anything needs changed.</p>
                    <div id="svdpReviewContent" class="svdp-review-sections"></div>
                    <div class="svdp-inline-error" data-error-for="review"></div>
                </section>

                <section class="svdp-builder-step" data-step-panel="confirmation" hidden>
                    <h3>Request Submitted</h3>
                    <div id="svdpConfirmationContent" class="svdp-review-sections"></div>
                </section>

                <div id="svdpMaxCostModal" class="svdp-confirmation-modal" hidden>
                    <div class="svdp-confirmation-modal-backdrop" data-max-cost-cancel="true"></div>
                    <div class="svdp-confirmation-modal-card" role="dialog" aria-modal="true" aria-labelledby="svdpMaxCostTitle">
                        <h3 id="svdpMaxCostTitle">Confirm maximum cost</h3>
                        <div id="svdpMaxCostContent"></div>
                        <div class="svdp-confirmation-modal-actions">
                            <button type="button" class="svdp-btn svdp-btn-secondary" data-max-cost-cancel="true">Go Back</button>
                            <button type="button" class="svdp-btn svdp-btn-primary" id="svdpMaxCostConfirm">Confirm and Submit</button>
                        </div>
                    </div>
                </div>

                <div id="svdpFormMessage" class="svdp-message" style="display: none;"></div>

                <div class="svdp-builder-actions">
                    <button type="button" id="svdpBuilderBack" class="svdp-btn svdp-btn-secondary">Back</button>
                    <button type="button" id="svdpBuilderNext" class="svdp-btn svdp-btn-primary">Continue</button>
                    <button type="submit" id="svdpBuilderSubmit" class="svdp-btn svdp-btn-primary" hidden>Submit Request</button>
                </div>
            </main>

            <aside class="svdp-builder-summary" aria-label="Current request summary" hidden>
                <div class="svdp-summary-card">
                    <h4>Request Summary</h4>
                    <div id="svdpSelectedTypesSummary" class="svdp-summary-chip-list"></div>
                    <div class="svdp-summary-row" data-summary-row="furniture">
                        <span>Furniture items</span>
                        <strong id="svdpSummaryFurnitureCount">0</strong>
                    </div>
                    <div class="svdp-summary-row" data-summary-row="household_goods">
                        <span>Household Goods units</span>
                        <strong id="svdpSummaryHouseholdGoodsUnits">0</strong>
                    </div>
                    <div class="svdp-summary-row">
                        <span>Maximum <span id="svdpSummaryEntityLabel"><?php echo esc_html($requestor_entity_label); ?></span> Cost</span>
                        <strong id="svdpSummaryEstimatedCost">$0.00</strong>
                    </div>
                    <div class="svdp-summary-row" data-summary-row="delivery">
                        <span>Delivery</span>
                        <strong id="svdpSummaryDelivery">Not selected</strong>
                    </div>
                    <p class="svdp-summary-policy-note">Maximum cost based on current catalog settings. Actual fulfillment details and final redemption totals are recorded when the voucher is redeemed.</p>
                    <button type="button" id="svdpSummaryAction" class="svdp-summary-action">Continue</button>
                </div>
            </aside>
        </div>

        <div id="svdpMobileSummaryBar" class="svdp-mobile-summary-bar" hidden>
            <div class="svdp-mobile-summary-copy">
                <div id="svdpMobileSummaryTypes" class="svdp-mobile-summary-types">Furniture</div>
                <div id="svdpMobileSummaryTotal" class="svdp-mobile-summary-total">0 items • Maximum $0</div>
                <div id="svdpMobileSummaryDelivery" class="svdp-mobile-summary-delivery">Delivery not selected</div>
            </div>
            <button type="button" id="svdpMobileSummaryAction" class="svdp-mobile-summary-action">Continue</button>
        </div>
    </form>
</div>
