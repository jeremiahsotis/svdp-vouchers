<?php
/**
 * Plugin-internal PDF dependency bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_PDF_Dependency {

    /**
     * Relative path to the plugin-local PDF autoloader entrypoint.
     */
    const AUTOLOAD_RELATIVE_PATH = 'vendor/autoload.php';

    /**
     * Load the plugin-local TCPDF dependency once.
     *
     * @return bool
     */
    public static function bootstrap() {
        if (class_exists('TCPDF', false)) {
            return true;
        }

        $autoload_path = SVDP_VOUCHERS_PLUGIN_DIR . self::AUTOLOAD_RELATIVE_PATH;
        if (!file_exists($autoload_path)) {
            return false;
        }

        require_once $autoload_path;

        return class_exists('TCPDF', false);
    }
}
