<?php
global $wpdb;
$vouchers_table = $wpdb->prefix . 'svdp_vouchers';
$conferences_table = $wpdb->prefix . 'svdp_conferences';

// Get date ranges
$today = date('Y-m-d');
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$ninety_days_ago = date('Y-m-d', strtotime('-90 days'));
$this_year = date('Y-01-01');

// Overall stats
$total_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM $vouchers_table WHERE status != 'Denied'");
$active_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM $vouchers_table WHERE status = 'Active' AND voucher_created_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$redeemed_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM $vouchers_table WHERE status = 'Redeemed'");
$denied_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM $vouchers_table WHERE status = 'Denied'");

// Time-based stats
$vouchers_30_days = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $vouchers_table WHERE voucher_created_date >= %s AND status != 'Denied'", $thirty_days_ago));
$vouchers_90_days = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $vouchers_table WHERE voucher_created_date >= %s AND status != 'Denied'", $ninety_days_ago));
$vouchers_this_year = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $vouchers_table WHERE voucher_created_date >= %s AND status != 'Denied'", $this_year));

// Household stats
$total_adults = $wpdb->get_var("SELECT SUM(adults) FROM $vouchers_table WHERE status != 'Denied'");
$total_children = $wpdb->get_var("SELECT SUM(children) FROM $vouchers_table WHERE status != 'Denied'");
$total_people_served = $total_adults + $total_children;
$total_value = $wpdb->get_var("SELECT SUM(voucher_value) FROM $vouchers_table WHERE status != 'Denied'");

// Per-organization breakdown with redemption metrics
$organization_stats = $wpdb->get_results("
    SELECT c.name,
           c.organization_type,
           COUNT(v.id) as vouchers_issued,
           SUM(CASE WHEN v.status = 'Redeemed' THEN 1 ELSE 0 END) as vouchers_redeemed,
           SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.items_adult_redeemed, 0) + COALESCE(v.items_children_redeemed, 0) ELSE 0 END) as items_redeemed,
           SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.redemption_total_value, 0) ELSE 0 END) as redemption_value,
           SUM(v.adults + v.children) as people_served
    FROM $conferences_table c
    LEFT JOIN $vouchers_table v ON c.id = v.conference_id AND v.status != 'Denied'
    WHERE c.active = 1
    GROUP BY c.id, c.name, c.organization_type
    ORDER BY vouchers_issued DESC
");

// Coat stats - All Time
$coats_adults_all_time = $wpdb->get_var("SELECT COALESCE(SUM(coat_adults_issued), 0) FROM $vouchers_table WHERE coat_status = 'Issued'");
$coats_children_all_time = $wpdb->get_var("SELECT COALESCE(SUM(coat_children_issued), 0) FROM $vouchers_table WHERE coat_status = 'Issued'");
$coats_total_all_time = $coats_adults_all_time + $coats_children_all_time;

// Calculate the start of current coat season (most recent August 1st)
$current_month = date('n'); // 1-12
$current_year = date('Y');
$season_start_date = ($current_month >= 8) ? "$current_year-08-01" : ($current_year - 1) . "-08-01";

// Coat stats - This Season
$coats_adults_this_season = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(coat_adults_issued), 0) FROM $vouchers_table WHERE coat_status = 'Issued' AND coat_issued_date >= %s",
    $season_start_date
));
$coats_children_this_season = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(coat_children_issued), 0) FROM $vouchers_table WHERE coat_status = 'Issued' AND coat_issued_date >= %s",
    $season_start_date
));
$coats_total_this_season = $coats_adults_this_season + $coats_children_this_season;

// Item-based metrics (NEW in Phase 7)
$items_adult_redeemed = $wpdb->get_var("SELECT COALESCE(SUM(items_adult_redeemed), 0) FROM $vouchers_table WHERE status = 'Redeemed'");
$items_children_redeemed = $wpdb->get_var("SELECT COALESCE(SUM(items_children_redeemed), 0) FROM $vouchers_table WHERE status = 'Redeemed'");
$items_total_redeemed = $items_adult_redeemed + $items_children_redeemed;
$redemption_total_value = $wpdb->get_var("SELECT COALESCE(SUM(redemption_total_value), 0) FROM $vouchers_table WHERE status = 'Redeemed'");
$avg_items_per_voucher = $redeemed_vouchers > 0 ? round($items_total_redeemed / $redeemed_vouchers, 1) : 0;

