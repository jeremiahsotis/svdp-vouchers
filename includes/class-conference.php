<?php
/**
 * Conference management
 */
class SVDP_Conference {
    
    /**
     * Get all active conferences
     */
    public static function get_all($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';
        
        $where = $active_only ? "WHERE active = 1" : "";
        $sql = "SELECT * FROM $table $where ORDER BY name ASC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get conference by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get conference by slug
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE slug = %s",
            $slug
        ));
    }
    
    /**
     * Create conference
     */
    public static function create($name, $slug = '', $is_emergency = 0, $organization_type = 'conference', $eligibility_days = 90, $regular_items = 7) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';

        // Generate slug if not provided
        if (empty($slug)) {
            $slug = sanitize_title($name);
        }

        $default_voucher_types = $organization_type === 'store'
            ? ['clothing']
            : ['clothing', 'furniture'];

        $result = $wpdb->insert($table, [
            'name' => sanitize_text_field($name),
            'slug' => sanitize_title($slug),
            'is_emergency' => intval($is_emergency),
            'organization_type' => sanitize_text_field($organization_type),
            'eligibility_days' => intval($eligibility_days),
            'regular_items_per_person' => intval($regular_items),
            'emergency_items_per_person' => 3, // Default for emergency vouchers
            'allowed_voucher_types' => wp_json_encode($default_voucher_types),
            'active' => 1,
        ]);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }
    
    /**
     * Update conference
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_conferences';
        
        $update_data = [];
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['slug'])) {
            $update_data['slug'] = sanitize_title($data['slug']);
        }
        
        if (isset($data['active'])) {
            $update_data['active'] = intval($data['active']);
        }

        if (isset($data['notification_email'])) {
            $update_data['notification_email'] = sanitize_email($data['notification_email']);
        }

        if (isset($data['eligibility_days'])) {
            $update_data['eligibility_days'] = intval($data['eligibility_days']);
        }

        if (isset($data['items_per_person'])) {
            $update_data['regular_items_per_person'] = intval($data['items_per_person']);
        }

        if (isset($data['allowed_voucher_types'])) {
            $existing_conference = self::get_by_id($id);
            $default_types = ($existing_conference && $existing_conference->organization_type === 'store')
                ? ['clothing']
                : ['clothing', 'furniture'];
            $update_data['allowed_voucher_types'] = SVDP_Settings::encode_voucher_types($data['allowed_voucher_types'], $default_types);
        }

        if (isset($data['custom_form_text'])) {
            $update_data['custom_form_text'] = sanitize_textarea_field($data['custom_form_text']);
        }

        if (isset($data['custom_rules_text'])) {
            $update_data['custom_rules_text'] = sanitize_textarea_field($data['custom_rules_text']);
        }

        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update($table, $update_data, ['id' => $id]);
    }
    
    /**
     * Delete conference (soft delete by setting active = 0)
     */
    public static function delete($id) {
        return self::update($id, ['active' => 0]);
    }
    
    /**
     * REST API: Get conferences
     */
    public static function get_conferences($request) {
        $conferences = self::get_all(true);
        
        return rest_ensure_response([
            'success' => true,
            'conferences' => $conferences,
        ]);
    }
}
