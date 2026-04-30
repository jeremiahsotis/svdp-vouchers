<?php
/**
 * Manager Class
 *
 * Handles CRUD operations for managers who can approve emergency voucher overrides
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SVDP_Manager {
    const MAX_FAILED_ATTEMPTS = 5;
    const LOCKOUT_SECONDS = 900;

    /**
     * Create a new manager with auto-generated or manually assigned code.
     */
    public static function create($name, $manual_code = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        $code = self::generate_code($manual_code);
        if (is_wp_error($code)) {
            return [
                'success' => false,
                'error' => $code->get_error_message()
            ];
        }

        if (self::code_exists($code)) {
            return [
                'success' => false,
                'error' => 'Manager code already exists'
            ];
        }

        $code_hash = wp_hash_password($code);

        $result = $wpdb->insert($table, [
            'name' => sanitize_text_field($name),
            'code_hash' => $code_hash,
            'active' => 1,
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_used_at' => null,
            'created_date' => current_time('mysql')
        ]);

        if ($result) {
            return [
                'success' => true,
                'id' => $wpdb->insert_id,
                'code' => $code, // Only returned once!
                'name' => $name
            ];
        }

        return ['success' => false];
    }

    /**
     * Generate a 4-character override code or validate a manually assigned one.
     */
    private static function generate_code($manual = null) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        if ($manual !== null && trim((string) $manual) !== '') {
            $code = strtoupper(trim((string) $manual));

            if (!preg_match('/^[A-Z2-9]{4}$/', $code)) {
                return new WP_Error('invalid_code_format', 'Code must be 4 characters A-Z, 2-9');
            }

            return $code;
        }

        do {
            $code = '';
            for ($i = 0; $i < 4; $i++) {
                $code .= $chars[wp_rand(0, strlen($chars) - 1)];
            }
        } while (self::code_exists($code));

        return $code;
    }

    /**
     * Check whether an override code already belongs to any manager.
     */
    private static function code_exists($code) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        $managers = $wpdb->get_results("SELECT code_hash FROM $table");

        foreach ($managers as $manager) {
            if (wp_check_password($code, $manager->code_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate a manager code
     *
     * @param string $code The code to validate
     * @param string|null $manager_name Optional manager identity to validate against.
     * @return array Result with 'valid', 'id', and 'name' keys
     */
    public static function validate_code($code, $manager_name = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return ['valid' => false, 'error' => 'missing_code'];
        }

        if ($manager_name !== null && trim((string) $manager_name) !== '') {
            $manager = $wpdb->get_row($wpdb->prepare(
                "SELECT id, name, code_hash, failed_attempts, locked_until FROM $table WHERE active = 1 AND name = %s LIMIT 1",
                sanitize_text_field($manager_name)
            ));

            if (!$manager) {
                return ['valid' => false, 'error' => 'manager_not_found'];
            }

            if (self::is_locked($manager)) {
                return [
                    'valid' => false,
                    'id' => intval($manager->id),
                    'name' => $manager->name,
                    'locked' => true,
                    'locked_until' => $manager->locked_until
                ];
            }

            if (wp_check_password($code, $manager->code_hash)) {
                self::record_success($manager->id);

                return [
                    'valid' => true,
                    'id' => intval($manager->id),
                    'name' => $manager->name,
                    'authority' => [
                        'managerId' => intval($manager->id),
                        'managerName' => $manager->name
                    ]
                ];
            }

            return self::record_failure($manager);
        }

        // Get all active managers
        $managers = $wpdb->get_results(
            "SELECT id, name, code_hash, failed_attempts, locked_until FROM $table WHERE active = 1"
        );

        // Check each manager's code hash
        foreach ($managers as $manager) {
            if (self::is_locked($manager)) {
                continue;
            }

            if (wp_check_password($code, $manager->code_hash)) {
                self::record_success($manager->id);

                return [
                    'valid' => true,
                    'id' => intval($manager->id),
                    'name' => $manager->name,
                    'authority' => [
                        'managerId' => intval($manager->id),
                        'managerName' => $manager->name
                    ]
                ];
            }
        }

        return ['valid' => false];
    }

    /**
     * Validate a code and write an audit row for the attempt.
     */
    public static function validate_code_with_audit($code, $manager_name, $reason_id, $context = null, $voucher_id = null) {
        global $wpdb;

        $result = self::validate_code($code, $manager_name);
        $reason = self::get_reason_snapshot($reason_id);
        $audit_table = $wpdb->prefix . 'svdp_override_audit';

        $wpdb->insert($audit_table, [
            'voucher_id' => $voucher_id ? intval($voucher_id) : null,
            'manager_id' => isset($result['id']) ? intval($result['id']) : null,
            'manager_name_snapshot' => sanitize_text_field($manager_name),
            'actor_user_id' => get_current_user_id(),
            'success' => !empty($result['valid']) ? 1 : 0,
            'reason_id' => $reason ? intval($reason->id) : (intval($reason_id) ?: null),
            'reason_text_snapshot' => $reason ? $reason->reason_text : null,
            'context' => sanitize_text_field($context),
            'created_at' => current_time('mysql')
        ]);

        return $result;
    }

    /**
     * Get all managers
     */
    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        return $wpdb->get_results(
            "SELECT id, name, active, failed_attempts, locked_until, last_used_at, created_date
             FROM $table
             ORDER BY created_date DESC"
        );
    }

    /**
     * Update manager name
     */
    public static function update($id, $name) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        return $wpdb->update(
            $table,
            ['name' => sanitize_text_field($name)],
            ['id' => intval($id)]
        );
    }

    /**
     * Deactivate a manager (soft delete)
     */
    public static function deactivate($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        return $wpdb->update(
            $table,
            ['active' => 0],
            ['id' => intval($id)]
        );
    }

    /**
     * Reactivate a deactivated manager
     */
    public static function reactivate($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        return $wpdb->update(
            $table,
            ['active' => 1],
            ['id' => intval($id)]
        );
    }

    /**
     * Regenerate code for a manager
     *
     * @return array Result with 'success' and 'code' keys
     */
    public static function regenerate_code($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        $code = self::generate_code();
        $code_hash = wp_hash_password($code);

        $result = $wpdb->update(
            $table,
            [
                'code_hash' => $code_hash,
                'failed_attempts' => 0,
                'locked_until' => null
            ],
            ['id' => intval($id)]
        );

        if ($result !== false) {
            return ['success' => true, 'code' => $code];
        }

        return ['success' => false];
    }

    /**
     * REST API endpoint wrapper for validate_code
     */
    public static function validate_code_endpoint($request) {
        $code = $request->get_param('code');
        $manager_name = $request->get_param('managerName');
        $reason_id = $request->get_param('reasonId');
        $context = $request->get_param('context');
        $voucher_id = $request->get_param('voucherId');

        if (empty($code)) {
            return new WP_Error('missing_code', 'Manager code is required', ['status' => 400]);
        }

        if (empty($manager_name)) {
            return new WP_Error('missing_manager_name', 'Manager name is required', ['status' => 400]);
        }

        if (empty($reason_id)) {
            return new WP_Error('missing_reason', 'Override reason is required', ['status' => 400]);
        }

        return self::validate_code_with_audit($code, $manager_name, $reason_id, $context, $voucher_id);
    }

    /**
     * Determine whether a manager is currently locked.
     */
    private static function is_locked($manager) {
        if (empty($manager->locked_until)) {
            return false;
        }

        return strtotime($manager->locked_until) > time();
    }

    /**
     * Reset failed attempts after a successful validation.
     */
    private static function record_success($manager_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';

        $wpdb->update(
            $table,
            [
                'failed_attempts' => 0,
                'locked_until' => null,
                'last_used_at' => current_time('mysql')
            ],
            ['id' => intval($manager_id)]
        );
    }

    /**
     * Increment failed attempts and lock the manager when the threshold is met.
     */
    private static function record_failure($manager) {
        global $wpdb;
        $table = $wpdb->prefix . 'svdp_managers';
        $failed_attempts = intval($manager->failed_attempts) + 1;
        $locked_until = null;

        if ($failed_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $locked_until = date('Y-m-d H:i:s', time() + self::LOCKOUT_SECONDS);
        }

        $wpdb->update(
            $table,
            [
                'failed_attempts' => $failed_attempts,
                'locked_until' => $locked_until
            ],
            ['id' => intval($manager->id)]
        );

        return [
            'valid' => false,
            'id' => intval($manager->id),
            'name' => $manager->name,
            'failedAttempts' => $failed_attempts,
            'locked' => $locked_until !== null,
            'locked_until' => $locked_until
        ];
    }

    /**
     * Capture the selected reason text at validation time.
     */
    private static function get_reason_snapshot($reason_id) {
        global $wpdb;

        $reason_id = intval($reason_id);
        if ($reason_id <= 0) {
            return null;
        }

        $table = $wpdb->prefix . 'svdp_override_reasons';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, reason_text FROM $table WHERE id = %d LIMIT 1",
            $reason_id
        ));
    }
}
