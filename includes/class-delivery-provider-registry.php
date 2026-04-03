<?php
/**
 * Delivery Provider Registry
 *
 * Stores delivery providers keyed by their normalized slug.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Delivery_Provider_Registry {

    /**
     * Registered delivery providers keyed by slug.
     *
     * @var array<string, SVDP_Delivery_Provider_Interface>
     */
    protected $providers = array();

    /**
     * Create a delivery provider registry instance.
     *
     * @param array $providers Initial provider objects.
     */
    public function __construct($providers = array()) {
        $this->register_many($providers);
    }

    /**
     * Register a delivery provider.
     *
     * @param mixed $provider Delivery provider instance.
     * @return self|array|WP_Error
     */
    public function register($provider) {
        if (!$provider instanceof SVDP_Delivery_Provider_Interface) {
            return self::error(
                'delivery_provider_invalid',
                'Delivery providers must implement SVDP_Delivery_Provider_Interface.'
            );
        }

        $slug = $this->normalize_identifier($provider->get_slug());

        if ($slug === '') {
            return self::error(
                'delivery_provider_slug_required',
                'Delivery providers must provide a non-empty slug.'
            );
        }

        $this->providers[$slug] = $provider;

        return $this;
    }

    /**
     * Register multiple delivery providers.
     *
     * @param array $providers Delivery provider instances.
     * @return self|array|WP_Error
     */
    public function register_many($providers) {
        foreach ((array) $providers as $provider) {
            $result = $this->register($provider);

            if ($this->is_error_result($result)) {
                return $result;
            }
        }

        return $this;
    }

    /**
     * Get a registered delivery provider by slug.
     *
     * @param string $slug Delivery provider slug.
     * @return SVDP_Delivery_Provider_Interface|null
     */
    public function get($slug) {
        $slug = $this->normalize_identifier($slug);

        return isset($this->providers[$slug]) ? $this->providers[$slug] : null;
    }

    /**
     * Get all registered delivery providers.
     *
     * @return array<string, SVDP_Delivery_Provider_Interface>
     */
    public function all() {
        return $this->providers;
    }

    /**
     * Normalize a registry slug.
     *
     * @param string $identifier Raw slug.
     * @return string
     */
    protected function normalize_identifier($identifier) {
        $identifier = strtolower(trim((string) $identifier));

        return preg_replace('/[^a-z0-9_-]/', '', $identifier);
    }

    /**
     * Determine whether a value is a WP-style error result.
     *
     * @param mixed $value Potential error value.
     * @return bool
     */
    protected function is_error_result($value) {
        if (function_exists('is_wp_error') && is_wp_error($value)) {
            return true;
        }

        return is_array($value)
            && !empty($value['error'])
            && !empty($value['code'])
            && array_key_exists('message', $value);
    }

    /**
     * Create an error object without requiring WordPress during local linting.
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @param array $data Optional error context.
     * @return array|WP_Error
     */
    protected static function error($code, $message, $data = array()) {
        if (class_exists('WP_Error')) {
            return new WP_Error($code, $message, $data);
        }

        return array(
            'error' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        );
    }
}
