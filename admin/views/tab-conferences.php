<div class="svdp-conferences-tab">

    <div class="svdp-card">
        <h2>Add New Conference or Partner</h2>
        <form id="svdp-add-conference-form">
            <table class="form-table">
                <tr>
                    <th><label>Organization Type *</label></th>
                    <td>
                        <label style="margin-right: 20px;">
                            <input type="radio" name="organization_type" value="conference" checked>
                            Conference
                        </label>
                        <label style="margin-right: 20px;">
                            <input type="radio" name="organization_type" value="partner">
                            Partner
                        </label>
                        <p class="description">Conferences are St. Vincent de Paul organizations. Partners are external organizations that will be billed.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="conference_name">Organization Name *</label></th>
                    <td>
                        <input type="text" id="conference_name" name="name" class="regular-text" required>
                        <p class="description">Full name (e.g., "St Mary - Fort Wayne" or "Community Partner Organization")</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="conference_slug">Slug</label></th>
                    <td>
                        <input type="text" id="conference_slug" name="slug" class="regular-text">
                        <p class="description">URL-friendly version (auto-generated if left blank)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="eligibility_days">Eligibility Window (Days)</label></th>
                    <td>
                        <input type="number" id="eligibility_days" name="eligibility_days" value="90" min="1" max="365" class="small-text">
                        <p class="description">Days between voucher requests (default: 90)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="regular_items">Items Per Person</label></th>
                    <td>
                        <input type="number" id="regular_items" name="regular_items" value="7" min="1" max="20" class="small-text">
                        <p class="description">Number of items allowed per person (default: 7)</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Add Organization</button>
            </p>
        </form>
        <div id="add-conference-message"></div>
    </div>

    <div class="svdp-card">
        <h2>Existing Organizations</h2>
        <p class="description">
            Manage conferences and partners. The <strong>Store</strong> cannot be deleted as it's used by the cashier station.
        </p>

        <table class="wp-list-table widefat fixed striped" style="table-layout: auto;">
            <thead>
                <tr>
                    <th style="width: 15%;">Name</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 12%;">Slug</th>
                    <th style="width: 8%;">Eligibility</th>
                    <th style="width: 8%;">Items/Person</th>
                    <th style="width: 12%;">Voucher Types</th>
                    <th style="width: 15%;">Notification Email</th>
                    <th style="width: 20%;">Actions</th>
                </tr>
            </thead>
            <tbody id="conferences-list">
                <?php
                $conferences = SVDP_Conference::get_all(false);

                // Group by organization type
                $grouped = [
                    'store' => [],
                    'conference' => [],
                    'partner' => []
                ];

                foreach ($conferences as $conf) {
                    $type = $conf->organization_type ?? 'conference';
                    if (!isset($grouped[$type])) {
                        $grouped[$type] = [];
                    }
                    $grouped[$type][] = $conf;
                }

                // Display in order: Store, Conferences, Partners
                foreach (['store', 'conference', 'partner'] as $type):
                    if (empty($grouped[$type])) continue;

                    // Group header
                    $type_label = ucfirst($type);
                    if ($type === 'store') $type_label = 'Store (Internal)';
                    ?>
                    <tr class="group-header" style="background: #f0f0f1; font-weight: bold;">
                        <td colspan="8" style="padding: 10px;">
                            <?php echo $type_label; ?>
                            <span style="font-weight: normal; color: #666;">(<?php echo count($grouped[$type]); ?>)</span>
                        </td>
                    </tr>
                    <?php

                    foreach ($grouped[$type] as $conference):
                        $is_store = $conference->organization_type === 'store';
                        $allowed_types = SVDP_Settings::normalize_voucher_types(
                            $conference->allowed_voucher_types,
                            $is_store ? ['clothing'] : ['clothing', 'furniture']
                        );
                ?>
                <tr data-id="<?php echo $conference->id; ?>"
                    data-org-type="<?php echo esc_attr($conference->organization_type); ?>"
                    class="<?php echo !$conference->active ? 'inactive' : ''; ?>">
                    <td>
                        <strong><?php echo esc_html($conference->name); ?></strong>
                        <?php if (!$conference->active): ?>
                            <span class="dashicons dashicons-hidden" title="Inactive" style="color: #999;"></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $badge_colors = [
                            'conference' => '#2271b1',
                            'partner' => '#d63638',
                            'store' => '#00a32a'
                        ];
                        $color = $badge_colors[$conference->organization_type] ?? '#666';
                        ?>
                        <span style="display: inline-block; padding: 3px 8px; background: <?php echo $color; ?>; color: white; border-radius: 3px; font-size: 11px; font-weight: bold;">
                            <?php echo strtoupper($conference->organization_type); ?>
                        </span>
                    </td>
                    <td><code style="font-size: 11px;"><?php echo esc_html($conference->slug); ?></code></td>
                    <td>
                        <input type="number" class="eligibility-days small-text"
                               value="<?php echo esc_attr($conference->eligibility_days ?? 90); ?>"
                               data-id="<?php echo $conference->id; ?>"
                               min="1" max="365"
                               <?php echo $is_store ? 'disabled' : ''; ?>
                               style="width: 60px;">
                        <span style="font-size: 11px; color: #666;">days</span>
                    </td>
                    <td>
                        <input type="number" class="items-per-person small-text"
                               value="<?php echo esc_attr($conference->regular_items_per_person ?? 7); ?>"
                               data-id="<?php echo $conference->id; ?>"
                               min="1" max="20"
                               <?php echo $is_store ? 'disabled' : ''; ?>
                               style="width: 50px;">
                    </td>
                    <td>
                        <button class="button button-small edit-voucher-types"
                                data-id="<?php echo $conference->id; ?>"
                                data-types='<?php echo esc_attr(json_encode($allowed_types)); ?>'
                                <?php echo $is_store ? 'disabled' : ''; ?>>
                            <span class="dashicons dashicons-tickets-alt" style="font-size: 14px; margin-top: 2px;"></span>
                            Edit Types
                        </button>
                        <div style="font-size: 10px; color: #666; margin-top: 2px;">
                            <?php echo implode(', ', array_map('ucfirst', $allowed_types)); ?>
                        </div>
                    </td>
                    <td>
                        <input type="email" class="notification-email"
                               value="<?php echo esc_attr($conference->notification_email); ?>"
                               data-id="<?php echo $conference->id; ?>"
                               placeholder="email@example.com"
                               style="width: 100%;">
                    </td>
                    <td>
                        <?php if (!$is_store): ?>
                            <button class="button button-small update-conference" data-id="<?php echo $conference->id; ?>">
                                <span class="dashicons dashicons-saved" style="font-size: 14px; margin-top: 3px;"></span>
                                Update
                            </button>
                            <button class="button button-small edit-custom-text" data-id="<?php echo $conference->id; ?>">
                                <span class="dashicons dashicons-edit" style="font-size: 14px; margin-top: 3px;"></span>
                                Custom Text
                            </button>
                            <button class="button button-small delete-conference" data-id="<?php echo $conference->id; ?>" style="color: #d63638;">
                                <span class="dashicons dashicons-trash" style="font-size: 14px; margin-top: 3px;"></span>
                            </button>
                        <?php else: ?>
                            <em style="color: #666;">System managed</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                    endforeach;
                endforeach;
                ?>
            </tbody>
        </table>
    </div>

    <div class="svdp-card">
        <h2>Usage Instructions</h2>
        <ol>
            <li>Create a new WordPress page for each conference or partner (e.g., "Clothing Voucher Request - St Mary")</li>
            <li>Use shortcode: <code>[svdp_voucher_request conference="slug-here"]</code></li>
            <li>Publish the page and share the URL with the organization</li>
        </ol>
        <p><strong>For the cashier station:</strong> Create a page and use the shortcode <code>[svdp_cashier_station]</code></p>
        <p class="description">Only users with the "SVdP Cashier" role or administrators can access the cashier station.</p>
    </div>

