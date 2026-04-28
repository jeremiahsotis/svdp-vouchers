<?php
/**
 * Address provider contract.
 */

if (!defined('ABSPATH')) {
    exit;
}

interface SVDP_Address_Provider_Interface {

    /**
     * Search for address candidates.
     *
     * Implementations return normalized results shaped for public REST use:
     * label, normalized_address, latitude, longitude, source, confidence,
     * line1, city, state, and zip.
     *
     * @param string $query Address search query.
     * @param array  $args  Optional provider arguments.
     * @return array|WP_Error
     */
    public function search($query, $args = []);
}
