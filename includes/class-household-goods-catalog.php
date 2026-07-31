<?php
/**
 * Household Goods catalog, limits, and configuration audit.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Household_Goods_Catalog {

    const SELECTED_CATEGORY_LIMIT_SETTING = 'household_goods_selected_category_limit';
    const VOUCHER_QUANTITY_MAX_SETTING = 'household_goods_voucher_quantity_max';

    /**
     * Fetch browse groups for admin or request use.
     *
     * @param bool $include_archived Include archived groups.
     * @return array
     */
    public static function get_browse_groups($include_archived = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_browse_groups';
        $where = $include_archived ? '' : 'WHERE active = 1';

        return $wpdb->get_results(
            "SELECT g.*,
                    u.display_name AS updated_by_name,
                    (
                        SELECT COUNT(*)
                        FROM {$wpdb->prefix}svdp_household_goods_catalog c
                        WHERE c.browse_group_id = g.id AND c.active = 1
                    ) AS active_category_count
             FROM $table g
             LEFT JOIN {$wpdb->users} u ON g.updated_by_user_id = u.ID
             $where
             ORDER BY g.active DESC, g.sort_order ASC, g.name ASC"
        );
    }

    /**
     * Fetch a browse group.
     *
     * @param int $id Browse group ID.
     * @return object|null
     */
    public static function get_browse_group($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_browse_groups';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)));
    }

    /**
     * Create a browse group.
     *
     * @param array $data Raw input.
     * @return int|WP_Error
     */
    public static function create_browse_group($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_browse_groups';

        $prepared = self::prepare_browse_group_data($data);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        if (self::active_browse_group_name_exists($prepared['name'])) {
            return new WP_Error('household_goods_group_duplicate', 'An active browse group with that name already exists.');
        }

        $result = $wpdb->insert($table, $prepared);
        if ($result === false) {
            return new WP_Error('household_goods_group_create_failed', 'Failed to create Household Goods browse group.');
        }

        $id = intval($wpdb->insert_id);
        self::audit('household_goods', 'browse_group', $id, $prepared['name'], 'created', null, 'created', 'Household Goods browse group added: ' . $prepared['name']);

        return $id;
    }

    /**
     * Update a browse group.
     *
     * @param int   $id Browse group ID.
     * @param array $data Raw input.
     * @return bool|WP_Error
     */
    public static function update_browse_group($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_browse_groups';

        $existing = self::get_browse_group($id);
        if (!$existing) {
            return new WP_Error('household_goods_group_not_found', 'Household Goods browse group not found.');
        }

        $prepared = self::prepare_browse_group_data($data, $existing);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        if (!empty($prepared['active']) && self::active_browse_group_name_exists($prepared['name'], $id)) {
            return new WP_Error('household_goods_group_duplicate', 'An active browse group with that name already exists.');
        }

        if (empty($prepared['active'])) {
            $archive_check = self::can_archive_browse_group($id);
            if (is_wp_error($archive_check)) {
                return $archive_check;
            }
        }

        $result = $wpdb->update($table, $prepared, ['id' => intval($id)]);
        if ($result === false) {
            return new WP_Error('household_goods_group_update_failed', 'Failed to update Household Goods browse group.');
        }

        self::audit_changed_fields('browse_group', $id, $existing, (object) $prepared, [
            'name' => 'name',
            'sort_order' => 'sort_order',
            'active' => 'status',
        ], $prepared['name']);

        return true;
    }

    /**
     * Archive or restore a browse group.
     *
     * @param int $id Browse group ID.
     * @param int $active Target active state.
     * @return bool|WP_Error
     */
    public static function set_browse_group_active($id, $active) {
        $existing = self::get_browse_group($id);
        if (!$existing) {
            return new WP_Error('household_goods_group_not_found', 'Household Goods browse group not found.');
        }

        return self::update_browse_group($id, [
            'name' => $existing->name,
            'sort_order' => $existing->sort_order,
            'active' => $active ? 1 : 0,
        ]);
    }

    /**
     * Fetch catalog categories for admin.
     *
     * @param array $filters Optional filters.
     * @return array
     */
    public static function get_catalog_categories($filters = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';
        $groups_table = $wpdb->prefix . 'svdp_household_goods_browse_groups';
        $where = ['1=1'];
        $values = [];

        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = 'c.active = %d';
            $values[] = intval($filters['active']);
        }

        if (!empty($filters['browse_group_id'])) {
            $where[] = 'c.browse_group_id = %d';
            $values[] = intval($filters['browse_group_id']);
        }

        if (!empty($filters['search'])) {
            $where[] = 'c.name LIKE %s';
            $values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }

        $sql = "SELECT c.*, g.name AS browse_group_name, g.active AS browse_group_active, u.display_name AS updated_by_name
                FROM $table c
                LEFT JOIN $groups_table g ON c.browse_group_id = g.id
                LEFT JOIN {$wpdb->users} u ON c.updated_by_user_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.active DESC, g.sort_order ASC, g.name ASC, c.sort_order ASC, c.name ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Fetch active catalog grouped for future request flows.
     *
     * @return array
     */
    public static function get_active_grouped_for_request() {
        $groups = self::get_browse_groups(false);
        $categories = self::get_catalog_categories(['active' => 1]);
        $grouped = [];

        foreach ($groups as $group) {
            $grouped[(int) $group->id] = [
                'id' => (int) $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'sortOrder' => (int) $group->sort_order,
                'categories' => [],
            ];
        }

        foreach ($categories as $category) {
            $group_id = (int) $category->browse_group_id;
            if (!isset($grouped[$group_id]) || empty($category->browse_group_active)) {
                continue;
            }

            $grouped[$group_id]['categories'][] = self::format_request_category($category);
        }

        return array_values(array_filter($grouped, function($group) {
            return !empty($group['categories']);
        }));
    }

    /**
     * Fetch a catalog category.
     *
     * @param int $id Category ID.
     * @return object|null
     */
    public static function get_category($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)));
    }

    /**
     * Create a catalog category.
     *
     * @param array $data Raw input.
     * @return int|WP_Error
     */
    public static function create_category($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';

        $prepared = self::prepare_category_data($data);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        if (self::active_category_name_exists($prepared['browse_group_id'], $prepared['name'])) {
            return new WP_Error('household_goods_category_duplicate', 'An active category with that name already exists in the selected browse group.');
        }

        $result = $wpdb->insert($table, $prepared);
        if ($result === false) {
            return new WP_Error('household_goods_category_create_failed', 'Failed to create Household Goods category.');
        }

        $id = intval($wpdb->insert_id);
        self::audit('household_goods', 'catalog_category', $id, $prepared['name'], 'created', null, 'created', 'Household Goods category added: ' . $prepared['name']);

        return $id;
    }

    /**
     * Update a catalog category.
     *
     * @param int   $id Category ID.
     * @param array $data Raw input.
     * @return bool|WP_Error
     */
    public static function update_category($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';

        $existing = self::get_category($id);
        if (!$existing) {
            return new WP_Error('household_goods_category_not_found', 'Household Goods category not found.');
        }

        $prepared = self::prepare_category_data($data, $existing);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        if (!empty($prepared['active']) && self::active_category_name_exists($prepared['browse_group_id'], $prepared['name'], $id)) {
            return new WP_Error('household_goods_category_duplicate', 'An active category with that name already exists in the selected browse group.');
        }

        $result = $wpdb->update($table, $prepared, ['id' => intval($id)]);
        if ($result === false) {
            return new WP_Error('household_goods_category_update_failed', 'Failed to update Household Goods category.');
        }

        self::audit_changed_fields('catalog_category', $id, $existing, (object) $prepared, [
            'name' => 'name',
            'browse_group_id' => 'browse_group',
            'estimated_conference_partner_cost_per_unit' => 'estimated_conference_partner_cost_per_unit',
            'pricing_type' => 'pricing_type',
            'price_min' => 'retail_price_min',
            'price_max' => 'retail_price_max',
            'price_fixed' => 'retail_price_fixed',
            'show_price_as_max' => 'show_price_as_max',
            'discount_type' => 'coverage_type',
            'discount_value' => 'coverage_value',
            'quantity_max' => 'quantity_max',
            'cashier_guidance' => 'cashier_guidance',
            'sort_order' => 'sort_order',
            'active' => 'status',
        ], $prepared['name']);

        return true;
    }

    /**
     * Archive or restore a catalog category.
     *
     * @param int $id Category ID.
     * @param int $active Target active state.
     * @return bool|WP_Error
     */
    public static function set_category_active($id, $active) {
        $existing = self::get_category($id);
        if (!$existing) {
            return new WP_Error('household_goods_category_not_found', 'Household Goods category not found.');
        }

        return self::update_category($id, [
            'browse_group_id' => $existing->browse_group_id,
            'name' => $existing->name,
            'estimated_conference_partner_cost_per_unit' => $existing->estimated_conference_partner_cost_per_unit,
            'quantity_max' => $existing->quantity_max,
            'cashier_guidance' => $existing->cashier_guidance,
            'sort_order' => $existing->sort_order,
            'active' => $active ? 1 : 0,
        ]);
    }

    /**
     * Get fixed and configurable limits.
     *
     * @return array
     */
    public static function get_limits() {
        return [
            'selected_category_limit' => self::get_selected_category_limit(),
            'voucher_quantity_max' => self::get_voucher_quantity_max(),
        ];
    }

    /**
     * Get voucher-wide requested quantity max.
     *
     * @return int
     */
    public static function get_voucher_quantity_max() {
        return max(0, intval(SVDP_Settings::get_setting(self::VOUCHER_QUANTITY_MAX_SETTING, '0')));
    }

    public static function get_selected_category_limit() {
        return max(0, intval(SVDP_Settings::get_setting(self::SELECTED_CATEGORY_LIMIT_SETTING, '0')));
    }

    public static function update_selected_category_limit($value) {
        $limit = self::sanitize_integer_field($value, 'Maximum selected categories');
        if (is_wp_error($limit)) return $limit;
        $before = self::get_selected_category_limit();
        if (!SVDP_Settings::update_setting(self::SELECTED_CATEGORY_LIMIT_SETTING, (string) $limit, 'integer')) return new WP_Error('household_goods_limit_update_failed', 'Failed to update the selected-category limit.');
        if ($before !== $limit) self::audit('household_goods', 'limit', self::SELECTED_CATEGORY_LIMIT_SETTING, 'Household Goods selected-category limit', 'selected_category_limit', $before, $limit, 'Household Goods maximum selected categories changed from ' . $before . ' to ' . $limit . '. Zero means no limit. The new value applies to future requests only.');
        return true;
    }

    /**
     * Update voucher-wide requested quantity max.
     *
     * @param mixed $value Raw value.
     * @return bool|WP_Error
     */
    public static function update_voucher_quantity_max($value) {
        $quantity_max = self::sanitize_integer_field($value, 'Maximum total requested quantity');
        if (is_wp_error($quantity_max)) {
            return $quantity_max;
        }

        $before = self::get_voucher_quantity_max();
        $updated = SVDP_Settings::update_setting(self::VOUCHER_QUANTITY_MAX_SETTING, (string) $quantity_max, 'integer');
        if (!$updated) {
            return new WP_Error('household_goods_limit_update_failed', 'Failed to update Household Goods voucher-wide quantity limit.');
        }

        if ((int) $before !== (int) $quantity_max) {
            self::audit('household_goods', 'limit', self::VOUCHER_QUANTITY_MAX_SETTING, 'Household Goods voucher-wide quantity limit', 'voucher_quantity_max', $before, $quantity_max, 'Household Goods voucher-wide quantity maximum changed from ' . $before . ' to ' . $quantity_max . '. The new value applies to future requests only.');
        }

        return true;
    }

    /**
     * Build immutable request-line snapshots for future Household Goods issuance.
     *
     * @param array $requested_categories Array of category IDs to quantities.
     * @return array|WP_Error
     */
    public static function build_request_line_snapshots($requested_categories) {
        $requested_categories = (array) $requested_categories;
        $category_ids = array_values(array_filter(array_map('intval', array_keys($requested_categories))));

        $selected_category_limit = self::get_selected_category_limit();
        if ($selected_category_limit > 0 && count($category_ids) > $selected_category_limit) {
            return new WP_Error('household_goods_selected_limit_exceeded', 'Selected categories must be ' . $selected_category_limit . ' or fewer.');
        }

        if (empty($category_ids)) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';
        $groups_table = $wpdb->prefix . 'svdp_household_goods_browse_groups';
        $placeholders = implode(', ', array_fill(0, count($category_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, g.name AS browse_group_name
             FROM $table c
             LEFT JOIN $groups_table g ON c.browse_group_id = g.id
             WHERE c.id IN ($placeholders) AND c.active = 1 AND g.active = 1
             ORDER BY g.sort_order ASC, c.sort_order ASC, c.name ASC",
            $category_ids
        ));

        if (count($rows) !== count($category_ids)) {
            return new WP_Error('household_goods_category_unavailable', 'One or more Household Goods categories is no longer active.');
        }

        $voucher_quantity_max = self::get_voucher_quantity_max();
        $total_quantity = 0;
        $snapshots = [];

        foreach ($rows as $row) {
            $quantity = self::sanitize_integer_field($requested_categories[$row->id] ?? 0, $row->name . ' quantity');
            if (is_wp_error($quantity)) {
                return $quantity;
            }

            if ($quantity <= 0) {
                return new WP_Error('household_goods_quantity_required', $row->name . ' quantity must be greater than zero.');
            }

            if ((int) $row->quantity_max > 0 && $quantity > (int) $row->quantity_max) {
                return new WP_Error('household_goods_category_limit_exceeded', $row->name . ' is limited to ' . (int) $row->quantity_max . ' on one voucher.');
            }

            $total_quantity += $quantity;
            if ($voucher_quantity_max > 0 && $total_quantity > $voucher_quantity_max) {
                return new WP_Error(
                    'household_goods_voucher_limit_exceeded',
                    'This Household Goods voucher is limited to ' . $voucher_quantity_max . ' total requested items. You submitted ' . $total_quantity . '.'
                );
            }

            $snapshots[] = [
                'line_type' => 'household_goods',
                'source_catalog_id' => (int) $row->id,
                'requested_quantity' => $quantity,
                'requested_name_snapshot' => $row->name,
                'requested_group_snapshot' => $row->browse_group_name,
                'estimated_conference_partner_cost_per_unit_snapshot' => number_format((float) $row->estimated_conference_partner_cost_per_unit, 2, '.', ''),
                'requested_pricing_type_snapshot' => $row->pricing_type,
                'requested_price_min_snapshot' => $row->price_min,
                'requested_price_max_snapshot' => $row->price_max,
                'requested_price_fixed_snapshot' => $row->price_fixed,
                'show_price_as_max_snapshot' => (int) $row->show_price_as_max,
                'discount_type_snapshot' => $row->discount_type,
                'discount_value_snapshot' => $row->discount_value,
                'quantity_max_snapshot' => (int) $row->quantity_max,
                'cashier_guidance_snapshot' => $row->cashier_guidance,
                'sort_order_snapshot' => (int) $row->sort_order,
                'voucher_quantity_max_snapshot' => $voucher_quantity_max,
                'selected_category_limit_snapshot' => $selected_category_limit,
            ];
        }

        return $snapshots;
    }

    /**
     * Fetch configuration audit rows.
     *
     * @param int $limit Maximum rows.
     * @return array
     */
    public static function get_configuration_audit($limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_configuration_audit';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name AS changed_by_name
             FROM $table a
             LEFT JOIN {$wpdb->users} u ON a.changed_by_user_id = u.ID
             ORDER BY a.changed_at DESC, a.id DESC
             LIMIT %d",
            max(1, min(250, intval($limit)))
        ));
    }

    private static function prepare_browse_group_data($data, $existing = null) {
        $name = sanitize_text_field($data['name'] ?? '');
        if ($name === '') {
            return new WP_Error('household_goods_group_name_required', 'Browse group name is required.');
        }

        $sort_order = self::sanitize_integer_field($data['sort_order'] ?? ($existing->sort_order ?? 0), 'Sort order');
        if (is_wp_error($sort_order)) {
            return $sort_order;
        }

        return [
            'name' => $name,
            'slug' => $existing ? $existing->slug : sanitize_title($name),
            'sort_order' => $sort_order,
            'active' => array_key_exists('active', $data) ? (!empty($data['active']) ? 1 : 0) : ($existing ? intval($existing->active) : 1),
            'updated_by_user_id' => get_current_user_id() ?: null,
        ];
    }

    private static function prepare_category_data($data, $existing = null) {
        $name = sanitize_text_field($data['name'] ?? '');
        if ($name === '') {
            return new WP_Error('household_goods_category_name_required', 'Category name is required.');
        }

        $browse_group_id = intval($data['browse_group_id'] ?? ($existing->browse_group_id ?? 0));
        $group = self::get_browse_group($browse_group_id);
        if (!$group || intval($group->active) !== 1) {
            return new WP_Error('household_goods_group_required', 'Choose an active browse group.');
        }

        $pricing_type = sanitize_key($data['pricing_type'] ?? ($existing->pricing_type ?? 'fixed'));
        if (!in_array($pricing_type, ['fixed', 'range'], true)) return new WP_Error('household_goods_pricing_type_invalid', 'Choose Fixed or Range pricing.');
        $price_fixed = $pricing_type === 'fixed' ? self::sanitize_decimal_field($data['price_fixed'] ?? ($existing->price_fixed ?? ''), 'Fixed retail price') : null;
        $price_min = $pricing_type === 'range' ? self::sanitize_decimal_field($data['price_min'] ?? ($existing->price_min ?? ''), 'Minimum retail price') : null;
        $price_max = $pricing_type === 'range' ? self::sanitize_decimal_field($data['price_max'] ?? ($existing->price_max ?? ''), 'Maximum retail price') : null;
        foreach ([$price_fixed, $price_min, $price_max] as $price) if (is_wp_error($price)) return $price;
        if ($pricing_type === 'range' && (float) $price_max < (float) $price_min) return new WP_Error('household_goods_price_range_invalid', 'Maximum retail price must be at least the minimum retail price.');
        $discount_type = sanitize_key($data['discount_type'] ?? ($existing->discount_type ?? 'percent'));
        if (!in_array($discount_type, ['percent', 'fixed'], true)) return new WP_Error('household_goods_discount_type_invalid', 'Choose Percent or Fixed Dollar Amount coverage.');
        $discount_value = self::sanitize_decimal_field($data['discount_value'] ?? ($existing->discount_value ?? '50'), 'Organization coverage');
        if (is_wp_error($discount_value)) return $discount_value;
        if ($discount_type === 'percent' && (float) $discount_value > 100) return new WP_Error('household_goods_discount_percent_invalid', 'Percentage coverage cannot exceed 100.');
        $retail_max = $pricing_type === 'fixed' ? (float) $price_fixed : (float) $price_max;
        if ($discount_type === 'fixed' && (float) $discount_value > $retail_max) return new WP_Error('household_goods_discount_fixed_invalid', 'Fixed coverage cannot exceed the maximum retail price.');
        $cost = $discount_type === 'percent' ? $retail_max * ((float) $discount_value / 100) : (float) $discount_value;

        $quantity_max = self::sanitize_integer_field($data['quantity_max'] ?? ($existing->quantity_max ?? 0), 'Quantity maximum');
        if (is_wp_error($quantity_max)) {
            return $quantity_max;
        }

        $sort_order = self::sanitize_integer_field($data['sort_order'] ?? ($existing->sort_order ?? 0), 'Sort order');
        if (is_wp_error($sort_order)) {
            return $sort_order;
        }

        return [
            'browse_group_id' => $browse_group_id,
            'name' => $name,
            'slug' => $existing ? $existing->slug : sanitize_title($name),
            'estimated_conference_partner_cost_per_unit' => $cost,
            'pricing_type' => $pricing_type,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'price_fixed' => $price_fixed,
            'show_price_as_max' => $pricing_type === 'range' && array_key_exists('show_price_as_max', $data) ? (!empty($data['show_price_as_max']) ? 1 : 0) : ($pricing_type === 'range' ? (int) ($existing->show_price_as_max ?? 1) : 0),
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'quantity_max' => $quantity_max,
            'cashier_guidance' => sanitize_textarea_field($data['cashier_guidance'] ?? ($existing->cashier_guidance ?? '')),
            'sort_order' => $sort_order,
            'active' => array_key_exists('active', $data) ? (!empty($data['active']) ? 1 : 0) : ($existing ? intval($existing->active) : 1),
            'updated_by_user_id' => get_current_user_id() ?: null,
        ];
    }

    private static function sanitize_decimal_field($value, $label) {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null) {
            return new WP_Error('household_goods_decimal_required', $label . ' is required.');
        }

        if (!is_numeric($value)) {
            return new WP_Error('household_goods_decimal_invalid', $label . ' must be a valid number.');
        }

        $decimal = round((float) $value, 2);
        if ($decimal < 0) {
            return new WP_Error('household_goods_decimal_negative', $label . ' must be zero or greater.');
        }

        return number_format($decimal, 2, '.', '');
    }

    private static function sanitize_integer_field($value, $label) {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null || !preg_match('/^\d+$/', (string) $value)) {
            return new WP_Error('household_goods_integer_invalid', $label . ' must be a non-negative whole number.');
        }

        return intval($value);
    }

    private static function active_browse_group_name_exists($name, $exclude_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_browse_groups';

        $query = "SELECT COUNT(*) FROM $table WHERE active = 1 AND LOWER(name) = LOWER(%s)";
        $args = [$name];
        if ($exclude_id) {
            $query .= ' AND id != %d';
            $args[] = intval($exclude_id);
        }

        return intval($wpdb->get_var($wpdb->prepare($query, $args))) > 0;
    }

    private static function active_category_name_exists($browse_group_id, $name, $exclude_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';

        $query = "SELECT COUNT(*) FROM $table WHERE active = 1 AND browse_group_id = %d AND LOWER(name) = LOWER(%s)";
        $args = [intval($browse_group_id), $name];
        if ($exclude_id) {
            $query .= ' AND id != %d';
            $args[] = intval($exclude_id);
        }

        return intval($wpdb->get_var($wpdb->prepare($query, $args))) > 0;
    }

    private static function can_archive_browse_group($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_household_goods_catalog';
        $categories = $wpdb->get_col($wpdb->prepare(
            "SELECT name FROM $table WHERE browse_group_id = %d AND active = 1 ORDER BY sort_order ASC, name ASC",
            intval($id)
        ));

        if (!empty($categories)) {
            return new WP_Error(
                'household_goods_group_has_active_categories',
                'Archive or reassign these active categories before archiving the browse group: ' . implode(', ', $categories) . '.'
            );
        }

        return true;
    }

    private static function format_request_category($category) {
        return [
            'id' => (int) $category->id,
            'browseGroupId' => (int) $category->browse_group_id,
            'browseGroupName' => $category->browse_group_name,
            'name' => $category->name,
            'slug' => $category->slug,
            'estimatedConferencePartnerCostPerUnit' => (float) $category->estimated_conference_partner_cost_per_unit,
            'pricingType' => $category->pricing_type,
            'priceMin' => $category->price_min === null ? null : (float) $category->price_min,
            'priceMax' => $category->price_max === null ? null : (float) $category->price_max,
            'priceFixed' => $category->price_fixed === null ? null : (float) $category->price_fixed,
            'showPriceAsMax' => !empty($category->show_price_as_max),
            'discountType' => $category->discount_type,
            'discountValue' => (float) $category->discount_value,
            'priceDisplay' => $category->pricing_type === 'fixed' ? '$' . number_format((float) $category->price_fixed, 2) : (!empty($category->show_price_as_max) ? 'Up to $' . number_format((float) $category->price_max, 2) : '$' . number_format((float) $category->price_min, 2) . '–$' . number_format((float) $category->price_max, 2)),
            'quantityMax' => (int) $category->quantity_max,
            'sortOrder' => (int) $category->sort_order,
        ];
    }

    private static function audit_changed_fields($record_type, $record_id, $before, $after, $fields, $record_name) {
        foreach ($fields as $field => $label) {
            $before_value = isset($before->$field) ? (string) $before->$field : null;
            $after_value = isset($after->$field) ? (string) $after->$field : null;

            if ($before_value === $after_value) {
                continue;
            }

            $summary = self::human_record_type($record_type) . ' ' . $record_name . ' ' . str_replace('_', ' ', $label) . ' changed. The new value applies to future requests only.';
            self::audit('household_goods', $record_type, $record_id, $record_name, $label, $before_value, $after_value, $summary);
        }
    }

    private static function audit($area, $record_type, $record_identifier, $record_name, $field_changed, $before_value, $after_value, $summary) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_configuration_audit';

        $wpdb->insert($table, [
            'configuration_area' => $area,
            'record_type' => $record_type,
            'record_identifier' => (string) $record_identifier,
            'record_name_snapshot' => $record_name,
            'field_changed' => $field_changed,
            'before_value' => $before_value === null ? null : (string) $before_value,
            'after_value' => $after_value === null ? null : (string) $after_value,
            'changed_by_user_id' => get_current_user_id() ?: null,
            'changed_at' => current_time('mysql'),
            'human_summary' => $summary,
        ]);
    }

    private static function human_record_type($record_type) {
        $labels = [
            'browse_group' => 'Browse group',
            'catalog_category' => 'Household Goods category',
        ];

        return $labels[$record_type] ?? $record_type;
    }
}
