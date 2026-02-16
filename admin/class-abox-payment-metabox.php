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
     * @param array  $clauses Query clauses.
     * @param object $query   The query object.
     * @param array  $args    Query args.
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
