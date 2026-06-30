<div class="svdp-settings-tab">

    <?php
    // Get current settings
    $adult_item_value = SVDP_Settings::get_setting('adult_item_value', '5.00');
    $child_item_value = SVDP_Settings::get_setting('child_item_value', '3.00');
    $store_hours = SVDP_Settings::get_setting('store_hours', 'Monday-Friday 9am-5pm');
    $redemption_instructions = SVDP_Settings::get_setting('redemption_instructions', 'Neighbors should visit the store and provide their first name, last name, and date of birth at the counter.');
    $available_voucher_types = implode(',', SVDP_Settings::get_available_voucher_types());
    $voucher_type_capabilities = class_exists('SVDP_Voucher_Type_Settings') ? SVDP_Voucher_Type_Settings::get_capabilities() : [];
    $voucher_type_descriptions = class_exists('SVDP_Voucher_Type_Settings') ? SVDP_Voucher_Type_Settings::get_descriptions() : [];
    $voucher_type_labels = [
        'clothing' => 'Clothing Voucher',
        'furniture' => 'Furniture Voucher',
        'household_goods' => 'Household Goods Voucher',
    ];
    ?>

    <div class="svdp-card">
        <h2>Item Value Configuration</h2>
        <p>Set the dollar value per item for redemption calculations. These values are used when vouchers are redeemed to calculate the total redemption value.</p>

        <table class="form-table">
            <tr>
                <th><label for="adult_item_value">Adult Item Value</label></th>
                <td>
                    <input type="number" id="adult_item_value" name="adult_item_value"
                           value="<?php echo esc_attr($adult_item_value); ?>"
                           step="0.01" min="0" class="regular-text" />
                    <p class="description">Dollar value per adult item (e.g., 5.00)</p>
                </td>
            </tr>
            <tr>
                <th><label for="child_item_value">Child Item Value</label></th>
                <td>
                    <input type="number" id="child_item_value" name="child_item_value"
                           value="<?php echo esc_attr($child_item_value); ?>"
                           step="0.01" min="0" class="regular-text" />
                    <p class="description">Dollar value per child item (e.g., 3.00)</p>
                </td>
            </tr>
        </table>

        <div style="background: #f0f0f1; padding: 15px; margin-top: 15px; border-left: 4px solid #2271b1;">
            <strong>Example Calculation:</strong><br>
            If a voucher is redeemed with <strong>5 adult items</strong> and <strong>3 child items</strong>:<br>
            <span id="example-calculation">
                (5 × $<?php echo $adult_item_value; ?>) + (3 × $<?php echo $child_item_value; ?>) =
                $<?php echo number_format((5 * $adult_item_value) + (3 * $child_item_value), 2); ?>
            </span>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Voucher Types Management</h2>
        <p>Configure the root voucher types available for new requests, the delivery capability for each type, and the short description shown on the Assistance Needed step.</p>

        <table class="form-table">
            <tr>
                <th><label for="available_voucher_types">Available Voucher Types</label></th>
                <td>
                    <input type="text" id="available_voucher_types" name="available_voucher_types"
                           value="<?php echo esc_attr($available_voucher_types); ?>"
                           class="regular-text" />
                    <p class="description">Comma-separated list of available root types. Release C supports clothing, furniture, and household_goods.</p>
                </td>
            </tr>
        </table>

        <?php if (class_exists('SVDP_Voucher_Type_Settings')): ?>
            <table class="widefat striped" style="margin-top: 16px;">
                <thead>
                    <tr>
                        <th>Voucher Type</th>
                        <th>Delivery Available</th>
                        <th>Assistance Step Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (SVDP_Voucher_Type_Settings::get_root_voucher_types() as $voucher_type): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($voucher_type_labels[$voucher_type] ?? ucfirst($voucher_type)); ?></strong>
                                <br>
                                <code><?php echo esc_html($voucher_type); ?></code>
                            </td>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        id="voucher_type_delivery_<?php echo esc_attr($voucher_type); ?>"
                                        name="voucher_type_delivery_<?php echo esc_attr($voucher_type); ?>"
                                        value="1"
                                        <?php checked(!empty($voucher_type_capabilities[$voucher_type]['delivery_available'])); ?>
                                    >
                                    Delivery can be selected for this voucher type
                                </label>
                                <?php if ($voucher_type === 'clothing'): ?>
                                    <p class="description">Default: off</p>
                                <?php else: ?>
                                    <p class="description">Default: on</p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <textarea
                                    id="voucher_type_description_<?php echo esc_attr($voucher_type); ?>"
                                    name="voucher_type_description_<?php echo esc_attr($voucher_type); ?>"
                                    rows="3"
                                    class="large-text"
                                ><?php echo esc_textarea($voucher_type_descriptions[$voucher_type] ?? SVDP_Voucher_Type_Settings::get_default_description($voucher_type)); ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="svdp-card">
        <h2>Form Boilerplate Text</h2>
        <p>This text appears on all voucher request forms. Use this for general information that applies to all organizations.</p>

        <table class="form-table">
            <tr>
                <th><label for="store_hours">Store Hours</label></th>
                <td>
                    <input type="text" id="store_hours" name="store_hours"
                           value="<?php echo esc_attr($store_hours); ?>"
                           class="regular-text" />
                    <p class="description">Hours when neighbors can redeem vouchers</p>
                </td>
            </tr>
            <tr>
                <th><label for="redemption_instructions">Redemption Instructions</label></th>
                <td>
                    <textarea id="redemption_instructions" name="redemption_instructions"
                              rows="4" class="large-text"><?php echo esc_textarea($redemption_instructions); ?></textarea>
                    <p class="description">Instructions for neighbors when redeeming vouchers (e.g., what to bring, store location)</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px;">
        <button type="button" id="save-settings" class="button button-primary button-large">
            <span class="dashicons dashicons-saved" style="margin-top: 3px;"></span>
            Save All Settings
        </button>
        <span id="settings-save-status" style="margin-left: 15px; font-weight: bold;"></span>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Update example calculation in real-time
        $('#adult_item_value, #child_item_value').on('input', function() {
            var adultValue = parseFloat($('#adult_item_value').val()) || 0;
            var childValue = parseFloat($('#child_item_value').val()) || 0;
            var total = (5 * adultValue) + (3 * childValue);
            $('#example-calculation').html(
                '(5 × $' + adultValue.toFixed(2) + ') + (3 × $' + childValue.toFixed(2) + ') = $' + total.toFixed(2)
            );
        });

        // Save settings via AJAX
        $('#save-settings').on('click', function() {
            var button = $(this);
            var status = $('#settings-save-status');

            button.prop('disabled', true);
            status.html('<span style="color: #666;">Saving...</span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'svdp_save_settings',
                    nonce: svdpAdmin.nonce,
                    adult_item_value: $('#adult_item_value').val(),
                    child_item_value: $('#child_item_value').val(),
                    store_hours: $('#store_hours').val(),
                    redemption_instructions: $('#redemption_instructions').val(),
                    available_voucher_types: $('#available_voucher_types').val(),
                    voucher_type_delivery_clothing: $('#voucher_type_delivery_clothing').is(':checked') ? '1' : '',
                    voucher_type_delivery_furniture: $('#voucher_type_delivery_furniture').is(':checked') ? '1' : '',
                    voucher_type_delivery_household_goods: $('#voucher_type_delivery_household_goods').is(':checked') ? '1' : '',
                    voucher_type_description_clothing: $('#voucher_type_description_clothing').val(),
                    voucher_type_description_furniture: $('#voucher_type_description_furniture').val(),
                    voucher_type_description_household_goods: $('#voucher_type_description_household_goods').val()
                },
                success: function(response) {
                    if (response.success) {
                        status.html('<span style="color: #008000;">✓ Settings saved successfully!</span>');
                        setTimeout(function() {
                            status.fadeOut(function() {
                                $(this).html('').show();
                            });
                        }, 3000);
                    } else {
                        status.html('<span style="color: #dc3232;">Error: ' + (response.data || 'Failed to save settings') + '</span>');
                    }
                    button.prop('disabled', false);
                },
                error: function() {
                    status.html('<span style="color: #dc3232;">Error: Failed to save settings</span>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>


    <div class="svdp-card">
        <h2>Database Info</h2>
        <?php
        global $wpdb;
        $vouchers_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}svdp_vouchers");
        $active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}svdp_vouchers WHERE status = 'Active'");
        $redeemed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}svdp_vouchers WHERE status = 'Redeemed'");
        $conferences_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}svdp_conferences WHERE active = 1");
        ?>
        
        <table class="widefat">
            <tr>
                <th>Total Vouchers</th>
                <td><?php echo number_format($vouchers_count); ?></td>
            </tr>
            <tr>
                <th>Active Vouchers</th>
                <td><?php echo number_format($active_count); ?></td>
            </tr>
            <tr>
                <th>Redeemed Vouchers</th>
                <td><?php echo number_format($redeemed_count); ?></td>
            </tr>
            <tr>
                <th>Active Conferences</th>
                <td><?php echo number_format($conferences_count); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="svdp-card">
        <h2>System Information</h2>
        <table class="widefat">
            <tr>
                <th>Plugin Version</th>
                <td><?php echo SVDP_VOUCHERS_VERSION; ?></td>
            </tr>
            <tr>
                <th>WordPress Version</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th>PHP Version</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
        </table>
    </div>
    
</div>
