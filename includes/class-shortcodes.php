<?php
/**
 * Shortcode handlers
 */
class SVDP_Shortcodes {
    
    public function __construct() {
        add_shortcode('svdp_voucher_request', [$this, 'render_voucher_request']);
        add_shortcode('svdp_cashier_station', [$this, 'render_cashier_station']);
    }
    
    /**
     * Render voucher request form
     * Usage: [svdp_voucher_request conference="st-mary-fort-wayne"]
     */
    public function render_voucher_request($atts) {
        $atts = shortcode_atts([
            'conference' => '',
        ], $atts);
        
        // Get conference
        if (!empty($atts['conference'])) {
            $conference = SVDP_Conference::get_by_slug($atts['conference']);
            if (!$conference) {
                return '<p>Error: Conference not found.</p>';
            }
        }
        
        // Get all conferences for dropdown
        $conferences = SVDP_Conference::get_all(true);
        
        ob_start();
        include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/voucher-request-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render cashier station
     * Usage: [svdp_cashier_station]
     */
    public function render_cashier_station($atts) {
        // Check if user is logged in and has cashier role
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to access the cashier station.</p>';
        }
        
        $user = wp_get_current_user();
        if (!SVDP_Permissions::user_can_access_cashier($user)) {
            return '<p>You do not have permission to access the cashier station.</p>';
        }
        
        ob_start();
        include SVDP_VOUCHERS_PLUGIN_DIR . 'public/templates/cashier-station.php';
        return ob_get_clean();
    }
}
