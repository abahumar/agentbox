<?php
/**
 * Checkout integration
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Checkout class
 */
class ABOX_Checkout {

    /**
     * Constructor
     */
    public function __construct() {
        // Classic checkout - save boxes to order
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_boxes_to_order' ), 10, 2 );

        // Block checkout support (WC 7.2+)
        add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'save_boxes_to_order_blocks' ) );

        // Clear session after order completion
        add_action( 'woocommerce_thankyou', array( $this, 'clear_session' ) );

        // Display boxes breakdown on thank you page
        add_action( 'woocommerce_thankyou', array( $this, 'display_boxes_thankyou' ), 5 );

        // Display boxes in order confirmation emails
        add_action( 'woocommerce_email_after_order_table', array( $this, 'display_boxes_email' ), 10, 4 );
    }

    /**
     * Save boxes breakdown to order meta during checkout
     *
     * @param WC_Order $order Order object.
     * @param array    $data  Posted data.
     */
    public function save_boxes_to_order( $order, $data ) {
        if ( ! WC()->session ) {
            return;
        }

        $boxes    = WC()->session->get( 'abox_boxes' );
        $agent_id = WC()->session->get( 'abox_agent_id' );

        if ( empty( $boxes ) ) {
            return;
        }

        // Use HPOS-compatible methods
        $order->update_meta_data( '_abox_boxes', $boxes );
        $order->update_meta_data( '_abox_agent_id', $agent_id );
        $order->update_meta_data( '_abox_is_box_order', 'yes' );
    }

    /**
     * Save boxes for block checkout
     *
     * @param WC_Order $order Order object.
     */
    public function save_boxes_to_order_blocks( $order ) {
        $this->save_boxes_to_order( $order, array() );
        $order->save();
    }

    /**
     * Clear session data after order completion
     *
     * @param int $order_id Order ID.
     */
    public function clear_session( $order_id ) {
        if ( ! WC()->session ) {
            return;
        }

        WC()->session->set( 'abox_boxes', null );
        WC()->session->set( 'abox_agent_id', null );
    }

    /**
     * Display boxes breakdown on thank you page
     *
     * @param int $order_id Order ID.
     */
    public function display_boxes_thankyou( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $boxes = $order->get_meta( '_abox_boxes' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            return;
        }

        ?>
        <style>
            .abox-thankyou-breakdown {
                margin-bottom: 30px;
            }
            .abox-thankyou-breakdown h2 {
                margin-bottom: 20px;
            }
            .abox-thankyou-box {
                margin-bottom: 25px;
            }
            .abox-thankyou-box h3 {
                margin-bottom: 10px;
                padding: 10px;
                background: #f7f7f7;
                border-left: 4px solid #96588a;
            }
            .abox-thankyou-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }
            .abox-thankyou-table th,
            .abox-thankyou-table td {
                padding: 10px 12px;
                text-align: left;
                border-bottom: 1px solid #e5e5e5;
            }
            .abox-thankyou-table th {
                background: #f9f9f9;
                font-weight: 600;
                font-size: 0.9em;
                text-transform: uppercase;
                color: #555;
            }
            .abox-thankyou-table td.abox-qty,
            .abox-thankyou-table td.abox-price {
                text-align: right;
                white-space: nowrap;
            }
            .abox-thankyou-table th.abox-qty,
            .abox-thankyou-table th.abox-price {
                text-align: right;
            }
            .abox-variation-attrs {
                display: block;
                color: #666;
                font-size: 0.85em;
                font-style: italic;
                margin-top: 3px;
            }
            .abox-box-total {
                text-align: right;
                font-weight: 600;
                padding: 10px 12px;
                background: #f9f9f9;
                border-top: 2px solid #e5e5e5;
            }
        </style>
        <div class="abox-thankyou-breakdown">
            <h2><?php esc_html_e( 'Box Order Breakdown', 'agent-box-orders' ); ?></h2>

            <?php foreach ( $boxes as $index => $box ) : ?>
                <?php
                $box_total = 0;
                foreach ( $box['items'] as $item ) {
                    $box_total += $item['price'] * $item['quantity'];
                }
                ?>
                <div class="abox-thankyou-box">
                    <h3>
                        <?php
                        printf(
                            /* translators: 1: box number, 2: customer label */
                            esc_html__( 'Box %1$d: %2$s', 'agent-box-orders' ),
                            $index + 1,
                            esc_html( $box['label'] )
                        );
                        ?>
                    </h3>
                    <table class="abox-thankyou-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                                <th class="abox-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                                <th class="abox-price"><?php esc_html_e( 'Total', 'agent-box-orders' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $box['items'] as $item ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $item['product_name'] ); ?>
                                        <?php if ( ! empty( $item['variation_attrs'] ) ) : ?>
                                            <span class="abox-variation-attrs"><?php echo esc_html( $item['variation_attrs'] ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="abox-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                                    <td class="abox-price"><?php echo wc_price( $item['price'] * $item['quantity'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="abox-box-total"><?php esc_html_e( 'Box Total:', 'agent-box-orders' ); ?></td>
                                <td class="abox-box-total"><?php echo wc_price( $box_total ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Display boxes in order confirmation emails
     *
     * @param WC_Order $order         Order object.
     * @param bool     $sent_to_admin Whether email is for admin.
     * @param bool     $plain_text    Whether plain text email.
     * @param WC_Email $email         Email object.
     */
    public function display_boxes_email( $order, $sent_to_admin, $plain_text, $email ) {
        $boxes = $order->get_meta( '_abox_boxes' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            return;
        }

        if ( $plain_text ) {
            $this->display_boxes_email_plain( $boxes );
        } else {
            $this->display_boxes_email_html( $boxes );
        }
    }

    /**
     * Display boxes in plain text email
     *
     * @param array $boxes Boxes data.
     */
    private function display_boxes_email_plain( $boxes ) {
        echo "\n" . esc_html__( 'Box Order Breakdown', 'agent-box-orders' ) . "\n";
        echo str_repeat( '-', 40 ) . "\n\n";

        foreach ( $boxes as $index => $box ) {
            printf(
                /* translators: 1: box number, 2: customer label */
                esc_html__( 'Box %1$d: %2$s', 'agent-box-orders' ) . "\n",
                $index + 1,
                $box['label']
            );

            foreach ( $box['items'] as $item ) {
                $variation_text = ! empty( $item['variation_attrs'] ) ? ' [' . $item['variation_attrs'] . ']' : '';
                printf(
                    "  - %s%s x %d (%s)\n",
                    $item['product_name'],
                    $variation_text,
                    $item['quantity'],
                    wp_strip_all_tags( wc_price( $item['price'] * $item['quantity'] ) )
                );
            }

            echo "\n";
        }
    }

    /**
     * Display boxes in HTML email
     *
     * @param array $boxes Boxes data.
     */
    private function display_boxes_email_html( $boxes ) {
        ?>
        <div style="margin-bottom: 40px;">
            <h2 style="color: #96588a; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left;">
                <?php esc_html_e( 'Box Order Breakdown', 'agent-box-orders' ); ?>
            </h2>

            <?php foreach ( $boxes as $index => $box ) : ?>
                <div style="margin-bottom: 20px; padding: 10px; background: #f7f7f7; border-left: 4px solid #96588a;">
                    <h3 style="margin: 0 0 10px; font-size: 14px; font-weight: bold;">
                        <?php
                        printf(
                            /* translators: 1: box number, 2: customer label */
                            esc_html__( 'Box %1$d: %2$s', 'agent-box-orders' ),
                            $index + 1,
                            esc_html( $box['label'] )
                        );
                        ?>
                    </h3>
                    <ul style="margin: 0; padding: 0 0 0 20px;">
                        <?php foreach ( $box['items'] as $item ) : ?>
                            <li style="margin-bottom: 5px;">
                                <?php echo esc_html( $item['product_name'] ); ?>
                                <?php if ( ! empty( $item['variation_attrs'] ) ) : ?>
                                    <br><em style="color: #888; font-size: 12px;"><?php echo esc_html( $item['variation_attrs'] ); ?></em>
                                <?php endif; ?>
                                &times; <?php echo esc_html( $item['quantity'] ); ?>
                                <span style="color: #666;">
                                    (<?php echo wc_price( $item['price'] * $item['quantity'] ); ?>)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
