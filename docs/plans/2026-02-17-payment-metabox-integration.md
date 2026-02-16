# Payment Metabox Integration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Move the standalone `wc-order-payment-metabox` plugin into Agent Box Orders with admin settings for managing payment statuses and collection methods dynamically.

**Architecture:** New `ABOX_Payment_Metabox` class handles the order edit metabox and order list columns. Settings use custom WooCommerce repeater fields stored as `abox_payment_statuses` and `abox_collection_methods` options. Create-order page reads from the same options instead of hardcoded values.

**Tech Stack:** PHP/WordPress, WooCommerce Settings API, vanilla JS for repeater UI

---

### Task 1: Add default options helper to ABOX_Settings

**Files:**
- Modify: `includes/class-abox-settings.php`

**Step 1: Add static methods for default payment statuses and collection methods**

Add two new static methods after `get_settings()` (after line 181):

```php
/**
 * Get default payment statuses
 *
 * @return array
 */
public static function get_default_payment_statuses() {
    return array(
        array( 'slug' => 'done', 'label' => 'Done Payment', 'bg_color' => '#74d62f', 'text_color' => '#ffffff' ),
        array( 'slug' => 'cash_cashier', 'label' => 'Cash di Cashier', 'bg_color' => '#dd3333', 'text_color' => '#ffffff' ),
        array( 'slug' => 'cod', 'label' => 'Cash on Delivery (COD)', 'bg_color' => '#dd3333', 'text_color' => '#ffffff' ),
        array( 'slug' => 'pending_payment', 'label' => 'Pending Payment', 'bg_color' => '#dd3333', 'text_color' => '#ffffff' ),
        array( 'slug' => 'partial', 'label' => 'Partial Payment', 'bg_color' => '#eeee22', 'text_color' => '#555555' ),
    );
}

/**
 * Get default collection methods
 *
 * @return array
 */
public static function get_default_collection_methods() {
    return array(
        array( 'slug' => 'postage', 'label' => 'Postage', 'bg_color' => '#f760ed', 'text_color' => '#ffffff' ),
        array( 'slug' => 'pickup_hq', 'label' => 'Pickup - HQ', 'bg_color' => '#eeee22', 'text_color' => '#555555' ),
        array( 'slug' => 'pickup_terengganu', 'label' => 'Pickup - Terengganu', 'bg_color' => '#1e73be', 'text_color' => '#ffffff' ),
        array( 'slug' => 'runner_delivered', 'label' => 'Runner Delivered', 'bg_color' => '#8224e3', 'text_color' => '#ffffff' ),
    );
}

/**
 * Get payment statuses from settings (with defaults fallback)
 *
 * @return array Array of ['slug' => '...', 'label' => '...', 'bg_color' => '...', 'text_color' => '...']
 */
public static function get_payment_statuses() {
    $statuses = get_option( 'abox_payment_statuses' );
    if ( false === $statuses || ! is_array( $statuses ) ) {
        $statuses = self::get_default_payment_statuses();
        update_option( 'abox_payment_statuses', $statuses );
    }
    return $statuses;
}

/**
 * Get collection methods from settings (with defaults fallback)
 *
 * @return array Array of ['slug' => '...', 'label' => '...', 'bg_color' => '...', 'text_color' => '...']
 */
public static function get_collection_methods() {
    $methods = get_option( 'abox_collection_methods' );
    if ( false === $methods || ! is_array( $methods ) ) {
        $methods = self::get_default_collection_methods();
        update_option( 'abox_collection_methods', $methods );
    }
    return $methods;
}
```

**Step 2: Commit**

```bash
git add includes/class-abox-settings.php
git commit -m "feat: add payment statuses and collection methods option helpers"
```

---

### Task 2: Add repeater settings UI to ABOX_Settings

**Files:**
- Modify: `includes/class-abox-settings.php`
- Create: `assets/js/admin-settings.js`
- Modify: `assets/css/admin.css`

**Step 1: Add Payment & Collection settings section to `add_settings()` method**

In `includes/class-abox-settings.php`, before the final `return $settings;` on line 140, add a new section with two custom repeater fields. Add the section after the packing list `sectionend` (after line 137):

```php
array(
    'title' => __( 'Payment & Collection Settings', 'agent-box-orders' ),
    'type'  => 'title',
    'desc'  => __( 'Configure payment statuses and collection methods available for orders.', 'agent-box-orders' ),
    'id'    => 'abox_payment_collection_section',
),
array(
    'title'   => __( 'Payment Statuses', 'agent-box-orders' ),
    'id'      => 'abox_payment_statuses',
    'type'    => 'abox_repeater',
    'default' => self::get_default_payment_statuses(),
),
array(
    'title'   => __( 'Collection Methods', 'agent-box-orders' ),
    'id'      => 'abox_collection_methods',
    'type'    => 'abox_repeater',
    'default' => self::get_default_collection_methods(),
),
array(
    'type' => 'sectionend',
    'id'   => 'abox_payment_collection_section',
),
```

**Step 2: Register the custom field type renderer**

