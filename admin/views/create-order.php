<?php
/**
 * Admin Create Order Page Template
 *
 * @package Agent_Box_Orders
 *
 * @var array $settings Plugin settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$order_statuses = wc_get_order_statuses();
$countries      = WC()->countries->get_countries();
$default_country = WC()->countries->get_base_country();
?>

<div class="wrap abox-admin-create-order">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Create Box Order', 'agent-box-orders' ); ?></h1>
    <hr class="wp-header-end">

    <form id="abox-admin-order-form" method="post">
        <div class="abox-admin-layout">
            <!-- Main Content -->
            <div class="abox-admin-main">
                <!-- Boxes Section -->
                <div class="abox-admin-section">
                    <h2><?php esc_html_e( 'Order Boxes', 'agent-box-orders' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Add boxes with products for this order. Each box represents items for a specific customer.', 'agent-box-orders' ); ?></p>

                    <div id="abox-boxes-container">
                        <!-- Boxes will be added here dynamically -->
                    </div>

                    <button type="button" id="abox-add-box-btn" class="button">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e( 'Add Box', 'agent-box-orders' ); ?>
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="abox-admin-sidebar">
                <!-- Order Actions -->
                <div class="abox-admin-panel">
                    <h3><?php esc_html_e( 'Create Order', 'agent-box-orders' ); ?></h3>
                    <div class="abox-panel-content">
                        <div class="abox-form-row">
                            <label for="abox-order-status"><?php esc_html_e( 'Order Status', 'agent-box-orders' ); ?></label>
                            <select id="abox-order-status" name="order_status">
                                <?php foreach ( $order_statuses as $status => $label ) : ?>
                                    <option value="<?php echo esc_attr( str_replace( 'wc-', '', $status ) ); ?>" <?php selected( 'wc-on-hold', $status ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="abox-summary">
                            <div class="abox-summary-row">
                                <span><?php esc_html_e( 'Total Boxes:', 'agent-box-orders' ); ?></span>
                                <strong id="abox-total-boxes">0</strong>
                            </div>
                            <div class="abox-summary-row">
                                <span><?php esc_html_e( 'Total Items:', 'agent-box-orders' ); ?></span>
                                <strong id="abox-total-items">0</strong>
                            </div>
                            <div class="abox-summary-row abox-summary-total">
                                <span><?php esc_html_e( 'Order Total:', 'agent-box-orders' ); ?></span>
                                <strong id="abox-grand-total"><?php echo wc_price( 0 ); ?></strong>
                            </div>
                        </div>

                        <button type="submit" id="abox-submit-btn" class="button button-primary button-large">
                            <?php esc_html_e( 'Create Order', 'agent-box-orders' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Payment & Collection -->
                <div class="abox-admin-panel">
                    <h3><?php esc_html_e( 'Payment & Collection', 'agent-box-orders' ); ?></h3>
                    <div class="abox-panel-content">
                        <div class="abox-form-row">
                            <label for="abox-payment-status"><?php esc_html_e( 'Payment Status', 'agent-box-orders' ); ?></label>
                            <select id="abox-payment-status" name="payment_status" style="width:100%;">
                                <option value=""><?php esc_html_e( '— Select —', 'agent-box-orders' ); ?></option>
                                <option value="done"><?php esc_html_e( 'Done Payment', 'agent-box-orders' ); ?></option>
                                <option value="cash_cashier"><?php esc_html_e( 'Cash di Cashier', 'agent-box-orders' ); ?></option>
                                <option value="cod"><?php esc_html_e( 'Cash on Delivery (COD)', 'agent-box-orders' ); ?></option>
                                <option value="partial"><?php esc_html_e( 'Partial Payment', 'agent-box-orders' ); ?></option>
                            </select>
                        </div>

                        <div class="abox-form-row">
                            <label for="abox-collection-method"><?php esc_html_e( 'Collection Method', 'agent-box-orders' ); ?></label>
                            <select id="abox-collection-method" name="collection_method" style="width:100%;">
                                <option value=""><?php esc_html_e( '— Select —', 'agent-box-orders' ); ?></option>
                                <option value="postage"><?php esc_html_e( 'Postage', 'agent-box-orders' ); ?></option>
                                <option value="pickup_hq"><?php esc_html_e( 'Pickup - HQ', 'agent-box-orders' ); ?></option>
                                <option value="pickup_terengganu"><?php esc_html_e( 'Pickup - Terengganu', 'agent-box-orders' ); ?></option>
                                <option value="runner_delivered"><?php esc_html_e( 'Runner Delivered', 'agent-box-orders' ); ?></option>
                            </select>
                        </div>

                        <hr style="margin: 15px 0; border-top: 1px solid #ddd;">

                        <p class="abox-form-row" style="margin-bottom: 8px;">
                            <strong><?php esc_html_e( 'For Pickup/COD Only', 'agent-box-orders' ); ?></strong>
                        </p>

                        <div class="abox-form-row">
                            <label for="abox-pickup-cod-date"><?php esc_html_e( 'Date', 'agent-box-orders' ); ?></label>
                            <input type="date" id="abox-pickup-cod-date" name="pickup_cod_date" style="width:100%;">
                        </div>

                        <div class="abox-form-row">
                            <label for="abox-pickup-cod-time"><?php esc_html_e( 'Time', 'agent-box-orders' ); ?></label>
                            <input type="time" id="abox-pickup-cod-time" name="pickup_cod_time" style="width:100%;">
                        </div>
                    </div>
                </div>

                <!-- Payment Receipts -->
                <div class="abox-admin-panel">
                    <h3><?php esc_html_e( 'Payment Receipts', 'agent-box-orders' ); ?></h3>
                    <div class="abox-panel-content">
                        <div class="abox-receipt-upload-area" style="border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 4px; margin-bottom: 10px; cursor: pointer; transition: border-color 0.2s;">
                            <span class="dashicons dashicons-upload" style="font-size: 32px; width: 32px; height: 32px; color: #999;"></span>
                            <p style="margin: 10px 0 0; color: #666; font-size: 12px;"><?php esc_html_e( 'JPG, PNG, PDF (Max 5MB each)', 'agent-box-orders' ); ?></p>
                        </div>

                        <input type="file" name="receipt_files[]" id="abox-receipt-files" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple style="display: none;">

                        <p>
                            <button type="button" class="button abox-select-receipt-btn" style="width: 100%;">
                                <?php esc_html_e( 'Select Receipt Files', 'agent-box-orders' ); ?>
                            </button>
                        </p>

                        <div class="abox-selected-files-list" style="display: none; margin-top: 10px; padding: 10px; background: #e7f7ed; border-radius: 4px;">
                            <p style="margin: 0 0 5px; font-weight: bold; font-size: 12px; color: #00a32a;"><?php esc_html_e( 'Files to upload:', 'agent-box-orders' ); ?></p>
                            <ul style="margin: 0; padding-left: 20px; font-size: 12px;"></ul>
                        </div>

                        <hr style="margin: 15px 0;">

                        <div class="abox-form-row">
                            <label for="abox-receipt-notes"><strong><?php esc_html_e( 'Account Team Notes:', 'agent-box-orders' ); ?></strong></label>
                            <textarea id="abox-receipt-notes" name="receipt_notes" rows="3" style="width: 100%;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Customer Section -->
                <div class="abox-admin-panel">
                    <h3><?php esc_html_e( 'Customer', 'agent-box-orders' ); ?></h3>
                    <div class="abox-panel-content">
                        <div class="abox-form-row">
                            <label for="abox-customer-select"><?php esc_html_e( 'Select Customer', 'agent-box-orders' ); ?></label>
                            <select id="abox-customer-select" name="customer_id" style="width: 100%;">
                                <option value="0"><?php esc_html_e( '— Guest (enter details below) —', 'agent-box-orders' ); ?></option>
                            </select>
                        </div>

                        <div id="abox-billing-details" class="abox-billing-details">
                            <h4><?php esc_html_e( 'Billing Details', 'agent-box-orders' ); ?></h4>

                            <div class="abox-form-row abox-form-row-half">
                                <div>
                                    <label for="abox-billing-first-name"><?php esc_html_e( 'First Name', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-billing-first-name" name="billing[first_name]">
                                </div>
                                <div>
                                    <label for="abox-billing-last-name"><?php esc_html_e( 'Last Name', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-billing-last-name" name="billing[last_name]">
                                </div>
                            </div>

                            <div class="abox-form-row">
                                <label for="abox-billing-email"><?php esc_html_e( 'Email', 'agent-box-orders' ); ?></label>
                                <input type="email" id="abox-billing-email" name="billing[email]">
                            </div>

                            <div class="abox-form-row">
                                <label for="abox-billing-phone"><?php esc_html_e( 'Phone', 'agent-box-orders' ); ?></label>
                                <input type="tel" id="abox-billing-phone" name="billing[phone]">
                            </div>

                            <div class="abox-form-row">
                                <label for="abox-billing-address-1"><?php esc_html_e( 'Address', 'agent-box-orders' ); ?></label>
                                <input type="text" id="abox-billing-address-1" name="billing[address_1]" placeholder="<?php esc_attr_e( 'Street address', 'agent-box-orders' ); ?>">
                            </div>

                            <div class="abox-form-row">
                                <input type="text" id="abox-billing-address-2" name="billing[address_2]" placeholder="<?php esc_attr_e( 'Apartment, suite, etc. (optional)', 'agent-box-orders' ); ?>">
                            </div>

                            <div class="abox-form-row abox-form-row-half">
                                <div>
                                    <label for="abox-billing-city"><?php esc_html_e( 'City', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-billing-city" name="billing[city]">
                                </div>
                                <div>
                                    <label for="abox-billing-postcode"><?php esc_html_e( 'Postcode', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-billing-postcode" name="billing[postcode]">
                                </div>
                            </div>

                            <div class="abox-form-row abox-form-row-half">
                                <div>
                                    <label for="abox-billing-country"><?php esc_html_e( 'Country', 'agent-box-orders' ); ?></label>
                                    <select id="abox-billing-country" name="billing[country]">
                                        <option value=""><?php esc_html_e( 'Select a country...', 'agent-box-orders' ); ?></option>
                                        <?php foreach ( $countries as $code => $name ) : ?>
                                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_country, $code ); ?>>
                                                <?php echo esc_html( $name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="abox-billing-state"><?php esc_html_e( 'State/Region', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-billing-state" name="billing[state]">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Section -->
                <div class="abox-admin-panel">
                    <h3><?php esc_html_e( 'Shipping Address', 'agent-box-orders' ); ?></h3>
                    <div class="abox-panel-content">
                        <div class="abox-form-row">
                            <label class="abox-checkbox-label">
                                <input type="checkbox" id="abox-ship-different" name="ship_to_different" value="1">
                                <?php esc_html_e( 'Ship to a different address', 'agent-box-orders' ); ?>
                            </label>
                        </div>

                        <div id="abox-shipping-fields" class="abox-shipping-fields" style="display: none;">
                            <div class="abox-form-row abox-form-row-half">
                                <div>
                                    <label for="abox-shipping-first-name"><?php esc_html_e( 'First Name', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-shipping-first-name" name="shipping[first_name]">
                                </div>
                                <div>
                                    <label for="abox-shipping-last-name"><?php esc_html_e( 'Last Name', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-shipping-last-name" name="shipping[last_name]">
                                </div>
                            </div>

                            <div class="abox-form-row">
                                <label for="abox-shipping-address-1"><?php esc_html_e( 'Address', 'agent-box-orders' ); ?></label>
                                <input type="text" id="abox-shipping-address-1" name="shipping[address_1]" placeholder="<?php esc_attr_e( 'Street address', 'agent-box-orders' ); ?>">
                            </div>

                            <div class="abox-form-row">
                                <input type="text" id="abox-shipping-address-2" name="shipping[address_2]" placeholder="<?php esc_attr_e( 'Apartment, suite, etc. (optional)', 'agent-box-orders' ); ?>">
                            </div>

                            <div class="abox-form-row abox-form-row-half">
                                <div>
                                    <label for="abox-shipping-city"><?php esc_html_e( 'City', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-shipping-city" name="shipping[city]">
                                </div>
                                <div>
                                    <label for="abox-shipping-postcode"><?php esc_html_e( 'Postcode', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-shipping-postcode" name="shipping[postcode]">
                                </div>
                            </div>

                            <div class="abox-form-row abox-form-row-half">
                                <div>
                                    <label for="abox-shipping-country"><?php esc_html_e( 'Country', 'agent-box-orders' ); ?></label>
                                    <select id="abox-shipping-country" name="shipping[country]">
                                        <option value=""><?php esc_html_e( 'Select a country...', 'agent-box-orders' ); ?></option>
                                        <?php foreach ( $countries as $code => $name ) : ?>
                                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_country, $code ); ?>>
                                                <?php echo esc_html( $name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="abox-shipping-state"><?php esc_html_e( 'State/Region', 'agent-box-orders' ); ?></label>
                                    <input type="text" id="abox-shipping-state" name="shipping[state]">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="abox-messages" class="abox-messages" style="display: none;"></div>
    </form>
</div>

<!-- Box Template -->
<script type="text/template" id="abox-box-template">
    <div class="abox-box postbox" data-box-index="{{index}}">
        <div class="postbox-header">
            <h2 class="hndle">
                <span class="dashicons dashicons-archive"></span>
                <?php esc_html_e( 'Box', 'agent-box-orders' ); ?> <span class="abox-box-number">{{index}}</span>
            </h2>
            <div class="handle-actions">
                <button type="button" class="handlediv abox-toggle-box" aria-expanded="true">
                    <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
                <button type="button" class="abox-remove-box button-link" title="<?php esc_attr_e( 'Remove Box', 'agent-box-orders' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>

        <div class="inside">
            <div class="abox-form-row">
                <label><?php esc_html_e( 'Customer Label', 'agent-box-orders' ); ?></label>
                <input type="text" class="abox-customer-label regular-text" placeholder="<?php esc_attr_e( 'e.g., John Doe, Customer #123', 'agent-box-orders' ); ?>">
            </div>

            <table class="abox-items-table widefat">
                <thead>
                    <tr>
                        <th class="abox-col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                        <th class="abox-col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                        <th class="abox-col-price"><?php esc_html_e( 'Price', 'agent-box-orders' ); ?></th>
                        <th class="abox-col-subtotal"><?php esc_html_e( 'Total', 'agent-box-orders' ); ?></th>
                        <th class="abox-col-actions"></th>
                    </tr>
                </thead>
                <tbody class="abox-items-container">
                    <!-- Items will be added here -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <button type="button" class="abox-add-item button button-secondary">
                                <span class="dashicons dashicons-plus"></span>
                                <?php esc_html_e( 'Add Item', 'agent-box-orders' ); ?>
                            </button>
                        </td>
                    </tr>
                    <tr class="abox-box-total-row">
                        <td colspan="3" class="abox-box-total-label"><?php esc_html_e( 'Box Total:', 'agent-box-orders' ); ?></td>
                        <td class="abox-box-total-value"><?php echo wc_price( 0 ); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</script>

<!-- Item Row Template -->
<script type="text/template" id="abox-item-template">
    <tr class="abox-item-row">
        <td class="abox-col-product">
            <div class="abox-product-search-wrapper">
                <div class="abox-product-search-field">
                    <input type="text" class="abox-product-search regular-text" placeholder="<?php esc_attr_e( 'Search products...', 'agent-box-orders' ); ?>" autocomplete="off">
                    <input type="hidden" class="abox-product-id" value="">
                    <input type="hidden" class="abox-variation-id" value="">
                    <input type="hidden" class="abox-product-type" value="">
                    <input type="hidden" class="abox-product-price" value="0">
                    <div class="abox-search-results"></div>
                </div>
                <div class="abox-variation-select-wrapper" style="display: none;">
                    <select class="abox-variation-select">
                        <option value=""><?php esc_html_e( 'Select variation...', 'agent-box-orders' ); ?></option>
                    </select>
                    <span class="abox-variation-loading spinner" style="display: none;"></span>
                </div>
            </div>
        </td>
        <td class="abox-col-qty">
            <input type="number" class="abox-quantity small-text" value="1" min="1" max="999" step="1">
        </td>
        <td class="abox-col-price">
            <span class="abox-price-display">—</span>
        </td>
        <td class="abox-col-subtotal">
            <span class="abox-row-total">—</span>
        </td>
        <td class="abox-col-actions">
            <button type="button" class="abox-remove-item button-link" title="<?php esc_attr_e( 'Remove', 'agent-box-orders' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </td>
    </tr>
</script>
