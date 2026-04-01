<?php
/**
 * Role and capability management.
 */
class SVDP_Permissions {

    /**
     * Ensure roles and capabilities exist for cashier and furniture workflows.
     */
    public static function register_roles_and_capabilities() {
        add_role('svdp_cashier', 'SVdP Cashier', [
            'read' => true,
            'access_cashier_station' => true,
        ]);

        self::grant_caps_to_role('svdp_cashier', [
            'read',
            'access_cashier_station',
        ]);

        self::grant_caps_to_role('administrator', [
            'access_cashier_station',
            'svdp_redeem_furniture_vouchers',
            'svdp_manage_furniture_catalog',
        ]);
    }

    /**
     * Check whether a user can access cashier voucher views.
     *
     * @param WP_User|int|null $user User object, user ID, or current user.
     * @return bool
     */
    public static function user_can_access_cashier($user = null) {
        return self::user_has_capability($user, 'access_cashier_station') || self::user_has_capability($user, 'manage_options');
    }

    /**
     * Check whether a user can mutate furniture vouchers.
     *
     * @param WP_User|int|null $user User object, user ID, or current user.
     * @return bool
     */
    public static function user_can_redeem_furniture_vouchers($user = null) {
        return self::user_has_capability($user, 'svdp_redeem_furniture_vouchers') || self::user_has_capability($user, 'manage_options');
    }

    /**
     * Check whether a user can manage furniture source data.
     *
     * @param WP_User|int|null $user User object, user ID, or current user.
     * @return bool
     */
    public static function user_can_manage_furniture_catalog($user = null) {
        return self::user_has_capability($user, 'svdp_manage_furniture_catalog') || self::user_has_capability($user, 'manage_options');
    }

    /**
     * Grant a list of capabilities to a role.
     *
     * @param string $role_name Role slug.
     * @param array  $caps Capability strings.
     * @return void
     */
    private static function grant_caps_to_role($role_name, $caps) {
        $role = get_role($role_name);
        if (!$role) {
            return;
        }

        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }

    /**
     * Capability check helper that supports a user object, user ID, or current user.
     *
     * @param WP_User|int|null $user User object, user ID, or current user.
     * @param string           $cap Capability to check.
     * @return bool
     */
    private static function user_has_capability($user, $cap) {
        if ($user instanceof WP_User) {
            return $user->exists() && $user->has_cap($cap);
        }

        if (is_numeric($user)) {
            $user = get_userdata((int) $user);
            return $user instanceof WP_User && $user->has_cap($cap);
        }

        if (!is_user_logged_in()) {
            return false;
        }

        return current_user_can($cap);
    }
}
