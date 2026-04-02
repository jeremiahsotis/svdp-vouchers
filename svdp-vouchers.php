<?php
/**
 * Plugin Name: SVdP Vouchers
 * Description: Virtual clothing voucher management system for St. Vincent de Paul
 * Version: 2.0.0
 * Author: Jeremiah Otis
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: svdp-vouchers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SVDP_VOUCHERS_VERSION', '2.0.0');
define('SVDP_VOUCHERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SVDP_VOUCHERS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-pdf-dependency.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-database.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-settings.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-permissions.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-conference.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-voucher.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-furniture-catalog.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-furniture-cancellation-reason.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-furniture-photo-storage.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-furniture-receipt.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-invoice.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-statement.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-furniture-voucher.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-cashier-shell.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-admin.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-manager.php';
require_once SVDP_VOUCHERS_PLUGIN_DIR . 'includes/class-override-reason.php';

// Load plugin-local PDF support before document features attempt to use it.
SVDP_PDF_Dependency::bootstrap();

/**
 * Main plugin class
 */
class SVDP_Vouchers_Plugin {
    
    public function __construct() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Initialize plugin
        add_action('plugins_loaded', [$this, 'init']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // WordPress Heartbeat for session keep-alive
        add_filter('heartbeat_settings', [$this, 'configure_heartbeat']);
        add_filter('heartbeat_received', [$this, 'heartbeat_received'], 10, 2);

        // REST API authentication for long-running sessions
        add_filter('rest_authentication_errors', [$this, 'handle_rest_authentication'], 99);
        add_filter('rest_pre_serve_request', [$this, 'serve_cashier_html_fragments'], 10, 4);

        // Extend cashier sessions beyond the default WordPress cookie lifetime
        add_filter('auth_cookie_expiration', [$this, 'extend_cashier_auth_cookie'], 10, 3);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        SVDP_Database::maybe_upgrade();
        SVDP_Permissions::register_roles_and_capabilities();

        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        SVDP_Database::maybe_upgrade();
        SVDP_Permissions::register_roles_and_capabilities();

        // Initialize shortcodes
        new SVDP_Shortcodes();
        
        // Initialize admin
        if (is_admin()) {
            new SVDP_Admin();
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Get all vouchers
        register_rest_route('svdp/v1', '/vouchers', [
            'methods' => 'GET',
            'callback' => ['SVDP_Voucher', 'get_vouchers'],
            'permission_callback' => [$this, 'user_can_access_cashier']
        ]);
        
        // Check for duplicate
        register_rest_route('svdp/v1', '/vouchers/check-duplicate', [
            'methods' => 'POST',
            'callback' => ['SVDP_Voucher', 'check_duplicate'],
            'permission_callback' => '__return_true'
        ]);
        
        // Create voucher
        register_rest_route('svdp/v1', '/vouchers/create', [
            'methods' => 'POST',
            'callback' => ['SVDP_Voucher', 'create_voucher'],
            'permission_callback' => '__return_true'
        ]);

        // Get active catalog items for the public furniture request flow
        register_rest_route('svdp/v1', '/catalog-items', [
            'methods' => 'GET',
            'callback' => ['SVDP_Furniture_Catalog', 'get_public_catalog_items'],
            'permission_callback' => '__return_true'
        ]);
        
        // Update voucher status
        register_rest_route('svdp/v1', '/vouchers/(?P<id>\d+)/status', [
            'methods' => 'PATCH',
            'callback' => ['SVDP_Voucher', 'update_status'],
            'permission_callback' => [$this, 'user_can_access_cashier']
        ]);
        
        // Update coat status
        register_rest_route('svdp/v1', '/vouchers/(?P<id>\d+)/coat', [
            'methods' => 'PATCH',
            'callback' => ['SVDP_Voucher', 'update_coat_status'],
            'permission_callback' => [$this, 'user_can_access_cashier']
        ]);
        
        // Get conferences
        register_rest_route('svdp/v1', '/conferences', [
            'methods' => 'GET',
            'callback' => ['SVDP_Conference', 'get_conferences'],
            'permission_callback' => '__return_true'
        ]);

        // Create denied voucher (for tracking)
        register_rest_route('svdp/v1', '/vouchers/create-denied', [
            'methods' => 'POST',
            'callback' => ['SVDP_Voucher', 'create_denied_voucher'],
            'permission_callback' => '__return_true'
        ]);

        // Nonce refresh endpoint (fallback if heartbeat fails)
        register_rest_route('svdp/v1', '/auth/refresh-nonce', [
            'methods' => 'POST',
            'callback' => [$this, 'refresh_nonce'],
            'permission_callback' => function() {
                // Only require logged in - don't check nonce
                return is_user_logged_in();
            }
        ]);

        // Manager validation endpoint
        register_rest_route('svdp/v1', '/managers/validate', [
            'methods' => 'POST',
            'callback' => ['SVDP_Manager', 'validate_code_endpoint'],
            'permission_callback' => '__return_true'
        ]);

        // Get active override reasons
        register_rest_route('svdp/v1', '/override-reasons', [
            'methods' => 'GET',
            'callback' => ['SVDP_Override_Reason', 'get_active_endpoint'],
            'permission_callback' => '__return_true'
        ]);

        // Cashier shell fragments
        register_rest_route('svdp/v1', '/cashier/ping', [
            'methods' => 'POST',
            'callback' => ['SVDP_Cashier_Shell', 'ping'],
            'permission_callback' => [$this, 'user_can_access_cashier']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers', [
            'methods' => 'GET',
            'callback' => ['SVDP_Cashier_Shell', 'get_vouchers_fragment'],
            'permission_callback' => [$this, 'user_can_access_cashier']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => ['SVDP_Cashier_Shell', 'get_voucher_detail_fragment'],
            'permission_callback' => [$this, 'user_can_access_cashier']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers/(?P<id>\d+)/items/(?P<item_id>\d+)/photo', [
            'methods' => 'POST',
            'callback' => ['SVDP_Furniture_Voucher', 'upload_item_photo'],
            'permission_callback' => [$this, 'user_can_redeem_furniture_vouchers']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers/(?P<id>\d+)/items/(?P<item_id>\d+)/complete', [
            'methods' => 'POST',
            'callback' => ['SVDP_Furniture_Voucher', 'complete_item'],
            'permission_callback' => [$this, 'user_can_redeem_furniture_vouchers']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers/(?P<id>\d+)/items/(?P<item_id>\d+)/substitute', [
            'methods' => 'POST',
            'callback' => ['SVDP_Furniture_Voucher', 'substitute_item'],
            'permission_callback' => [$this, 'user_can_redeem_furniture_vouchers']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers/(?P<id>\d+)/items/(?P<item_id>\d+)/cancel', [
            'methods' => 'POST',
            'callback' => ['SVDP_Furniture_Voucher', 'cancel_item'],
            'permission_callback' => [$this, 'user_can_redeem_furniture_vouchers']
        ]);

        register_rest_route('svdp/v1', '/cashier/vouchers/(?P<id>\d+)/complete', [
            'methods' => 'POST',
            'callback' => ['SVDP_Furniture_Voucher', 'complete_voucher'],
            'permission_callback' => [$this, 'user_can_redeem_furniture_vouchers']
        ]);

        register_rest_route('svdp/v1', '/admin/invoices', [
            'methods' => 'GET',
            'callback' => ['SVDP_Invoice', 'get_admin_invoices'],
            'permission_callback' => [$this, 'user_can_manage_admin']
        ]);

        register_rest_route('svdp/v1', '/admin/statements/default-range', [
            'methods' => 'GET',
            'callback' => ['SVDP_Statement', 'get_default_range'],
            'permission_callback' => [$this, 'user_can_manage_admin']
        ]);

        register_rest_route('svdp/v1', '/admin/statements/generate', [
            'methods' => 'POST',
            'callback' => ['SVDP_Statement', 'generate_statement'],
            'permission_callback' => [$this, 'user_can_manage_admin']
        ]);

        register_rest_route('svdp/v1', '/admin/statements/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => ['SVDP_Statement', 'get_statement'],
            'permission_callback' => [$this, 'user_can_manage_admin']
        ]);

    }

