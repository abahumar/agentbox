<?php
/**
 * Order meta box
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Meta_Box class
 */
class ABOX_Meta_Box {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 25, 2 );
    }

    /**
     * Add meta box to order edit screen
     *
     * @param string $post_type         Post type.
     * @param mixed  $post_or_order_obj Post or order object.
     */
    public function add_meta_boxes( $post_type, $post_or_order_obj ) {
        // Get the order object
        $order = $this->get_order_from_context( $post_type, $post_or_order_obj );

        if ( ! $order ) {
            return;
        }

        // Only show meta box if order has boxes data
        $is_box_order = $order->get_meta( '_abox_is_box_order' );

        if ( 'yes' !== $is_box_order ) {
            return;
        }

        // Get the correct screen ID for HPOS compatibility
        $screen_id = $this->get_order_screen_id();

        add_meta_box(
            'abox-boxes-breakdown',
            __( 'Box Order Breakdown', 'agent-box-orders' ),
            array( $this, 'render_meta_box' ),
            $screen_id,
            'normal',
            'high'
        );
    }

    /**
     * Get order screen ID (HPOS compatible)
     *
     * @return string
     */
    private function get_order_screen_id() {
        // Check if HPOS is enabled
        if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) ) {
            $controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class );

            if ( $controller && method_exists( $controller, 'custom_orders_table_usage_is_enabled' ) && $controller->custom_orders_table_usage_is_enabled() ) {
                return wc_get_page_screen_id( 'shop-order' );
            }
        }

        return 'shop_order';
    }

    /**
     * Get order object from context
     *
     * @param string $post_type         Post type.
     * @param mixed  $post_or_order_obj Post or order object.
     * @return WC_Order|null
     */
    private function get_order_from_context( $post_type, $post_or_order_obj ) {
        // Direct WC_Order object (HPOS)
        if ( $post_or_order_obj instanceof WC_Order ) {
            return $post_or_order_obj;
        }

        // WP_Post object (classic)
        if ( $post_or_order_obj instanceof WP_Post && 'shop_order' === $post_or_order_obj->post_type ) {
            return wc_get_order( $post_or_order_obj->ID );
        }

        // Fallback for global $theorder
        global $theorder;
        if ( $theorder instanceof WC_Order ) {
            return $theorder;
        }

        return null;
    }

    /**
     * Render the meta box content
     *
     * @param mixed $post_or_order_obj Post or order object.
     */
    public function render_meta_box( $post_or_order_obj ) {
        $order = $post_or_order_obj instanceof WC_Order
            ? $post_or_order_obj
            : wc_get_order( $post_or_order_obj->ID );

        if ( ! $order ) {
            echo '<p>' . esc_html__( 'Order not found.', 'agent-box-orders' ) . '</p>';
            return;
        }

        $boxes    = $order->get_meta( '_abox_boxes' );
        $agent_id = $order->get_meta( '_abox_agent_id' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            echo '<p>' . esc_html__( 'No box data available.', 'agent-box-orders' ) . '</p>';
            return;
        }

        // Show edit button if admin editing is enabled
        $show_edit_button = 'yes' === get_option( 'abox_enable_admin_editing', 'no' ) && current_user_can( 'manage_woocommerce' );

        if ( $show_edit_button ) {
            echo '<div class="abox-edit-button-container" style="margin-bottom: 12px;">';
            echo '<button type="button" class="button abox-edit-btn">';
            echo '<span class="dashicons dashicons-edit" style="vertical-align: middle;"></span> ';
            echo esc_html__( 'Edit Boxes', 'agent-box-orders' );
            echo '</button>';
            echo '</div>';
        }

        include ABOX_PLUGIN_DIR . 'admin/views/meta-box-boxes.php';
    }
}
