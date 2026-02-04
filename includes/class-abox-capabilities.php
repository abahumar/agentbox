<?php
/**
 * Capabilities management
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Capabilities class
 */
class ABOX_Capabilities {

    /**
     * Custom capability for creating box orders
     */
    const CAPABILITY = 'abox_create_orders';

    /**
     * Sales agent role slug
     */
    const ROLE_SLUG = 'sales_agent';

    /**
     * Sales agent role name
     */
    const ROLE_NAME = 'Sales Agent';

    /**
     * Plugin activation
     */
    public static function activate() {
        // Get customer role capabilities as base
        $customer_role = get_role( 'customer' );
        $capabilities = $customer_role ? $customer_role->capabilities : array( 'read' => true );
        $capabilities[ self::CAPABILITY ] = true;

        // Create sales_agent role
        add_role( self::ROLE_SLUG, self::ROLE_NAME, $capabilities );

        // Add capability to administrator
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( self::CAPABILITY );
        }

        // Add capability to shop_manager
        $shop_manager = get_role( 'shop_manager' );
        if ( $shop_manager ) {
            $shop_manager->add_cap( self::CAPABILITY );
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Remove capability from roles
        $roles = array( 'administrator', 'shop_manager', self::ROLE_SLUG );

        foreach ( $roles as $role_slug ) {
            $role = get_role( $role_slug );
            if ( $role ) {
                $role->remove_cap( self::CAPABILITY );
            }
        }

        // Note: We keep the sales_agent role to preserve user assignments
        // Uncomment below to remove role on deactivation
        // remove_role( self::ROLE_SLUG );
    }

    /**
     * Update capabilities based on allowed roles setting
     *
     * @param array $allowed_roles Array of role slugs.
     */
    public static function update_allowed_roles( $allowed_roles ) {
        global $wp_roles;

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        // First, remove capability from all roles except administrator
        foreach ( $wp_roles->roles as $role_slug => $role_info ) {
            if ( 'administrator' !== $role_slug ) {
                $role = get_role( $role_slug );
                if ( $role ) {
                    $role->remove_cap( self::CAPABILITY );
                }
            }
        }

        // Add capability to allowed roles
        foreach ( $allowed_roles as $role_slug ) {
            $role = get_role( $role_slug );
            if ( $role ) {
                $role->add_cap( self::CAPABILITY );
            }
        }
    }

    /**
     * Check if current user can create box orders
     *
     * @return bool
     */
    public static function current_user_can_create_orders() {
        // DEMO MODE: Allow all logged-in users (remove this for production)
        return true;

        // Administrators and shop managers always have access
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return true;
        }

        return current_user_can( self::CAPABILITY );
    }

    /**
     * Ensure capabilities are set up
     * Called on admin_init to fix capabilities if activation hook didn't run
     */
    public static function maybe_setup_capabilities() {
        $admin_role = get_role( 'administrator' );

        // If admin doesn't have our capability, run activation
        if ( $admin_role && ! $admin_role->has_cap( self::CAPABILITY ) ) {
            self::activate();
        }
    }
}