Add to the constructor (after line 23):

```php
add_action( 'woocommerce_admin_field_abox_repeater', array( $this, 'render_repeater_field' ) );
add_action( 'woocommerce_admin_settings_sanitize_option_abox_payment_statuses', array( $this, 'sanitize_repeater' ), 10, 3 );
add_action( 'woocommerce_admin_settings_sanitize_option_abox_collection_methods', array( $this, 'sanitize_repeater' ), 10, 3 );
```

**Step 3: Add the render method**

Add this method to the class:

```php
/**
 * Render repeater field for WooCommerce settings
 *
 * @param array $value Field config.
 */
public function render_repeater_field( $value ) {
    $option_value = get_option( $value['id'], $value['default'] ?? array() );
    if ( ! is_array( $option_value ) ) {
        $option_value = $value['default'] ?? array();
    }
    $field_id = esc_attr( $value['id'] );
    ?>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label><?php echo esc_html( $value['title'] ); ?></label>
        </th>
        <td class="forminp">
            <table class="abox-repeater-table widefat" data-field-id="<?php echo $field_id; ?>">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Slug', 'agent-box-orders' ); ?></th>
                        <th><?php esc_html_e( 'Label', 'agent-box-orders' ); ?></th>
                        <th><?php esc_html_e( 'Badge Color', 'agent-box-orders' ); ?></th>
                        <th><?php esc_html_e( 'Text Color', 'agent-box-orders' ); ?></th>
                        <th><?php esc_html_e( 'Preview', 'agent-box-orders' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $option_value as $i => $row ) : ?>
                        <tr class="abox-repeater-row">
                            <td><input type="text" name="<?php echo $field_id; ?>[<?php echo $i; ?>][slug]" value="<?php echo esc_attr( $row['slug'] ?? '' ); ?>" class="abox-repeater-slug" style="width:120px;" required></td>
                            <td><input type="text" name="<?php echo $field_id; ?>[<?php echo $i; ?>][label]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" class="abox-repeater-label" style="width:200px;" required></td>
                            <td><input type="color" name="<?php echo $field_id; ?>[<?php echo $i; ?>][bg_color]" value="<?php echo esc_attr( $row['bg_color'] ?? '#dd3333' ); ?>" class="abox-repeater-bg-color"></td>
                            <td><input type="color" name="<?php echo $field_id; ?>[<?php echo $i; ?>][text_color]" value="<?php echo esc_attr( $row['text_color'] ?? '#ffffff' ); ?>" class="abox-repeater-text-color"></td>
                            <td><mark class="order-status abox-repeater-preview" style="background-color:<?php echo esc_attr( $row['bg_color'] ?? '#dd3333' ); ?>;color:<?php echo esc_attr( $row['text_color'] ?? '#ffffff' ); ?>;"><span><?php echo esc_html( $row['label'] ?? '' ); ?></span></mark></td>
                            <td><button type="button" class="button abox-repeater-remove" title="<?php esc_attr_e( 'Remove', 'agent-box-orders' ); ?>"><span class="dashicons dashicons-trash"></span></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">
                            <button type="button" class="button abox-repeater-add" data-field-id="<?php echo $field_id; ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php esc_html_e( 'Add Row', 'agent-box-orders' ); ?>
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
    <?php
}

/**
 * Sanitize repeater field data
 *
 * @param mixed  $value     The value to sanitize.
 * @param array  $option    The option array.
 * @param mixed  $raw_value The raw value.
 * @return array
 */
public function sanitize_repeater( $value, $option, $raw_value ) {
    if ( ! is_array( $raw_value ) ) {
        return array();
    }

    $sanitized = array();
    foreach ( $raw_value as $row ) {
        if ( empty( $row['slug'] ) || empty( $row['label'] ) ) {
            continue;
        }
        $sanitized[] = array(
            'slug'       => sanitize_key( $row['slug'] ),
            'label'      => sanitize_text_field( $row['label'] ),
            'bg_color'   => sanitize_hex_color( $row['bg_color'] ?? '#dd3333' ) ?: '#dd3333',
            'text_color' => sanitize_hex_color( $row['text_color'] ?? '#ffffff' ) ?: '#ffffff',
        );
    }
    return $sanitized;
}
```

**Step 4: Enqueue admin-settings.js on the settings page**

Add to the constructor:

```php
add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts' ) );
```

Add the method:

```php
/**
 * Enqueue scripts for settings page
 *
 * @param string $hook_suffix The page hook.
 */
public function enqueue_settings_scripts( $hook_suffix ) {
    if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
        return;
    }
    if ( ! isset( $_GET['section'] ) || 'agent_box_orders' !== $_GET['section'] ) {
        return;
    }
    wp_enqueue_script(
        'abox-admin-settings',
        ABOX_PLUGIN_URL . 'assets/js/admin-settings.js',
        array( 'jquery' ),
        ABOX_VERSION,
        true
    );
}
```

**Step 5: Create `assets/js/admin-settings.js`**