    /**
     * Configure WordPress Heartbeat for cashier station
     */
    public function configure_heartbeat($settings) {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'svdp_cashier_station')) {
            $settings['interval'] = 15; // 15 seconds - faster than auto-refresh to always be fresh
        }
        return $settings;
    }

    /**
     * Handle Heartbeat tick - extend session and refresh nonce
     */
    public function heartbeat_received($response, $data) {
        // Check if this is a cashier station heartbeat
        if (isset($data['svdp_cashier_active']) && $data['svdp_cashier_active']) {
            if (is_user_logged_in()) {
                // Extend auth cookie to 14 days
                wp_set_auth_cookie(get_current_user_id(), true, is_ssl());

                // Generate fresh nonce
                $response['svdp_nonce'] = wp_create_nonce('wp_rest');
                $response['svdp_heartbeat_status'] = 'active';
            } else {
                $response['svdp_heartbeat_status'] = 'logged_out';
            }
        }
        return $response;
    }

    /**
     * Custom REST authentication for long-running cashier sessions
     *
     * WordPress REST API behavior:
     * - When X-WP-Nonce is present and INVALID, user is treated as anonymous
     * - This prevents is_user_logged_in() from working even with valid cookie
     *
     * This filter bypasses nonce validation for SVDP routes only,
     * allowing cookie-only authentication for long sessions.
     */
    public function handle_rest_authentication($result) {
        $rest_route = $this->get_current_rest_route();
        if (!$this->is_svdp_rest_route($rest_route)) {
            return $result;
        }

        $user_id = wp_validate_auth_cookie('', 'logged_in');
        if (!$user_id) {
            return $result;
        }

        wp_set_current_user($user_id);

        if ($result instanceof WP_Error) {
            $recoverable_errors = [
                'rest_cookie_invalid_nonce',
                'rest_not_logged_in',
            ];

            $error_codes = $result->get_error_codes();
            $has_recoverable_error = count(array_intersect($recoverable_errors, $error_codes)) > 0;

            if (!$has_recoverable_error) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Serve cashier fragment routes as raw HTML so HTMX can swap them directly.
     */
    public function serve_cashier_html_fragments($served, $result, $request, $server) {
        $route = $request->get_route();
        if (strpos($route, '/svdp/v1/cashier/') !== 0) {
            return $served;
        }

        $headers = $result->get_headers();
        $content_type = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';
        if (strpos($content_type, 'text/html') !== 0) {
            return $served;
        }

        $server->send_header('Content-Type', $content_type);
        $server->send_header('X-Robots-Tag', 'noindex');
        status_header($result->get_status());
        echo $result->get_data();

        return true;
    }

    /**
     * Check if current user has cashier access
     */
    public function user_can_access_cashier() {
        return SVDP_Permissions::user_can_access_cashier();
    }

    /**
     * Check whether the current user can mutate furniture voucher items.
     *
     * @return bool
     */
    public function user_can_redeem_furniture_vouchers() {
        return SVDP_Permissions::user_can_access_cashier()
            && SVDP_Permissions::user_can_redeem_furniture_vouchers();
    }

    /**
     * Check whether the current user can access admin accounting routes.
     *
     * @return bool
     */
    public function user_can_manage_admin() {
        return current_user_can('manage_options');
    }

    /**
     * Determine whether the current request is one of the plugin REST routes.
     *
     * @param string $rest_route Route path.
     * @return bool
     */
    private function is_svdp_rest_route($rest_route) {
        return is_string($rest_route) && strpos($rest_route, '/svdp/v1/') === 0;
    }

    /**
     * Resolve the current REST route for both pretty and query-string API URLs.
     *
     * @return string
     */
    private function get_current_rest_route() {
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        if (is_string($rest_route) && $rest_route !== '') {
            return '/' . ltrim($rest_route, '/');
        }

        if (isset($_GET['rest_route'])) {
            $rest_route = wp_unslash($_GET['rest_route']);
            if (is_string($rest_route) && $rest_route !== '') {
                return '/' . ltrim($rest_route, '/');
            }
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (!is_string($request_uri) || $request_uri === '') {
            return '';
        }

        $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($request_path) || $request_path === '') {
            return '';
        }

        $rest_prefix = '/' . trim(rest_get_url_prefix(), '/');
        $prefix_position = strpos($request_path, $rest_prefix . '/');
        if ($prefix_position === false) {
            return '';
        }

        return substr($request_path, $prefix_position + strlen($rest_prefix));
    }

    /**
     * Keep cashier auth cookies alive for longer staffed sessions.
     */
    public function extend_cashier_auth_cookie($length, $user_id, $remember) {
        $user = get_userdata($user_id);
        if (!$user) {
            return $length;
        }

        if (SVDP_Permissions::user_can_access_cashier($user)) {
            return 14 * DAY_IN_SECONDS;
        }

        return $length;
    }

    /**
     * Refresh nonce endpoint (fallback if heartbeat fails)
     */
    public function refresh_nonce($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_authenticated', 'You must be logged in', ['status' => 401]);
        }

        // Extend auth cookie
        wp_set_auth_cookie(get_current_user_id(), true, is_ssl());

        return [
            'success' => true,
            'nonce' => wp_create_nonce('wp_rest'),
            'timestamp' => current_time('mysql'),
        ];
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only on frontend
        if (is_admin()) {
            return;
        }

        $has_request_form = $this->page_has_shortcode('svdp_voucher_request');
        $has_cashier_shell = $this->page_has_shortcode('svdp_cashier_station');

        if (!$has_request_form && !$has_cashier_shell) {
            return;
        }

        wp_enqueue_style('svdp-vouchers-public', SVDP_VOUCHERS_PLUGIN_URL . 'public/css/voucher-forms.css', [], $this->get_asset_version('public/css/voucher-forms.css'));

        $item_values = SVDP_Settings::get_item_values();
        $script_data = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(),
            'nonce' => wp_create_nonce('wp_rest'),
            'deliveryFee' => 50,
            'itemValues' => [
                'adult' => floatval($item_values['adult']),
                'child' => floatval($item_values['child'])
            ]
        ];

        if ($has_request_form) {
            wp_enqueue_script('svdp-vouchers-request', SVDP_VOUCHERS_PLUGIN_URL . 'public/js/voucher-request.js', ['jquery'], $this->get_asset_version('public/js/voucher-request.js'), true);
            wp_localize_script('svdp-vouchers-request', 'svdpVouchers', $script_data);
        }

        if ($has_cashier_shell) {
            wp_enqueue_script('svdp-htmx', SVDP_VOUCHERS_PLUGIN_URL . 'public/vendor/htmx.min.js', [], '1.9.12', true);
            wp_enqueue_script('svdp-alpine', SVDP_VOUCHERS_PLUGIN_URL . 'public/vendor/alpine.min.js', [], '3.14.9', true);
            wp_script_add_data('svdp-alpine', 'defer', true);
            wp_add_inline_script('svdp-alpine', <<<'JS'
document.addEventListener('alpine:init', function() {
    if (!window.Alpine || window.Alpine.store('cashier')) {
        return;
    }

    window.Alpine.store('cashier', {
        sessionLost: false,
        keepaliveState: 'idle',
        keepaliveLabel: 'Connecting',
        emergencyOpen: false,
        activePanel: null,
        overrideOpen: false,
        selectedVoucherId: null
    });
});
JS, 'before');
            wp_enqueue_script('svdp-cashier-shell', SVDP_VOUCHERS_PLUGIN_URL . 'public/js/cashier-shell.js', ['svdp-htmx', 'svdp-alpine'], $this->get_asset_version('public/js/cashier-shell.js'), true);
            wp_localize_script('svdp-cashier-shell', 'svdpCashierShell', [
                'restUrl' => rest_url(),
                'nonce' => wp_create_nonce('wp_rest'),
                'loginUrl' => wp_login_url($this->current_frontend_url()),
                'pingInterval' => 60000,
            ]);
        }
    }

    /**
     * Return a cache-busting version for plugin assets.
     *
     * @param string $relative_path Asset path relative to the plugin root.
     * @return int|string
     */
    private function get_asset_version($relative_path) {
        $asset_path = SVDP_VOUCHERS_PLUGIN_DIR . ltrim($relative_path, '/');

        return file_exists($asset_path) ? filemtime($asset_path) : SVDP_VOUCHERS_VERSION;
    }

    /**
     * Check the active frontend post for a shortcode.
     */
    private function page_has_shortcode($shortcode) {
        global $post;

        return is_a($post, 'WP_Post') && has_shortcode($post->post_content, $shortcode);
    }

    /**
     * Build a login return URL for cashier re-auth.
     */
    private function current_frontend_url() {
        if (is_singular()) {
            $permalink = get_permalink();
            if ($permalink) {
                return $permalink;
            }
        }

        return home_url('/');
    }
}

// Initialize plugin
new SVDP_Vouchers_Plugin();
