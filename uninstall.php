<?php
/**
 * Uninstall script
 *
 * This file is executed when the plugin is deleted from WordPress.
 *
 * @package Agent_Box_Orders
 */

// Exit if not called by WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Option to preserve data on uninstall
$preserve_data = get_option( 'abox_preserve_data_on_uninstall', false );

if ( ! $preserve_data ) {
    // Delete plugin options
    delete_option( 'abox_max_boxes' );
    delete_option( 'abox_max_items_per_box' );
    delete_option( 'abox_allowed_roles' );
    delete_option( 'abox_clear_cart' );
    delete_option( 'abox_preserve_data_on_uninstall' );

    // Remove custom capability from all roles
    global $wp_roles;

    if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
    }

    foreach ( $wp_roles->roles as $role_slug => $role_info ) {
        $role = get_role( $role_slug );
        if ( $role ) {
            $role->remove_cap( 'abox_create_orders' );
        }
    }

    // Remove sales_agent role
    remove_role( 'sales_agent' );

    // Note: Order meta (_abox_boxes, _abox_agent_id, _abox_is_box_order) is preserved
    // as it contains important order history data.
    // To completely remove all data, you would need to:
    // 1. Delete all order meta with keys starting with '_abox_'
    // 2. This is intentionally not done to preserve order history
}
