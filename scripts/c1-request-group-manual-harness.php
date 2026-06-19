<?php
/**
 * Manual C1 request-group creation harness.
 *
 * Usage from the plugin directory, inside a WordPress/Local runtime:
 * php scripts/c1-request-group-manual-harness.php --run
 */

if (php_sapi_name() !== 'cli') {
    exit("CLI only.\n");
}

if (!in_array('--run', $argv, true)) {
    echo "Dry run only. Add --run to create a real C1 request group.\n";
    exit(0);
}

$wp_load = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wp_load)) {
    exit("Could not find wp-load.php at {$wp_load}\n");
}

require_once $wp_load;

$result = SVDP_Voucher_Request_Group::create([
    'firstName' => 'C1 Manual',
    'lastName' => 'Harness',
    'dob' => '1980-01-01',
    'adults' => 2,
    'children' => 1,
    'conference' => 'cathedral-immaculate-conception',
    'requestorName' => 'C1 Harness',
    'requestorEmail' => 'c1-harness@example.test',
    'createdBy' => 'Vincentian',
    'voucherTypes' => ['clothing', 'furniture', 'household_goods'],
    'deliveryRequested' => true,
    'deliveryAddress' => [
        'line1' => '123 Main St',
        'city' => 'Fort Wayne',
        'state' => 'IN',
        'zip' => '46802',
    ],
]);

if (is_wp_error($result)) {
    echo 'ERROR: ' . $result->get_error_code() . ' - ' . $result->get_error_message() . "\n";
    exit(1);
}

echo wp_json_encode($result, JSON_PRETTY_PRINT) . "\n";