```javascript
(function($) {
    'use strict';

    // Add row
    $(document).on('click', '.abox-repeater-add', function() {
        var fieldId = $(this).data('field-id');
        var tbody = $(this).closest('table').find('tbody');
        var index = tbody.find('tr').length;

        var row = '<tr class="abox-repeater-row">' +
            '<td><input type="text" name="' + fieldId + '[' + index + '][slug]" value="" class="abox-repeater-slug" style="width:120px;" required></td>' +
            '<td><input type="text" name="' + fieldId + '[' + index + '][label]" value="" class="abox-repeater-label" style="width:200px;" required></td>' +
            '<td><input type="color" name="' + fieldId + '[' + index + '][bg_color]" value="#dd3333" class="abox-repeater-bg-color"></td>' +
            '<td><input type="color" name="' + fieldId + '[' + index + '][text_color]" value="#ffffff" class="abox-repeater-text-color"></td>' +
            '<td><mark class="order-status abox-repeater-preview" style="background-color:#dd3333;color:#ffffff;"><span></span></mark></td>' +
            '<td><button type="button" class="button abox-repeater-remove" title="Remove"><span class="dashicons dashicons-trash"></span></button></td>' +
            '</tr>';

        tbody.append(row);
    });

    // Remove row
    $(document).on('click', '.abox-repeater-remove', function() {
        var tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        // Re-index
        tbody.find('tr').each(function(i) {
            $(this).find('input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                }
            });
        });
    });

    // Live preview update
    $(document).on('input change', '.abox-repeater-row input', function() {
        var row = $(this).closest('.abox-repeater-row');
        var label = row.find('.abox-repeater-label').val();
        var bgColor = row.find('.abox-repeater-bg-color').val();
        var textColor = row.find('.abox-repeater-text-color').val();
        var preview = row.find('.abox-repeater-preview');

        preview.css({ 'background-color': bgColor, 'color': textColor });
        preview.find('span').text(label);
    });

})(jQuery);
```

**Step 6: Add CSS for repeater table**

Append to `assets/css/admin.css`:

```css
/* Settings Repeater Table */
.abox-repeater-table {
    max-width: 800px;
}

.abox-repeater-table th {
    padding: 8px 10px;
    font-weight: 600;
}

.abox-repeater-table td {
    padding: 6px 10px;
    vertical-align: middle;
}

.abox-repeater-table input[type="color"] {
    width: 40px;
    height: 30px;
    padding: 2px;
    cursor: pointer;
}

.abox-repeater-preview {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 3px;
    font-size: 12px;
    line-height: 1.5;
}

.abox-repeater-remove .dashicons {
    color: #a00;
    font-size: 16px;
    width: 16px;
    height: 16px;
    line-height: 1;
}

.abox-repeater-add .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    vertical-align: text-top;
    margin-right: 2px;
}
```

**Step 7: Commit**

```bash
git add includes/class-abox-settings.php assets/js/admin-settings.js assets/css/admin.css
git commit -m "feat: add repeater settings UI for payment statuses and collection methods"
```

---

### Task 3: Create ABOX_Payment_Metabox class

**Files:**
- Create: `admin/class-abox-payment-metabox.php`

**Step 1: Create the class file**

Create `admin/class-abox-payment-metabox.php`. This is a migration of the standalone `wc-order-payment-metabox` plugin, modified to read statuses/methods from `ABOX_Settings` and generate dynamic CSS from saved colors.

Key changes from the standalone plugin:
- `get_payment_statuses()` calls `ABOX_Settings::get_payment_statuses()` and builds the `slug => label` map
- `get_collection_methods()` calls `ABOX_Settings::get_collection_methods()` and builds the `slug => label` map (plus legacy keys)
- `admin_styles()` generates CSS dynamically from saved `bg_color`/`text_color` per status/method
- `get_payment_status_class()` and `get_collection_method_class()` remain the same pattern (ps-/cm- prefix)
- All metabox, save, column, and sorting logic stays the same
- Database index creation moved to plugin activation hook

