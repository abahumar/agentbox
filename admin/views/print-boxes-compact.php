<?php
/**
 * Compact two-column packing list view
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

$order_number   = $order->get_order_number();
$order_date     = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );
$customer_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

// Get Pickup/COD date and time
$pickup_cod_date = $order->get_meta( '_pickup_cod_date' );
$pickup_cod_time = $order->get_meta( '_pickup_cod_time' );

/**
 * Helper function to get variation attributes string (values only, no attribute names)
 *
 * @param array $item Item data.
 * @return string Formatted variation values.
 */
function abox_get_variation_display( $item ) {
    // If no variation_id, return empty
    if ( empty( $item['variation_id'] ) ) {
        return '-';
    }

    // Fetch from the variation product
    $variation = wc_get_product( $item['variation_id'] );

    if ( ! $variation || 'variation' !== $variation->get_type() ) {
        return '-';
    }

    $parent_product       = wc_get_product( $item['product_id'] );
    $variation_attributes = $variation->get_variation_attributes();
    $attr_values          = array();

    foreach ( $variation_attributes as $attr_key => $attr_value ) {
        $taxonomy = str_replace( 'attribute_', '', $attr_key );

        if ( $attr_value ) {
            if ( taxonomy_exists( $taxonomy ) ) {
                $term = get_term_by( 'slug', $attr_value, $taxonomy );
                if ( $term ) {
                    $attr_value = $term->name;
                }
            }
            $attr_values[] = ucfirst( $attr_value );
        }
    }

    return ! empty( $attr_values ) ? implode( ', ', $attr_values ) : '-';
}

/**
 * Helper function to get parent product name
 *
 * @param array $item Item data.
 * @return string Parent product name.
 */
function abox_get_product_name( $item ) {
    // Get parent product
    $product = wc_get_product( $item['product_id'] );

    if ( $product ) {
        return $product->get_name();
    }

    // Fallback to stored product_name
    return isset( $item['product_name'] ) ? $item['product_name'] : '';
}

// Split boxes into two columns
$total_boxes  = count( $boxes );
$half         = ceil( $total_boxes / 2 );
$left_boxes   = array_slice( $boxes, 0, $half, true );
$right_boxes  = array_slice( $boxes, $half, null, true );

