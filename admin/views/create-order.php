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
                                    <option value="<?php echo esc_attr( str_replace( 'wc-', '', $status ) ); ?>" <?php selected( 'wc-processing', $status ); ?>>
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

                        <div id="abox-guest-billing" class="abox-guest-billing">
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
                <label><?php esc_html_e( 'Customer Label', 'agent-box-orders' ); ?> <span class="required">*</span></label>
                <input type="text" class="abox-customer-label regular-text" placeholder="<?php esc_attr_e( 'e.g., John Doe, Customer #123', 'agent-box-orders' ); ?>" required>
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
                <input type="text" class="abox-product-search regular-text" placeholder="<?php esc_attr_e( 'Search products...', 'agent-box-orders' ); ?>" autocomplete="off">
                <input type="hidden" class="abox-product-id" value="">
                <input type="hidden" class="abox-variation-id" value="">
                <input type="hidden" class="abox-product-type" value="">
                <input type="hidden" class="abox-product-price" value="0">
                <div class="abox-search-results"></div>
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
