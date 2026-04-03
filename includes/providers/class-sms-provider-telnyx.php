<?php
/**
 * Telnyx SMS Provider
 *
 * Accepts normalized SMS payloads and returns a structured stub result for
 * future Telnyx integration.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_SMS_Provider_Telnyx implements SVDP_Delivery_Provider_Interface {

    /**
     * Get the unique slug for this delivery provider.
     *
     * @return string
     */
    public function get_slug() {
        return 'telnyx';
    }

    /**
     * Send a normalized payload to the recipient.
     *
     * @param mixed $recipient Recipient for the delivery request.
     * @param array $payload Normalized delivery payload.
     * @return array|WP_Error
     */
    public function send($recipient, $payload = array()) {
        $recipient = $this->normalize_recipient($recipient);
        if ($recipient === '') {
            return self::error('delivery_sms_recipient_required', 'SMS delivery requires a recipient.');
        }

        $payload = is_array($payload) ? $payload : array();

        $message = isset($payload['message']) ? trim((string) $payload['message']) : '';
        if ($message === '') {
            return self::error('delivery_sms_message_required', 'SMS delivery requires a message.');
        }

        return array(
            'success' => true,
            'provider' => $this->get_slug(),
            'recipient' => $recipient,
            'stub' => true,
        );
    }

    /**
     * Normalize a recipient value for SMS delivery.
     *
     * @param mixed $recipient Raw recipient value.
     * @return string
     */
    protected function normalize_recipient($recipient) {
        if (is_array($recipient)) {
            foreach (array('phone', 'number', 'recipient', 'to') as $key) {
                if (empty($recipient[$key])) {
                    continue;
                }

                $value = trim((string) $recipient[$key]);
                if ($value !== '') {
                    return $value;
                }
            }

            foreach ($recipient as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        }

        return trim((string) $recipient);
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
