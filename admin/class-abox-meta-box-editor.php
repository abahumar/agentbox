<?php
/**
 * Meta box editor for box orders
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Meta_Box_Editor class
 */
class ABOX_Meta_Box_Editor {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // AJAX handlers
        add_action( 'wp_ajax_abox_admin_get_edit_template', array( $this, 'ajax_get_edit_template' ) );
        add_action( 'wp_ajax_abox_admin_save_boxes', array( $this, 'ajax_save_boxes' ) );
        add_action( 'wp_ajax_abox_admin_search_products', array( $this, 'ajax_search_products' ) );
        add_action( 'wp_ajax_abox_admin_get_variations', array( $this, 'ajax_get_variations' ) );
        add_action( 'wp_ajax_abox_admin_get_edit_history', array( $this, 'ajax_get_edit_history' ) );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        // Only load on order edit screens
        if ( ! in_array( $hook, array( 'post.php', 'woocommerce_page_wc-orders' ), true ) ) {
            return;
        }

        // Check if editing is enabled
        if ( 'yes' !== get_option( 'abox_enable_admin_editing', 'no' ) ) {
            return;
        }

        // Get order ID
        $order_id = $this->get_current_order_id();
        if ( ! $order_id ) {
            return;
        }

        // Check if this is a box order
        $order = wc_get_order( $order_id );
        if ( ! $order || 'yes' !== $order->get_meta( '_abox_is_box_order' ) ) {
            return;
        }

        wp_enqueue_style(
            'abox-admin-editor',
            ABOX_PLUGIN_URL . 'assets/css/admin-editor.css',
            array(),
            ABOX_VERSION
        );

        wp_enqueue_script(
            'abox-admin-editor',
            ABOX_PLUGIN_URL . 'assets/js/admin-editor.js',
            array( 'jquery' ),
            ABOX_VERSION,
            true
        );

