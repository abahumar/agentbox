<?php
/**
 * Plugin Name: Agent Box Orders for WooCommerce
 * Plugin URI: https://abahumar.com/agent-box-orders
 * Description: Allows sales agents to create multi-customer box orders for WooCommerce.
 * Version: 1.3.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Abahumar
 * Author URI: https://abahumar.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agent-box-orders
 * Domain Path: /languages
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'ABOX_VERSION', '1.3.0' );
define( 'ABOX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABOX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ABOX_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// HPOS Compatibility Declaration
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

/**
 * Main plugin class
 */
final class Agent_Box_Orders {

    /**
     * Single instance of the class
     *
     * @var Agent_Box_Orders
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Agent_Box_Orders
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ABOX_PLUGIN_DIR . 'includes/class-abox-capabilities.php';
        require_once ABOX_PLUGIN_DIR . 'includes/class-abox-settings.php';
        require_once ABOX_PLUGIN_DIR . 'includes/class-abox-shortcode.php';
        require_once ABOX_PLUGIN_DIR . 'includes/class-abox-ajax.php';
        require_once ABOX_PLUGIN_DIR . 'includes/class-abox-checkout.php';
        require_once ABOX_PLUGIN_DIR . 'includes/class-abox-loader.php';

        if ( is_admin() ) {
            require_once ABOX_PLUGIN_DIR . 'admin/class-abox-admin.php';
            require_once ABOX_PLUGIN_DIR . 'admin/class-abox-meta-box.php';
            require_once ABOX_PLUGIN_DIR . 'admin/class-abox-meta-box-editor.php';
            require_once ABOX_PLUGIN_DIR . 'admin/class-abox-admin-create-order.php';
            require_once ABOX_PLUGIN_DIR . 'admin/class-abox-payment-metabox.php';
        }

        require_once ABOX_PLUGIN_DIR . 'public/class-abox-public.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( 'ABOX_Capabilities', 'activate' ) );
        register_activation_hook( __FILE__, array( 'ABOX_Payment_Metabox', 'create_indexes' ) );
        register_deactivation_hook( __FILE__, array( 'ABOX_Capabilities', 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 20 );
    }

    /**
     * Initialize plugin after plugins loaded
     */
    public function init_plugin() {
        // Initialize loader
        new ABOX_Loader();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new Exception( 'Cannot unserialize singleton' );
    }
}

/**
 * Check if WooCommerce is active and initialize plugin
 */
function abox_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'abox_woocommerce_missing_notice' );
        return;
    }

    Agent_Box_Orders::get_instance();
}
add_action( 'plugins_loaded', 'abox_init', 10 );

/**
 * Admin notice for missing WooCommerce
 */
function abox_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: %s: WooCommerce plugin name */
                esc_html__( 'Agent Box Orders requires %s to be installed and active.', 'agent-box-orders' ),
                '<strong>WooCommerce</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}
