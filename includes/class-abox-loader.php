<?php
/**
 * Plugin loader
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Loader class
 */
class ABOX_Loader {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Ensure capabilities are set up (fixes case where activation hook didn't run)
        add_action( 'admin_init', array( 'ABOX_Capabilities', 'maybe_setup_capabilities' ) );
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Settings (always load in admin)
        new ABOX_Settings();

        // Shortcode
        new ABOX_Shortcode();

        // AJAX handlers
        new ABOX_Ajax();

        // Checkout integration
        new ABOX_Checkout();

        // Public assets
        new ABOX_Public();

        // Admin components
        if ( is_admin() ) {
            new ABOX_Admin();
            new ABOX_Meta_Box();
            new ABOX_Meta_Box_Editor();
            new ABOX_Admin_Create_Order();
            new ABOX_Payment_Metabox();
            new ABOX_Order_Filters();
        }
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'agent-box-orders',
            false,
            dirname( ABOX_PLUGIN_BASENAME ) . '/languages'
        );
    }
}
