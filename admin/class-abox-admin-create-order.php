<?php
/**
 * Admin Create Order Page
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Admin_Create_Order class
 */
class ABOX_Admin_Create_Order {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // AJAX handlers
        add_action( 'wp_ajax_abox_admin_search_customers', array( $this, 'search_customers' ) );
        add_action( 'wp_ajax_abox_admin_create_order', array( $this, 'create_order' ) );
        add_action( 'wp_ajax_abox_admin_search_products', array( $this, 'search_products' ) );
        add_action( 'wp_ajax_abox_admin_get_variations', array( $this, 'get_variations' ) );
    }

    /**
     * Add submenu page under WooCommerce
     */
    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Create Box Order', 'agent-box-orders' ),
            __( 'Create Box Order', 'agent-box-orders' ),
            'manage_woocommerce',
            'abox-create-order',
            array( $this, 'render_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        if ( 'woocommerce_page_abox-create-order' !== $hook ) {
            return;
        }

        // Enqueue WooCommerce admin styles and Select2
        wp_enqueue_style( 'woocommerce_admin_styles' );
        wp_enqueue_script( 'selectWoo' );

        wp_enqueue_style(
            'abox-admin-create-order',
            ABOX_PLUGIN_URL . 'assets/css/admin-create-order.css',
            array(),
            ABOX_VERSION
        );

        wp_enqueue_script(
            'abox-admin-create-order',
            ABOX_PLUGIN_URL . 'assets/js/admin-create-order.js',
            array( 'jquery', 'selectWoo', 'wp-util' ),
            ABOX_VERSION,
            true
        );

        $settings = ABOX_Settings::get_settings();

        wp_localize_script(
            'abox-admin-create-order',
            'abox_admin_vars',
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'abox_admin_nonce' ),
                'max_boxes'  => $settings['max_boxes'],
                'max_items'  => $settings['max_items_per_box'],
                'currency'   => array(
                    'symbol'   => get_woocommerce_currency_symbol(),
                    'decimal'  => wc_get_price_decimal_separator(),
                    'thousand' => wc_get_price_thousand_separator(),
                    'decimals' => wc_get_price_decimals(),
                    'position' => get_option( 'woocommerce_currency_pos' ),
                ),
                'i18n'       => array(
                    'confirm_remove_box'   => __( 'Are you sure you want to remove this box?', 'agent-box-orders' ),
                    'max_boxes_reached'    => sprintf( __( 'Maximum %d boxes allowed.', 'agent-box-orders' ), $settings['max_boxes'] ),
                    'max_items_reached'    => sprintf( __( 'Maximum %d items per box allowed.', 'agent-box-orders' ), $settings['max_items_per_box'] ),
                    'searching'            => __( 'Searching...', 'agent-box-orders' ),
                    'no_results'           => __( 'No products found.', 'agent-box-orders' ),
                    'error_occurred'       => __( 'An error occurred. Please try again.', 'agent-box-orders' ),
                    'error_no_boxes'       => __( 'Please add at least one box.', 'agent-box-orders' ),
                    'error_empty_label'    => __( 'Please enter a customer label for all boxes.', 'agent-box-orders' ),
                    'error_no_items'       => __( 'Please add at least one item to each box.', 'agent-box-orders' ),
                    'error_select_variation' => __( 'Please select a variation for all variable products.', 'agent-box-orders' ),
                    'select_variation'     => __( 'Select variation...', 'agent-box-orders' ),
                    'no_variations'        => __( 'No variations available', 'agent-box-orders' ),
                    'error_loading'        => __( 'Error loading variations', 'agent-box-orders' ),
                    'variable_product'     => __( 'Variable', 'agent-box-orders' ),
                    'creating_order'       => __( 'Creating Order...', 'agent-box-orders' ),
                    'search_customers'     => __( 'Search customers...', 'agent-box-orders' ),
                    'guest_customer'       => __( 'Guest (enter details manually)', 'agent-box-orders' ),
                ),
            )
        );
    }

    /**
     * Render the admin page
     */
    public function render_page() {
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'agent-box-orders' ) );
        }

        $settings = ABOX_Settings::get_settings();

        include ABOX_PLUGIN_DIR . 'admin/views/create-order.php';
    }

    /**
     * AJAX: Search customers
     */
    public function search_customers() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

        if ( strlen( $term ) < 2 ) {
            wp_send_json_success( array( 'customers' => array() ) );
        }

        $customers = get_users(
            array(
                'search'         => '*' . $term . '*',
                'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
                'number'         => 20,
                'orderby'        => 'display_name',
                'order'          => 'ASC',
            )
        );

        $results = array();

        foreach ( $customers as $customer ) {
            $results[] = array(
                'id'    => $customer->ID,
                'text'  => sprintf(
                    '%s (%s)',
                    $customer->display_name,
                    $customer->user_email
                ),
                'email' => $customer->user_email,
                'name'  => $customer->display_name,
            );
        }

        wp_send_json_success( array( 'customers' => $results ) );
    }

    /**
     * AJAX: Search products (same as frontend but with admin nonce)
     */
    public function search_products() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $search_term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

        if ( strlen( $search_term ) < 2 ) {
            wp_send_json_success( array( 'products' => array() ) );
        }

        $data_store  = WC_Data_Store::load( 'product' );
        $product_ids = $data_store->search_products( $search_term, '', true, false, 20 );

        $products = array();

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );

            if ( ! $product || ! $product->is_purchasable() ) {
                continue;
            }

            $product_type = $product->get_type();
            if ( 'variation' === $product_type ) {
                continue;
            }

            if ( 'variable' !== $product_type && ! $product->is_in_stock() && ! $product->backorders_allowed() ) {
                continue;
            }

            $max_qty = 999;
            if ( 'variable' !== $product_type && $product->managing_stock() ) {
                $stock_qty = $product->get_stock_quantity();
                if ( $stock_qty > 0 ) {
                    $max_qty = $stock_qty;
                }
            }

            $products[] = array(
                'id'           => $product->get_id(),
                'name'         => $product->get_name(),
                'sku'          => $product->get_sku(),
                'price'        => (float) $product->get_price(),
                'price_html'   => $product->get_price_html(),
                'image'        => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'stock_status' => $product->get_stock_status(),
                'max_qty'      => $max_qty,
                'type'         => $product_type,
            );
        }

        wp_send_json_success( array( 'products' => $products ) );
    }

    /**
     * AJAX: Get product variations
     */
    public function get_variations() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'agent-box-orders' ) ) );
        }

        $product = wc_get_product( $product_id );

        if ( ! $product || 'variable' !== $product->get_type() ) {
            wp_send_json_error( array( 'message' => __( 'Product is not a variable product.', 'agent-box-orders' ) ) );
        }

        $variations           = array();
        $available_variations = $product->get_available_variations();

        foreach ( $available_variations as $variation_data ) {
            $variation = wc_get_product( $variation_data['variation_id'] );

            if ( ! $variation || ! $variation->is_purchasable() ) {
                continue;
            }

            if ( ! $variation->is_in_stock() && ! $variation->backorders_allowed() ) {
                continue;
            }

            $attribute_parts       = array();
            $variation_attributes  = $variation->get_variation_attributes();

            foreach ( $variation_attributes as $attr_key => $attr_value ) {
                $taxonomy  = str_replace( 'attribute_', '', $attr_key );
                $attr_name = wc_attribute_label( $taxonomy, $product );

                if ( $attr_value ) {
                    if ( taxonomy_exists( $taxonomy ) ) {
                        $term = get_term_by( 'slug', $attr_value, $taxonomy );
                        if ( $term ) {
                            $attr_value = $term->name;
                        }
                    }
                    $attribute_parts[] = $attr_name . ': ' . ucfirst( $attr_value );
                }
            }

            if ( empty( $attribute_parts ) ) {
                $variation_name = $variation->get_name();
                $parent_name    = $product->get_name();
                if ( $variation_name !== $parent_name ) {
                    $attribute_string = str_replace( $parent_name . ' - ', '', $variation_name );
                } else {
                    $attribute_string = sprintf( __( 'Variation #%d', 'agent-box-orders' ), $variation->get_id() );
                }
            } else {
                $attribute_string = implode( ', ', $attribute_parts );
            }

            $max_qty = 999;
            if ( $variation->managing_stock() ) {
                $stock_qty = $variation->get_stock_quantity();
                if ( $stock_qty > 0 ) {
                    $max_qty = $stock_qty;
                }
            }

            $variations[] = array(
                'variation_id'  => $variation->get_id(),
                'attributes'    => $attribute_string,
                'sku'           => $variation->get_sku(),
                'price'         => (float) $variation->get_price(),
                'price_html'    => $variation->get_price_html(),
                'display_price' => wc_price( $variation->get_price() ),
                'stock_status'  => $variation->get_stock_status(),
                'max_qty'       => $max_qty,
                'image'         => wp_get_attachment_image_url( $variation->get_image_id(), 'thumbnail' ),
            );
        }

        if ( empty( $variations ) ) {
            wp_send_json_error( array( 'message' => __( 'No available variations found.', 'agent-box-orders' ) ) );
        }

        wp_send_json_success( array( 'variations' => $variations ) );
    }

    /**
     * AJAX: Create order directly
     */
    public function create_order() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        // Get form data
        $boxes        = isset( $_POST['boxes'] ) ? $_POST['boxes'] : array();
        $customer_id  = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
        $order_status = isset( $_POST['order_status'] ) ? sanitize_text_field( wp_unslash( $_POST['order_status'] ) ) : 'pending';
        $billing      = isset( $_POST['billing'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['billing'] ) ) : array();

        // Validate boxes
        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            wp_send_json_error( array( 'message' => __( 'No boxes provided.', 'agent-box-orders' ) ) );
        }

        // Sanitize boxes
        $sanitized_boxes = $this->sanitize_boxes( $boxes );

        if ( is_wp_error( $sanitized_boxes ) ) {
            wp_send_json_error( array( 'message' => $sanitized_boxes->get_error_message() ) );
        }

        // Create order
        try {
            $order = wc_create_order( array(
                'customer_id' => $customer_id,
                'status'      => $order_status,
            ) );

            if ( is_wp_error( $order ) ) {
                wp_send_json_error( array( 'message' => $order->get_error_message() ) );
            }

            // Set billing address
            if ( $customer_id ) {
                // Get customer data
                $customer = new WC_Customer( $customer_id );
                $order->set_billing_first_name( $customer->get_billing_first_name() ?: $customer->get_first_name() );
                $order->set_billing_last_name( $customer->get_billing_last_name() ?: $customer->get_last_name() );
                $order->set_billing_email( $customer->get_billing_email() ?: $customer->get_email() );
                $order->set_billing_phone( $customer->get_billing_phone() );
                $order->set_billing_address_1( $customer->get_billing_address_1() );
                $order->set_billing_address_2( $customer->get_billing_address_2() );
                $order->set_billing_city( $customer->get_billing_city() );
                $order->set_billing_state( $customer->get_billing_state() );
                $order->set_billing_postcode( $customer->get_billing_postcode() );
                $order->set_billing_country( $customer->get_billing_country() );
            } else {
                // Guest order - use provided billing details
                $order->set_billing_first_name( isset( $billing['first_name'] ) ? $billing['first_name'] : '' );
                $order->set_billing_last_name( isset( $billing['last_name'] ) ? $billing['last_name'] : '' );
                $order->set_billing_email( isset( $billing['email'] ) ? sanitize_email( $billing['email'] ) : '' );
                $order->set_billing_phone( isset( $billing['phone'] ) ? $billing['phone'] : '' );
                $order->set_billing_address_1( isset( $billing['address_1'] ) ? $billing['address_1'] : '' );
                $order->set_billing_address_2( isset( $billing['address_2'] ) ? $billing['address_2'] : '' );
                $order->set_billing_city( isset( $billing['city'] ) ? $billing['city'] : '' );
                $order->set_billing_state( isset( $billing['state'] ) ? $billing['state'] : '' );
                $order->set_billing_postcode( isset( $billing['postcode'] ) ? $billing['postcode'] : '' );
                $order->set_billing_country( isset( $billing['country'] ) ? $billing['country'] : '' );
            }

            // Add products to order
            foreach ( $sanitized_boxes as $box ) {
                foreach ( $box['items'] as $item ) {
                    $product_id   = $item['product_id'];
                    $variation_id = $item['variation_id'];
                    $quantity     = $item['quantity'];

                    if ( $variation_id ) {
                        $product = wc_get_product( $variation_id );
                    } else {
                        $product = wc_get_product( $product_id );
                    }

                    if ( ! $product ) {
                        continue;
                    }

                    $order->add_product( $product, $quantity );
                }
            }

            // Calculate totals
            $order->calculate_totals();

            // Mark as box order
            $order->update_meta_data( '_abox_is_box_order', 'yes' );
            $order->update_meta_data( '_abox_boxes', $sanitized_boxes );
            $order->update_meta_data( '_abox_agent_id', get_current_user_id() );
            $order->update_meta_data( '_abox_created_from_admin', 'yes' );

            // Add order note
            $order->add_order_note(
                sprintf(
                    /* translators: %s: user display name */
                    __( 'Box order created from admin by %s.', 'agent-box-orders' ),
                    wp_get_current_user()->display_name
                ),
                false,
                true
            );

            $order->save();

            wp_send_json_success( array(
                'message'   => __( 'Order created successfully.', 'agent-box-orders' ),
                'order_id'  => $order->get_id(),
                'edit_url'  => $order->get_edit_order_url(),
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Sanitize and validate boxes data
     *
     * @param array $boxes Raw boxes data.
     * @return array|WP_Error Sanitized boxes or error.
     */
    private function sanitize_boxes( $boxes ) {
        $settings  = ABOX_Settings::get_settings();
        $max_boxes = $settings['max_boxes'];
        $max_items = $settings['max_items_per_box'];

        if ( count( $boxes ) > $max_boxes ) {
            return new WP_Error(
                'too_many_boxes',
                sprintf(
                    /* translators: %d: maximum boxes allowed */
                    __( 'Maximum %d boxes allowed.', 'agent-box-orders' ),
                    $max_boxes
                )
            );
        }

        $sanitized = array();

        foreach ( $boxes as $index => $box ) {
            $label = isset( $box['label'] ) ? sanitize_text_field( wp_unslash( $box['label'] ) ) : '';

            $items = isset( $box['items'] ) ? $box['items'] : array();

            if ( empty( $items ) || ! is_array( $items ) ) {
                return new WP_Error(
                    'empty_box',
                    sprintf(
                        /* translators: %s: customer label */
                        __( 'Box "%s" has no items.', 'agent-box-orders' ),
                        $label
                    )
                );
            }

            if ( count( $items ) > $max_items ) {
                return new WP_Error(
                    'too_many_items',
                    sprintf(
                        /* translators: 1: customer label, 2: max items */
                        __( 'Box "%1$s" has too many items (maximum %2$d).', 'agent-box-orders' ),
                        $label,
                        $max_items
                    )
                );
            }

            $sanitized_items = array();

            foreach ( $items as $item ) {
                $product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
                $variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
                $quantity     = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;

                if ( ! $product_id || $quantity < 1 ) {
                    continue;
                }

                $product = wc_get_product( $product_id );

                if ( ! $product || ! $product->is_purchasable() ) {
                    return new WP_Error(
                        'invalid_product',
                        sprintf(
                            /* translators: %d: product ID */
                            __( 'Product ID %d is not available for purchase.', 'agent-box-orders' ),
                            $product_id
                        )
                    );
                }

                $product_name    = $product->get_name();
                $price           = (float) $product->get_price();
                $variation_attrs = '';

                if ( 'variable' === $product->get_type() ) {
                    if ( ! $variation_id ) {
                        return new WP_Error(
                            'missing_variation',
                            sprintf(
                                /* translators: %s: product name */
                                __( 'Please select a variation for "%s".', 'agent-box-orders' ),
                                $product_name
                            )
                        );
                    }

                    $variation = wc_get_product( $variation_id );

                    if ( ! $variation || ! $variation->is_purchasable() ) {
                        return new WP_Error(
                            'invalid_variation',
                            sprintf(
                                /* translators: %d: variation ID */
                                __( 'Variation ID %d is not available for purchase.', 'agent-box-orders' ),
                                $variation_id
                            )
                        );
                    }

                    $attributes = $variation->get_variation_attributes();
                    $attr_parts = array();
                    foreach ( $attributes as $attr_key => $attr_value ) {
                        $attr_name = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ), $product );
                        $taxonomy  = str_replace( 'attribute_', '', $attr_key );
                        if ( taxonomy_exists( $taxonomy ) ) {
                            $term = get_term_by( 'slug', $attr_value, $taxonomy );
                            if ( $term ) {
                                $attr_value = $term->name;
                            }
                        }
                        $attr_parts[] = $attr_name . ': ' . $attr_value;
                    }
                    $variation_attrs = implode( ', ', $attr_parts );
                    $price           = (float) $variation->get_price();
                }

                $sanitized_items[] = array(
                    'product_id'      => $product_id,
                    'variation_id'    => $variation_id,
                    'product_name'    => $product_name,
                    'variation_attrs' => $variation_attrs,
                    'quantity'        => $quantity,
                    'price'           => $price,
                );
            }

            if ( empty( $sanitized_items ) ) {
                return new WP_Error(
                    'empty_box',
                    sprintf(
                        /* translators: %s: customer label */
                        __( 'Box "%s" has no valid items.', 'agent-box-orders' ),
                        $label
                    )
                );
            }

            $sanitized[] = array(
                'label' => $label,
                'items' => $sanitized_items,
            );
        }

        if ( empty( $sanitized ) ) {
            return new WP_Error( 'no_valid_boxes', __( 'No valid boxes found.', 'agent-box-orders' ) );
        }

        return $sanitized;
    }
}
