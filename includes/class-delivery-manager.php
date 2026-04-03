<?php
/**
 * Delivery Manager
 *
 * Provides the core abstraction for registering delivery methods/providers
 * and resolving them for future delivery flows.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Delivery_Manager {

    /**
     * Delivery method registry.
     *
     * @var SVDP_Delivery_Method_Registry
     */
    protected $method_registry;

    /**
     * Delivery provider registry.
     *
     * @var SVDP_Delivery_Provider_Registry
     */
    protected $provider_registry;

    /**
     * Create a delivery manager instance.
     *
     * @param array|SVDP_Delivery_Method_Registry $methods Initial method objects or registry.
     * @param array|SVDP_Delivery_Provider_Registry $providers Initial provider objects or registry.
     */
    public function __construct($methods = array(), $providers = array()) {
        $this->method_registry = $methods instanceof SVDP_Delivery_Method_Registry
            ? $methods
            : new SVDP_Delivery_Method_Registry();
        $this->provider_registry = $providers instanceof SVDP_Delivery_Provider_Registry
            ? $providers
            : new SVDP_Delivery_Provider_Registry();

        if (!($methods instanceof SVDP_Delivery_Method_Registry)) {
            $this->register_methods($methods);
        }

        if (!($providers instanceof SVDP_Delivery_Provider_Registry)) {
            $this->register_providers($providers);
        }
    }

    /**
     * Create a delivery manager with the default checkpoint 3 stack.
     *
     * @return self|array|WP_Error
     */
    public static function create_default() {
        $manager = new self();

        $methods_result = $manager->register_methods(array(
            new SVDP_Delivery_Method_Email(),
            new SVDP_Delivery_Method_SMS(),
        ));

        if (self::is_error_result($methods_result)) {
            return $methods_result;
        }

        $providers_result = $manager->register_providers(array(
            new SVDP_Email_Provider_WP_Mail(),
            new SVDP_SMS_Provider_Telnyx(),
        ));

        if (self::is_error_result($providers_result)) {
            return $providers_result;
        }

        return $manager;
    }

    /**
     * Register a delivery method.
     *
     * @param mixed $method Delivery method instance.
     * @return self|array|WP_Error
     */
    public function register_method($method) {
        $result = $this->method_registry->register($method);

        if (self::is_error_result($result)) {
            return $result;
        }

        return $this;
    }

    /**
     * Register multiple delivery methods.
     *
     * @param array $methods Delivery method instances.
     * @return self|array|WP_Error
     */
    public function register_methods($methods) {
        $result = $this->method_registry->register_many($methods);

        if (self::is_error_result($result)) {
            return $result;
        }

        return $this;
    }

    /**
     * Register a delivery provider.
     *
     * @param mixed $provider Delivery provider instance.
     * @return self|array|WP_Error
     */
    public function register_provider($provider) {
        $result = $this->provider_registry->register($provider);

        if (self::is_error_result($result)) {
            return $result;
        }

        return $this;
    }

    /**
     * Register multiple delivery providers.
     *
     * @param array $providers Delivery provider instances.
     * @return self|array|WP_Error
     */
    public function register_providers($providers) {
        $result = $this->provider_registry->register_many($providers);

        if (self::is_error_result($result)) {
            return $result;
        }

        return $this;
    }

    /**
     * Get a registered delivery method by slug.
     *
     * @param string $slug Delivery method slug.
     * @return SVDP_Delivery_Method_Interface|null
     */
    public function get_method($slug) {
        return $this->method_registry->get($slug);
    }

    /**
     * Get a registered delivery provider by slug.
     *
     * @param string $slug Delivery provider slug.
     * @return SVDP_Delivery_Provider_Interface|null
     */
    public function get_provider($slug) {
        return $this->provider_registry->get($slug);
    }

    /**
     * Send a delivery payload using a registered method/provider pair.
     *
     * @param string $method Delivery method slug.
     * @param mixed $recipient Recipient for the delivery request.
     * @param array $payload Raw delivery payload.
     * @return mixed
     */
    public function send($method, $recipient, $payload = array()) {
        $payload = is_array($payload) ? $payload : array();
        $delivery_method = $this->get_method($method);

        if (!$delivery_method) {
            return self::error('delivery_method_not_registered', 'Delivery method is not registered.');
        }

        $provider_slug = $delivery_method->get_provider_slug($payload);

        if (self::is_error_result($provider_slug)) {
            return $provider_slug;
        }

        $provider_slug = $this->normalize_identifier($provider_slug);

        if ($provider_slug === '') {
            return self::error('delivery_provider_not_defined', 'Delivery method did not resolve a delivery provider.');
        }

        $provider = $this->get_provider($provider_slug);

        if (!$provider) {
            return self::error('delivery_provider_not_registered', 'Delivery provider is not registered.');
        }

        $provider_payload = $delivery_method->build_payload($recipient, $payload);

        if (self::is_error_result($provider_payload)) {
            return $provider_payload;
        }

        if (!is_array($provider_payload)) {
            return self::error('delivery_payload_invalid', 'Delivery methods must return an array payload.');
        }

        return $provider->send($recipient, $provider_payload);
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

    /**
     * Determine whether a value is a WP-style error result.
     *
     * @param mixed $value Potential error value.
     * @return bool
     */
    protected static function is_error_result($value) {
        if (function_exists('is_wp_error') && is_wp_error($value)) {
            return true;
        }

        return is_array($value)
            && !empty($value['error'])
            && !empty($value['code'])
            && array_key_exists('message', $value);
    }
}
