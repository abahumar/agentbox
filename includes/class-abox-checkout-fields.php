<?php
/**
 * Checkout Fields Integration
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ABOX_Checkout_Fields class
 */
class ABOX_Checkout_Fields {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_checkout_fields' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_checkout_fields' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add collection fields to checkout
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function add_checkout_fields( $fields ) {
		$collection_methods = ABOX_Settings::get_collection_methods();

		// Don't add fields if no collection methods configured
		if ( empty( $collection_methods ) ) {
			return $fields;
		}

		// Build options array for select field
		$method_options = array( '' => __( '— Select Collection Method —', 'agent-box-orders' ) );
		foreach ( $collection_methods as $method ) {
			$method_options[ $method['slug'] ] = $method['label'];
		}

		// Add collection method field
		$fields['billing']['collection_method'] = array(
			'type'     => 'select',
			'label'    => __( 'Collection Method', 'agent-box-orders' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'priority' => 25,
			'options'  => $method_options,
		);

		// Add pickup date field
		$fields['billing']['pickup_date'] = array(
			'type'              => 'date',
			'label'             => __( 'Pickup Date', 'agent-box-orders' ),
			'required'          => false,
			'class'             => array( 'form-row-wide', 'abox-pickup-field' ),
			'priority'          => 26,
			'custom_attributes' => array(
				'min' => gmdate( 'Y-m-d' ),
			),
		);

		// Add pickup time field
		$fields['billing']['pickup_time'] = array(
			'type'     => 'time',
			'label'    => __( 'Pickup Time', 'agent-box-orders' ),
			'required' => false,
			'class'    => array( 'form-row-wide', 'abox-pickup-field' ),
			'priority' => 27,
		);

		return $fields;
	}

	/**
	 * Validate checkout fields
	 */
	public function validate_checkout_fields() {
		// Placeholder - will implement next
	}

	/**
	 * Save checkout fields to order meta
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_checkout_fields( $order_id ) {
		// Placeholder - will implement next
	}

	/**
	 * Enqueue scripts for conditional field display
	 */
	public function enqueue_scripts() {
		// Placeholder - will implement next
	}
}
