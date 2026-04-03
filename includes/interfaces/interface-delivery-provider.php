<?php
/**
 * Delivery provider contract
 *
 * Providers execute the final transport-specific send operation once a
 * delivery method has normalized the outbound payload.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

interface SVDP_Delivery_Provider_Interface {

    /**
     * Get the unique slug for this delivery provider.
     *
     * @return string
     */
    public function get_slug();

    /**
     * Send a normalized payload to the recipient.
     *
     * @param mixed $recipient Recipient for the delivery request.
     * @param array $payload Normalized delivery payload.
     * @return mixed
     */
    public function send($recipient, $payload = array());
}
