<?php
/**
 * Furniture voucher photo normalization and storage.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Furniture_Photo_Storage {

    const BASE_SUBDIR = 'svdp-vouchers';
    const MAX_FILE_SIZE = 8388608; // 8 MB
    const MAX_LONG_EDGE = 1600;
    const THUMB_LONG_EDGE = 360;
    const JPEG_QUALITY = 80;

    /**
     * Normalize one uploaded item photo into the plugin-managed uploads tree.
     *
     * @param int   $voucher_id Root voucher ID.
     * @param int   $item_id Voucher item ID.
     * @param array $file One uploaded file entry.
     * @return array|WP_Error
     */
    public static function store_uploaded_photo($voucher_id, $item_id, $file) {
        $voucher_id = intval($voucher_id);
        $item_id = intval($item_id);

        if ($voucher_id <= 0 || $item_id <= 0) {
            return new WP_Error('invalid_photo_context', 'Invalid photo upload context.', ['status' => 400]);
        }

        $validated = self::validate_file($file);
        if (is_wp_error($validated)) {
            return $validated;
        }

        $paths = self::ensure_item_directory($voucher_id, $item_id);
        if (is_wp_error($paths)) {
            return $paths;
        }

        $source_basename = pathinfo((string) $file['name'], PATHINFO_FILENAME);
        $source_basename = sanitize_file_name($source_basename);
        if ($source_basename === '') {
            $source_basename = 'photo';
        }

        $filename = wp_unique_filename($paths['absolute_dir'], $source_basename . '.jpg');
        $absolute_path = trailingslashit($paths['absolute_dir']) . $filename;
        $relative_path = trailingslashit($paths['relative_dir']) . $filename;

        $normalized = self::normalize_image($file['tmp_name'], $absolute_path, self::MAX_LONG_EDGE);
        if (is_wp_error($normalized)) {
            return $normalized;
        }

        $thumb_relative_path = self::get_thumbnail_relative_path($relative_path);
        $thumb_absolute_path = self::absolute_path_from_relative($thumb_relative_path);
        $thumb_result = self::normalize_image($absolute_path, $thumb_absolute_path, self::THUMB_LONG_EDGE);
        if (is_wp_error($thumb_result)) {
            self::delete_relative_path($relative_path);
            return $thumb_result;
        }

        return [
            'file_path' => $relative_path,
            'file_name' => basename($relative_path),
            'mime_type' => 'image/jpeg',
            'file_size' => filesize($absolute_path),
            'image_width' => intval($normalized['width'] ?? 0),
            'image_height' => intval($normalized['height'] ?? 0),
            'url' => self::public_url_from_relative($relative_path),
            'thumbnail_url' => self::public_url_from_relative($thumb_relative_path),
        ];
    }

    /**
     * Build public URLs for one stored photo.
     *
     * @param string $relative_path Relative uploads path stored in DB.
     * @return array
     */
    public static function build_public_urls($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);

        return [
            'url' => self::public_url_from_relative($relative_path),
            'thumbnail_url' => self::public_url_from_relative(self::get_thumbnail_relative_path($relative_path)),
        ];
    }

    /**
     * Delete one stored photo and its thumbnail by relative path.
     *
     * @param string $relative_path Relative uploads path stored in DB.
     * @return void
     */
    public static function delete_relative_path($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return;
        }

        $paths = [
            self::absolute_path_from_relative($relative_path),
            self::absolute_path_from_relative(self::get_thumbnail_relative_path($relative_path)),
        ];

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                wp_delete_file($path);
            }
        }
    }

    /**
     * Validate the uploaded file before normalization.
     *
     * @param mixed $file Raw uploaded file.
     * @return true|WP_Error
     */
    private static function validate_file($file) {
        if (!is_array($file) || empty($file['tmp_name']) || empty($file['name'])) {
            return new WP_Error('photo_required', 'Choose one photo to upload.', ['status' => 400]);
        }

        $error_code = isset($file['error']) ? intval($file['error']) : UPLOAD_ERR_OK;
        if ($error_code !== UPLOAD_ERR_OK) {
            return new WP_Error('photo_upload_failed', self::map_upload_error($error_code), ['status' => 400]);
        }

        $file_size = isset($file['size']) ? intval($file['size']) : 0;
        if ($file_size <= 0) {
            return new WP_Error('photo_empty', 'The uploaded photo is empty.', ['status' => 400]);
        }

        if ($file_size > self::MAX_FILE_SIZE) {
            return new WP_Error('photo_too_large', 'Photos must be 8 MB or smaller.', ['status' => 400]);
        }

        $image_size = @getimagesize($file['tmp_name']);
        if (!is_array($image_size) || empty($image_size['mime'])) {
            return new WP_Error('photo_invalid', 'Upload a valid image file.', ['status' => 400]);
        }

        $allowed_mimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        if (!in_array($image_size['mime'], $allowed_mimes, true)) {
            return new WP_Error('photo_type_invalid', 'Only JPEG, PNG, GIF, or WebP photos are allowed.', ['status' => 400]);
        }

        return true;
    }

    /**
     * Normalize an image into JPEG and constrain its long edge.
     *
     * @param string $source_path Source image path.
     * @param string $destination_path Destination JPEG path.
     * @param int    $max_long_edge Max dimension for the long edge.
     * @return array|WP_Error
     */
    private static function normalize_image($source_path, $destination_path, $max_long_edge) {
        self::load_image_dependencies();

        $editor = wp_get_image_editor($source_path);
        if (is_wp_error($editor)) {
            return new WP_Error(
                'photo_editor_unavailable',
                self::build_editor_error_message($editor),
                [
                    'status' => 500,
                    'editor_error_code' => $editor->get_error_code(),
                ]
            );
        }

        $size = $editor->get_size();
        if (is_wp_error($size) || empty($size['width']) || empty($size['height'])) {
            return new WP_Error('photo_size_invalid', 'The uploaded photo dimensions could not be read.', ['status' => 400]);
        }

        if (max(intval($size['width']), intval($size['height'])) > intval($max_long_edge)) {
            $resized = $editor->resize($max_long_edge, $max_long_edge, false);
            if (is_wp_error($resized)) {
                return new WP_Error('photo_resize_failed', 'The uploaded photo could not be resized.', ['status' => 500]);
            }
        }

        if (method_exists($editor, 'set_quality')) {
            $editor->set_quality(self::JPEG_QUALITY);
        }

        $saved = $editor->save($destination_path, 'image/jpeg');
        if (is_wp_error($saved)) {
            return new WP_Error('photo_save_failed', 'The uploaded photo could not be saved.', ['status' => 500]);
        }

        return $saved;
    }

    /**
     * Build a cashier-visible message from a WordPress image editor failure.
     *
     * @param WP_Error $error WordPress image editor error.
     * @return string
     */
    private static function build_editor_error_message($error) {
        $message = trim((string) $error->get_error_message());
        if ($message === '') {
            return 'Image processing is unavailable for this upload.';
        }

        return 'Image processing is unavailable for this upload. WordPress reported: ' . $message;
    }

    /**
     * Ensure the plugin-managed item directory exists in uploads.
     *
     * @param int $voucher_id Root voucher ID.
     * @param int $item_id Voucher item ID.
     * @return array|WP_Error
     */
    private static function ensure_item_directory($voucher_id, $item_id) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return new WP_Error('uploads_unavailable', 'The uploads directory is not available.', ['status' => 500]);
        }

        $relative_dir = trailingslashit(self::BASE_SUBDIR . '/' . $voucher_id) . $item_id;
        $absolute_dir = trailingslashit($uploads['basedir']) . $relative_dir;

        if (!wp_mkdir_p($absolute_dir)) {
            return new WP_Error('photo_directory_failed', 'The photo storage directory could not be created.', ['status' => 500]);
        }

        return [
            'absolute_dir' => $absolute_dir,
            'relative_dir' => $relative_dir,
        ];
    }

    /**
     * Build the thumbnail variant path from one stored relative file path.
     *
     * @param string $relative_path Relative uploads path.
     * @return string
     */
    private static function get_thumbnail_relative_path($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return '';
        }

        $extension = pathinfo($relative_path, PATHINFO_EXTENSION);
        $filename = pathinfo($relative_path, PATHINFO_FILENAME);
        $directory = pathinfo($relative_path, PATHINFO_DIRNAME);

        $thumb_name = $filename . '-thumb.' . ($extension !== '' ? $extension : 'jpg');

        if ($directory === '.' || $directory === '') {
            return $thumb_name;
        }

        return trailingslashit($directory) . $thumb_name;
    }

    /**
     * Resolve an absolute uploads path from one stored relative path.
     *
     * @param string $relative_path Relative uploads path.
     * @return string
     */
    private static function absolute_path_from_relative($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return '';
        }

        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . $relative_path;
    }

    /**
     * Resolve a public uploads URL from one stored relative path.
     *
     * @param string $relative_path Relative uploads path.
     * @return string
     */
    private static function public_url_from_relative($relative_path) {
        $relative_path = self::normalize_managed_relative_path($relative_path);
        if ($relative_path === null) {
            return null;
        }

        $uploads = wp_upload_dir();
        return trailingslashit($uploads['baseurl']) . $relative_path;
    }

    /**
     * Ensure one stored file path remains inside the plugin-managed uploads tree.
     *
     * @param mixed $relative_path Stored relative uploads path.
     * @return string|null
     */
    private static function normalize_managed_relative_path($relative_path) {
        $relative_path = ltrim(wp_normalize_path((string) $relative_path), '/');
        if ($relative_path === '' || strpos($relative_path, '../') !== false) {
            return null;
        }

        $base_prefix = trailingslashit(self::BASE_SUBDIR);
        if (strpos($relative_path, $base_prefix) !== 0) {
            return null;
        }

        return $relative_path;
    }

    /**
     * Load WordPress image editor helpers on demand.
     *
     * @return void
     */
    private static function load_image_dependencies() {
        if (!function_exists('wp_get_image_editor')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }

    /**
     * Convert a PHP upload error code into a cashier-facing message.
     *
     * @param int $error_code PHP upload error code.
     * @return string
     */
    private static function map_upload_error($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded photo is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded photo did not finish uploading. Try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'Choose one photo to upload.';
            default:
                return 'The uploaded photo could not be processed.';
        }
    }
}