```php
<?php
/**
 * Payment & Collection Metabox for WooCommerce Orders
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Payment_Metabox class
 *
 * Adds Payment Status and Collection Method dropdowns to WooCommerce orders,
 * with sortable order list columns and dynamic badge colors from settings.
 */
class ABOX_Payment_Metabox {

    /**
     * The meta key currently being sorted.
     *
     * @var string
     */
    private $sorting_meta_key = '';

    /**
     * Sort order.
     *
     * @var string
     */
    private $sorting_order = 'DESC';

    /**
     * Constructor
     */
    public function __construct() {
        // Metabox.
        add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
        add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_metabox' ) );

        // Admin columns - HPOS.
        add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_columns' ) );
        add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_order_columns' ), 10, 2 );

        // Admin columns - Legacy.
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns_legacy' ), 10, 2 );

        // Make columns sortable.
        add_filter( 'woocommerce_shop_order_list_table_sortable_columns', array( $this, 'sortable_columns' ) );
        add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'sortable_columns' ) );

        // Default hidden columns.
        add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );

        // Handle sorting - HPOS.
        add_filter( 'woocommerce_order_query_args', array( $this, 'handle_order_sorting' ) );
        add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'modify_order_query_clauses' ), 10, 3 );

        // Handle sorting - Legacy.
        add_filter( 'request', array( $this, 'handle_order_sorting_legacy' ) );
        add_filter( 'posts_clauses', array( $this, 'modify_posts_clauses' ), 10, 2 );

        // Dynamic badge styles.
        add_action( 'admin_head', array( $this, 'admin_styles' ) );
    }

    /**
     * Get payment statuses as slug => label array.
     *
     * @return array
     */
    public function get_payment_statuses() {
        $raw = ABOX_Settings::get_payment_statuses();
        $statuses = array( '' => __( '— Select —', 'agent-box-orders' ) );
        foreach ( $raw as $item ) {
            $statuses[ $item['slug'] ] = $item['label'];
        }
        return $statuses;
    }

    /**
     * Get collection methods as slug => label array.
     *
     * @return array
     */
    public function get_collection_methods() {
        $raw = ABOX_Settings::get_collection_methods();
        $methods = array( '' => __( '— Select —', 'agent-box-orders' ) );
        foreach ( $raw as $item ) {
            $methods[ $item['slug'] ] = $item['label'];
        }
        // Legacy keys for backward compatibility.
        $methods['pickup'] = __( 'Pickup', 'agent-box-orders' );
        $methods['runner'] = __( 'Runner Delivered', 'agent-box-orders' );
        return $methods;
    }

    /**
     * Get color config for a status/method type.
     *
     * @param string $type 'payment' or 'collection'.
     * @return array slug => ['bg' => '#...', 'text' => '#...']
     */
    private function get_colors( $type ) {
        $raw = 'payment' === $type
            ? ABOX_Settings::get_payment_statuses()
            : ABOX_Settings::get_collection_methods();

        $colors = array();
        foreach ( $raw as $item ) {
            $colors[ $item['slug'] ] = array(
                'bg'   => $item['bg_color'],
                'text' => $item['text_color'],
            );
        }
        return $colors;
    }

    /**
     * Output dynamic CSS for badge colors.
     */
    public function admin_styles() {
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->id, array( 'edit-shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
            return;
        }

        echo '<style>';

        // Payment status badges.
        $ps_colors = $this->get_colors( 'payment' );
        foreach ( $ps_colors as $slug => $c ) {
            printf(
                '.order-status.ps-%s { background-color: %s; color: %s; }',
                esc_attr( $slug ),
                esc_attr( $c['bg'] ),
                esc_attr( $c['text'] )
            );
        }

        // Collection method badges.
        $cm_colors = $this->get_colors( 'collection' );
        foreach ( $cm_colors as $slug => $c ) {
            printf(
                '.order-status.cm-%s { background-color: %s; color: %s; }',
                esc_attr( $slug ),
                esc_attr( $c['bg'] ),
                esc_attr( $c['text'] )
            );
        }

        // Legacy collection badges.
        echo '.order-status.cm-pickup { background-color: #eeee22; color: #555; }';
        echo '.order-status.cm-runner { background-color: #8224e3; color: #fff; }';

        echo '</style>';
    }

    /**
     * Get CSS class for payment status badge.
     *
     * @param string $status The status slug.
     * @return string
     */
    private function get_payment_status_class( $status ) {
        $colors = $this->get_colors( 'payment' );
        if ( isset( $colors[ $status ] ) ) {
            return 'ps-' . $status;
        }
        return 'ps-cod'; // fallback.
    }

    /**
     * Get CSS class for collection method badge.
     *
     * @param string $method The method slug.
     * @return string
     */
    private function get_collection_method_class( $method ) {
        $colors = $this->get_colors( 'collection' );
        $legacy = array( 'pickup', 'runner' );
        if ( isset( $colors[ $method ] ) || in_array( $method, $legacy, true ) ) {
            return 'cm-' . $method;
        }
        return 'cm-postage'; // fallback.
    }

    /**
     * Get human-friendly label for collection method.
     *
     * @param string $method The method slug.
     * @return string
     */
    private function get_collection_label( $method ) {
        $methods = $this->get_collection_methods();
        if ( isset( $methods[ $method ] ) ) {
            return $methods[ $method ];
        }
        return ucwords( str_replace( array( '_', '-' ), ' ', (string) $method ) );
    }

    /**
     * Add metabox to order edit screen.
     */
    public function add_metabox() {
        // HPOS.
        add_meta_box(
            'abox_payment_collection_metabox',
            __( 'Payment & Collection', 'agent-box-orders' ),
            array( $this, 'render_metabox' ),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );

        // Legacy.
        add_meta_box(
            'abox_payment_collection_metabox',
            __( 'Payment & Collection', 'agent-box-orders' ),
            array( $this, 'render_metabox' ),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render the metabox.
     *
     * @param WP_Post|WC_Order $post_or_order Post or order object.
     */
    public function render_metabox( $post_or_order ) {
        if ( $post_or_order instanceof WC_Order ) {
            $order = $post_or_order;
        } else {
            $order = wc_get_order( $post_or_order->ID );
        }

        if ( ! $order ) {
            return;
        }

        $payment_status    = $order->get_meta( '_payment_status', true );
        $collection_method = $order->get_meta( '_collection_method', true );
        $pickup_cod_date   = $order->get_meta( '_pickup_cod_date', true );
        $pickup_cod_time   = $order->get_meta( '_pickup_cod_time', true );

        wp_nonce_field( 'abox_payment_collection_nonce', 'abox_payment_collection_nonce' );
        ?>
        <p>
            <label for="payment_status"><strong><?php esc_html_e( 'Payment Status', 'agent-box-orders' ); ?></strong></label><br>
            <select name="payment_status" id="payment_status" style="width:100%">
                <?php foreach ( $this->get_payment_statuses() as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $payment_status, $key ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="collection_method"><strong><?php esc_html_e( 'Collection Method', 'agent-box-orders' ); ?></strong></label><br>
            <select name="collection_method" id="collection_method" style="width:100%">
                <?php foreach ( $this->get_collection_methods() as $key => $label ) :
                    $is_legacy = in_array( $key, array( 'pickup', 'runner' ), true );
                    if ( $is_legacy && $collection_method !== $key ) {
                        continue;
                    }
                ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $collection_method, $key ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="pickup_cod_date"><strong><?php esc_html_e( 'Pickup/COD Date', 'agent-box-orders' ); ?></strong></label><br>
            <input type="date" name="pickup_cod_date" id="pickup_cod_date" value="<?php echo esc_attr( $pickup_cod_date ); ?>" style="width:100%">
        </p>
        <p>
            <label for="pickup_cod_time"><strong><?php esc_html_e( 'Pickup/COD Time', 'agent-box-orders' ); ?></strong></label><br>
            <input type="time" name="pickup_cod_time" id="pickup_cod_time" value="<?php echo esc_attr( $pickup_cod_time ); ?>" style="width:100%">
        </p>
        <?php
    }

    /**
     * Save metabox data.
     *
     * @param int $order_id The order ID.
     */
    public function save_metabox( $order_id ) {
        if (
            ! isset( $_POST['abox_payment_collection_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abox_payment_collection_nonce'] ) ), 'abox_payment_collection_nonce' )
        ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $current_user = wp_get_current_user();
        $user_name    = $current_user->display_name ? $current_user->display_name : $current_user->user_login;

        // Payment Status.
        if ( isset( $_POST['payment_status'] ) ) {
            $new_status = sanitize_text_field( wp_unslash( $_POST['payment_status'] ) );
            $old_status = $order->get_meta( '_payment_status', true );

            if ( $new_status !== $old_status ) {
                $statuses  = $this->get_payment_statuses();
                $old_label = $statuses[ $old_status ] ?? __( 'Not set', 'agent-box-orders' );
                $new_label = $statuses[ $new_status ] ?? __( 'Not set', 'agent-box-orders' );

                $order->add_order_note(
                    sprintf(
                        __( 'Payment Status changed from %1$s to %2$s by %3$s', 'agent-box-orders' ),
                        '<strong>' . $old_label . '</strong>',
                        '<strong>' . $new_label . '</strong>',
                        $user_name
                    ),
                    false,
                    true
                );
                $order->update_meta_data( '_payment_status', $new_status );
            }
        }

        // Collection Method.
        if ( isset( $_POST['collection_method'] ) ) {
            $new_method = sanitize_text_field( wp_unslash( $_POST['collection_method'] ) );
            $old_method = $order->get_meta( '_collection_method', true );

            if ( $new_method !== $old_method ) {
                $old_label = $this->get_collection_label( $old_method );
                $new_label = $this->get_collection_label( $new_method );

                $order->add_order_note(
                    sprintf(
                        __( 'Collection Method changed from %1$s to %2$s by %3$s', 'agent-box-orders' ),
                        '<strong>' . $old_label . '</strong>',
                        '<strong>' . $new_label . '</strong>',
                        $user_name
                    ),
                    false,
                    true
                );
                $order->update_meta_data( '_collection_method', $new_method );
            }
        }

        // Pickup/COD Date.
        if ( isset( $_POST['pickup_cod_date'] ) ) {
            $new_date = sanitize_text_field( wp_unslash( $_POST['pickup_cod_date'] ) );
            $old_date = $order->get_meta( '_pickup_cod_date', true );

            if ( $new_date !== $old_date ) {
                $old_label = $old_date ? date( 'd/m/Y', strtotime( $old_date ) ) : __( 'Not set', 'agent-box-orders' );
                $new_label = $new_date ? date( 'd/m/Y', strtotime( $new_date ) ) : __( 'Not set', 'agent-box-orders' );

                $order->add_order_note(
                    sprintf(
                        __( 'Pickup/COD Date changed from %1$s to %2$s by %3$s', 'agent-box-orders' ),
                        '<strong>' . $old_label . '</strong>',
                        '<strong>' . $new_label . '</strong>',
                        $user_name
                    ),
                    false,
                    true
                );
                $order->update_meta_data( '_pickup_cod_date', $new_date );
            }
        }

        // Pickup/COD Time.
        if ( isset( $_POST['pickup_cod_time'] ) ) {
            $new_time = sanitize_text_field( wp_unslash( $_POST['pickup_cod_time'] ) );
            $old_time = $order->get_meta( '_pickup_cod_time', true );

            if ( $new_time !== $old_time ) {
                $old_label = $old_time ? date( 'g:i A', strtotime( $old_time ) ) : __( 'Not set', 'agent-box-orders' );
                $new_label = $new_time ? date( 'g:i A', strtotime( $new_time ) ) : __( 'Not set', 'agent-box-orders' );

                $order->add_order_note(
                    sprintf(
                        __( 'Pickup/COD Time changed from %1$s to %2$s by %3$s', 'agent-box-orders' ),
                        '<strong>' . $old_label . '</strong>',
                        '<strong>' . $new_label . '</strong>',
                        $user_name
                    ),
                    false,
                    true
                );
                $order->update_meta_data( '_pickup_cod_time', $new_time );
            }
        }

        $order->save();
    }

    /**
     * Add columns to orders list.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_order_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( 'order_status' === $key ) {
                $new_columns['payment_status']    = __( 'Payment Status', 'agent-box-orders' );
                $new_columns['collection_method']  = __( 'Collection', 'agent-box-orders' );
                $new_columns['pickup_cod_date']    = __( 'Pickup/COD Date', 'agent-box-orders' );
                $new_columns['pickup_cod_time']    = __( 'Pickup/COD Time', 'agent-box-orders' );
            }
        }
        return $new_columns;
    }

    /**
     * Render columns - HPOS.
     *
     * @param string   $column The column name.
     * @param WC_Order $order  The order object.
     */
    public function render_order_columns( $column, $order ) {
        $this->output_column_content( $column, $order );
    }

    /**
     * Render columns - Legacy.
     *
     * @param string $column  The column name.
     * @param int    $post_id The post ID.
     */
    public function render_order_columns_legacy( $column, $post_id ) {
        $order = wc_get_order( $post_id );
        if ( $order ) {
            $this->output_column_content( $column, $order );
        }
    }

    /**
     * Output column content.
     *
     * @param string   $column The column name.
     * @param WC_Order $order  The order object.
     */
    private function output_column_content( $column, $order ) {
        switch ( $column ) {
            case 'payment_status':
                $status   = $order->get_meta( '_payment_status', true );
                $statuses = $this->get_payment_statuses();
                $label    = $statuses[ $status ] ?? '—';
                $class    = $this->get_payment_status_class( $status );
                if ( $status ) {
                    printf(
                        '<mark class="order-status %s"><span>%s</span></mark>',
                        esc_attr( $class ),
                        esc_html( $label )
                    );
                } else {
                    echo '—';
                }
                break;

            case 'collection_method':
                $method = $order->get_meta( '_collection_method', true );
                $label  = $this->get_collection_label( $method );
                $class  = $this->get_collection_method_class( $method );
                if ( $method ) {
                    printf(
                        '<mark class="order-status %s"><span>%s</span></mark>',
                        esc_attr( $class ),
                        esc_html( $label )
                    );
                } else {
                    echo '—';
                }
                break;

            case 'pickup_cod_date':
                $date = $order->get_meta( '_pickup_cod_date', true );
                echo $date ? esc_html( date( 'd/m/Y', strtotime( $date ) ) ) : '—';
                break;

            case 'pickup_cod_time':
                $time = $order->get_meta( '_pickup_cod_time', true );
                echo $time ? esc_html( date( 'g:i A', strtotime( $time ) ) ) : '—';
                break;
        }
    }

    /**
     * Make columns sortable.
     *
     * @param array $columns Sortable columns.
     * @return array
     */
    public function sortable_columns( $columns ) {
        $columns['payment_status']    = 'payment_status';
        $columns['collection_method'] = 'collection_method';
        $columns['pickup_cod_date']   = 'pickup_cod_date';
        $columns['pickup_cod_time']   = 'pickup_cod_time';
        return $columns;
    }

    /**
     * Set default hidden columns.
     *
     * @param array     $hidden List of hidden columns.
     * @param WP_Screen $screen The screen object.
     * @return array
     */
    public function default_hidden_columns( $hidden, $screen ) {
        if ( isset( $screen->id ) && in_array( $screen->id, array( 'edit-shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
            $our_columns = array( 'payment_status', 'collection_method', 'pickup_cod_date', 'pickup_cod_time' );
            $hidden      = array_diff( $hidden, $our_columns );
        }
        return $hidden;
    }

    /**
     * Handle sorting by custom meta - HPOS.
     *
     * @param array $query_args Query args.
     * @return array
     */
    public function handle_order_sorting( $query_args ) {
        if ( ! isset( $query_args['orderby'] ) ) {
            return $query_args;
        }

        $meta_keys = array(
            'payment_status'    => '_payment_status',
            'collection_method' => '_collection_method',
            'pickup_cod_date'   => '_pickup_cod_date',
            'pickup_cod_time'   => '_pickup_cod_time',
        );

        if ( isset( $meta_keys[ $query_args['orderby'] ] ) ) {
            $meta_key                 = $meta_keys[ $query_args['orderby'] ];
            $this->sorting_meta_key   = $meta_key;
            $this->sorting_order      = isset( $query_args['order'] ) ? strtoupper( $query_args['order'] ) : 'DESC';
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => $meta_key,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => $meta_key,
                    'compare' => 'NOT EXISTS',
                ),
            );
            $query_args['orderby'] = 'none';
        }

        return $query_args;
    }

    /**
     * Modify HPOS query clauses for sorting.
     *
     * @param array $clauses Query clauses.
     * @param object $query  The query object.
     * @param array $args    Query args.
     * @return array
     */
    public function modify_order_query_clauses( $clauses, $query, $args ) {
        if ( empty( $this->sorting_meta_key ) ) {
            return $clauses;
        }

        global $wpdb;

        $meta_key = esc_sql( $this->sorting_meta_key );
        $order    = 'ASC' === $this->sorting_order ? 'ASC' : 'DESC';

        $clauses['join']    .= " LEFT JOIN {$wpdb->prefix}wc_orders_meta AS sort_meta ON {$wpdb->prefix}wc_orders.id = sort_meta.order_id AND sort_meta.meta_key = '{$meta_key}'";
        $clauses['orderby']  = "CASE WHEN sort_meta.meta_value IS NULL OR sort_meta.meta_value = '' THEN 1 ELSE 0 END ASC, sort_meta.meta_value {$order}";

        $this->sorting_meta_key = '';

        return $clauses;
    }

    /**
     * Handle sorting by custom meta - Legacy.
     *
     * @param array $vars Request vars.
     * @return array
     */
    public function handle_order_sorting_legacy( $vars ) {
        global $pagenow;

        if ( 'edit.php' !== $pagenow || ! isset( $vars['orderby'] ) ) {
            return $vars;
        }

        $meta_keys = array(
            'payment_status'    => '_payment_status',
            'collection_method' => '_collection_method',
            'pickup_cod_date'   => '_pickup_cod_date',
            'pickup_cod_time'   => '_pickup_cod_time',
        );

        if ( isset( $meta_keys[ $vars['orderby'] ] ) ) {
            $meta_key               = $meta_keys[ $vars['orderby'] ];
            $this->sorting_meta_key = $meta_key;
            $this->sorting_order    = isset( $vars['order'] ) ? strtoupper( $vars['order'] ) : 'DESC';

            $vars['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => $meta_key,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => $meta_key,
                    'compare' => 'NOT EXISTS',
                ),
            );
            $vars['orderby'] = 'none';
        }

        return $vars;
    }

    /**
     * Modify Legacy query clauses for sorting.
     *
     * @param array    $clauses Query clauses.
     * @param WP_Query $query   The query object.
     * @return array
     */
    public function modify_posts_clauses( $clauses, $query ) {
        if ( empty( $this->sorting_meta_key ) || ! is_admin() ) {
            return $clauses;
        }

        global $wpdb;

        $meta_key = esc_sql( $this->sorting_meta_key );
        $order    = 'ASC' === $this->sorting_order ? 'ASC' : 'DESC';

        $clauses['join']    .= " LEFT JOIN {$wpdb->postmeta} AS sort_meta ON {$wpdb->posts}.ID = sort_meta.post_id AND sort_meta.meta_key = '{$meta_key}'";
        $clauses['orderby']  = "CASE WHEN sort_meta.meta_value IS NULL OR sort_meta.meta_value = '' THEN 1 ELSE 0 END ASC, sort_meta.meta_value {$order}";

        $this->sorting_meta_key = '';

        return $clauses;
    }

    /**
     * Create database indexes for sorting performance.
     * Called on plugin activation.
     */
    public static function create_indexes() {
        global $wpdb;

        // HPOS meta table.
        $hpos_table  = $wpdb->prefix . 'wc_orders_meta';
        $hpos_index  = 'idx_payment_collection_sort';
        $hpos_exists = $wpdb->get_var( "SHOW INDEX FROM {$hpos_table} WHERE Key_name = '{$hpos_index}'" );
        if ( ! $hpos_exists ) {
            $wpdb->query( "ALTER TABLE {$hpos_table} ADD INDEX {$hpos_index} (meta_key, meta_value(50))" );
        }

        // Legacy postmeta table.
        $legacy_index  = 'idx_payment_collection_sort';
        $legacy_exists = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = '{$legacy_index}'" );
        if ( ! $legacy_exists ) {
            $wpdb->query( "ALTER TABLE {$wpdb->postmeta} ADD INDEX {$legacy_index} (meta_key, meta_value(50))" );
        }
    }
}
```

