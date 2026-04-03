<?php
/**
 * wp_mail Email Provider
 *
 * Sends normalized email payloads via WordPress core mail transport.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Email_Provider_WP_Mail implements SVDP_Delivery_Provider_Interface {

    /**
     * Get the unique slug for this delivery provider.
     *
     * @return string
     */
    public function get_slug() {
        return 'wp_mail';
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
        if (empty($recipient)) {
            return self::error('delivery_email_recipient_required', 'Email delivery requires a recipient.');
        }

        $payload = is_array($payload) ? $payload : array();

        $subject = isset($payload['subject']) ? trim((string) $payload['subject']) : '';
        if ($subject === '') {
            return self::error('delivery_email_subject_required', 'Email delivery requires a subject.');
        }

        $message = isset($payload['message']) ? (string) $payload['message'] : '';
        if (trim(strip_tags($message)) === '') {
            return self::error('delivery_email_message_required', 'Email delivery requires a message.');
        }

        if (!function_exists('wp_mail')) {
            return self::error('delivery_email_provider_unavailable', 'wp_mail is not available.');
        }

        $headers = isset($payload['headers']) ? $payload['headers'] : array();
        $sent = wp_mail($recipient, $subject, $message, $headers);

        if (!$sent) {
            return self::error('delivery_email_send_failed', 'The email could not be sent.', array(
                'provider' => $this->get_slug(),
            ));
        }

        return array(
            'success' => true,
            'provider' => $this->get_slug(),
            'recipient' => $recipient,
        );
    }

    /**
     * Normalize a recipient value for wp_mail.
     *
     * @param mixed $recipient Raw recipient value.
     * @return string|array|null
     */
    protected function normalize_recipient($recipient) {
        if (is_array($recipient)) {
            $normalized = array();

            foreach ($recipient as $address) {
                $address = trim((string) $address);
                if ($address === '') {
                    continue;
                }

                $normalized[] = $address;
            }

            return !empty($normalized) ? $normalized : null;
        }

        $recipient = trim((string) $recipient);

        return $recipient !== '' ? $recipient : null;
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
