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
        add_action( 'woocommerce_admin_field_abox_repeater', array( $this, 'render_repeater_field' ) );
        add_action( 'woocommerce_admin_settings_sanitize_option_abox_payment_statuses', array( $this, 'sanitize_repeater' ), 10, 3 );
        add_action( 'woocommerce_admin_settings_sanitize_option_abox_collection_methods', array( $this, 'sanitize_repeater' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts' ) );
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
            array(
                'title' => __( 'Payment & Collection Settings', 'agent-box-orders' ),
                'type'  => 'title',
                'desc'  => __( 'Configure payment statuses and collection methods available for orders.', 'agent-box-orders' ),
                'id'    => 'abox_payment_collection_section',
            ),
            array(
                'title'   => __( 'Payment Statuses', 'agent-box-orders' ),
                'id'      => 'abox_payment_statuses',
                'type'    => 'abox_repeater',
                'default' => self::get_default_payment_statuses(),
            ),
            array(
                'title'   => __( 'Collection Methods', 'agent-box-orders' ),
                'id'      => 'abox_collection_methods',
                'type'    => 'abox_repeater',
                'default' => self::get_default_collection_methods(),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'abox_payment_collection_section',
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
     * Get default payment statuses
     *
     * @return array
     */
    public static function get_default_payment_statuses() {
        return array(
            array( 'slug' => 'done', 'label' => 'Done Payment', 'bg_color' => '#74d62f', 'text_color' => '#ffffff' ),
            array( 'slug' => 'cash_cashier', 'label' => 'Cash di Cashier', 'bg_color' => '#dd3333', 'text_color' => '#ffffff' ),
            array( 'slug' => 'cod', 'label' => 'Cash on Delivery (COD)', 'bg_color' => '#dd3333', 'text_color' => '#ffffff' ),
            array( 'slug' => 'pending_payment', 'label' => 'Pending Payment', 'bg_color' => '#dd3333', 'text_color' => '#ffffff' ),
            array( 'slug' => 'partial', 'label' => 'Partial Payment', 'bg_color' => '#eeee22', 'text_color' => '#555555' ),
        );
    }

    /**
     * Get default collection methods
     *
     * @return array
     */
    public static function get_default_collection_methods() {
        return array(
            array( 'slug' => 'postage', 'label' => 'Postage', 'bg_color' => '#f760ed', 'text_color' => '#ffffff' ),
            array( 'slug' => 'pickup_hq', 'label' => 'Pickup - HQ', 'bg_color' => '#eeee22', 'text_color' => '#555555' ),
            array( 'slug' => 'pickup_terengganu', 'label' => 'Pickup - Terengganu', 'bg_color' => '#1e73be', 'text_color' => '#ffffff' ),
            array( 'slug' => 'runner_delivered', 'label' => 'Runner Delivered', 'bg_color' => '#8224e3', 'text_color' => '#ffffff' ),
        );
    }

    /**
     * Get payment statuses from settings (with defaults fallback)
     *
     * @return array Array of ['slug' => '...', 'label' => '...', 'bg_color' => '...', 'text_color' => '...']
     */
    public static function get_payment_statuses() {
        $statuses = get_option( 'abox_payment_statuses' );
        if ( false === $statuses || ! is_array( $statuses ) ) {
            $statuses = self::get_default_payment_statuses();
            update_option( 'abox_payment_statuses', $statuses );
        }
        return $statuses;
    }

    /**
     * Get collection methods from settings (with defaults fallback)
     *
     * @return array Array of ['slug' => '...', 'label' => '...', 'bg_color' => '...', 'text_color' => '...']
     */
    public static function get_collection_methods() {
        $methods = get_option( 'abox_collection_methods' );
        if ( false === $methods || ! is_array( $methods ) ) {
            $methods = self::get_default_collection_methods();
            update_option( 'abox_collection_methods', $methods );
        }
        return $methods;
    }

    /**
     * Render repeater field for WooCommerce settings
     *
     * @param array $value Field config.
     */
    public function render_repeater_field( $value ) {
        $option_value = get_option( $value['id'], $value['default'] ?? array() );
        if ( ! is_array( $option_value ) ) {
            $option_value = $value['default'] ?? array();
        }
        $field_id = esc_attr( $value['id'] );
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php echo esc_html( $value['title'] ); ?></label>
            </th>
            <td class="forminp">
                <table class="abox-repeater-table widefat" data-field-id="<?php echo $field_id; ?>">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Slug', 'agent-box-orders' ); ?></th>
                            <th><?php esc_html_e( 'Label', 'agent-box-orders' ); ?></th>
                            <th><?php esc_html_e( 'Badge Color', 'agent-box-orders' ); ?></th>
                            <th><?php esc_html_e( 'Text Color', 'agent-box-orders' ); ?></th>
                            <th><?php esc_html_e( 'Preview', 'agent-box-orders' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $option_value as $i => $row ) : ?>
                            <tr class="abox-repeater-row">
                                <td><input type="text" name="<?php echo $field_id; ?>[<?php echo $i; ?>][slug]" value="<?php echo esc_attr( $row['slug'] ?? '' ); ?>" class="abox-repeater-slug" style="width:120px;" required></td>
                                <td><input type="text" name="<?php echo $field_id; ?>[<?php echo $i; ?>][label]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" class="abox-repeater-label" style="width:200px;" required></td>
                                <td><input type="color" name="<?php echo $field_id; ?>[<?php echo $i; ?>][bg_color]" value="<?php echo esc_attr( $row['bg_color'] ?? '#dd3333' ); ?>" class="abox-repeater-bg-color"></td>
                                <td><input type="color" name="<?php echo $field_id; ?>[<?php echo $i; ?>][text_color]" value="<?php echo esc_attr( $row['text_color'] ?? '#ffffff' ); ?>" class="abox-repeater-text-color"></td>
                                <td><mark class="order-status abox-repeater-preview" style="background-color:<?php echo esc_attr( $row['bg_color'] ?? '#dd3333' ); ?>;color:<?php echo esc_attr( $row['text_color'] ?? '#ffffff' ); ?>;"><span><?php echo esc_html( $row['label'] ?? '' ); ?></span></mark></td>
                                <td><button type="button" class="button abox-repeater-remove" title="<?php esc_attr_e( 'Remove', 'agent-box-orders' ); ?>"><span class="dashicons dashicons-trash"></span></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <button type="button" class="button abox-repeater-add" data-field-id="<?php echo $field_id; ?>">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php esc_html_e( 'Add Row', 'agent-box-orders' ); ?>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitize repeater field data
     *
     * @param mixed  $value     The value to sanitize.
     * @param array  $option    The option array.
     * @param mixed  $raw_value The raw value.
     * @return array
     */
    public function sanitize_repeater( $value, $option, $raw_value ) {
        if ( ! is_array( $raw_value ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $raw_value as $row ) {
            if ( empty( $row['slug'] ) || empty( $row['label'] ) ) {
                continue;
            }
            $sanitized[] = array(
                'slug'       => sanitize_key( $row['slug'] ),
                'label'      => sanitize_text_field( $row['label'] ),
                'bg_color'   => sanitize_hex_color( $row['bg_color'] ?? '#dd3333' ) ?: '#dd3333',
                'text_color' => sanitize_hex_color( $row['text_color'] ?? '#ffffff' ) ?: '#ffffff',
            );
        }
        return $sanitized;
    }

    /**
     * Enqueue scripts for settings page
     *
     * @param string $hook_suffix The page hook.
     */
    public function enqueue_settings_scripts( $hook_suffix ) {
        if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
            return;
        }
        if ( ! isset( $_GET['section'] ) || 'agent_box_orders' !== $_GET['section'] ) {
            return;
        }
        wp_enqueue_script(
            'abox-admin-settings',
            ABOX_PLUGIN_URL . 'assets/js/admin-settings.js',
            array( 'jquery' ),
            ABOX_VERSION,
            true
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