        wp_localize_script( 'abox-admin-editor', 'aboxEditorData', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'abox_admin_nonce' ),
            'orderId'        => $order_id,
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'decimals'       => wc_get_price_decimals(),
            'placeholder'    => wc_placeholder_img_src(),
            'i18n'           => array(
                'loading'          => __( 'Loading...', 'agent-box-orders' ),
                'saving'           => __( 'Saving...', 'agent-box-orders' ),
                'save'             => __( 'Save Changes', 'agent-box-orders' ),
                'error'            => __( 'An error occurred. Please try again.', 'agent-box-orders' ),
                'confirmDiscard'   => __( 'You have unsaved changes. Are you sure you want to discard them?', 'agent-box-orders' ),
                'confirmRemoveBox' => __( 'Are you sure you want to remove this box?', 'agent-box-orders' ),
                'searching'        => __( 'Searching...', 'agent-box-orders' ),
                'noResults'        => __( 'No products found.', 'agent-box-orders' ),
                'selectVariation'  => __( 'Select a variation...', 'agent-box-orders' ),
                'noVariations'     => __( 'No available variations found.', 'agent-box-orders' ),
                'newBoxLabel'      => __( 'Customer Name', 'agent-box-orders' ),
                'removeBox'        => __( 'Remove box', 'agent-box-orders' ),
                'product'          => __( 'Product', 'agent-box-orders' ),
                'qty'              => __( 'Qty', 'agent-box-orders' ),
                'price'            => __( 'Price', 'agent-box-orders' ),
                'subtotal'         => __( 'Subtotal', 'agent-box-orders' ),
                'searchPlaceholder'=> __( 'Search products to add...', 'agent-box-orders' ),
                'item'             => __( 'item', 'agent-box-orders' ),
                'items'            => __( 'items', 'agent-box-orders' ),
                'noBoxes'          => __( 'At least one box is required.', 'agent-box-orders' ),
                'emptyLabel'       => __( 'All boxes must have a customer label.', 'agent-box-orders' ),
                'emptyBox'         => __( 'Box "%s" has no items.', 'agent-box-orders' ),
                'invalidQty'       => __( 'All quantities must be at least 1.', 'agent-box-orders' ),
                'editHistory'      => __( 'Edit History', 'agent-box-orders' ),
                'noHistory'        => __( 'No edit history available.', 'agent-box-orders' ),
            ),
        ) );
    }

    /**
     * Get current order ID from context
     *
     * @return int|null
     */
    private function get_current_order_id() {
        // HPOS
        if ( isset( $_GET['id'] ) ) {
            return absint( $_GET['id'] );
        }

        // Classic
        if ( isset( $_GET['post'] ) ) {
            return absint( $_GET['post'] );
        }

        return null;
    }

    /**
     * AJAX: Get edit template
     */
    public function ajax_get_edit_template() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'agent-box-orders' ) ) );
        }

        $boxes = $order->get_meta( '_abox_boxes' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            wp_send_json_error( array( 'message' => __( 'No box data available.', 'agent-box-orders' ) ) );
        }

        ob_start();
        include ABOX_PLUGIN_DIR . 'admin/views/meta-box-editor.php';
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'  => $html,
            'boxes' => $boxes,
        ) );
    }

    /**
     * AJAX: Save boxes
     */
    public function ajax_save_boxes() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $boxes    = isset( $_POST['boxes'] ) ? $_POST['boxes'] : array();

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'agent-box-orders' ) ) );
        }

        // Get old boxes for comparison
        $old_boxes = $order->get_meta( '_abox_boxes' );

        // Sanitize new boxes
        $sanitized_boxes = $this->sanitize_boxes( $boxes );

        if ( is_wp_error( $sanitized_boxes ) ) {
            wp_send_json_error( array( 'message' => $sanitized_boxes->get_error_message() ) );
        }

        // Detect changes
        $changes = $this->detect_changes( $old_boxes, $sanitized_boxes );

        // Update order meta
        $order->update_meta_data( '_abox_boxes', $sanitized_boxes );

        // Update order line items
        $this->sync_order_line_items( $order, $sanitized_boxes );

        // Log changes
        if ( ! empty( $changes ) ) {
            $this->log_changes( $order, $changes );
        }

        // Recalculate totals
        $order->calculate_totals();
        $order->save();

        wp_send_json_success( array( 'message' => __( 'Changes saved successfully.', 'agent-box-orders' ) ) );
    }

    /**
     * Sanitize boxes data
     *
     * @param array $boxes Raw boxes data.
     * @return array|WP_Error
     */
    private function sanitize_boxes( $boxes ) {
        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            return new WP_Error( 'no_boxes', __( 'At least one box is required.', 'agent-box-orders' ) );
        }

        $sanitized = array();

        foreach ( $boxes as $box ) {
            $label = isset( $box['label'] ) ? sanitize_text_field( wp_unslash( $box['label'] ) ) : '';

            $items = isset( $box['items'] ) ? $box['items'] : array();

            if ( empty( $items ) || ! is_array( $items ) ) {
                return new WP_Error( 'empty_box', sprintf( __( 'Box "%s" has no items.', 'agent-box-orders' ), $label ) );
            }

            $sanitized_items = array();

            foreach ( $items as $item ) {
                $product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
                $variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
                $quantity     = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;

                if ( ! $product_id || $quantity < 1 ) {
                    continue;
                }

                // Get variation attributes if missing and we have a variation_id
                $variation_attrs = isset( $item['variation_attrs'] ) ? sanitize_text_field( wp_unslash( $item['variation_attrs'] ) ) : '';

                if ( empty( $variation_attrs ) && $variation_id ) {
                    $variation_attrs = $this->get_variation_attributes_string( $variation_id, $product_id );
                }

                // Get product name if missing
                $product_name = isset( $item['product_name'] ) ? sanitize_text_field( wp_unslash( $item['product_name'] ) ) : '';

                if ( empty( $product_name ) ) {
                    $product = wc_get_product( $variation_id ? $variation_id : $product_id );
                    if ( $product ) {
                        $product_name = $product->get_name();
                    }
                }

                $sanitized_items[] = array(
                    'product_id'      => $product_id,
                    'variation_id'    => $variation_id,
                    'product_name'    => $product_name,
                    'variation_attrs' => $variation_attrs,
                    'quantity'        => $quantity,
                    'price'           => isset( $item['price'] ) ? floatval( $item['price'] ) : 0,
                );
            }

            if ( empty( $sanitized_items ) ) {
                return new WP_Error( 'empty_box', sprintf( __( 'Box "%s" has no valid items.', 'agent-box-orders' ), $label ) );
            }

            $sanitized[] = array(
                'label' => $label,
                'items' => $sanitized_items,
            );
        }

        return $sanitized;
    }

    /**
     * Get variation attributes as a formatted string
     *
     * @param int $variation_id Variation ID.
     * @param int $product_id   Parent product ID.
     * @return string Formatted attributes string.
     */
    private function get_variation_attributes_string( $variation_id, $product_id ) {
        $variation = wc_get_product( $variation_id );

        if ( ! $variation || 'variation' !== $variation->get_type() ) {
            return '';
        }

        $parent_product       = wc_get_product( $product_id );
        $variation_attributes = $variation->get_variation_attributes();
        $attr_parts           = array();

        foreach ( $variation_attributes as $attr_key => $attr_value ) {
            $taxonomy  = str_replace( 'attribute_', '', $attr_key );
            $attr_name = wc_attribute_label( $taxonomy, $parent_product );

            if ( $attr_value ) {
                if ( taxonomy_exists( $taxonomy ) ) {
                    $term = get_term_by( 'slug', $attr_value, $taxonomy );
                    if ( $term ) {
                        $attr_value = $term->name;
                    }
                }
                $attr_parts[] = $attr_name . ': ' . ucfirst( $attr_value );
            }
        }

        return implode( ', ', $attr_parts );
    }

    /**
     * Detect changes between old and new boxes
     *
     * @param array $old_boxes Old boxes data.
     * @param array $new_boxes New boxes data.
     * @return array Changes array.
     */
    private function detect_changes( $old_boxes, $new_boxes ) {
        $changes = array();

        // Index old boxes by label for comparison
        $old_by_label = array();
        if ( is_array( $old_boxes ) ) {
            foreach ( $old_boxes as $box ) {
                $old_by_label[ $box['label'] ] = $box;
            }
        }

        $new_by_label = array();
        foreach ( $new_boxes as $box ) {
            $new_by_label[ $box['label'] ] = $box;
        }

        // Check for removed boxes
        foreach ( $old_by_label as $label => $old_box ) {
            if ( ! isset( $new_by_label[ $label ] ) ) {
                $changes[] = array(
                    'type'        => 'box_removed',
                    'box'         => $label,
                    'description' => sprintf( __( 'Box "%s" removed', 'agent-box-orders' ), $label ),
                );
            }
        }

        // Check for added boxes and item changes
        foreach ( $new_by_label as $label => $new_box ) {
            if ( ! isset( $old_by_label[ $label ] ) ) {
                $changes[] = array(
                    'type'        => 'box_added',
                    'box'         => $label,
                    'description' => sprintf( __( 'Box "%s" added', 'agent-box-orders' ), $label ),
                );
                continue;
            }

            $old_box = $old_by_label[ $label ];

            // Index items by product/variation ID
            $old_items = array();
            foreach ( $old_box['items'] as $item ) {
                $key = $item['variation_id'] ? 'v_' . $item['variation_id'] : 'p_' . $item['product_id'];
                $old_items[ $key ] = $item;
            }

            $new_items = array();
            foreach ( $new_box['items'] as $item ) {
                $key = $item['variation_id'] ? 'v_' . $item['variation_id'] : 'p_' . $item['product_id'];
                $new_items[ $key ] = $item;
            }

            // Check for removed items
            foreach ( $old_items as $key => $old_item ) {
                if ( ! isset( $new_items[ $key ] ) ) {
                    $changes[] = array(
                        'type'        => 'item_removed',
                        'box'         => $label,
                        'product'     => $old_item['product_name'],
                        'description' => sprintf( __( 'Box "%1$s": removed "%2$s"', 'agent-box-orders' ), $label, $old_item['product_name'] ),
                    );
                }
            }

            // Check for added items and quantity changes
            foreach ( $new_items as $key => $new_item ) {
                if ( ! isset( $old_items[ $key ] ) ) {
                    $changes[] = array(
                        'type'        => 'item_added',
                        'box'         => $label,
                        'product'     => $new_item['product_name'],
                        'qty'         => $new_item['quantity'],
                        'description' => sprintf( __( 'Box "%1$s": added "%2$s" x%3$d', 'agent-box-orders' ), $label, $new_item['product_name'], $new_item['quantity'] ),
                    );
                } elseif ( $old_items[ $key ]['quantity'] !== $new_item['quantity'] ) {
                    $changes[] = array(
                        'type'        => 'qty_changed',
                        'box'         => $label,
                        'product'     => $new_item['product_name'],
                        'from'        => $old_items[ $key ]['quantity'],
                        'to'          => $new_item['quantity'],
                        'description' => sprintf( __( 'Box "%1$s": "%2$s" qty %3$d → %4$d', 'agent-box-orders' ), $label, $new_item['product_name'], $old_items[ $key ]['quantity'], $new_item['quantity'] ),
                    );
                }
            }
        }

        return $changes;
    }

    /**
     * Log changes to order
     *
     * @param WC_Order $order   Order object.
     * @param array    $changes Changes array.
     */
    private function log_changes( $order, $changes ) {
        $user = wp_get_current_user();

        // Add to edit history meta
        $history = $order->get_meta( '_abox_edit_history' );
        if ( ! is_array( $history ) ) {
            $history = array();
        }

        $history[] = array(
            'user_id'   => $user->ID,
            'user_name' => $user->display_name,
            'timestamp' => current_time( 'mysql' ),
            'changes'   => $changes,
        );

        $order->update_meta_data( '_abox_edit_history', $history );

        // Add order note
        $note_parts = array();
        foreach ( $changes as $change ) {
            $note_parts[] = $change['description'];
        }

        $order->add_order_note(
            sprintf(
                /* translators: 1: user name, 2: changes list */
                __( 'Box order edited by %1$s — %2$s', 'agent-box-orders' ),
                $user->display_name,
                implode( '; ', $note_parts )
            ),
            false,
            true
        );
    }

    /**
     * Sync order line items with boxes
     *
     * @param WC_Order $order Order object.
     * @param array    $boxes Boxes data.
     */
    private function sync_order_line_items( $order, $boxes ) {
        // Remove all existing line items
        foreach ( $order->get_items() as $item_id => $item ) {
            $order->remove_item( $item_id );
        }

        // Aggregate products from all boxes
        $aggregated = array();

        foreach ( $boxes as $box ) {
            foreach ( $box['items'] as $item ) {
                $key = $item['variation_id'] ? 'v_' . $item['variation_id'] : 'p_' . $item['product_id'];

                if ( isset( $aggregated[ $key ] ) ) {
                    $aggregated[ $key ]['quantity'] += $item['quantity'];
                } else {
                    $aggregated[ $key ] = array(
                        'product_id'   => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity'     => $item['quantity'],
                        'price'        => $item['price'],
                    );
                }
            }
        }

        // Add new line items
        foreach ( $aggregated as $item_data ) {
            $product = $item_data['variation_id']
                ? wc_get_product( $item_data['variation_id'] )
                : wc_get_product( $item_data['product_id'] );

            if ( ! $product ) {
                continue;
            }

            $item = new WC_Order_Item_Product();
            $item->set_product( $product );
            $item->set_quantity( $item_data['quantity'] );
            $item->set_subtotal( $item_data['price'] * $item_data['quantity'] );
            $item->set_total( $item_data['price'] * $item_data['quantity'] );

            $order->add_item( $item );
        }
    }

    /**
     * AJAX: Search products
     */
    public function ajax_search_products() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

        if ( strlen( $term ) < 2 ) {
            wp_send_json_success( array( 'products' => array() ) );
        }

        $data_store  = WC_Data_Store::load( 'product' );
        $product_ids = $data_store->search_products( $term, '', true, false, 20 );

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

            $products[] = array(
                'id'         => $product->get_id(),
                'name'       => $product->get_name(),
                'sku'        => $product->get_sku(),
                'price'      => (float) $product->get_price(),
                'price_html' => $product->get_price_html(),
                'image'      => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'type'       => $product_type,
            );
        }

        wp_send_json_success( array( 'products' => $products ) );
    }

    /**
     * AJAX: Get variations
     */
    public function ajax_get_variations() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $product    = wc_get_product( $product_id );

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

            $attribute_parts      = array();
            $variation_attributes = $variation->get_variation_attributes();

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

            $variations[] = array(
                'variation_id'  => $variation->get_id(),
                'attributes'    => implode( ', ', $attribute_parts ),
                'sku'           => $variation->get_sku(),
                'price'         => (float) $variation->get_price(),
                'display_price' => wc_price( $variation->get_price() ),
            );
        }

        wp_send_json_success( array( 'variations' => $variations ) );
    }

    /**
     * AJAX: Get edit history
     */
    public function ajax_get_edit_history() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'agent-box-orders' ) ) );
        }

        $history = $order->get_meta( '_abox_edit_history' );

        if ( ! is_array( $history ) ) {
            $history = array();
        }

        // Reverse to show newest first
        $history = array_reverse( $history );

        wp_send_json_success( array( 'history' => $history ) );
    }
}
