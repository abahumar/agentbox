<?php
/**
 * Order list filter dropdowns for Payment Status and Collection Method
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ABOX_Order_Filters class
 *
 * Adds filter dropdowns above the WooCommerce admin orders list
 * for Payment Status and Collection Method.
 */
class ABOX_Order_Filters {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Render dropdowns - HPOS.
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'render_filters' ) );

		// Render dropdowns - Legacy.
		add_action( 'restrict_manage_posts', array( $this, 'render_filters_legacy' ) );

		// Apply filtering - HPOS.
		add_filter( 'woocommerce_order_query_args', array( $this, 'apply_filters_hpos' ) );

		// Apply filtering - Legacy.
		add_filter( 'request', array( $this, 'apply_filters_legacy' ) );
	}

	/**
	 * Render filter dropdowns - HPOS orders screen.
	 */
	public function render_filters() {
		$this->output_dropdowns();
	}

	/**
	 * Render filter dropdowns - Legacy orders screen.
	 *
	 * @param string $post_type Current post type.
	 */
	public function render_filters_legacy( $post_type ) {
		if ( 'shop_order' !== $post_type ) {
			return;
		}
		$this->output_dropdowns();
	}

	/**
	 * Output the filter dropdown HTML.
	 */
	private function output_dropdowns() {
		$current_payment_status    = isset( $_GET['_payment_status'] ) ? sanitize_text_field( wp_unslash( $_GET['_payment_status'] ) ) : '';
		$current_collection_method = isset( $_GET['_collection_method'] ) ? sanitize_text_field( wp_unslash( $_GET['_collection_method'] ) ) : '';

		$payment_statuses    = ABOX_Settings::get_payment_statuses();
		$collection_methods  = ABOX_Settings::get_collection_methods();

		// Payment Status dropdown.
		echo '<select name="_payment_status" id="filter-by-payment-status">';
		echo '<option value="">' . esc_html__( 'Payment Status', 'agent-box-orders' ) . '</option>';
		foreach ( $payment_statuses as $status ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $status['slug'] ),
				selected( $current_payment_status, $status['slug'], false ),
				esc_html( $status['label'] )
			);
		}
		echo '</select>';

		// Collection Method dropdown.
		echo '<select name="_collection_method" id="filter-by-collection-method">';
		echo '<option value="">' . esc_html__( 'Collection Method', 'agent-box-orders' ) . '</option>';
		foreach ( $collection_methods as $method ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $method['slug'] ),
				selected( $current_collection_method, $method['slug'], false ),
				esc_html( $method['label'] )
			);
		}
		echo '</select>';
	}

	/**
	 * Apply filter to HPOS order query.
	 *
	 * @param array $query_args WooCommerce order query args.
	 * @return array
	 */
	public function apply_filters_hpos( $query_args ) {
		$payment_status    = isset( $_GET['_payment_status'] ) ? sanitize_text_field( wp_unslash( $_GET['_payment_status'] ) ) : '';
		$collection_method = isset( $_GET['_collection_method'] ) ? sanitize_text_field( wp_unslash( $_GET['_collection_method'] ) ) : '';

		if ( $payment_status || $collection_method ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}

			if ( $payment_status ) {
				$query_args['meta_query'][] = array(
					'key'     => '_payment_status',
					'value'   => $payment_status,
					'compare' => '=',
				);
			}

			if ( $collection_method ) {
				$query_args['meta_query'][] = array(
					'key'     => '_collection_method',
					'value'   => $collection_method,
					'compare' => '=',
				);
			}

			if ( count( $query_args['meta_query'] ) > 1 ) {
				$query_args['meta_query']['relation'] = 'AND';
			}
		}

		return $query_args;
	}

	/**
	 * Apply filter to Legacy post query.
	 *
	 * @param array $vars Request vars.
	 * @return array
	 */
	public function apply_filters_legacy( $vars ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'shop_order' !== $typenow ) {
			return $vars;
		}

		$payment_status    = isset( $_GET['_payment_status'] ) ? sanitize_text_field( wp_unslash( $_GET['_payment_status'] ) ) : '';
		$collection_method = isset( $_GET['_collection_method'] ) ? sanitize_text_field( wp_unslash( $_GET['_collection_method'] ) ) : '';

		if ( $payment_status && $collection_method ) {
			// Both filters active — use meta_query with AND.
			$vars['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_payment_status',
					'value'   => $payment_status,
					'compare' => '=',
				),
				array(
					'key'     => '_collection_method',
					'value'   => $collection_method,
					'compare' => '=',
				),
			);
		} elseif ( $payment_status ) {
			$vars['meta_key']   = '_payment_status';
			$vars['meta_value'] = $payment_status;
		} elseif ( $collection_method ) {
			$vars['meta_key']   = '_collection_method';
			$vars['meta_value'] = $collection_method;
		}

		return $vars;
	}
}