// Get the maximum number of rows needed
$max_rows = max( count( $left_boxes ), count( $right_boxes ) );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php printf( esc_html__( 'Packing List - Order #%s', 'agent-box-orders' ), esc_html( $order_number ) ); ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            line-height: 1.2;
            color: #333;
            background: #fff;
            padding: 10px;
        }

        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            z-index: 100;
        }

        .print-actions button {
            padding: 8px 16px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
        }

        .btn-print {
            background: #0073aa;
            color: #fff;
        }

        .btn-close {
            background: #666;
            color: #fff;
        }

        .header {
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #333;
        }

        .header h1 {
            font-size: 16px;
            margin: 0 0 4px 0;
            text-align: left;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            font-size: 15px;
        }

        .order-info-item {
            display: flex;
            gap: 4px;
        }

        .order-info-item strong {
            color: #666;
        }

        .boxes-container {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .boxes-column {
            flex: 1;
            min-width: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 2px 4px;
            text-align: left;
            border: 1px solid #ccc;
            line-height: 1.2;
        }

        .items-table th {
            background: #e0e0e0;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
        }

        .items-table .col-checkbox {
            width: 20px;
            text-align: center;
        }

        .items-table .col-variation {
            width: 150px;
        }

        .items-table .col-qty {
            width: 30px;
            text-align: center;
        }

        .checkbox-cell {
            width: 10px;
            height: 10px;
            border: 1px solid #333;
            display: inline-block;
            background: #fff;
        }

        .box-header-row td {
            background: #f5f5f5;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            padding: 3px 4px;
            border: 1px solid #ccc;
        }

        .empty-cell {
            border: none !important;
            background: transparent !important;
        }

        @media print {
            .print-actions {
                display: none !important;
            }

            body {
                padding: 5px;
            }

            .boxes-container {
                gap: 8px;
            }

            .items-table th,
            .items-table td {
                padding: 1px 3px;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" class="btn-print" onclick="window.print();">
            <?php esc_html_e( 'Print', 'agent-box-orders' ); ?>
        </button>
        <button type="button" class="btn-close" onclick="window.close();">
            <?php esc_html_e( 'Close', 'agent-box-orders' ); ?>
        </button>
    </div>

    <div class="header">
        <h1><?php esc_html_e( 'Packing List', 'agent-box-orders' ); ?></h1>
        <div class="order-info">
            <div class="order-info-item">
                <strong><?php esc_html_e( 'Order:', 'agent-box-orders' ); ?></strong>
                <span>#<?php echo esc_html( $order_number ); ?></span>
            </div>
            <div class="order-info-item">
                <strong><?php esc_html_e( 'Date:', 'agent-box-orders' ); ?></strong>
                <span><?php echo esc_html( $order_date ); ?></span>
            </div>
            <div class="order-info-item">
                <strong><?php esc_html_e( 'Customer:', 'agent-box-orders' ); ?></strong>
                <span><?php echo esc_html( $customer_name ); ?></span>
            </div>
            <?php if ( $pickup_cod_date ) : ?>
                <div class="order-info-item">
                    <strong><?php esc_html_e( 'Pickup/COD Date:', 'agent-box-orders' ); ?></strong>
                    <span><?php echo esc_html( $pickup_cod_date ); ?></span>
                </div>
            <?php endif; ?>
            <?php if ( $pickup_cod_time ) : ?>
                <div class="order-info-item">
                    <strong><?php esc_html_e( 'Pickup/COD Time:', 'agent-box-orders' ); ?></strong>
                    <span><?php echo esc_html( date( 'h:iA', strtotime( $pickup_cod_time ) ) ); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="boxes-container">
        <!-- Left Column -->
        <div class="boxes-column">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-checkbox"><?php esc_html_e( '✓', 'agent-box-orders' ); ?></th>
                        <th class="col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                        <th class="col-variation"><?php esc_html_e( 'Variation', 'agent-box-orders' ); ?></th>
                        <th class="col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $box_index = 0;
                    foreach ( $left_boxes as $box ) :
                        $box_index++;
                    ?>
                        <tr class="box-header-row">
                            <td colspan="4"><?php printf( 'Box %d: %s', $box_index, esc_html( $box['label'] ) ); ?></td>
                        </tr>
                        <?php foreach ( $box['items'] as $item ) : ?>
                            <tr>
                                <td class="col-checkbox"><span class="checkbox-cell"></span></td>
                                <td class="col-product"><?php echo esc_html( abox_get_product_name( $item ) ); ?></td>
                                <td class="col-variation"><?php echo esc_html( abox_get_variation_display( $item ) ); ?></td>
                                <td class="col-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Column -->
        <?php if ( ! empty( $right_boxes ) ) : ?>
        <div class="boxes-column">
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-checkbox"><?php esc_html_e( '✓', 'agent-box-orders' ); ?></th>
                        <th class="col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                        <th class="col-variation"><?php esc_html_e( 'Variation', 'agent-box-orders' ); ?></th>
                        <th class="col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $right_box_index = $half;
                    foreach ( $right_boxes as $box ) :
                        $right_box_index++;
                    ?>
                        <tr class="box-header-row">
                            <td colspan="4"><?php printf( 'Box %d: %s', $right_box_index, esc_html( $box['label'] ) ); ?></td>
                        </tr>
                        <?php foreach ( $box['items'] as $item ) : ?>
                            <tr>
                                <td class="col-checkbox"><span class="checkbox-cell"></span></td>
                                <td class="col-product"><?php echo esc_html( abox_get_product_name( $item ) ); ?></td>
                                <td class="col-variation"><?php echo esc_html( abox_get_variation_display( $item ) ); ?></td>
                                <td class="col-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
