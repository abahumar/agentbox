<?php
/**
 * Admin functionality
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Admin class
 */
class ABOX_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_column' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_column' ), 10, 2 );

        // HPOS support for order columns
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_column' ) );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_column_hpos' ), 10, 2 );

        // Print view AJAX handler
        add_action( 'wp_ajax_abox_print_boxes', array( $this, 'print_boxes_view' ) );

        // Collecting list AJAX handler
        add_action( 'wp_ajax_abox_collecting_list', array( $this, 'collecting_list_view' ) );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        // Load on order edit screens
        if ( ! in_array( $screen_id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
            return;
        }

        wp_enqueue_style(
            'abox-admin',
            ABOX_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ABOX_VERSION
        );
    }

    /**
     * Add custom column to orders list
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_order_column( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            // Add after order status column
            if ( 'order_status' === $key ) {
                $new_columns['abox_boxes'] = __( 'Boxes', 'agent-box-orders' );
            }
        }

        return $new_columns;
    }

    /**
     * Render custom column content (classic orders)
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_order_column( $column, $post_id ) {
        if ( 'abox_boxes' !== $column ) {
            return;
        }

        $order = wc_get_order( $post_id );
        $this->output_boxes_column( $order );
    }

    /**
     * Render custom column content (HPOS)
     *
     * @param string   $column Column name.
     * @param WC_Order $order  Order object.
     */
    public function render_order_column_hpos( $column, $order ) {
        if ( 'abox_boxes' !== $column ) {
            return;
        }

        $this->output_boxes_column( $order );
    }

    /**
     * Output boxes column content
     *
     * @param WC_Order|null $order Order object.
     */
    private function output_boxes_column( $order ) {
        if ( ! $order ) {
            echo '—';
            return;
        }

        $is_box_order = $order->get_meta( '_abox_is_box_order' );

        if ( 'yes' !== $is_box_order ) {
            echo '—';
            return;
        }

        $boxes = $order->get_meta( '_abox_boxes' );
        $count = is_array( $boxes ) ? count( $boxes ) : 0;

        if ( $count > 0 ) {
            printf(
                '<span class="abox-boxes-badge" title="%s">%d</span>',
                esc_attr__( 'Box order', 'agent-box-orders' ),
                $count
            );
        } else {
            echo '—';
        }
    }

    /**
     * Render print-friendly boxes view for warehouse
     */
    public function print_boxes_view() {
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'agent-box-orders' ) );
        }

        // Verify nonce
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'abox_print_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'agent-box-orders' ) );
        }

        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

        if ( ! $order_id ) {
            wp_die( esc_html__( 'Invalid order ID.', 'agent-box-orders' ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'agent-box-orders' ) );
        }

        $boxes    = $order->get_meta( '_abox_boxes' );
        $agent_id = $order->get_meta( '_abox_agent_id' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            wp_die( esc_html__( 'No box data available for this order.', 'agent-box-orders' ) );
        }

        // Get template setting
        $template = get_option( 'abox_packing_list_template', 'default' );

        // Include appropriate print template
        if ( 'compact' === $template ) {
            include ABOX_PLUGIN_DIR . 'admin/views/print-boxes-compact.php';
        } else {
            include ABOX_PLUGIN_DIR . 'admin/views/print-boxes.php';
        }
        exit;
    }

    /**
     * Get print URL for an order
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public static function get_print_url( $order_id ) {
        return add_query_arg(
            array(
                'action'   => 'abox_print_boxes',
                'order_id' => $order_id,
                'nonce'    => wp_create_nonce( 'abox_print_nonce' ),
            ),
            admin_url( 'admin-ajax.php' )
        );
    }

    /**
     * Get collecting list URL for an order
     *
     * @param int $order_id Order ID.
     * @return string
     */
    public static function get_collecting_list_url( $order_id ) {
        return add_query_arg(
            array(
                'action'   => 'abox_collecting_list',
                'order_id' => $order_id,
                'nonce'    => wp_create_nonce( 'abox_collecting_nonce' ),
            ),
            admin_url( 'admin-ajax.php' )
        );
    }

    /**
     * Get the human-friendly label for a collection method value.
     * Includes support for legacy values to keep older orders readable.
     *
     * @param string $method Stored collection method key.
     * @return string
     */
    public static function get_collection_method_label( $method ) {
        if ( empty( $method ) ) {
            return '';
        }

        $map = array(
            'postage'            => __( 'Postage', 'agent-box-orders' ),
            'pickup_hq'          => __( 'Pickup - HQ', 'agent-box-orders' ),
            'pickup_terengganu'  => __( 'Pickup - Terengganu', 'agent-box-orders' ),
            'runner_delivered'   => __( 'Runner Delivered', 'agent-box-orders' ),
            // Legacy values kept for backward compatibility.
            'pickup'             => __( 'Pickup', 'agent-box-orders' ),
            'runner'             => __( 'Runner Delivered', 'agent-box-orders' ),
        );

        if ( isset( $map[ $method ] ) ) {
            return $map[ $method ];
        }

        return ucwords( str_replace( array( '_', '-' ), ' ', $method ) );
    }

    /**
     * Render collecting list view (consolidated items from all boxes)
     */
    public function collecting_list_view() {
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'agent-box-orders' ) );
        }

        // Verify nonce
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'abox_collecting_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'agent-box-orders' ) );
        }

        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

        if ( ! $order_id ) {
            wp_die( esc_html__( 'Invalid order ID.', 'agent-box-orders' ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'agent-box-orders' ) );
        }

        $boxes    = $order->get_meta( '_abox_boxes' );
        $agent_id = $order->get_meta( '_abox_agent_id' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            wp_die( esc_html__( 'No box data available for this order.', 'agent-box-orders' ) );
        }

        // Consolidate all items from all boxes
        $consolidated_items = array();

        foreach ( $boxes as $box ) {
            foreach ( $box['items'] as $item ) {
                // Create unique key based on product_id and variation_id
                $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : 0;
                $key = $item['product_id'] . '_' . $variation_id;

                if ( isset( $consolidated_items[ $key ] ) ) {
                    // Add quantity to existing item
                    $consolidated_items[ $key ]['quantity'] += $item['quantity'];
                } else {
                    // Add new item
                    $consolidated_items[ $key ] = $item;
                }
            }
        }

        // Sort by product name
        usort( $consolidated_items, function( $a, $b ) {
            return strcmp( $a['product_name'], $b['product_name'] );
        });

        include ABOX_PLUGIN_DIR . 'admin/views/print-collecting-list.php';
        exit;
    }
}
