<?php
/**
 * Email Delivery Method
 *
 * Normalizes link-style email payloads for delivery providers.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Delivery_Method_Email implements SVDP_Delivery_Method_Interface {

    /**
     * Default email provider slug for this checkpoint.
     *
     * @var string
     */
    const DEFAULT_PROVIDER = 'wp_mail';

    /**
     * Get the unique slug for this delivery method.
     *
     * @return string
     */
    public function get_slug() {
        return 'email';
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
            return self::error('delivery_email_attachments_not_supported', 'Email delivery only supports link-style payloads in this checkpoint.');
        }

        $subject = isset($payload['subject']) ? trim((string) $payload['subject']) : '';
        if ($subject === '') {
            return self::error('delivery_email_subject_required', 'Email delivery requires a subject.');
        }

        $message = isset($payload['message']) ? (string) $payload['message'] : '';
        if (trim(strip_tags($message)) === '') {
            return self::error('delivery_email_message_required', 'Email delivery requires a message.');
        }

        $headers = $this->normalize_headers(isset($payload['headers']) ? $payload['headers'] : array());
        if (!$this->has_content_type_header($headers)) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        $normalized = array(
            'subject' => $subject,
            'message' => $message,
        );

        if (!empty($headers)) {
            $normalized['headers'] = $headers;
        }

        $provider = $this->extract_provider_override($payload);
        if ($provider !== '') {
            $normalized['provider'] = $provider;
        }

        return $normalized;
    }

    /**
     * Normalize headers to a list of non-empty strings.
     *
     * @param mixed $headers Raw headers payload.
     * @return array
     */
    protected function normalize_headers($headers) {
        if (is_string($headers)) {
            $headers = preg_split('/\r\n|\r|\n/', $headers);
        }

        $normalized = array();
        foreach ((array) $headers as $header) {
            $header = trim((string) $header);
            if ($header === '') {
                continue;
            }

            $normalized[] = $header;
        }

        return $normalized;
    }

    /**
     * Determine whether the payload already declares a content type.
     *
     * @param array $headers Normalized header list.
     * @return bool
     */
    protected function has_content_type_header($headers) {
        foreach ((array) $headers as $header) {
            if (stripos((string) $header, 'content-type:') === 0) {
                return true;
            }
        }

        return false;
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
