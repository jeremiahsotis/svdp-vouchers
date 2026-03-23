<?php
/**
 * Neighbor-facing furniture receipt generation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Furniture_Receipt {

    /**
     * Generate and store one neighbor receipt for a completed furniture voucher.
     *
     * @param array $voucher Formatted cashier voucher data.
     * @return array|WP_Error
     */
    public static function create_for_voucher($voucher) {
        $voucher_id = intval($voucher['id'] ?? 0);
        if ($voucher_id <= 0) {
            return new WP_Error('receipt_voucher_invalid', 'Furniture receipt generation requires a valid voucher.', ['status' => 400]);
        }

        $html = self::render_template('public/templates/documents/furniture-receipt.php', [
            'voucher' => $voucher,
        ]);

        return self::store_document($voucher_id, 'neighbor-receipt.html', $html);
    }

    /**
     * Resolve a public URL from one stored relative receipt path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return string|null
     */
    public static function public_url_from_relative_path($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return null;
        }

        $uploads = wp_upload_dir();
        return trailingslashit($uploads['baseurl']) . $relative_path;
    }

    /**
     * Delete one stored receipt file by relative path.
     *
     * @param string|null $relative_path Stored relative uploads path.
     * @return void
     */
    public static function delete_document($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return;
        }

        $uploads = wp_upload_dir();
        $absolute_path = trailingslashit($uploads['basedir']) . $relative_path;
        if (file_exists($absolute_path)) {
            wp_delete_file($absolute_path);
        }
    }

    /**
     * Render a document template inside the plugin.
     *
     * @param string $relative_path Template path relative to plugin dir.
     * @param array  $vars Template variables.
     * @return string
     */
    private static function render_template($relative_path, $vars = []) {
        $template_path = SVDP_VOUCHERS_PLUGIN_DIR . ltrim($relative_path, '/');

        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Store rendered receipt HTML in the voucher documents directory.
     *
     * @param int    $voucher_id Root voucher ID.
     * @param string $file_name Target file name.
     * @param string $html Rendered HTML payload.
     * @return array|WP_Error
     */
    private static function store_document($voucher_id, $file_name, $html) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return new WP_Error('receipt_uploads_unavailable', 'The uploads directory is not available for receipt storage.', ['status' => 500]);
        }

        $relative_dir = trailingslashit(SVDP_Furniture_Photo_Storage::BASE_SUBDIR . '/' . intval($voucher_id)) . 'documents';
        $absolute_dir = trailingslashit($uploads['basedir']) . $relative_dir;

        if (!wp_mkdir_p($absolute_dir)) {
            return new WP_Error('receipt_directory_failed', 'The receipt storage directory could not be created.', ['status' => 500]);
        }

        $relative_path = trailingslashit($relative_dir) . sanitize_file_name($file_name);
        $absolute_path = trailingslashit($absolute_dir) . sanitize_file_name($file_name);
        $bytes_written = file_put_contents($absolute_path, $html);

        if ($bytes_written === false) {
            return new WP_Error('receipt_write_failed', 'The receipt file could not be written to storage.', ['status' => 500]);
        }

        return [
            'file_path' => $relative_path,
            'url' => self::public_url_from_relative_path($relative_path),
        ];
    }

    /**
     * Ensure one stored document path stays inside the voucher documents subtree.
     *
     * @param mixed $relative_path Stored relative uploads path.
     * @return string|null
     */
    private static function normalize_managed_relative_path($relative_path) {
        $relative_path = ltrim(wp_normalize_path((string) $relative_path), '/');
        if ($relative_path === '' || strpos($relative_path, '../') !== false) {
            return null;
        }

        $base_prefix = trailingslashit(SVDP_Furniture_Photo_Storage::BASE_SUBDIR);
        if (strpos($relative_path, $base_prefix) !== 0 || strpos($relative_path, '/documents/') === false) {
            return null;
        }

        return $relative_path;
    }
}
