<?php
/**
 * Simplified packing list view
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
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 20px;
        }

        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .print-actions button {
            padding: 10px 20px;
            font-size: 14px;
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
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .order-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            font-size: 12px;
        }

        .order-info-item {
            display: flex;
            gap: 8px;
        }

        .order-info-item strong {
            color: #666;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th,
        .items-table td {
            padding: 6px 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .items-table th {
            background: #f5f5f5;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
        }

        .items-table .col-checkbox {
            width: 35px;
            text-align: center;
        }

        .items-table .col-variation {
            width: 150px;
        }

        .items-table .col-qty {
            width: 50px;
            text-align: center;
        }

        .checkbox-cell {
            width: 14px;
            height: 14px;
            border: 1.5px solid #333;
            display: inline-block;
        }

        .box-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .box-header {
            background: #333;
            color: #fff;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .box-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #fff;
            color: #333;
            border-radius: 50%;
            font-weight: bold;
            font-size: 11px;
        }

        .box-section .items-table {
            margin-top: 0;
        }

        @media print {
            .print-actions {
                display: none !important;
            }

            body {
                padding: 20px;
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
        </div>
    </div>

    <?php foreach ( $boxes as $index => $box ) : ?>
        <div class="box-section">
            <div class="box-header">
                <span class="box-number"><?php echo esc_html( $index + 1 ); ?></span>
                <?php echo esc_html( $box['label'] ); ?>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-checkbox"><?php esc_html_e( '✓', 'agent-box-orders' ); ?></th>
                        <th class="col-product"><?php esc_html_e( 'Product Name', 'agent-box-orders' ); ?></th>
                        <th class="col-variation"><?php esc_html_e( 'Variation', 'agent-box-orders' ); ?></th>
                        <th class="col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $box['items'] as $item ) : ?>
                        <tr>
                            <td class="col-checkbox"><span class="checkbox-cell"></span></td>
                            <td class="col-product"><?php echo esc_html( $item['product_name'] ); ?></td>
                            <td class="col-variation"><?php echo ! empty( $item['variation_attrs'] ) ? esc_html( $item['variation_attrs'] ) : '-'; ?></td>
                            <td class="col-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</body>
</html>
