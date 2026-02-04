<?php
/**
 * Order form template
 *
 * @package Agent_Box_Orders
 *
 * @var array  $atts     Shortcode attributes
 * @var array  $settings Plugin settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="abox-order-form-wrapper">
    <div class="abox-header">
        <h2><?php echo esc_html( $atts['title'] ); ?></h2>
        <p class="abox-agent-info">
            <?php
            printf(
                /* translators: %s: agent display name */
                esc_html__( 'Logged in as: %s', 'agent-box-orders' ),
                '<strong>' . esc_html( $current_user->display_name ) . '</strong>'
            );
            ?>
        </p>
    </div>

    <form id="abox-order-form" method="post">
        <div id="abox-boxes-container">
            <!-- Boxes will be added here dynamically via JavaScript -->
        </div>

        <div class="abox-form-actions">
            <button type="button" id="abox-add-box-btn" class="button abox-add-box">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Box', 'agent-box-orders' ); ?>
            </button>

            <div class="abox-form-summary">
                <div class="abox-summary-row">
                    <span class="abox-summary-label"><?php esc_html_e( 'Total Boxes:', 'agent-box-orders' ); ?></span>
                    <span id="abox-total-boxes" class="abox-summary-value">0</span>
                </div>
                <div class="abox-summary-row">
                    <span class="abox-summary-label"><?php esc_html_e( 'Total Items:', 'agent-box-orders' ); ?></span>
                    <span id="abox-total-items" class="abox-summary-value">0</span>
                </div>
                <div class="abox-summary-row abox-summary-total">
                    <span class="abox-summary-label"><?php esc_html_e( 'Estimated Total:', 'agent-box-orders' ); ?></span>
                    <span id="abox-grand-total" class="abox-summary-value"><?php echo wc_price( 0 ); ?></span>
                </div>
            </div>

            <button type="submit" id="abox-submit-btn" class="button button-primary abox-submit">
                <?php esc_html_e( 'Proceed to Checkout', 'agent-box-orders' ); ?>
            </button>
        </div>

        <div id="abox-messages" class="abox-messages" style="display: none;"></div>
    </form>
</div>

<!-- Box Template (hidden, used by JavaScript) -->
<script type="text/template" id="abox-box-template">
    <div class="abox-box" data-box-index="{{index}}">
        <div class="abox-box-header">
            <h3>
                <span class="abox-box-icon dashicons dashicons-archive"></span>
                <span class="abox-box-title"><?php esc_html_e( 'Box', 'agent-box-orders' ); ?> <span class="abox-box-number">{{index}}</span></span>
            </h3>
            <button type="button" class="abox-remove-box button-link" title="<?php esc_attr_e( 'Remove Box', 'agent-box-orders' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>

        <div class="abox-box-content">
            <div class="abox-label-row">
                <label for="abox-label-{{index}}"><?php esc_html_e( 'Customer Label', 'agent-box-orders' ); ?> <span class="required">*</span></label>
                <input type="text"
                       id="abox-label-{{index}}"
                       class="abox-customer-label"
                       placeholder="<?php esc_attr_e( 'e.g., John Doe, Customer #123', 'agent-box-orders' ); ?>"
                       required>
            </div>

            <div class="abox-items-section">
                <div class="abox-items-header">
                    <span class="abox-col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></span>
                    <span class="abox-col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></span>
                    <span class="abox-col-price"><?php esc_html_e( 'Price', 'agent-box-orders' ); ?></span>
                    <span class="abox-col-subtotal"><?php esc_html_e( 'Subtotal', 'agent-box-orders' ); ?></span>
                    <span class="abox-col-actions"></span>
                </div>

                <div class="abox-items-container">
                    <!-- Item rows will be added here -->
                </div>

                <button type="button" class="abox-add-item button button-secondary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e( 'Add Item', 'agent-box-orders' ); ?>
                </button>
            </div>

            <div class="abox-box-footer">
                <span class="abox-box-total-label"><?php esc_html_e( 'Box Total:', 'agent-box-orders' ); ?></span>
                <span class="abox-box-total-value"><?php echo wc_price( 0 ); ?></span>
            </div>
        </div>
    </div>
</script>

<!-- Item Row Template (hidden, used by JavaScript) -->
<script type="text/template" id="abox-item-template">
    <div class="abox-item-row">
        <div class="abox-col-product">
            <div class="abox-product-search-wrapper">
                <input type="text"
                       class="abox-product-search"
                       placeholder="<?php esc_attr_e( 'Search products...', 'agent-box-orders' ); ?>"
                       autocomplete="off">
                <input type="hidden" class="abox-product-id" value="">
                <input type="hidden" class="abox-variation-id" value="">
                <input type="hidden" class="abox-product-type" value="">
                <input type="hidden" class="abox-product-price" value="0">
                <div class="abox-search-results"></div>
                <div class="abox-variation-select-wrapper" style="display: none;">
                    <select class="abox-variation-select">
                        <option value=""><?php esc_html_e( 'Select variation...', 'agent-box-orders' ); ?></option>
                    </select>
                    <span class="abox-variation-loading" style="display: none;"><?php esc_html_e( 'Loading...', 'agent-box-orders' ); ?></span>
                </div>
            </div>
        </div>
        <div class="abox-col-qty">
            <input type="number"
                   class="abox-quantity"
                   value="1"
                   min="1"
                   max="999"
                   step="1">
        </div>
        <div class="abox-col-price">
            <span class="abox-price-display">-</span>
        </div>
        <div class="abox-col-subtotal">
            <span class="abox-row-total">-</span>
        </div>
        <div class="abox-col-actions">
            <button type="button" class="abox-remove-item button-link" title="<?php esc_attr_e( 'Remove Item', 'agent-box-orders' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    </div>
</script>
