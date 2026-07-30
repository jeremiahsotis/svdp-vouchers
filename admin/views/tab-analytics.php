<?php
$analytics_data = SVDP_Analytics::get_dashboard_data(SVDP_Analytics::get_default_filters());
if (is_wp_error($analytics_data)) {
    echo '<div class="notice notice-error"><p>' . esc_html($analytics_data->get_error_message()) . '</p></div>';
    return;
}

$all_organizations = SVDP_Analytics::get_organizations();
$voucher_type_options = SVDP_Analytics::get_voucher_type_options();
$filters = $analytics_data['filters'];

if (!function_exists('svdp_analytics_stat_box')) {
    function svdp_analytics_stat_box($id, $label, $class = '') {
        ?>
        <div class="stat-box <?php echo esc_attr($class); ?>">
            <div class="stat-number" id="<?php echo esc_attr($id); ?>">0</div>
            <div class="stat-label"><?php echo esc_html($label); ?></div>
            <div class="stat-detail" id="<?php echo esc_attr($id . '_detail'); ?>"></div>
        </div>
        <?php
    }
}
?>

<div class="svdp-analytics-tab">
    <div class="svdp-card svdp-filters-panel">
        <h2>Filters</h2>
        <p class="description">Filter analytics by date range, organization, and voucher type. Month to Date is the default starting view.</p>

        <div class="filter-grid">
            <div class="filter-group">
                <label for="svdp_filter_date_range">Date Range</label>
                <select id="svdp_filter_date_range" name="date_range">
                    <option value="mtd" selected>Month to Date</option>
                    <option value="all">All Time</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="ytd">Year to Date</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            <div class="filter-group custom-date-group" id="custom_date_inputs" hidden>
                <label>Custom Dates</label>
                <div class="custom-date-row">
                    <input type="date" id="svdp_filter_start_date" name="start_date">
                    <span>to</span>
                    <input type="date" id="svdp_filter_end_date" name="end_date">
                </div>
            </div>

            <div class="filter-group">
                <label for="svdp_filter_org_type">Organization Type</label>
                <select id="svdp_filter_org_type" name="org_type">
                    <option value="all">All Types</option>
                    <option value="conference">Conference</option>
                    <option value="partner">Partner</option>
                    <option value="store">Store</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="svdp_filter_org_id">Specific Organization</label>
                <select id="svdp_filter_org_id" name="org_id">
                    <option value="all">All Organizations</option>
                    <?php foreach ($all_organizations as $org): ?>
                        <option value="<?php echo esc_attr($org->id); ?>" data-type="<?php echo esc_attr($org->organization_type); ?>">
                            <?php echo esc_html($org->name); ?> (<?php echo esc_html(SVDP_Analytics::format_organization_type($org->organization_type)); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="svdp_filter_voucher_type">Voucher Type</label>
                <select id="svdp_filter_voucher_type" name="voucher_type">
                    <option value="all">All Types</option>
                    <?php foreach ($voucher_type_options as $type => $label): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <button type="button" id="svdp_apply_filters" class="button button-primary">Apply Filters</button>
            <button type="button" id="svdp_reset_filters" class="button">Reset</button>
            <span id="svdp_filter_loading" hidden>
                <span class="spinner is-active"></span> Loading...
            </span>
        </div>

        <div id="svdp_active_filters">
            <strong>Active Filters:</strong>
            <div id="svdp_filter_chips"></div>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Voucher Overview</h2>
        <p class="description selected-date-label"></p>
        <div class="stats-grid">
            <?php svdp_analytics_stat_box('overview_total_vouchers', 'Total Vouchers'); ?>
            <?php svdp_analytics_stat_box('overview_total_redeemed', 'Total Redeemed', 'success'); ?>
            <?php svdp_analytics_stat_box('overview_currently_active', 'Currently Active', 'info'); ?>
            <?php svdp_analytics_stat_box('overview_total_expired', 'Total Expired', 'warning'); ?>
            <?php svdp_analytics_stat_box('overview_total_denied', 'Denied/Blocked', 'warning'); ?>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Current Month-to-Date Overview</h2>
        <p class="description">Defaults to Month to Date and updates to the selected Date Range when filters are applied.</p>
        <div class="stats-grid">
            <?php svdp_analytics_stat_box('period_total_vouchers', 'Total Vouchers'); ?>
            <?php svdp_analytics_stat_box('period_total_redeemed', 'Total Redeemed', 'success'); ?>
            <?php svdp_analytics_stat_box('period_currently_active', 'Currently Active', 'info'); ?>
            <?php svdp_analytics_stat_box('period_total_expired', 'Total Expired', 'warning'); ?>
            <?php svdp_analytics_stat_box('period_total_denied', 'Denied/Blocked', 'warning'); ?>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Community Impact</h2>
        <p class="description selected-date-label"></p>
        <div class="stats-grid">
            <?php svdp_analytics_stat_box('impact_people_served', 'People Served', 'success'); ?>
            <?php svdp_analytics_stat_box('impact_total_value', 'Total Value Provided', 'success'); ?>
            <?php svdp_analytics_stat_box('impact_total_items', 'Total Items Provided', 'info'); ?>
        </div>

        <h3>Community Impact by Voucher Type</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Voucher Type</th>
                    <th>People Served</th>
                    <th>Adults</th>
                    <th>Children</th>
                    <th>Total Value Provided</th>
                    <th>Total Items Provided</th>
                </tr>
            </thead>
            <tbody id="impact_by_type_body"></tbody>
        </table>

        <h3>Winter Coats</h3>
        <div class="stats-grid">
            <?php svdp_analytics_stat_box('coats_total', 'Total Coats', 'info'); ?>
            <?php svdp_analytics_stat_box('coats_adults', 'Adult Coats', 'info'); ?>
            <?php svdp_analytics_stat_box('coats_children', 'Child Coats', 'info'); ?>
        </div>
    </div>

    <div class="svdp-card">
        <h2>Performance by Organization</h2>
        <p class="description">Detailed redemption metrics for each Conference, Partner, and Store.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Type</th>
                    <th>Vouchers Issued</th>
                    <th>Vouchers Redeemed</th>
                    <th>Redemption Rate</th>
                    <th>Items Redeemed</th>
                    <th>Redemption Value</th>
                </tr>
            </thead>
            <tbody id="organization_performance_body"></tbody>
        </table>
    </div>

    <div class="svdp-card">
        <h2>Denied/Blocked Vouchers</h2>
        <p class="description">Voucher requests blocked due to eligibility rules.</p>

        <div class="stats-grid">
            <?php svdp_analytics_stat_box('denied_total', 'Denied/Blocked', 'warning'); ?>
        </div>

        <h3>Denied by Organization</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Denied Count</th>
                </tr>
            </thead>
            <tbody id="denied_by_org_body"></tbody>
        </table>

        <h3>Recent Denied Vouchers</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>DOB</th>
                    <th>Organization</th>
                    <th>Voucher Type</th>
                    <th>Requested By</th>
                    <th>Date</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody id="recent_denied_body"></tbody>
        </table>
    </div>

    <div class="svdp-card">
        <h2>Emergency Override Statistics</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody id="override_summary_body"></tbody>
        </table>

        <h3>Overrides by Manager</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Manager</th>
                    <th>Overrides Approved</th>
                    <th>% of Total Overrides</th>
                </tr>
            </thead>
            <tbody id="override_manager_body"></tbody>
        </table>

        <h3>Overrides by Reason</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Reason</th>
                    <th>Count</th>
                    <th>% of Total Overrides</th>
                </tr>
            </thead>
            <tbody id="override_reason_body"></tbody>
        </table>
    </div>

    <div class="svdp-card">
        <h2>Export Data</h2>
        <p class="description">Export voucher rows using the active filters at the top of this page.</p>
        <p id="export_filter_summary" class="svdp-export-summary"></p>

        <form id="svdp-export-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="svdp_export_vouchers">
            <?php wp_nonce_field('svdp_export', 'svdp_export_nonce'); ?>
            <input type="hidden" name="filter_date_range" id="export_filter_date_range" value="<?php echo esc_attr($filters['date_range']); ?>">
            <input type="hidden" name="filter_start_date" id="export_filter_start_date" value="">
            <input type="hidden" name="filter_end_date" id="export_filter_end_date" value="">
            <input type="hidden" name="filter_org_type" id="export_filter_org_type" value="all">
            <input type="hidden" name="filter_org_id" id="export_filter_org_id" value="all">
            <input type="hidden" name="filter_voucher_type" id="export_filter_voucher_type" value="all">

            <p class="submit">
                <button type="submit" class="button button-primary">Export Data</button>
            </p>
        </form>
    </div>
</div>

<style>
.svdp-analytics-tab .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin: 20px 0;
}

