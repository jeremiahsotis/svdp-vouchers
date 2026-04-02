<?php
/**
 * Minimal plugin-local autoloader for TCPDF.
 *
 * This keeps a stable entrypoint for the vouchers plugin while still allowing
 * a future Composer install to replace this file with a generated autoloader.
 */

$tcpdf_path = __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';

if (file_exists($tcpdf_path)) {
    require_once $tcpdf_path;
}
