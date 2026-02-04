<?php
/**
 * Meta box editor template
 *
 * @package Agent_Box_Orders
 *
 * @var array $boxes Boxes data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="abox-edit-mode">
    <div class="abox-edit-header">
        <h3>
            <?php esc_html_e( 'Editing Box Order', 'agent-box-orders' ); ?>
            <span class="abox-unsaved-indicator" style="display: none;">*</span>
        </h3>
        <div class="abox-edit-actions">
            <button type="button" class="button abox-cancel-btn"><?php esc_html_e( 'Cancel', 'agent-box-orders' ); ?></button>
            <button type="button" class="button button-primary abox-save-btn"><?php esc_html_e( 'Save Changes', 'agent-box-orders' ); ?></button>
        </div>
    </div>

    <div class="abox-accordion">
        <?php foreach ( $boxes as $index => $box ) :
            $box_total  = 0;
            $item_count = 0;
            foreach ( $box['items'] as $item ) {
                $box_total  += $item['price'] * $item['quantity'];
                $item_count += $item['quantity'];
            }
        ?>
            <div class="abox-accordion-item">
                <div class="abox-accordion-header">
                    <span class="abox-accordion-toggle dashicons dashicons-arrow-right-alt2"></span>
                    <div class="abox-accordion-label">
                        <input type="text" value="<?php echo esc_attr( $box['label'] ); ?>" placeholder="<?php esc_attr_e( 'Customer Name', 'agent-box-orders' ); ?>">
                    </div>
                    <div class="abox-accordion-summary">
                        <span class="abox-box-items">
                            <?php
                            printf(
                                esc_html( _n( '%d item', '%d items', $item_count, 'agent-box-orders' ) ),
                                $item_count
                            );
                            ?>
                        </span>
                        <span class="abox-box-total"><?php echo wc_price( $box_total ); ?></span>
                    </div>
                    <button type="button" class="abox-accordion-remove" title="<?php esc_attr_e( 'Remove box', 'agent-box-orders' ); ?>">&times;</button>
                </div>
                <div class="abox-accordion-content">
                    <table class="abox-edit-items-table">
                        <thead>
                            <tr>
                                <th class="abox-col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-price"><?php esc_html_e( 'Price', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-subtotal"><?php esc_html_e( 'Subtotal', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $box['items'] as $item ) :
                                $subtotal     = $item['price'] * $item['quantity'];
                                $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : 0;
                                $product      = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $item['product_id'] );
                            ?>
                                <tr data-product-id="<?php echo esc_attr( $item['product_id'] ); ?>"
                                    data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
                                    data-product-name="<?php echo esc_attr( $item['product_name'] ); ?>"
                                    data-variation-attrs="<?php echo esc_attr( isset( $item['variation_attrs'] ) ? $item['variation_attrs'] : '' ); ?>"
                                    data-price="<?php echo esc_attr( $item['price'] ); ?>">
                                    <td class="abox-col-product">
                                        <?php echo esc_html( $item['product_name'] ); ?>
                                        <?php if ( ! empty( $item['variation_attrs'] ) ) : ?>
                                            <br><small><?php echo esc_html( $item['variation_attrs'] ); ?></small>
                                        <?php endif; ?>
                                        <?php if ( $product && $product->get_sku() ) : ?>
                                            <span class="abox-product-sku">(<?php echo esc_html( $product->get_sku() ); ?>)</span>
                                        <?php endif; ?>
                                        <?php if ( ! $product ) : ?>
                                            <span class="abox-product-deleted"><?php esc_html_e( '(deleted)', 'agent-box-orders' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="abox-col-qty">
                                        <input type="number" class="abox-qty-input" value="<?php echo esc_attr( $item['quantity'] ); ?>" min="1">
                                    </td>
                                    <td class="abox-col-price"><?php echo wc_price( $item['price'] ); ?></td>
                                    <td class="abox-col-subtotal abox-item-subtotal"><?php echo wc_price( $subtotal ); ?></td>
                                    <td class="abox-col-actions">
                                        <button type="button" class="abox-item-remove">&times;</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="abox-product-search-container">
                        <input type="text" class="abox-product-search-input" placeholder="<?php esc_attr_e( 'Search products to add...', 'agent-box-orders' ); ?>">
                        <div class="abox-search-results"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="abox-add-box-container">
        <button type="button" class="abox-add-box-btn">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'Add New Box', 'agent-box-orders' ); ?>
        </button>
    </div>

    <?php
    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $order    = wc_get_order( $order_id );
    $history  = $order ? $order->get_meta( '_abox_edit_history' ) : array();
    if ( ! empty( $history ) ) :
    ?>
        <a href="#" class="abox-edit-history-link">
            <span class="dashicons dashicons-backup"></span>
            <?php esc_html_e( 'View Edit History', 'agent-box-orders' ); ?>
        </a>
    <?php endif; ?>
</div>