.svdp-analytics-tab .stat-box {
    background: #fff;
    border: 1px solid #ddd;
    border-left: 4px solid #006BA8;
    padding: 18px;
    text-align: center;
    border-radius: 4px;
}

.svdp-analytics-tab .stat-box.success {
    border-left-color: #28a745;
}

.svdp-analytics-tab .stat-box.warning {
    border-left-color: #ffc107;
}

.svdp-analytics-tab .stat-box.info {
    border-left-color: #17a2b8;
}

.svdp-analytics-tab .stat-number {
    font-size: 30px;
    font-weight: 700;
    color: #006BA8;
    margin-bottom: 5px;
}

.svdp-analytics-tab .stat-box.success .stat-number {
    color: #28a745;
}

.svdp-analytics-tab .stat-box.warning .stat-number {
    color: #b15d00;
}

.svdp-analytics-tab .stat-box.info .stat-number {
    color: #167386;
}

.svdp-analytics-tab .stat-label {
    font-size: 14px;
    color: #555;
    font-weight: 600;
}

.svdp-analytics-tab .stat-detail {
    font-size: 12px;
    color: #777;
    margin-top: 5px;
}

.svdp-analytics-tab .svdp-filters-panel {
    background: #f8f9fa;
    border-left: 4px solid #2271b1;
}

