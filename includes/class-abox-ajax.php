<?php
/**
 * AJAX handlers
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Ajax class
 */
class ABOX_Ajax {

    /**
     * Constructor
     */
    public function __construct() {
        // Product search (logged-in users)
        add_action( 'wp_ajax_abox_search_products', array( $this, 'search_products' ) );
        // Product search (guest users - for demo/guest mode)
        add_action( 'wp_ajax_nopriv_abox_search_products', array( $this, 'search_products' ) );

        // Submit boxes (logged-in users)
        add_action( 'wp_ajax_abox_submit_boxes', array( $this, 'submit_boxes' ) );
        // Submit boxes (guest users - for demo/guest mode)
        add_action( 'wp_ajax_nopriv_abox_submit_boxes', array( $this, 'submit_boxes' ) );

        // Get product variations (logged-in users)
        add_action( 'wp_ajax_abox_get_variations', array( $this, 'get_product_variations' ) );
        // Get product variations (guest users - for demo/guest mode)
        add_action( 'wp_ajax_nopriv_abox_get_variations', array( $this, 'get_product_variations' ) );
    }

    /**
     * AJAX Product Search
     */
    public function search_products() {
        // Verify nonce
        if ( ! check_ajax_referer( 'abox_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'agent-box-orders' ) ) );
        }

        // Check capability (skip if guest mode enabled)
        $settings = ABOX_Settings::get_settings();
        if ( ! $settings['guest_mode'] && ! ABOX_Capabilities::current_user_can_create_orders() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $search_term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

        if ( strlen( $search_term ) < 2 ) {
            wp_send_json_success( array( 'products' => array() ) );
        }

        // Use WooCommerce data store for product search
        $data_store  = WC_Data_Store::load( 'product' );
        $product_ids = $data_store->search_products( $search_term, '', true, false, 20 );

        $products = array();

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );

            if ( ! $product || ! $product->is_purchasable() ) {
                continue;
            }

            // Skip variations - only show parent products and simple products
            $product_type = $product->get_type();
            if ( 'variation' === $product_type ) {
                continue;
            }

            // Skip out of stock products unless backorders allowed (for simple products)
            // Variable products are handled differently - we check variation stock
            if ( 'variable' !== $product_type && ! $product->is_in_stock() && ! $product->backorders_allowed() ) {
                continue;
            }

            // Get max quantity (for simple products)
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
     * AJAX Get Product Variations
     */
    public function get_product_variations() {
        // Verify nonce
        if ( ! check_ajax_referer( 'abox_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'agent-box-orders' ) ) );
        }

        // Check capability (skip if guest mode enabled)
        $settings = ABOX_Settings::get_settings();
        if ( ! $settings['guest_mode'] && ! ABOX_Capabilities::current_user_can_create_orders() ) {
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

        $variations = array();
        $available_variations = $product->get_available_variations();

        foreach ( $available_variations as $variation_data ) {
            $variation = wc_get_product( $variation_data['variation_id'] );

            if ( ! $variation || ! $variation->is_purchasable() ) {
                continue;
            }

            // Skip out of stock variations unless backorders allowed
            if ( ! $variation->is_in_stock() && ! $variation->backorders_allowed() ) {
                continue;
            }

            // Build attribute string (e.g., "Size: Large, Color: Red")
            $attribute_parts = array();
            $variation_attributes = $variation->get_variation_attributes();

            foreach ( $variation_attributes as $attr_key => $attr_value ) {
                // Get the attribute label
                $taxonomy = str_replace( 'attribute_', '', $attr_key );
                $attr_name = wc_attribute_label( $taxonomy, $product );

                // Get the term name if it's a taxonomy attribute
                if ( $attr_value ) {
                    if ( taxonomy_exists( $taxonomy ) ) {
                        $term = get_term_by( 'slug', $attr_value, $taxonomy );
                        if ( $term ) {
                            $attr_value = $term->name;
                        }
                    }
                    $attribute_parts[] = ucfirst( $attr_value );
                } else {
                    // "Any" attribute - get from variation_data if available
                    if ( isset( $variation_data['attributes'][ $attr_key ] ) && $variation_data['attributes'][ $attr_key ] ) {
                        $val = $variation_data['attributes'][ $attr_key ];
                        if ( taxonomy_exists( $taxonomy ) ) {
                            $term = get_term_by( 'slug', $val, $taxonomy );
                            if ( $term ) {
                                $val = $term->name;
                            }
                        }
                        $attribute_parts[] = ucfirst( $val );
                    }
                }
            }

            // If still no attributes, try to get from variation name
            if ( empty( $attribute_parts ) ) {
                $variation_name = $variation->get_name();
                $parent_name = $product->get_name();
                if ( $variation_name !== $parent_name ) {
                    $attribute_string = str_replace( $parent_name . ' - ', '', $variation_name );
                } else {
                    $attribute_string = sprintf( __( 'Variation #%d', 'agent-box-orders' ), $variation->get_id() );
                }
            } else {
                $attribute_string = implode( ', ', $attribute_parts );
            }

            // Get max quantity
            $max_qty = 999;
            if ( $variation->managing_stock() ) {
                $stock_qty = $variation->get_stock_quantity();
                if ( $stock_qty > 0 ) {
                    $max_qty = $stock_qty;
                }
            }

            $variations[] = array(
                'variation_id'     => $variation->get_id(),
                'attributes'       => $attribute_string,
                'sku'              => $variation->get_sku(),
                'price'            => (float) $variation->get_price(),
                'price_html'       => $variation->get_price_html(),
                'display_price'    => wc_price( $variation->get_price() ),
                'stock_status'     => $variation->get_stock_status(),
                'max_qty'          => $max_qty,
                'image'            => wp_get_attachment_image_url( $variation->get_image_id(), 'thumbnail' ),
            );
        }

        if ( empty( $variations ) ) {
            wp_send_json_error( array( 'message' => __( 'No available variations found.', 'agent-box-orders' ) ) );
        }

        wp_send_json_success( array( 'variations' => $variations ) );
    }

    /**
     * Submit boxes and build cart
     */
    public function submit_boxes() {
        // Verify nonce
        if ( ! check_ajax_referer( 'abox_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'agent-box-orders' ) ) );
        }

        // Check capability (skip if guest mode enabled)
        $settings = ABOX_Settings::get_settings();
        if ( ! $settings['guest_mode'] && ! ABOX_Capabilities::current_user_can_create_orders() ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        // Get and validate boxes data
        $boxes = isset( $_POST['boxes'] ) ? $_POST['boxes'] : array();

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            wp_send_json_error( array( 'message' => __( 'No boxes provided.', 'agent-box-orders' ) ) );
        }

        // Sanitize and validate boxes
        $sanitized_boxes = $this->sanitize_boxes( $boxes );

        if ( is_wp_error( $sanitized_boxes ) ) {
            wp_send_json_error( array( 'message' => $sanitized_boxes->get_error_message() ) );
        }

        // Get settings
        $settings = ABOX_Settings::get_settings();

        // Clear cart if setting enabled
        if ( $settings['clear_cart'] ) {
            WC()->cart->empty_cart();
        }

        // Aggregate products from all boxes
        $aggregated = $this->aggregate_products( $sanitized_boxes );

        // Add to cart
        foreach ( $aggregated as $key => $item_data ) {
            $product_id   = $item_data['product_id'];
            $variation_id = $item_data['variation_id'];
            $quantity     = $item_data['quantity'];

            // For variable products, we need to pass variation data
            if ( $variation_id ) {
                $variation   = wc_get_product( $variation_id );
                $attributes  = $variation ? $variation->get_variation_attributes() : array();
                $result      = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
            } else {
                $result = WC()->cart->add_to_cart( $product_id, $quantity );
            }

            if ( ! $result ) {
                $product      = wc_get_product( $variation_id ? $variation_id : $product_id );
                $product_name = $product ? $product->get_name() : "ID: {$product_id}";

                wp_send_json_error( array(
                    'message' => sprintf(
                        /* translators: %s: product name */
                        __( 'Could not add "%s" to cart. Please check stock availability.', 'agent-box-orders' ),
                        $product_name
                    ),
                ) );
            }
        }

        // Store boxes in session for later retrieval during checkout
        WC()->session->set( 'abox_boxes', $sanitized_boxes );
        WC()->session->set( 'abox_agent_id', get_current_user_id() );

        wp_send_json_success( array(
            'redirect' => wc_get_checkout_url(),
            'message'  => __( 'Cart built successfully. Redirecting to checkout...', 'agent-box-orders' ),
        ) );
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

                // Handle variable products
                $product_name    = $product->get_name();
                $price           = (float) $product->get_price();
                $variation_attrs = '';

                if ( 'variable' === $product->get_type() ) {
                    // Variable products require a variation_id
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

                    // Get variation attributes for display
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
                        $attr_parts[] = ucfirst( $attr_value );
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

    /**
     * Aggregate products across all boxes
     *
     * For variable products, we aggregate by variation_id.
     * For simple products, we aggregate by product_id.
     *
     * @param array $boxes Sanitized boxes.
     * @return array Aggregated products with quantities.
     */
    private function aggregate_products( $boxes ) {
        $aggregated = array();

        foreach ( $boxes as $box ) {
            foreach ( $box['items'] as $item ) {
                $product_id   = $item['product_id'];
                $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : 0;

                // Use variation_id as key if it exists, otherwise product_id
                $key = $variation_id ? "v_{$variation_id}" : "p_{$product_id}";

                if ( isset( $aggregated[ $key ] ) ) {
                    $aggregated[ $key ]['quantity'] += $item['quantity'];
                } else {
                    $aggregated[ $key ] = array(
                        'product_id'   => $product_id,
                        'variation_id' => $variation_id,
                        'quantity'     => $item['quantity'],
                    );
                }
            }
        }

        return $aggregated;
    }
}
