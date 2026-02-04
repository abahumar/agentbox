<?php
/**
 * Public-facing functionality
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Public class
 */
class ABOX_Public {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages with our shortcode
        global $post;

        if ( ! $post || ! has_shortcode( $post->post_content, 'abox_order_form' ) ) {
            return;
        }

        // Always enqueue styles (needed for login form styling too)
        // Include dashicons as dependency for icon display on frontend
        wp_enqueue_style(
            'abox-public',
            ABOX_PLUGIN_URL . 'assets/css/public.css',
            array( 'dashicons' ),
            ABOX_VERSION
        );

        // Get settings
        $settings = ABOX_Settings::get_settings();

        // Don't load JS if user can't create orders (skip check if guest mode enabled)
        if ( ! $settings['guest_mode'] ) {
            if ( ! is_user_logged_in() || ! ABOX_Capabilities::current_user_can_create_orders() ) {
                return;
            }
        }

        // Enqueue scripts
        wp_enqueue_script(
            'abox-public',
            ABOX_PLUGIN_URL . 'assets/js/public.js',
            array( 'jquery' ),
            ABOX_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'abox-public', 'abox_vars', array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'abox_nonce' ),
            'max_boxes'  => $settings['max_boxes'],
            'max_items'  => $settings['max_items_per_box'],
            'currency'   => array(
                'symbol'    => get_woocommerce_currency_symbol(),
                'position'  => get_option( 'woocommerce_currency_pos', 'left' ),
                'decimals'  => wc_get_price_decimals(),
                'decimal'   => wc_get_price_decimal_separator(),
                'thousand'  => wc_get_price_thousand_separator(),
            ),
            'i18n'       => array(
                'box_label'             => __( 'Box', 'agent-box-orders' ),
                'add_box'               => __( 'Add Box', 'agent-box-orders' ),
                'remove_box'            => __( 'Remove Box', 'agent-box-orders' ),
                'add_item'              => __( 'Add Item', 'agent-box-orders' ),
                'remove_item'           => __( 'Remove', 'agent-box-orders' ),
                'search_placeholder'    => __( 'Search products...', 'agent-box-orders' ),
                'customer_label'        => __( 'Customer Label', 'agent-box-orders' ),
                'product'               => __( 'Product', 'agent-box-orders' ),
                'quantity'              => __( 'Qty', 'agent-box-orders' ),
                'price'                 => __( 'Price', 'agent-box-orders' ),
                'subtotal'              => __( 'Subtotal', 'agent-box-orders' ),
                'submit'                => __( 'Proceed to Checkout', 'agent-box-orders' ),
                'submitting'            => __( 'Building cart...', 'agent-box-orders' ),
                'searching'             => __( 'Searching...', 'agent-box-orders' ),
                'no_results'            => __( 'No products found', 'agent-box-orders' ),
                'error_no_boxes'        => __( 'Please add at least one box.', 'agent-box-orders' ),
                'error_empty_label'     => __( 'Please enter a label for all boxes.', 'agent-box-orders' ),
                'error_no_items'        => __( 'Each box must have at least one item with a product selected.', 'agent-box-orders' ),
                'confirm_remove_box'    => __( 'Are you sure you want to remove this box?', 'agent-box-orders' ),
                'max_boxes_reached'     => __( 'Maximum number of boxes reached.', 'agent-box-orders' ),
                'max_items_reached'     => __( 'Maximum number of items per box reached.', 'agent-box-orders' ),
                'error_occurred'        => __( 'An error occurred. Please try again.', 'agent-box-orders' ),
                'variable_product'      => __( 'Variable', 'agent-box-orders' ),
                'select_variation'      => __( 'Select variation...', 'agent-box-orders' ),
                'no_variations'         => __( 'No variations available', 'agent-box-orders' ),
                'error_loading'         => __( 'Error loading variations', 'agent-box-orders' ),
                'error_select_variation' => __( 'Please select a variation for all variable products.', 'agent-box-orders' ),
            ),
        ) );
    }
}