.svdp-analytics-tab .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.svdp-analytics-tab .filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.svdp-analytics-tab .filter-group select,
.svdp-analytics-tab .filter-group input[type="date"] {
    width: 100%;
}

.svdp-analytics-tab .custom-date-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.svdp-analytics-tab .filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
    margin-top: 10px;
}

.svdp-analytics-tab #svdp_filter_loading .spinner {
    float: none;
    margin: 0 4px 0 0;
}

.svdp-analytics-tab #svdp_active_filters {
    margin-top: 15px;
}

.svdp-analytics-tab #svdp_filter_chips {
    display: inline-block;
    margin-left: 10px;
}

.svdp-analytics-tab .filter-chip {
    display: inline-block;
    background: #2271b1;
    color: #fff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 8px;
    margin-bottom: 5px;
}

.svdp-analytics-tab .org-type-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.svdp-analytics-tab .org-type-conference {
    background: #e3f2fd;
    color: #1976d2;
}

.svdp-analytics-tab .org-type-partner {
    background: #fff3e0;
    color: #a04f00;
}

.svdp-analytics-tab .org-type-store {
    background: #e8f5e9;
    color: #2f6f33;
}

.svdp-analytics-tab .svdp-export-summary {
    font-weight: 600;
}
</style>

<script>
jQuery(function($) {
    var initialData = <?php echo wp_json_encode($analytics_data); ?>;

    function formatNumber(value) {
        return Number(value || 0).toLocaleString();
    }

    function formatMoney(value) {
        return '$' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function pct(part, total) {
        return total > 0 ? Math.round((Number(part || 0) / Number(total)) * 1000) / 10 : 0;
    }

    function getFilters() {
        return {
            date_range: $('#svdp_filter_date_range').val(),
            start_date: $('#svdp_filter_start_date').val(),
            end_date: $('#svdp_filter_end_date').val(),
            org_type: $('#svdp_filter_org_type').val(),
            org_id: $('#svdp_filter_org_id').val(),
            voucher_type: $('#svdp_filter_voucher_type').val()
        };
    }

    function setStat(prefix, data) {
        $('#' + prefix + '_total_vouchers').text(formatNumber(data.total_vouchers));
        $('#' + prefix + '_total_redeemed').text(formatNumber(data.total_redeemed));
        $('#' + prefix + '_currently_active').text(formatNumber(data.currently_active));
        $('#' + prefix + '_total_expired').text(formatNumber(data.total_expired));
        $('#' + prefix + '_total_denied').text(formatNumber(data.total_denied));
    }

    function renderRows($tbody, rows, emptyColspan, renderer) {
        $tbody.empty();
        if (!rows || rows.length === 0) {
            $tbody.append('<tr><td colspan="' + emptyColspan + '">No data found for the selected filters.</td></tr>');
            return;
        }
        rows.forEach(function(row) {
            $tbody.append(renderer(row));
        });
    }

    function renderAnalytics(data) {
        $('.selected-date-label').text(data.date_label);
        setStat('overview', data.overview);
        setStat('period', data.period_overview);

        var impact = data.community_impact.total || {};
        $('#impact_people_served').text(formatNumber(impact.people_served));
        $('#impact_people_served_detail').text(formatNumber(impact.adults) + ' adults, ' + formatNumber(impact.children) + ' children');
        $('#impact_total_value').text(formatMoney(impact.total_value));
        $('#impact_total_items').text(formatNumber(impact.total_items));

        renderRows($('#impact_by_type_body'), data.community_impact.by_type, 6, function(row) {
            return '<tr>' +
                '<td><strong>' + escapeHtml(row.voucher_type_label) + '</strong></td>' +
                '<td>' + formatNumber(row.people_served) + '</td>' +
                '<td>' + formatNumber(row.adults) + '</td>' +
                '<td>' + formatNumber(row.children) + '</td>' +
                '<td>' + formatMoney(row.total_value) + '</td>' +
                '<td>' + formatNumber(row.total_items) + '</td>' +
                '</tr>';
        });

        $('#coats_total').text(formatNumber(data.winter_coats.total));
        $('#coats_adults').text(formatNumber(data.winter_coats.adults));
        $('#coats_children').text(formatNumber(data.winter_coats.children));

        renderRows($('#organization_performance_body'), data.organizations, 7, function(row) {
            var rate = pct(row.vouchers_redeemed, row.vouchers_issued);
            return '<tr>' +
                '<td><strong>' + escapeHtml(row.name) + '</strong></td>' +
                '<td><span class="org-type-badge org-type-' + escapeHtml(row.organization_type) + '">' + escapeHtml(row.organization_type_label) + '</span></td>' +
                '<td>' + formatNumber(row.vouchers_issued) + '</td>' +
                '<td>' + formatNumber(row.vouchers_redeemed) + '</td>' +
                '<td><strong>' + rate + '%</strong></td>' +
                '<td>' + formatNumber(row.items_redeemed) + '</td>' +
                '<td><strong>' + formatMoney(row.redemption_value) + '</strong></td>' +
                '</tr>';
        });

        $('#denied_total').text(formatNumber(data.denied.total));
        renderRows($('#denied_by_org_body'), data.denied.by_organization, 2, function(row) {
            return '<tr><td>' + escapeHtml(row.name) + '</td><td>' + formatNumber(row.denied_count) + '</td></tr>';
        });
        renderRows($('#recent_denied_body'), data.denied.recent, 7, function(row) {
            return '<tr>' +
                '<td>' + escapeHtml(row.name) + '</td>' +
                '<td>' + escapeHtml(row.dob) + '</td>' +
                '<td>' + escapeHtml(row.conference_name) + '</td>' +
                '<td>' + escapeHtml(row.voucher_type_label) + '</td>' +
                '<td>' + escapeHtml(row.requested_by) + '</td>' +
                '<td>' + escapeHtml(row.voucher_created_date) + '</td>' +
                '<td><small>' + escapeHtml(row.denial_reason) + '</small></td>' +
                '</tr>';
        });

        var overridePct = Number(data.overrides.override_percentage || 0);
        $('#override_summary_body').html('<tr><td><strong>Total Overrides</strong></td><td>' + formatNumber(data.overrides.override_count) + '</td><td>' + overridePct + '%</td></tr>');
        renderRows($('#override_manager_body'), data.overrides.by_manager, 3, function(row) {
            return '<tr><td>' + escapeHtml(row.manager_name) + '</td><td>' + formatNumber(row.override_count) + '</td><td>' + pct(row.override_count, data.overrides.override_count) + '%</td></tr>';
        });
        renderRows($('#override_reason_body'), data.overrides.by_reason, 3, function(row) {
            return '<tr><td>' + escapeHtml(row.reason_text) + '</td><td>' + formatNumber(row.override_count) + '</td><td>' + pct(row.override_count, data.overrides.override_count) + '%</td></tr>';
        });

        syncFiltersToExport(data.filters);
        updateFilterChips(data.filters, data.date_label);
    }

    function applyFilters(filters) {
        $('#svdp_filter_loading').removeAttr('hidden');
        $('#svdp_apply_filters').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'svdp_apply_analytics_filters',
                nonce: '<?php echo esc_js(wp_create_nonce('svdp_analytics_filters')); ?>',
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    renderAnalytics(response.data);
                } else {
                    alert('Error applying filters: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error communicating with server.');
            },
            complete: function() {
                $('#svdp_filter_loading').attr('hidden', 'hidden');
                $('#svdp_apply_filters').prop('disabled', false);
            }
        });
    }

    function syncFiltersToExport(filters) {
        $('#export_filter_date_range').val(filters.date_range);
        $('#export_filter_start_date').val(filters.start_date || '');
        $('#export_filter_end_date').val(filters.end_date || '');
        $('#export_filter_org_type').val(filters.org_type);
        $('#export_filter_org_id').val(filters.org_id);
        $('#export_filter_voucher_type').val(filters.voucher_type);
    }

    function updateFilterChips(filters, dateLabel) {
        var chips = ['Date: ' + dateLabel];

        if (filters.org_type !== 'all') {
            chips.push('Organization Type: ' + $('#svdp_filter_org_type option:selected').text());
        }
        if (filters.org_id !== 'all') {
            chips.push('Organization: ' + $('#svdp_filter_org_id option:selected').text());
        }
        if (filters.voucher_type !== 'all') {
            chips.push('Voucher Type: ' + $('#svdp_filter_voucher_type option:selected').text());
        }

        $('#svdp_filter_chips').html(chips.map(function(chip) {
            return '<span class="filter-chip">' + escapeHtml(chip) + '</span>';
        }).join(''));
        $('#export_filter_summary').text(chips.join(' | '));
    }

    $('#svdp_filter_date_range').on('change', function() {
        $('#custom_date_inputs').prop('hidden', $(this).val() !== 'custom');
    });

    $('#svdp_filter_org_type').on('change', function() {
        var selectedType = $(this).val();
        var $orgSelect = $('#svdp_filter_org_id');

        $orgSelect.find('option').each(function() {
            var optionType = $(this).data('type');
            $(this).prop('hidden', selectedType !== 'all' && optionType && optionType !== selectedType);
        });
        $orgSelect.val('all');
    });

    $('#svdp_apply_filters').on('click', function() {
        var filters = getFilters();
        if (filters.date_range === 'custom' && (!filters.start_date || !filters.end_date)) {
            alert('Please select both start and end dates for the custom range.');
            return;
        }
        applyFilters(filters);
    });

    $('#svdp_reset_filters').on('click', function() {
        $('#svdp_filter_date_range').val('mtd');
        $('#svdp_filter_org_type').val('all').trigger('change');
        $('#svdp_filter_org_id').val('all');
        $('#svdp_filter_voucher_type').val('all');
        $('#svdp_filter_start_date').val('');
        $('#svdp_filter_end_date').val('');
        $('#custom_date_inputs').prop('hidden', true);
        applyFilters(getFilters());
    });

    renderAnalytics(initialData);
});
</script>
