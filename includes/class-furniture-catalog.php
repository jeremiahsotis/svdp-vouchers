<?php
/**
 * Furniture catalog management.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Furniture_Catalog {

    const DEFAULT_DISCOUNT_TYPE = 'percent';
    const DEFAULT_DISCOUNT_VALUE = '50.00';

    /**
     * Supported catalog categories.
     *
     * @return array
     */
    public static function get_categories() {
        return [
            'used_furniture' => 'Used Furniture',
            'handmade_furniture' => 'Handmade Furniture',
            'mattresses_frames' => 'Mattresses & Frames',
            'household_goods' => 'Household Goods',
        ];
    }

    /**
     * Supported pricing types.
     *
     * @return array
     */
    public static function get_pricing_types() {
        return [
            'range' => 'Range',
            'fixed' => 'Fixed',
        ];
    }

    /**
     * Supported conference coverage types.
     *
     * Stored internally as discount_* fields for backward-compatible schema evolution.
     *
     * @return array
     */
    public static function get_discount_types() {
        return [
            'percent' => 'Percent',
            'fixed' => 'Fixed Dollar Amount',
        ];
    }

    /**
     * Fetch all catalog items for admin management.
     *
     * @param bool $include_inactive Whether inactive rows should be returned.
     * @return array
     */
    public static function get_all($include_inactive = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_catalog_items';

        $where = $include_inactive ? '' : 'WHERE active = 1';

        return $wpdb->get_results(
            "SELECT *
             FROM $table
             $where
             ORDER BY active DESC, category ASC, sort_order ASC, name ASC"
        );
    }

    /**
     * Fetch active catalog rows grouped for the public request form.
     *
     * @return array
     */
    public static function get_active_grouped_for_public() {
        $items = self::get_all(false);
        $categories = self::get_categories();
        $grouped = [];

        foreach ($categories as $category_key => $category_label) {
            $grouped[$category_key] = [
                'key' => $category_key,
                'label' => $category_label,
                'items' => [],
            ];
        }

        foreach ($items as $item) {
            if (!isset($grouped[$item->category])) {
                continue;
            }

            $grouped[$item->category]['items'][] = self::format_public_item($item);
        }

        return array_values(array_filter($grouped, function($group) {
            return !empty($group['items']);
        }));
    }

    /**
     * Fetch active catalog rows by ID.
     *
     * @param array $ids Catalog item IDs.
     * @param bool  $active_only Whether only active rows should be returned.
     * @return array
     */
    public static function get_items_by_ids($ids, $active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_catalog_items';

        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '%d'));
        $where = $active_only ? 'AND active = 1' : '';
        $query = $wpdb->prepare(
            "SELECT *
             FROM $table
             WHERE id IN ($placeholders)
             $where
             ORDER BY category ASC, sort_order ASC, name ASC",
            $ids
        );

        return $wpdb->get_results($query);
    }

    /**
     * Public REST endpoint for active catalog items.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function get_public_catalog_items($request) {
        return rest_ensure_response([
            'success' => true,
            'categories' => self::get_active_grouped_for_public(),
        ]);
    }

    /**
     * Fetch a single catalog item.
     *
     * @param int $id Catalog item ID.
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_catalog_items';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id))
        );
    }

    /**
     * Create a catalog item.
     *
     * @param array $data Raw catalog item input.
     * @return int|WP_Error
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_catalog_items';

        $prepared = self::prepare_item_data($data);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        $result = $wpdb->insert($table, $prepared);
        if ($result === false) {
            return new WP_Error('catalog_item_create_failed', 'Failed to create furniture catalog item.');
        }

        return intval($wpdb->insert_id);
    }

    /**
     * Update a catalog item.
     *
     * @param int   $id Catalog item ID.
     * @param array $data Raw catalog item input.
     * @return bool|WP_Error
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_catalog_items';

        $existing = self::get_by_id($id);
        if (!$existing) {
            return new WP_Error('catalog_item_not_found', 'Furniture catalog item not found.');
        }

        $prepared = self::prepare_item_data($data, $existing);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        $result = $wpdb->update($table, $prepared, ['id' => intval($id)]);
        if ($result === false) {
            return new WP_Error('catalog_item_update_failed', 'Failed to update furniture catalog item.');
        }

        return true;
    }

    /**
     * Set the active state for a catalog item.
     *
     * @param int $id Catalog item ID.
     * @param int $active Target active state.
     * @return bool|WP_Error
     */
    public static function set_active($id, $active) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_catalog_items';

        $existing = self::get_by_id($id);
        if (!$existing) {
            return new WP_Error('catalog_item_not_found', 'Furniture catalog item not found.');
        }

        $result = $wpdb->update(
            $table,
            ['active' => $active ? 1 : 0],
            ['id' => intval($id)]
        );

        if ($result === false) {
            return new WP_Error('catalog_item_status_failed', 'Failed to update furniture catalog item status.');
        }

        return true;
    }

    /**
     * Build sanitized insert/update data.
     *
     * @param array       $data Raw item data.
     * @param object|null $existing Existing row for update preservation.
     * @return array|WP_Error
     */
    private static function prepare_item_data($data, $existing = null) {
        $name = sanitize_text_field($data['name'] ?? '');
        if ($name === '') {
            return new WP_Error('catalog_item_name_required', 'Catalog item name is required.');
        }

        $category = sanitize_key($data['category'] ?? '');
        $categories = self::get_categories();
        if (!isset($categories[$category])) {
            return new WP_Error('catalog_item_category_invalid', 'Please choose a valid catalog category.');
        }

        $pricing_type = sanitize_key($data['pricing_type'] ?? '');
        $pricing_types = self::get_pricing_types();
        if (!isset($pricing_types[$pricing_type])) {
            return new WP_Error('catalog_item_pricing_type_invalid', 'Please choose a valid pricing type.');
        }

        $discount_type = sanitize_key($data['discount_type'] ?? ($existing->discount_type ?? self::DEFAULT_DISCOUNT_TYPE));
        $discount_types = self::get_discount_types();
        if (!isset($discount_types[$discount_type])) {
            return new WP_Error('catalog_item_discount_type_invalid', 'Please choose a valid conference coverage type.');
        }

        $default_discount_value = $existing && isset($existing->discount_value)
            ? $existing->discount_value
            : self::DEFAULT_DISCOUNT_VALUE;
        $discount_value = self::sanitize_decimal_field(
            $data['discount_value'] ?? $default_discount_value,
            'Conference coverage amount',
            true
        );
        if (is_wp_error($discount_value)) {
            return $discount_value;
        }

        if ($discount_type === 'percent' && (float) $discount_value > 100) {
            return new WP_Error('catalog_item_discount_percent_invalid', 'Conference coverage percent must be between 0 and 100.');
        }

        $sort_order = isset($data['sort_order']) && $data['sort_order'] !== '' ? intval($data['sort_order']) : 0;
        if ($sort_order < 0) {
            return new WP_Error('catalog_item_sort_order_invalid', 'Sort order must be zero or greater.');
        }

        $prepared = [
            'name' => $name,
            'category' => $category,
            'pricing_type' => $pricing_type,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'sort_order' => $sort_order,
            'active' => array_key_exists('active', $data)
                ? (!empty($data['active']) ? 1 : 0)
                : ($existing ? intval($existing->active) : 1),
            'allow_substitution' => $existing ? intval($existing->allow_substitution) : 1,
        ];

        if ($pricing_type === 'range') {
            $price_min = self::sanitize_decimal_field($data['price_min'] ?? '', 'Minimum price', true);
            if (is_wp_error($price_min)) {
                return $price_min;
            }

            $price_max = self::sanitize_decimal_field($data['price_max'] ?? '', 'Maximum price', true);
            if (is_wp_error($price_max)) {
                return $price_max;
            }

            if ((float) $price_max < (float) $price_min) {
                return new WP_Error('catalog_item_price_range_invalid', 'Maximum price must be greater than or equal to minimum price.');
            }

            $prepared['price_min'] = $price_min;
            $prepared['price_max'] = $price_max;
            $prepared['price_fixed'] = null;
        } else {
            $price_fixed = self::sanitize_decimal_field($data['price_fixed'] ?? '', 'Fixed price', true);
            if (is_wp_error($price_fixed)) {
                return $price_fixed;
            }

            $prepared['price_min'] = null;
            $prepared['price_max'] = null;
            $prepared['price_fixed'] = $price_fixed;
        }

        return $prepared;
    }

    /**
     * Sanitize an optional decimal field.
     *
     * @param mixed  $value Raw decimal value.
     * @param string $label Human-readable field label.
     * @param bool   $required Whether the field is required.
     * @return string|null|WP_Error
     */
    private static function sanitize_decimal_field($value, $label, $required = false) {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === '' || $value === null) {
            if ($required) {
                return new WP_Error('catalog_item_decimal_required', $label . ' is required.');
            }

            return null;
        }

        if (!is_numeric($value)) {
            return new WP_Error('catalog_item_decimal_invalid', $label . ' must be a valid number.');
        }

        $decimal = round((float) $value, 2);
        if ($decimal < 0) {
            return new WP_Error('catalog_item_decimal_negative', $label . ' must be zero or greater.');
        }

        return number_format($decimal, 2, '.', '');
    }

    /**
     * Format one catalog row for the public request UI.
     *
     * @param object $item Catalog row.
     * @return array
     */
    private static function format_public_item($item) {
        $pricing_type = $item->pricing_type === 'fixed' ? 'fixed' : 'range';
        $price_min = $item->price_min !== null ? (float) $item->price_min : null;
        $price_max = $item->price_max !== null ? (float) $item->price_max : null;
        $price_fixed = $item->price_fixed !== null ? (float) $item->price_fixed : null;
        $discount_type = isset($item->discount_type) && $item->discount_type === 'fixed'
            ? 'fixed'
            : self::DEFAULT_DISCOUNT_TYPE;
        $discount_value = isset($item->discount_value) && $item->discount_value !== null
            ? (float) $item->discount_value
            : (float) self::DEFAULT_DISCOUNT_VALUE;

        if ($pricing_type === 'fixed') {
            $price_display = '$' . number_format((float) $price_fixed, 2);
        } else {
            $price_display = '$' . number_format((float) $price_min, 2) . ' - $' . number_format((float) $price_max, 2);
        }

        return [
            'id' => (int) $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'categoryLabel' => self::get_categories()[$item->category] ?? $item->category,
            'pricingType' => $pricing_type,
            'priceMin' => $price_min,
            'priceMax' => $price_max,
            'priceFixed' => $price_fixed,
            'discountType' => $discount_type,
            'discountValue' => $discount_value,
            'sortOrder' => (int) $item->sort_order,
            'priceDisplay' => $price_display,
        ];
    }
}
