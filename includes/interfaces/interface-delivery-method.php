<?php
/**
 * Delivery method contract
 *
 * Defines how delivery methods identify themselves, resolve a provider,
 * and normalize payloads before a provider sends them.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

interface SVDP_Delivery_Method_Interface {

    /**
     * Get the unique slug for this delivery method.
     *
     * @return string
     */
    public function get_slug();

    /**
     * Get the provider slug used by this delivery method.
     *
     * @param array $payload Raw payload for the pending delivery request.
     * @return string
     */
    public function get_provider_slug($payload = array());

    /**
     * Build the payload that will be sent to the provider.
     *
     * @param mixed $recipient Recipient for the delivery request.
     * @param array $payload Raw delivery payload.
     * @return array|WP_Error
     */
    public function build_payload($recipient, $payload = array());
}
