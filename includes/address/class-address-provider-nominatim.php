<?php
/**
 * Nominatim address search provider.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!interface_exists('SVDP_Address_Provider_Interface')) {
    require_once __DIR__ . '/class-address-provider-interface.php';
}

class SVDP_Address_Provider_Nominatim implements SVDP_Address_Provider_Interface {

    const ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    const SOURCE = 'nominatim';

    /**
     * Search Nominatim for address candidates.
     *
     * @param string $query Address search query.
     * @param array  $args  Optional provider arguments.
     * @return array|WP_Error
     */
    public function search($query, $args = []) {
        $query = trim((string) $query);

        if ($query === '') {
            return [];
        }

        $args = wp_parse_args($args, [
            'limit' => 5,
            'countrycodes' => 'us',
        ]);

        $request_url = add_query_arg([
            'q' => $query,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'limit' => max(1, min(10, intval($args['limit']))),
            'countrycodes' => sanitize_text_field($args['countrycodes']),
        ], self::ENDPOINT);

        $response = wp_remote_get($request_url, [
            'timeout' => 6,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => $this->get_user_agent(),
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error(
                'address_provider_error',
                'Address search provider returned an unexpected response.',
                ['status' => $status_code]
            );
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return new WP_Error(
                'address_provider_invalid_json',
                'Address search provider returned invalid JSON.',
                ['status' => 502]
            );
        }

        return $this->normalize_results($decoded);
    }

    /**
     * Normalize Nominatim rows for plugin consumers.
     *
     * @param array $rows Raw Nominatim response rows.
     * @return array
     */
    private function normalize_results($rows) {
        $results = [];

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['lat']) || empty($row['lon'])) {
                continue;
            }

            $display_name = isset($row['display_name'])
                ? sanitize_text_field($row['display_name'])
                : '';

            if ($display_name === '') {
                continue;
            }

            $latitude = (float) $row['lat'];
            $longitude = (float) $row['lon'];

            $results[] = [
                'label' => $display_name,
                'normalized_address' => $display_name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'source' => self::SOURCE,
                'confidence' => $this->normalize_confidence($row['importance'] ?? null),
            ];
        }

        return $results;
    }

    /**
     * Convert Nominatim importance into a bounded confidence value.
     *
     * @param mixed $importance Raw importance value.
     * @return float|null
     */
    private function normalize_confidence($importance) {
        if ($importance === null || $importance === '') {
            return null;
        }

        $confidence = (float) $importance;
        return max(0.0, min(1.0, round($confidence, 4)));
    }

    /**
     * Build a Nominatim-compliant user agent.
     *
     * @return string
     */
    private function get_user_agent() {
        $home_url = function_exists('home_url') ? home_url('/') : '';
        $admin_email = function_exists('get_bloginfo') ? get_bloginfo('admin_email') : '';
        $suffix = trim($home_url . ($admin_email ? ' ' . $admin_email : ''));

        return trim('SVdP Vouchers/' . SVDP_VOUCHERS_VERSION . ' ' . $suffix);
    }
}