**Step 2: Commit**

```bash
git add admin/class-abox-payment-metabox.php
git commit -m "feat: add ABOX_Payment_Metabox class migrated from standalone plugin"
```

---

### Task 4: Wire up the new class in main plugin file

**Files:**
- Modify: `agent-box-orders.php:85-90` (inside `load_dependencies()` in the `is_admin()` block)
- Modify: `agent-box-orders.php:99` (activation hook)

**Step 1: Add require_once for ABOX_Payment_Metabox**

Add after line 89 (after `class-abox-admin-create-order.php` require):

```php
require_once ABOX_PLUGIN_DIR . 'admin/class-abox-payment-metabox.php';
```

**Step 2: Add activation hook for database indexes**

Add after line 99 (after `ABOX_Capabilities::activate` hook):

```php
register_activation_hook( __FILE__, array( 'ABOX_Payment_Metabox', 'create_indexes' ) );
```

**Step 3: Commit**

```bash
git add agent-box-orders.php
git commit -m "feat: load ABOX_Payment_Metabox and register activation hook for DB indexes"
```

---

### Task 5: Update create-order page to use dynamic options

**Files:**
- Modify: `admin/views/create-order.php:191-211`

**Step 1: Replace hardcoded payment status options (lines 193-200)**

Replace:
```php
<select id="abox-payment-status" name="payment_status" style="width:100%;">
    <option value=""><?php esc_html_e( '— Select —', 'agent-box-orders' ); ?></option>
    <option value="pending"><?php esc_html_e( 'Pending Payment', 'agent-box-orders' ); ?></option>
    <option value="done"><?php esc_html_e( 'Done Payment', 'agent-box-orders' ); ?></option>
    <option value="cash_cashier"><?php esc_html_e( 'Cash di Cashier', 'agent-box-orders' ); ?></option>
    <option value="cod"><?php esc_html_e( 'Cash on Delivery (COD)', 'agent-box-orders' ); ?></option>
    <option value="partial"><?php esc_html_e( 'Partial Payment', 'agent-box-orders' ); ?></option>
</select>
```

