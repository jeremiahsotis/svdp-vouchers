<?php
/**
 * Furniture cancellation reason management.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Furniture_Cancellation_Reason {

    /**
     * Fetch all cancellation reasons for admin management.
     *
     * @param bool $include_inactive Whether inactive rows should be returned.
     * @return array
     */
    public static function get_all($include_inactive = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';

        $where = $include_inactive ? '' : 'WHERE active = 1';

        return $wpdb->get_results(
            "SELECT *
             FROM $table
             $where
             ORDER BY active DESC, display_order ASC, reason_text ASC"
        );
    }

    /**
     * Fetch a single cancellation reason.
     *
     * @param int $id Reason ID.
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id))
        );
    }

    /**
     * Create a cancellation reason.
     *
     * @param array $data Raw reason data.
     * @return int|WP_Error
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';

        $prepared = self::prepare_reason_data($data);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        $result = $wpdb->insert($table, $prepared);
        if ($result === false) {
            return new WP_Error('furniture_reason_create_failed', 'Failed to create furniture cancellation reason.');
        }

        return intval($wpdb->insert_id);
    }

    /**
     * Update a cancellation reason.
     *
     * @param int   $id Reason ID.
     * @param array $data Raw reason data.
     * @return bool|WP_Error
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';

        $existing = self::get_by_id($id);
        if (!$existing) {
            return new WP_Error('furniture_reason_not_found', 'Furniture cancellation reason not found.');
        }

        $prepared = self::prepare_reason_data($data, $existing);
        if (is_wp_error($prepared)) {
            return $prepared;
        }

        $result = $wpdb->update($table, $prepared, ['id' => intval($id)]);
        if ($result === false) {
            return new WP_Error('furniture_reason_update_failed', 'Failed to update furniture cancellation reason.');
        }

        return true;
    }

    /**
     * Set the active state for a cancellation reason.
     *
     * @param int $id Reason ID.
     * @param int $active Target active state.
     * @return bool|WP_Error
     */
    public static function set_active($id, $active) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';

        $existing = self::get_by_id($id);
        if (!$existing) {
            return new WP_Error('furniture_reason_not_found', 'Furniture cancellation reason not found.');
        }

        $result = $wpdb->update(
            $table,
            ['active' => $active ? 1 : 0],
            ['id' => intval($id)]
        );

        if ($result === false) {
            return new WP_Error('furniture_reason_status_failed', 'Failed to update furniture cancellation reason status.');
        }

        return true;
    }

    /**
     * Build sanitized insert/update data.
     *
     * @param array       $data Raw reason data.
     * @param object|null $existing Existing row for update preservation.
     * @return array|WP_Error
     */
    private static function prepare_reason_data($data, $existing = null) {
        $reason_text = sanitize_text_field($data['reason_text'] ?? '');
        if ($reason_text === '') {
            return new WP_Error('furniture_reason_text_required', 'Reason text is required.');
        }

        if (isset($data['display_order']) && $data['display_order'] !== '') {
            $display_order = intval($data['display_order']);
        } elseif ($existing) {
            $display_order = intval($existing->display_order);
        } else {
            $display_order = self::get_next_display_order();
        }

        if ($display_order < 0) {
            return new WP_Error('furniture_reason_order_invalid', 'Display order must be zero or greater.');
        }

        return [
            'reason_text' => $reason_text,
            'display_order' => $display_order,
            'active' => array_key_exists('active', $data)
                ? (!empty($data['active']) ? 1 : 0)
                : ($existing ? intval($existing->active) : 1),
        ];
    }

    /**
     * Compute the next display order.
     *
     * @return int
     */
    private static function get_next_display_order() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_furniture_cancellation_reasons';

        $max_order = $wpdb->get_var("SELECT MAX(display_order) FROM $table");
        return $max_order === null ? 0 : intval($max_order) + 1;
    }
}
