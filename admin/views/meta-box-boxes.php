<?php
/**
 * Meta box view for boxes breakdown
 *
 * @package Agent_Box_Orders
 *
 * @var WC_Order $order    Order object
 * @var array    $boxes    Boxes data
 * @var int      $agent_id Agent user ID
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$agent_user  = $agent_id ? get_user_by( 'ID', $agent_id ) : null;
$total_items = 0;
$grand_total = 0;

// Calculate totals
foreach ( $boxes as $box ) {
    foreach ( $box['items'] as $item ) {
        $total_items += $item['quantity'];
        $grand_total += $item['price'] * $item['quantity'];
    }
}
?>

<div class="abox-meta-box-content">
    <?php if ( $agent_user ) : ?>
        <div class="abox-agent-info-row">
            <span class="dashicons dashicons-admin-users"></span>
            <strong><?php esc_html_e( 'Created by Agent:', 'agent-box-orders' ); ?></strong>
            <?php echo esc_html( $agent_user->display_name ); ?>
            <span class="abox-agent-email">(<?php echo esc_html( $agent_user->user_email ); ?>)</span>
        </div>
    <?php endif; ?>

    <div class="abox-summary-bar">
        <span class="abox-summary-item">
            <strong><?php esc_html_e( 'Total Boxes:', 'agent-box-orders' ); ?></strong>
            <?php echo count( $boxes ); ?>
        </span>
        <span class="abox-summary-item">
            <strong><?php esc_html_e( 'Total Items:', 'agent-box-orders' ); ?></strong>
            <?php echo esc_html( $total_items ); ?>
        </span>
        <span class="abox-summary-item">
            <strong><?php esc_html_e( 'Boxes Subtotal:', 'agent-box-orders' ); ?></strong>
            <?php echo wc_price( $grand_total ); ?>
        </span>
    </div>

    <div class="abox-boxes-list">
        <?php foreach ( $boxes as $index => $box ) :
            $box_total = 0;
            foreach ( $box['items'] as $item ) {
                $box_total += $item['price'] * $item['quantity'];
            }
        ?>
            <div class="abox-box-item">
                <div class="abox-box-header-admin">
                    <h4>
                        <span class="abox-box-number"><?php echo esc_html( $index + 1 ); ?>.</span>
                        <?php echo esc_html( $box['label'] ); ?>
                    </h4>
                    <span class="abox-box-item-count">
                        <?php
                        $item_count = array_sum( array_column( $box['items'], 'quantity' ) );
                        printf(
                            /* translators: %d: item count */
                            esc_html( _n( '%d item', '%d items', $item_count, 'agent-box-orders' ) ),
                            $item_count
                        );
                        ?>
                    </span>
                </div>

                <table class="widefat striped abox-items-table">
                    <thead>
                        <tr>
                            <th class="abox-col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                            <th class="abox-col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                            <th class="abox-col-price"><?php esc_html_e( 'Unit Price', 'agent-box-orders' ); ?></th>
                            <th class="abox-col-subtotal"><?php esc_html_e( 'Subtotal', 'agent-box-orders' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $box['items'] as $item ) :
                            $subtotal     = $item['price'] * $item['quantity'];
                            $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : 0;
                            $product      = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $item['product_id'] );
                            $parent_product = wc_get_product( $item['product_id'] );
                        ?>
                            <tr>
                                <td class="abox-col-product">
                                    <?php if ( $parent_product ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $item['product_id'] ) ); ?>" target="_blank">
                                            <?php echo esc_html( $item['product_name'] ); ?>
                                        </a>
                                        <?php if ( ! empty( $item['variation_attrs'] ) ) : ?>
                                            <br><span class="abox-variation-attrs"><?php echo esc_html( $item['variation_attrs'] ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( $product && $product->get_sku() ) : ?>
                                            <span class="abox-product-sku">(<?php echo esc_html( $product->get_sku() ); ?>)</span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <?php echo esc_html( $item['product_name'] ); ?>
                                        <?php if ( ! empty( $item['variation_attrs'] ) ) : ?>
                                            <br><span class="abox-variation-attrs"><?php echo esc_html( $item['variation_attrs'] ); ?></span>
                                        <?php endif; ?>
                                        <span class="abox-product-deleted"><?php esc_html_e( '(deleted)', 'agent-box-orders' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="abox-col-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                                <td class="abox-col-price"><?php echo wc_price( $item['price'] ); ?></td>
                                <td class="abox-col-subtotal"><?php echo wc_price( $subtotal ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="abox-box-total-label">
                                <?php esc_html_e( 'Box Total:', 'agent-box-orders' ); ?>
                            </th>
                            <th class="abox-box-total-value">
                                <?php echo wc_price( $box_total ); ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="abox-actions">
        <a href="<?php echo esc_url( ABOX_Admin::get_print_url( $order->get_id() ) ); ?>" target="_blank" class="button button-primary abox-print-breakdown">
            <span class="dashicons dashicons-printer"></span>
            <?php esc_html_e( 'Print Packing List', 'agent-box-orders' ); ?>
        </a>
        <button type="button" class="button abox-copy-breakdown" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
            <span class="dashicons dashicons-clipboard"></span>
            <?php esc_html_e( 'Copy Breakdown', 'agent-box-orders' ); ?>
        </button>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.abox-copy-breakdown').on('click', function() {
        var text = '';
        <?php foreach ( $boxes as $index => $box ) : ?>
        text += "<?php echo esc_js( sprintf( __( 'Box %d: %s', 'agent-box-orders' ), $index + 1, $box['label'] ) ); ?>\n";
        <?php foreach ( $box['items'] as $item ) :
            $variation_text = ! empty( $item['variation_attrs'] ) ? ' (' . $item['variation_attrs'] . ')' : '';
        ?>
        text += "  - <?php echo esc_js( $item['product_name'] . $variation_text ); ?> x <?php echo esc_js( $item['quantity'] ); ?>\n";
        <?php endforeach; ?>
        text += "\n";
        <?php endforeach; ?>

        navigator.clipboard.writeText(text).then(function() {
            alert('<?php echo esc_js( __( 'Breakdown copied to clipboard!', 'agent-box-orders' ) ); ?>');
        }).catch(function() {
            alert('<?php echo esc_js( __( 'Failed to copy. Please try again.', 'agent-box-orders' ) ); ?>');
        });
    });
});
</script>
