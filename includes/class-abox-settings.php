<?php
/**
 * Settings management
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Settings class
 */
class ABOX_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_section' ) );
        add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_settings' ), 10, 2 );
        add_action( 'woocommerce_update_options_advanced', array( $this, 'save_settings' ) );
    }

    /**
     * Add settings section under WooCommerce > Settings > Advanced
     *
     * @param array $sections Existing sections.
     * @return array
     */
    public function add_section( $sections ) {
        $sections['agent_box_orders'] = __( 'Agent Box Orders', 'agent-box-orders' );
        return $sections;
    }

    /**
     * Add settings fields
     *
     * @param array  $settings        Existing settings.
     * @param string $current_section Current section ID.
     * @return array
     */
    public function add_settings( $settings, $current_section ) {
        if ( 'agent_box_orders' !== $current_section ) {
            return $settings;
        }

        $settings = array(
            array(
                'title' => __( 'Agent Box Orders Settings', 'agent-box-orders' ),
                'type'  => 'title',
                'desc'  => __( 'Configure settings for the agent box ordering system.', 'agent-box-orders' ),
                'id'    => 'abox_settings_section',
            ),
            array(
                'title'             => __( 'Maximum Boxes per Order', 'agent-box-orders' ),
                'desc'              => __( 'Maximum number of boxes an agent can create per order.', 'agent-box-orders' ),
                'id'                => 'abox_max_boxes',
                'type'              => 'number',
                'default'           => '10',
                'css'               => 'width: 80px;',
                'custom_attributes' => array(
                    'min'  => '1',
                    'max'  => '100',
                    'step' => '1',
                ),
            ),
            array(
                'title'             => __( 'Maximum Items per Box', 'agent-box-orders' ),
                'desc'              => __( 'Maximum number of product rows per box.', 'agent-box-orders' ),
                'id'                => 'abox_max_items_per_box',
                'type'              => 'number',
                'default'           => '20',
                'css'               => 'width: 80px;',
                'custom_attributes' => array(
                    'min'  => '1',
                    'max'  => '100',
                    'step' => '1',
                ),
            ),
            array(
                'title'   => __( 'Allowed Roles', 'agent-box-orders' ),
                'desc'    => __( 'Select which user roles can create box orders. Administrator always has access.', 'agent-box-orders' ),
                'id'      => 'abox_allowed_roles',
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'css'     => 'min-width: 350px;',
                'default' => array( 'sales_agent', 'shop_manager' ),
                'options' => self::get_available_roles(),
            ),
            array(
                'title'   => __( 'Clear Cart on Submit', 'agent-box-orders' ),
                'desc'    => __( 'Clear existing cart items before adding box order items.', 'agent-box-orders' ),
                'id'      => 'abox_clear_cart',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title'   => __( 'Guest Mode', 'agent-box-orders' ),
                'desc'    => __( 'Allow non-logged-in users to use the order form (for demo purposes).', 'agent-box-orders' ),
                'id'      => 'abox_guest_mode',
                'type'    => 'checkbox',
                'default' => 'no',
            ),
            array(
                'title'   => __( 'Admin Box Editing', 'agent-box-orders' ),
                'desc'    => __( 'Allow administrators to edit box orders after submission.', 'agent-box-orders' ),
                'id'      => 'abox_enable_admin_editing',
                'type'    => 'checkbox',
                'default' => 'no',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'abox_settings_section',
            ),
            array(
                'title' => __( 'Packing List Settings', 'agent-box-orders' ),
                'type'  => 'title',
                'desc'  => __( 'Configure the packing list print template.', 'agent-box-orders' ),
                'id'    => 'abox_packing_list_section',
            ),
            array(
                'title'   => __( 'Packing List Template', 'agent-box-orders' ),
                'desc'    => __( 'Select the template style for printing packing lists.', 'agent-box-orders' ),
                'id'      => 'abox_packing_list_template',
                'type'    => 'select',
                'default' => 'default',
                'options' => array(
                    'default' => __( 'Default (Single Column)', 'agent-box-orders' ),
                    'compact' => __( 'Compact (Two-Column)', 'agent-box-orders' ),
                ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'abox_packing_list_section',
            ),
        );

        return $settings;
    }

    /**
     * Get available WordPress roles
     *
     * @return array
     */
    public static function get_available_roles() {
        global $wp_roles;

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        $roles = array();
        foreach ( $wp_roles->roles as $slug => $role ) {
            // Exclude administrator as they always have access
            if ( 'administrator' !== $slug ) {
                $roles[ $slug ] = $role['name'];
            }
        }

        return $roles;
    }

    /**
     * Get plugin settings with defaults
     *
     * @return array
     */
    public static function get_settings() {
        return array(
            'max_boxes'              => absint( get_option( 'abox_max_boxes', 10 ) ),
            'max_items_per_box'      => absint( get_option( 'abox_max_items_per_box', 20 ) ),
            'allowed_roles'          => get_option( 'abox_allowed_roles', array( 'sales_agent', 'shop_manager' ) ),
            'clear_cart'             => 'yes' === get_option( 'abox_clear_cart', 'yes' ),
            'guest_mode'             => 'yes' === get_option( 'abox_guest_mode', 'no' ),
            'enable_admin_editing'   => 'yes' === get_option( 'abox_enable_admin_editing', 'no' ),
            'packing_list_template'  => get_option( 'abox_packing_list_template', 'default' ),
        );
    }

    /**
     * Update role capabilities when settings saved
     */
    public function save_settings() {
        // Check if we're saving our section
        if ( isset( $_POST['abox_allowed_roles'] ) ) {
            $allowed_roles = array_map( 'sanitize_text_field', (array) $_POST['abox_allowed_roles'] );
            ABOX_Capabilities::update_allowed_roles( $allowed_roles );
        } elseif ( isset( $_POST['save'] ) && isset( $_GET['section'] ) && 'agent_box_orders' === $_GET['section'] ) {
            // If no roles selected, clear all except admin
            ABOX_Capabilities::update_allowed_roles( array() );
        }
    }
}
