<?php
/**
 * SMS Delivery Method
 *
 * Normalizes link-style SMS payloads for delivery providers.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Delivery_Method_SMS implements SVDP_Delivery_Method_Interface {

    /**
     * Default SMS provider slug for this checkpoint.
     *
     * @var string
     */
    const DEFAULT_PROVIDER = 'telnyx';

    /**
     * Get the unique slug for this delivery method.
     *
     * @return string
     */
    public function get_slug() {
        return 'sms';
    }

    /**
     * Get the provider slug used by this delivery method.
     *
     * @param array $payload Raw payload for the pending delivery request.
     * @return string
     */
    public function get_provider_slug($payload = array()) {
        $provider = $this->extract_provider_override($payload);

        return $provider !== '' ? $provider : self::DEFAULT_PROVIDER;
    }

    /**
     * Build the payload that will be sent to the provider.
     *
     * @param mixed $recipient Recipient for the delivery request.
     * @param array $payload Raw delivery payload.
     * @return array|WP_Error
     */
    public function build_payload($recipient, $payload = array()) {
        $payload = is_array($payload) ? $payload : array();

        if (!empty($payload['attachment']) || !empty($payload['attachments']) || !empty($payload['pdf']) || !empty($payload['pdf_document'])) {
            return self::error('delivery_sms_attachments_not_supported', 'SMS delivery only supports link-style payloads in this checkpoint.');
        }

        $message = $this->normalize_message($payload);
        if ($message === '') {
            return self::error('delivery_sms_message_required', 'SMS delivery requires a usable message.');
        }

        $normalized = array(
            'message' => $message,
        );

        $provider = $this->extract_provider_override($payload);
        if ($provider !== '') {
            $normalized['provider'] = $provider;
        }

        if (!empty($payload['metadata']) && is_array($payload['metadata'])) {
            $normalized['metadata'] = $payload['metadata'];
        }

        return $normalized;
    }

    /**
     * Normalize an SMS message from the payload.
     *
     * @param array $payload Raw delivery payload.
     * @return string
     */
    protected function normalize_message($payload) {
        $message = isset($payload['message']) ? trim((string) $payload['message']) : '';

        if (trim(strip_tags($message)) !== '') {
            return $this->collapse_whitespace($message);
        }

        $link = $this->extract_link($payload);
        if ($link === '') {
            return '';
        }

        $prefix = $this->extract_message_prefix($payload);
        if ($prefix !== '') {
            return $this->collapse_whitespace($prefix . ' ' . $link);
        }

        return $link;
    }

    /**
     * Extract a link value from the raw payload.
     *
     * @param array $payload Raw delivery payload.
     * @return string
     */
    protected function extract_link($payload) {
        foreach (array('link', 'url', 'link_url', 'delivery_url', 'voucher_url') as $key) {
            if (empty($payload[$key])) {
                continue;
            }

            $link = trim((string) $payload[$key]);
            if ($link !== '') {
                return $link;
            }
        }

        return '';
    }

    /**
     * Extract a message prefix for link-based SMS payloads.
     *
     * @param array $payload Raw delivery payload.
     * @return string
     */
    protected function extract_message_prefix($payload) {
        foreach (array('message_prefix', 'intro', 'label', 'link_text') as $key) {
            if (empty($payload[$key])) {
                continue;
            }

            $prefix = $this->collapse_whitespace((string) $payload[$key]);
            if ($prefix !== '') {
                return $prefix;
            }
        }

        return '';
    }

    /**
     * Extract a provider override from the raw payload.
     *
     * @param mixed $payload Raw delivery payload.
     * @return string
     */
    protected function extract_provider_override($payload) {
        $payload = is_array($payload) ? $payload : array();

        foreach (array('provider', 'provider_slug', 'provider_override') as $key) {
            if (empty($payload[$key])) {
                continue;
            }

            $provider = strtolower(trim((string) $payload[$key]));
            $provider = preg_replace('/[^a-z0-9_-]/', '', $provider);

            if ($provider !== '') {
                return $provider;
            }
        }

        return '';
    }

    /**
     * Collapse repeated whitespace while preserving the message content.
     *
     * @param string $value Raw string.
     * @return string
     */
    protected function collapse_whitespace($value) {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
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