With:
```php
<select id="abox-payment-status" name="payment_status" style="width:100%;">
    <option value=""><?php esc_html_e( '— Select —', 'agent-box-orders' ); ?></option>
    <?php foreach ( ABOX_Settings::get_payment_statuses() as $status ) : ?>
        <option value="<?php echo esc_attr( $status['slug'] ); ?>">
            <?php echo esc_html( $status['label'] ); ?>
        </option>
    <?php endforeach; ?>
</select>
```

**Step 2: Replace hardcoded collection method options (lines 205-211)**

Replace:
```php
<select id="abox-collection-method" name="collection_method" style="width:100%;">
    <option value=""><?php esc_html_e( '— Select —', 'agent-box-orders' ); ?></option>
    <option value="postage"><?php esc_html_e( 'Postage', 'agent-box-orders' ); ?></option>
    <option value="pickup_hq"><?php esc_html_e( 'Pickup - HQ', 'agent-box-orders' ); ?></option>
    <option value="pickup_terengganu"><?php esc_html_e( 'Pickup - Terengganu', 'agent-box-orders' ); ?></option>
    <option value="runner_delivered"><?php esc_html_e( 'Runner Delivered', 'agent-box-orders' ); ?></option>
</select>
```

With:
```php
<select id="abox-collection-method" name="collection_method" style="width:100%;">
    <option value=""><?php esc_html_e( '— Select —', 'agent-box-orders' ); ?></option>
    <?php foreach ( ABOX_Settings::get_collection_methods() as $method ) : ?>
        <option value="<?php echo esc_attr( $method['slug'] ); ?>">
            <?php echo esc_html( $method['label'] ); ?>
        </option>
    <?php endforeach; ?>
</select>
```