</div>

<!-- Modal for Editing Voucher Types -->
<div id="voucher-types-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
        <h2 style="margin-top: 0;">Configure Allowed Voucher Types</h2>
        <p>Select which voucher types this organization can offer:</p>

        <div id="voucher-types-checkboxes" style="margin: 20px 0;">
            <!-- Checkboxes will be populated by JavaScript -->
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <button type="button" class="button" id="cancel-voucher-types">Cancel</button>
            <button type="button" class="button button-primary" id="save-voucher-types" style="margin-left: 10px;">Save Types</button>
        </div>
    </div>
</div>

<!-- Modal for Editing Custom Text -->
<div id="custom-text-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h2 style="margin-top: 0;">Custom Form Text & Rules</h2>
        <p>This text will appear on the voucher request form for this organization.</p>

        <table class="form-table">
            <tr>
                <th><label for="custom_form_text">Custom Introduction Text</label></th>
                <td>
                    <textarea id="custom_form_text" rows="4" style="width: 100%;" placeholder="Welcome to our voucher program..."></textarea>
                    <p class="description">Appears at the top of the form (optional)</p>
                </td>
            </tr>
            <tr>
                <th><label for="custom_rules_text">Custom Rules & Requirements</label></th>
                <td>
                    <textarea id="custom_rules_text" rows="6" style="width: 100%;" placeholder="• Rule 1&#10;• Rule 2&#10;• Rule 3"></textarea>
                    <p class="description">Bullet points or numbered list of rules (optional)</p>
                </td>
            </tr>
        </table>

        <div style="margin-top: 30px; text-align: right;">
            <button type="button" class="button" id="cancel-custom-text">Cancel</button>
            <button type="button" class="button button-primary" id="save-custom-text" style="margin-left: 10px;">Save Text</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentEditId = null;

    // Load available voucher types from settings
    const availableTypes = <?php echo wp_json_encode(SVDP_Settings::get_available_voucher_types()); ?>;

    // Voucher Types Modal
    $('.edit-voucher-types').on('click', function() {
        currentEditId = $(this).data('id');
        const currentTypes = $(this).data('types');

        // Populate checkboxes
        let html = '';
        availableTypes.forEach(function(type) {
            const checked = currentTypes.includes(type.trim()) ? 'checked' : '';
            const typeLabel = type.trim().charAt(0).toUpperCase() + type.trim().slice(1);
            html += `
                <label style="display: block; margin: 10px 0;">
                    <input type="checkbox" name="voucher_type" value="${type.trim()}" ${checked}>
                    ${typeLabel}
                </label>
            `;
        });
        $('#voucher-types-checkboxes').html(html);

        $('#voucher-types-modal').css('display', 'flex');
    });

    $('#cancel-voucher-types').on('click', function() {
        $('#voucher-types-modal').hide();
    });

    $('#save-voucher-types').on('click', function() {
        const selected = [];
        $('input[name="voucher_type"]:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            alert('Please select at least one voucher type.');
            return;
        }

        // Save via AJAX
        $.ajax({
            url: svdpAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'svdp_update_voucher_types',
                nonce: svdpAdmin.nonce,
                id: currentEditId,
                voucher_types: JSON.stringify(selected)
            },
            success: function(response) {
                if (response.success) {
                    alert('Voucher types updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error updating voucher types');
            }
        });
    });

    // Custom Text Modal
    $('.edit-custom-text').on('click', function() {
        currentEditId = $(this).data('id');

        // Load current custom text
        $.ajax({
            url: svdpAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'svdp_get_custom_text',
                nonce: svdpAdmin.nonce,
                id: currentEditId
            },
            success: function(response) {
                if (response.success) {
                    $('#custom_form_text').val(response.data.custom_form_text || '');
                    $('#custom_rules_text').val(response.data.custom_rules_text || '');
                    $('#custom-text-modal').css('display', 'flex');
                } else {
                    alert('Error loading custom text');
                }
            }
        });
    });

    $('#cancel-custom-text').on('click', function() {
        $('#custom-text-modal').hide();
    });

    $('#save-custom-text').on('click', function() {
        $.ajax({
            url: svdpAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'svdp_save_custom_text',
                nonce: svdpAdmin.nonce,
                id: currentEditId,
                custom_form_text: $('#custom_form_text').val(),
                custom_rules_text: $('#custom_rules_text').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('Custom text saved successfully!');
                    $('#custom-text-modal').hide();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error saving custom text');
            }
        });
    });
});
</script>

<style>
.inactive {
    opacity: 0.5;
}
.group-header td {
    border-bottom: 2px solid #ddd !important;
}
.small-text {
    width: 60px;
}
</style>