// Get item values from settings
$item_values = SVDP_Settings::get_item_values();

// Organization type breakdown
$org_type_stats = $wpdb->get_results("
    SELECT c.organization_type,
           COUNT(v.id) as voucher_count,
           SUM(CASE WHEN v.status = 'Redeemed' THEN 1 ELSE 0 END) as redeemed_count,
           SUM(CASE WHEN v.status = 'Redeemed' THEN COALESCE(v.items_adult_redeemed, 0) + COALESCE(v.items_children_redeemed, 0) ELSE 0 END) as items_provided
    FROM $conferences_table c
    LEFT JOIN $vouchers_table v ON c.id = v.conference_id AND v.status != 'Denied'
    WHERE c.active = 1
    GROUP BY c.organization_type
    ORDER BY voucher_count DESC
");

// Voucher type breakdown
$voucher_type_stats = $wpdb->get_results("
    SELECT COALESCE(voucher_type, 'clothing') as type,
           COUNT(*) as voucher_count,
           SUM(CASE WHEN status = 'Redeemed' THEN COALESCE(items_adult_redeemed, 0) + COALESCE(items_children_redeemed, 0) ELSE 0 END) as items_redeemed,
           SUM(CASE WHEN status = 'Redeemed' THEN COALESCE(redemption_total_value, 0) ELSE 0 END) as total_value
    FROM $vouchers_table
    WHERE status != 'Denied'
    GROUP BY voucher_type
    ORDER BY voucher_count DESC
");
// Get all active organizations for filter dropdown
$all_organizations = $wpdb->get_results("
    SELECT id, name, organization_type
    FROM $conferences_table
    WHERE active = 1
    ORDER BY organization_type, name
");
?>

<div class="svdp-analytics-tab">

    <!-- Filters Panel -->
    <div class="svdp-card svdp-filters-panel">
        <h2>🔍 Filters</h2>
        <p class="description">Filter all analytics metrics by date range, organization, and voucher type. Click "Apply Filters" to update the data.</p>

        <div class="filter-grid">
            <div class="filter-group">
                <label>📅 Date Range</label>
                <select id="svdp_filter_date_range" name="date_range">
                    <option value="all">All Time</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                    <option value="ytd">Year to Date</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            <div class="filter-group" id="custom_date_inputs" style="display: none;">
                <label>Custom Dates</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="date" id="svdp_filter_start_date" name="start_date" style="flex: 1;">
                    <span>to</span>
                    <input type="date" id="svdp_filter_end_date" name="end_date" style="flex: 1;">
                </div>
            </div>

            <div class="filter-group">
                <label>🏢 Organization Type</label>
                <select id="svdp_filter_org_type" name="org_type">
                    <option value="all">All Types</option>
                    <option value="conference">Conference</option>
                    <option value="partner">Partner</option>
                    <option value="store">Store</option>
                </select>
            </div>

            <div class="filter-group">
                <label>🏛️ Specific Organization</label>
                <select id="svdp_filter_org_id" name="org_id">
                    <option value="all">All Organizations</option>
                    <?php foreach ($all_organizations as $org): ?>
                        <option value="<?php echo esc_attr($org->id); ?>" data-type="<?php echo esc_attr($org->organization_type); ?>">
                            <?php echo esc_html($org->name); ?> (<?php echo ucfirst($org->organization_type); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>🎫 Voucher Type</label>
                <select id="svdp_filter_voucher_type" name="voucher_type">
                    <option value="all">All Types</option>
                    <option value="clothing">Clothing</option>
                    <option value="furniture">Furniture</option>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <button type="button" id="svdp_apply_filters" class="button button-primary">Apply Filters</button>
            <button type="button" id="svdp_reset_filters" class="button">Reset</button>
            <span id="svdp_filter_loading" style="display: none; margin-left: 10px;">
                <span class="spinner is-active" style="float: none; margin: 0;"></span> Loading...
            </span>
        </div>

        <div id="svdp_active_filters" style="margin-top: 15px; display: none;">
            <strong>Active Filters:</strong>
            <div id="svdp_filter_chips" style="display: inline-block; margin-left: 10px;"></div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="svdp-card">
        <h2>📊 Overview Statistics</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($total_vouchers); ?></div>
                <div class="stat-label">Total Vouchers</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($active_vouchers); ?></div>
                <div class="stat-label">Active (30 days)</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($redeemed_vouchers); ?></div>
                <div class="stat-label">Redeemed</div>
            </div>
            <div class="stat-box warning">
                <div class="stat-number"><?php echo number_format($denied_vouchers); ?></div>
                <div class="stat-label">Denied/Blocked</div>
            </div>
        </div>
    </div>
    
    <!-- Time Period Stats -->
    <div class="svdp-card">
        <h2>📅 Time Period Analysis</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($vouchers_30_days); ?></div>
                <div class="stat-label">Last 30 Days</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($vouchers_90_days); ?></div>
                <div class="stat-label">Last 90 Days</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($vouchers_this_year); ?></div>
                <div class="stat-label">This Year (<?php echo date('Y'); ?>)</div>
            </div>
        </div>
    </div>
    
    <!-- Impact Stats -->
    <div class="svdp-card">
        <h2>👥 Community Impact</h2>
        <div class="stats-grid">
            <div class="stat-box success">
                <div class="stat-number"><?php echo number_format($total_people_served); ?></div>
                <div class="stat-label">People Served</div>
                <div class="stat-detail"><?php echo number_format($total_adults); ?> adults, <?php echo number_format($total_children); ?> children</div>
            </div>
            <div class="stat-box success">
                <div class="stat-number">$<?php echo number_format($total_value); ?></div>
                <div class="stat-label">Total Value Provided</div>
            </div>
            <div class="stat-box info">
                <div class="stat-number"><?php echo number_format($coats_total_all_time); ?></div>
                <div class="stat-label">Winter Coats Issued (All Time)</div>
                <div class="stat-detail"><?php echo number_format($coats_adults_all_time); ?> adults, <?php echo number_format($coats_children_all_time); ?> children</div>
            </div>
            <div class="stat-box info">
                <div class="stat-number"><?php echo number_format($coats_total_this_season); ?></div>
                <div class="stat-label">Coats This Season</div>
                <div class="stat-detail"><?php echo number_format($coats_adults_this_season); ?> adults, <?php echo number_format($coats_children_this_season); ?> children</div>
            </div>
        </div>
    </div>

    <!-- Items Provided (NEW in Phase 7) -->
    <div class="svdp-card">
        <h2>📦 Items Provided</h2>
        <div class="stats-grid">
            <div class="stat-box success">
                <div class="stat-number"><?php echo number_format($items_total_redeemed); ?></div>
                <div class="stat-label">Total Items Redeemed</div>
                <div class="stat-detail"><?php echo number_format($items_adult_redeemed); ?> adult, <?php echo number_format($items_children_redeemed); ?> child</div>
            </div>
            <div class="stat-box success">
                <div class="stat-number">$<?php echo number_format($redemption_total_value, 2); ?></div>
                <div class="stat-label">Redemption Value</div>
                <div class="stat-detail">Adult: $<?php echo number_format($item_values['adult'], 2); ?>/item, Child: $<?php echo number_format($item_values['child'], 2); ?>/item</div>
            </div>
            <div class="stat-box info">
                <div class="stat-number"><?php echo number_format($avg_items_per_voucher, 1); ?></div>
                <div class="stat-label">Avg Items per Voucher</div>
            </div>
        </div>
    </div>

    <!-- Organization Type Breakdown -->
    <div class="svdp-card">
        <h2>🏢 Voucher Metrics by Organization Type</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Organization Type</th>
                    <th>Vouchers Issued</th>
                    <th>Vouchers Redeemed</th>
                    <th>Redemption Rate</th>
                    <th>Items Provided</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($org_type_stats as $stat):
                    $redemption_rate = $stat->voucher_count > 0 ? round(($stat->redeemed_count / $stat->voucher_count) * 100) : 0;
                    $type_label = ucfirst($stat->organization_type);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($type_label); ?></strong></td>
                    <td><?php echo number_format($stat->voucher_count); ?></td>
                    <td><?php echo number_format($stat->redeemed_count); ?></td>
                    <td><?php echo $redemption_rate; ?>%</td>
                    <td><?php echo number_format($stat->items_provided); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Voucher Type Breakdown -->
    <div class="svdp-card">
        <h2>🎫 Breakdown by Voucher Type</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Voucher Type</th>
                    <th>Vouchers Created</th>
                    <th>Items Redeemed</th>
                    <th>Redemption Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($voucher_type_stats as $stat):
                    $type_label = ucfirst($stat->type);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($type_label); ?></strong></td>
                    <td><?php echo number_format($stat->voucher_count); ?></td>
                    <td><?php echo number_format($stat->items_redeemed); ?></td>
                    <td>$<?php echo number_format($stat->total_value, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Per-Organization Breakdown -->
    <div class="svdp-card">
        <h2>🏛️ Performance by Organization</h2>
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
            <tbody>
                <?php foreach ($organization_stats as $stat):
                    $redemption_rate = $stat->vouchers_issued > 0 ? round(($stat->vouchers_redeemed / $stat->vouchers_issued) * 100) : 0;
                    $org_type_badge = ucfirst($stat->organization_type);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($stat->name); ?></strong></td>
                    <td><span class="org-type-badge org-type-<?php echo esc_attr($stat->organization_type); ?>"><?php echo esc_html($org_type_badge); ?></span></td>
                    <td><?php echo number_format($stat->vouchers_issued); ?></td>
                    <td><?php echo number_format($stat->vouchers_redeemed); ?></td>
                    <td><strong><?php echo $redemption_rate; ?>%</strong></td>
                    <td><?php echo number_format($stat->items_redeemed); ?></td>
                    <td><strong>$<?php echo number_format($stat->redemption_value, 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Denied Vouchers Analysis -->
    <div class="svdp-card">
        <h2>🚫 Denied/Blocked Vouchers</h2>
        <p class="description">These are voucher requests that were blocked due to eligibility rules. This data helps us understand demand and identify people attempting to get multiple vouchers.</p>
        
        <?php
        $denied_30_days = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $vouchers_table WHERE status = 'Denied' AND voucher_created_date >= %s", $thirty_days_ago));
        $denied_by_conference = $wpdb->get_results("
            SELECT c.name, COUNT(v.id) as denied_count
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            WHERE v.status = 'Denied'
            GROUP BY c.id, c.name
            ORDER BY denied_count DESC
            LIMIT 10
        ");
        
        // Get recent denied vouchers
        $recent_denied = $wpdb->get_results("
            SELECT v.*, c.name as conference_name
            FROM $vouchers_table v
            LEFT JOIN $conferences_table c ON v.conference_id = c.id
            WHERE v.status = 'Denied'
            ORDER BY v.created_at DESC
            LIMIT 20
        ");
        ?>
        
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-box warning">
                <div class="stat-number"><?php echo number_format($denied_vouchers); ?></div>
                <div class="stat-label">Total Denied (All Time)</div>
            </div>
            <div class="stat-box warning">
                <div class="stat-number"><?php echo number_format($denied_30_days); ?></div>
                <div class="stat-label">Denied (Last 30 Days)</div>
            </div>
        </div>
        
        <h3>Denied by Conference</h3>
        <table class="wp-list-table widefat fixed striped" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Conference</th>
                    <th>Denied Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($denied_by_conference as $stat): ?>
                <tr>
                    <td><?php echo esc_html($stat->name); ?></td>
                    <td><?php echo number_format($stat->denied_count); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Recent Denied Vouchers</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>DOB</th>
                    <th>Conference</th>
                    <th>Requested By</th>
                    <th>Date</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_denied as $voucher): ?>
                <tr>
                    <td><?php echo esc_html($voucher->first_name . ' ' . $voucher->last_name); ?></td>
                    <td><?php echo esc_html($voucher->dob); ?></td>
                    <td><?php echo esc_html($voucher->conference_name); ?></td>
                    <td><?php echo esc_html($voucher->vincentian_name ?: $voucher->created_by); ?></td>
                    <td><?php echo esc_html($voucher->voucher_created_date); ?></td>
                    <td><small><?php echo esc_html($voucher->denial_reason); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Export Section -->
    <div class="svdp-card">
        <h2>📥 Export Data</h2>
        <p>Download voucher data for reporting and analysis.</p>
        
        <form id="svdp-export-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="svdp_export_vouchers">
            <?php wp_nonce_field('svdp_export', 'svdp_export_nonce'); ?>

            <!-- Hidden inputs for filter state -->
            <input type="hidden" name="filter_date_range" id="export_filter_date_range" value="all">
            <input type="hidden" name="filter_start_date" id="export_filter_start_date" value="">
            <input type="hidden" name="filter_end_date" id="export_filter_end_date" value="">
            <input type="hidden" name="filter_org_type" id="export_filter_org_type" value="all">
            <input type="hidden" name="filter_org_id" id="export_filter_org_id" value="all">
            <input type="hidden" name="filter_voucher_type" id="export_filter_voucher_type" value="all">

            <table class="form-table">
                <tr>
                    <th><label>Date Range</label></th>
                    <td>
                        <select name="date_range" id="export_date_range">
                            <option value="all">All Time</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                            <option value="ytd">Year to Date</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </td>
                </tr>
                <tr id="custom_date_row" style="display: none;">
                    <th><label>Custom Dates</label></th>
                    <td>
                        <input type="date" name="start_date" id="start_date">
                        <span> to </span>
                        <input type="date" name="end_date" id="end_date">
                    </td>
                </tr>
                <tr>
                    <th><label>Include Denied Vouchers?</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="include_denied" value="1">
                            Include blocked/denied vouchers in export
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Export to Excel</button>
            </p>
        </form>
    </div>

    <!-- Override Analytics Section -->
    <div class="svdp-analytics-section">
        <h3>Emergency Override Statistics</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Total vouchers with overrides
                $override_count = $wpdb->get_var("
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}svdp_vouchers
                    WHERE manager_id IS NOT NULL
                ");

                $total_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}svdp_vouchers");
                $override_pct = $total_vouchers > 0 ? round(($override_count / $total_vouchers) * 100, 1) : 0;
                ?>
                <tr>
                    <td><strong>Total Overrides</strong></td>
                    <td><?php echo number_format($override_count); ?></td>
                    <td><?php echo $override_pct; ?>%</td>
                </tr>
            </tbody>
        </table>

        <h4>Overrides by Manager</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Manager</th>
                    <th>Overrides Approved</th>
                    <th>% of Total Overrides</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $manager_stats = $wpdb->get_results("
                    SELECT
                        m.name as manager_name,
                        COUNT(v.id) as override_count
                    FROM {$wpdb->prefix}svdp_vouchers v
                    INNER JOIN {$wpdb->prefix}svdp_managers m ON v.manager_id = m.id
                    WHERE v.manager_id IS NOT NULL
                    GROUP BY m.id, m.name
                    ORDER BY override_count DESC
                ");

                if (empty($manager_stats)) {
                    echo '<tr><td colspan="3">No override data yet.</td></tr>';
                } else {
                    foreach ($manager_stats as $stat) {
                        $pct = $override_count > 0 ? round(($stat->override_count / $override_count) * 100, 1) : 0;
                        echo '<tr>';
                        echo '<td>' . esc_html($stat->manager_name) . '</td>';
                        echo '<td>' . number_format($stat->override_count) . '</td>';
                        echo '<td>' . $pct . '%</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>

        <h4>Overrides by Reason</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Reason</th>
                    <th>Count</th>
                    <th>% of Total Overrides</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $reason_stats = $wpdb->get_results("
                    SELECT
                        r.reason_text,
                        COUNT(v.id) as override_count
                    FROM {$wpdb->prefix}svdp_vouchers v
                    INNER JOIN {$wpdb->prefix}svdp_override_reasons r ON v.reason_id = r.id
                    WHERE v.reason_id IS NOT NULL
                    GROUP BY r.id, r.reason_text
                    ORDER BY override_count DESC
                ");

                if (empty($reason_stats)) {
                    echo '<tr><td colspan="3">No override data yet.</td></tr>';
                } else {
                    foreach ($reason_stats as $stat) {
                        $pct = $override_count > 0 ? round(($stat->override_count / $override_count) * 100, 1) : 0;
                        echo '<tr>';
                        echo '<td>' . esc_html($stat->reason_text) . '</td>';
                        echo '<td>' . number_format($stat->override_count) . '</td>';
                        echo '<td>' . $pct . '%</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-box {
    background: white;
    border: 1px solid #ddd;
    border-left: 4px solid #006BA8;
    padding: 20px;
    text-align: center;
    border-radius: 4px;
}

.stat-box.success {
    border-left-color: #28a745;
}

.stat-box.warning {
    border-left-color: #ffc107;
}

.stat-box.info {
    border-left-color: #17a2b8;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #006BA8;
    margin-bottom: 5px;
}

.stat-box.success .stat-number {
    color: #28a745;
}

.stat-box.warning .stat-number {
    color: #ff9800;
}

.stat-box.info .stat-number {
    color: #17a2b8;
}

.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 600;
}

.stat-detail {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.org-type-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.org-type-conference {
    background: #e3f2fd;
    color: #1976d2;
}

.org-type-partner {
    background: #fff3e0;
    color: #f57c00;
}

.org-type-store {
    background: #e8f5e9;
    color: #388e3c;
}

/* Filters Panel */
.svdp-filters-panel {
    background: #f8f9fa;
    border-left: 4px solid #2271b1;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.filter-group select,
.filter-group input[type="date"] {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
}

.filter-actions {
    display: flex;
    align-items: center;
    padding-top: 10px;
    border-top: 1px solid #ddd;
    margin-top: 10px;
}

.filter-chip {
    display: inline-block;
    background: #2271b1;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 8px;
    margin-bottom: 5px;
}

.filter-chip .remove-filter {
    margin-left: 6px;
    cursor: pointer;
    font-weight: bold;
}

.filter-chip .remove-filter:hover {
    color: #ff4444;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle export date range changes
    $('#export_date_range').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_date_row').show();
        } else {
            $('#custom_date_row').hide();
        }
    });

    // Handle filter date range changes
    $('#svdp_filter_date_range').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_date_inputs').show();
        } else {
            $('#custom_date_inputs').hide();
        }
    });

    // Handle organization type filter changes (filter specific org dropdown)
    $('#svdp_filter_org_type').on('change', function() {
        var selectedType = $(this).val();
        var $orgSelect = $('#svdp_filter_org_id');

        if (selectedType === 'all') {
            $orgSelect.find('option').show();
        } else {
            $orgSelect.find('option').each(function() {
                var optionType = $(this).data('type');
                if (!optionType || optionType === selectedType) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        $orgSelect.val('all');
    });

    // Apply Filters
    $('#svdp_apply_filters').on('click', function() {
        var filters = {
            date_range: $('#svdp_filter_date_range').val(),
            start_date: $('#svdp_filter_start_date').val(),
            end_date: $('#svdp_filter_end_date').val(),
            org_type: $('#svdp_filter_org_type').val(),
            org_id: $('#svdp_filter_org_id').val(),
            voucher_type: $('#svdp_filter_voucher_type').val()
        };

        // Validate custom date range
        if (filters.date_range === 'custom' && (!filters.start_date || !filters.end_date)) {
            alert('Please select both start and end dates for custom range.');
            return;
        }

        applyFilters(filters);
    });

    // Reset Filters
    $('#svdp_reset_filters').on('click', function() {
        $('#svdp_filter_date_range').val('all');
        $('#svdp_filter_org_type').val('all');
        $('#svdp_filter_org_id').val('all');
        $('#svdp_filter_voucher_type').val('all');
        $('#svdp_filter_start_date').val('');
        $('#svdp_filter_end_date').val('');
        $('#custom_date_inputs').hide();
        $('#svdp_active_filters').hide();

        // Reload page to show all data
        window.location.reload();
    });

    function applyFilters(filters) {
        $('#svdp_filter_loading').show();
        $('#svdp_apply_filters').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'svdp_apply_analytics_filters',
                nonce: '<?php echo wp_create_nonce('svdp_analytics_filters'); ?>',
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    // Update all metrics with filtered data
                    updateAnalytics(response.data);
                    updateFilterChips(filters);
                    // Sync filter values to export form
                    syncFiltersToExport(filters);
                } else {
                    alert('Error applying filters: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error communicating with server.');
            },
            complete: function() {
                $('#svdp_filter_loading').hide();
                $('#svdp_apply_filters').prop('disabled', false);
            }
        });
    }

    function updateAnalytics(data) {
        // Update overview stats - Total Vouchers
        $('.stat-box').eq(0).find('.stat-number').text(data.total_vouchers.toLocaleString());

        // Update overview stats - Redeemed
        $('.stat-box').eq(2).find('.stat-number').text(data.redeemed_vouchers.toLocaleString());

        // Update Items Provided section
        var itemsCard = $('.svdp-card').filter(function() {
            return $(this).find('h2').text().includes('Items Provided');
        });
        itemsCard.find('.stat-box').eq(0).find('.stat-number').text(data.items_redeemed.toLocaleString());
        itemsCard.find('.stat-box').eq(1).find('.stat-number').text('$' + data.redemption_value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        // Update Per-Organization table
        var $orgTable = $('.svdp-card').filter(function() {
            return $(this).find('h2').text().includes('Performance by Organization');
        }).find('tbody');

        $orgTable.empty();

        if (data.organizations && data.organizations.length > 0) {
            $.each(data.organizations, function(i, org) {
                var redemptionRate = org.vouchers_issued > 0 ? Math.round((org.vouchers_redeemed / org.vouchers_issued) * 100) : 0;
                var orgTypeBadge = org.organization_type.charAt(0).toUpperCase() + org.organization_type.slice(1);
                var badgeClass = 'org-type-' + org.organization_type;

                var row = '<tr>' +
                    '<td><strong>' + org.name + '</strong></td>' +
                    '<td><span class="org-type-badge ' + badgeClass + '">' + orgTypeBadge + '</span></td>' +
                    '<td>' + parseInt(org.vouchers_issued).toLocaleString() + '</td>' +
                    '<td>' + parseInt(org.vouchers_redeemed).toLocaleString() + '</td>' +
                    '<td><strong>' + redemptionRate + '%</strong></td>' +
                    '<td>' + parseInt(org.items_redeemed).toLocaleString() + '</td>' +
                    '<td><strong>$' + parseFloat(org.redemption_value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td>' +
                    '</tr>';

                $orgTable.append(row);
            });
        } else {
            $orgTable.append('<tr><td colspan="7" style="text-align: center; padding: 20px;">No data found for the selected filters.</td></tr>');
        }
    }

    function syncFiltersToExport(filters) {
        // Sync current filter values to export form hidden inputs
        $('#export_filter_date_range').val(filters.date_range);
        $('#export_filter_start_date').val(filters.start_date || '');
        $('#export_filter_end_date').val(filters.end_date || '');
        $('#export_filter_org_type').val(filters.org_type);
        $('#export_filter_org_id').val(filters.org_id);
        $('#export_filter_voucher_type').val(filters.voucher_type);
    }

    function updateFilterChips(filters) {
        var chips = [];

        if (filters.date_range !== 'all') {
            var dateLabel = '';
            if (filters.date_range === 'custom') {
                dateLabel = filters.start_date + ' to ' + filters.end_date;
            } else if (filters.date_range === '30') {
                dateLabel = 'Last 30 Days';
            } else if (filters.date_range === '90') {
                dateLabel = 'Last 90 Days';
            } else if (filters.date_range === '365') {
                dateLabel = 'Last Year';
            } else if (filters.date_range === 'ytd') {
                dateLabel = 'Year to Date';
            }
            chips.push('<span class="filter-chip">📅 ' + dateLabel + '</span>');
        }

        if (filters.org_type !== 'all') {
            chips.push('<span class="filter-chip">🏢 ' + filters.org_type.charAt(0).toUpperCase() + filters.org_type.slice(1) + '</span>');
        }

        if (filters.org_id !== 'all') {
            var orgName = $('#svdp_filter_org_id option:selected').text();
            chips.push('<span class="filter-chip">🏛️ ' + orgName + '</span>');
        }

        if (filters.voucher_type !== 'all') {
            chips.push('<span class="filter-chip">🎫 ' + filters.voucher_type.charAt(0).toUpperCase() + filters.voucher_type.slice(1) + '</span>');
        }

        if (chips.length > 0) {
            $('#svdp_filter_chips').html(chips.join(''));
            $('#svdp_active_filters').show();
        } else {
            $('#svdp_active_filters').hide();
        }
    }
});
</script>