**Step 3: Commit**

```bash
git add admin/views/create-order.php
git commit -m "feat: use dynamic payment statuses and collection methods from settings"
```

---

### Task 6: Manual verification

**Step 1: Deactivate the standalone plugin**

Go to WordPress admin > Plugins and deactivate "WC Order Payment Metabox".

**Step 2: Verify Settings page**

Navigate to WooCommerce > Settings > Advanced > Agent Box Orders.
Expected: See "Payment & Collection Settings" section with two repeater tables pre-populated with default values. Add/remove rows should work. Color pickers should update the preview badges live.

**Step 3: Verify Create Box Order page**

Navigate to WooCommerce > Create Box Order.
Expected: Payment Status and Collection Method dropdowns show options from settings (not hardcoded).

**Step 4: Verify Order Edit screen**

Open any existing WooCommerce order.
Expected: "Payment & Collection" metabox appears in sidebar with dropdowns populated from settings. Saving changes adds order notes.

**Step 5: Verify Order List columns**

Navigate to WooCommerce > Orders.
Expected: Payment Status, Collection, Pickup/COD Date, Pickup/COD Time columns appear with colored badges. Columns are sortable.

**Step 6: Test adding new status**

Go to settings, add a new payment status (e.g. slug: `bank_transfer`, label: `Bank Transfer`, color: `#0073aa`). Save. Verify it appears in both the metabox dropdown and create-order dropdown.
