<?php
/**
 * Shortcode handler
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Shortcode class
 */
class ABOX_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'abox_order_form', array( $this, 'render_order_form' ) );

        // Handle login errors - redirect back to our page with error message
        add_action( 'wp_login_failed', array( $this, 'handle_login_failed' ) );
        add_filter( 'authenticate', array( $this, 'handle_empty_credentials' ), 30, 3 );
    }

    /**
     * Handle failed login attempts
     *
     * @param string $username The username that failed.
     */
    public function handle_login_failed( $username ) {
        // Only handle if login came from our form
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( ! isset( $_POST['abox_login_source'] ) || 'agent_form' !== $_POST['abox_login_source'] ) {
            return;
        }

        $referrer = wp_get_referer();
        if ( $referrer ) {
            $referrer_without_query = strtok( $referrer, '?' );
            $redirect_url = add_query_arg( 'abox_login_error', 'invalid', $referrer_without_query );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }

    /**
     * Handle empty username or password
     *
     * @param WP_User|WP_Error|null $user     User object or error.
     * @param string                $username Username.
     * @param string                $password Password.
     * @return WP_User|WP_Error
     */
    public function handle_empty_credentials( $user, $username, $password ) {
        // Only handle if login came from our form
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( ! isset( $_POST['abox_login_source'] ) || 'agent_form' !== $_POST['abox_login_source'] ) {
            return $user;
        }

        $referrer = wp_get_referer();
        if ( $referrer && ( empty( $username ) || empty( $password ) ) ) {
            $referrer_without_query = strtok( $referrer, '?' );
            $error_type = empty( $username ) ? 'empty_username' : 'empty_password';
            $redirect_url = add_query_arg( 'abox_login_error', $error_type, $referrer_without_query );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        return $user;
    }

    /**
     * Render the order form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_order_form( $atts ) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'title' => __( 'Create Box Order', 'agent-box-orders' ),
            ),
            $atts,
            'abox_order_form'
        );

        // Get settings
        $settings = ABOX_Settings::get_settings();

        // Check if user is logged in (skip if guest mode enabled)
        if ( ! $settings['guest_mode'] && ! is_user_logged_in() ) {
            return $this->render_login_message();
        }

        // Check if user has permission (skip if guest mode enabled)
        if ( ! $settings['guest_mode'] && ! ABOX_Capabilities::current_user_can_create_orders() ) {
            return $this->render_permission_denied();
        }

        // Start output buffering
        ob_start();

        // Include the form template
        include ABOX_PLUGIN_DIR . 'public/views/order-form.php';

        return ob_get_clean();
    }

    /**
     * Render login form for non-logged-in users
     *
     * @return string
     */
    private function render_login_message() {
        $redirect_url = get_permalink();
        $login_error  = isset( $_GET['abox_login_error'] ) ? sanitize_text_field( wp_unslash( $_GET['abox_login_error'] ) ) : '';

        ob_start();
        ?>
        <div class="abox-login-container">
            <div class="abox-login-box">
                <div class="abox-login-header">
                    <h2><?php esc_html_e( 'Agent Login', 'agent-box-orders' ); ?></h2>
                    <p><?php esc_html_e( 'Please log in to create box orders.', 'agent-box-orders' ); ?></p>
                </div>

                <?php if ( $login_error ) : ?>
                <div class="abox-login-error">
                    <?php echo esc_html( $this->get_login_error_message( $login_error ) ); ?>
                </div>
                <?php endif; ?>

                <div class="abox-login-form">
                    <?php
                    wp_login_form(
                        array(
                            'redirect'       => $redirect_url,
                            'form_id'        => 'abox-loginform',
                            'label_username' => __( 'Username or Email', 'agent-box-orders' ),
                            'label_password' => __( 'Password', 'agent-box-orders' ),
                            'label_remember' => __( 'Remember Me', 'agent-box-orders' ),
                            'label_log_in'   => __( 'Log In', 'agent-box-orders' ),
                            'remember'       => true,
                        )
                    );
                    ?>
                    <script>
                    (function() {
                        var form = document.getElementById('abox-loginform');
                        if (form) {
                            // Add hidden field for form identification
                            var hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'abox_login_source';
                            hidden.value = 'agent_form';
                            form.appendChild(hidden);

                            // Add password visibility toggle
                            var passwordInput = form.querySelector('input[type="password"]');
                            if (passwordInput) {
                                // Wrap password input
                                var wrapper = document.createElement('div');
                                wrapper.className = 'abox-password-wrapper';
                                passwordInput.parentNode.insertBefore(wrapper, passwordInput);
                                wrapper.appendChild(passwordInput);

                                // Create toggle button
                                var toggle = document.createElement('button');
                                toggle.type = 'button';
                                toggle.className = 'abox-password-toggle';
                                toggle.innerHTML = '<span class="dashicons dashicons-visibility"></span>';
                                toggle.setAttribute('aria-label', 'Show password');
                                wrapper.appendChild(toggle);

                                // Toggle password visibility
                                toggle.addEventListener('click', function() {
                                    var icon = toggle.querySelector('.dashicons');
                                    if (passwordInput.type === 'password') {
                                        passwordInput.type = 'text';
                                        icon.classList.remove('dashicons-visibility');
                                        icon.classList.add('dashicons-hidden');
                                        toggle.setAttribute('aria-label', 'Hide password');
                                    } else {
                                        passwordInput.type = 'password';
                                        icon.classList.remove('dashicons-hidden');
                                        icon.classList.add('dashicons-visibility');
                                        toggle.setAttribute('aria-label', 'Show password');
                                    }
                                });
                            }
                        }
                    })();
                    </script>
                </div>

                <?php if ( get_option( 'users_can_register' ) ) : ?>
                <div class="abox-login-footer">
                    <a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Register', 'agent-box-orders' ); ?></a>
                    <span class="abox-separator">|</span>
                    <a href="<?php echo esc_url( wp_lostpassword_url( $redirect_url ) ); ?>"><?php esc_html_e( 'Lost your password?', 'agent-box-orders' ); ?></a>
                </div>
                <?php else : ?>
                <div class="abox-login-footer">
                    <a href="<?php echo esc_url( wp_lostpassword_url( $redirect_url ) ); ?>"><?php esc_html_e( 'Lost your password?', 'agent-box-orders' ); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render permission denied message
     *
     * @return string
     */
    private function render_permission_denied() {
        return sprintf(
            '<div class="abox-notice abox-notice-error"><p>%s</p></div>',
            esc_html__( 'You do not have permission to create box orders. Please contact an administrator.', 'agent-box-orders' )
        );
    }

    /**
     * Get login error message based on error code
     *
     * @param string $error_code The error code.
     * @return string
     */
    private function get_login_error_message( $error_code ) {
        $messages = array(
            'invalid'        => __( 'Invalid username or password. Please try again.', 'agent-box-orders' ),
            'empty_username' => __( 'Please enter your username.', 'agent-box-orders' ),
            'empty_password' => __( 'Please enter your password.', 'agent-box-orders' ),
        );

        return isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : $messages['invalid'];
    }
}
