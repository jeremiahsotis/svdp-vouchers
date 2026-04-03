<?php
/**
 * Delivery Method Registry
 *
 * Stores delivery methods keyed by their normalized slug.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Delivery_Method_Registry {

    /**
     * Registered delivery methods keyed by slug.
     *
     * @var array<string, SVDP_Delivery_Method_Interface>
     */
    protected $methods = array();

    /**
     * Create a delivery method registry instance.
     *
     * @param array $methods Initial method objects.
     */
    public function __construct($methods = array()) {
        $this->register_many($methods);
    }

    /**
     * Register a delivery method.
     *
     * @param mixed $method Delivery method instance.
     * @return self
     */
    public function register($method) {
        if (!$method instanceof SVDP_Delivery_Method_Interface) {
            throw new InvalidArgumentException('Delivery methods must implement SVDP_Delivery_Method_Interface.');
        }

        $slug = $this->normalize_identifier($method->get_slug());

        if ($slug === '') {
            throw new InvalidArgumentException('Delivery methods must provide a non-empty slug.');
        }

        $this->methods[$slug] = $method;

        return $this;
    }

    /**
     * Register multiple delivery methods.
     *
     * @param array $methods Delivery method instances.
     * @return self
     */
    public function register_many($methods) {
        foreach ((array) $methods as $method) {
            $this->register($method);
        }

        return $this;
    }

    /**
     * Get a registered delivery method by slug.
     *
     * @param string $slug Delivery method slug.
     * @return SVDP_Delivery_Method_Interface|null
     */
    public function get($slug) {
        $slug = $this->normalize_identifier($slug);

        return isset($this->methods[$slug]) ? $this->methods[$slug] : null;
    }

    /**
     * Get all registered delivery methods.
     *
     * @return array<string, SVDP_Delivery_Method_Interface>
     */
    public function all() {
        return $this->methods;
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
}
