<?php
/**
 * CoCart - Setup Wizard.
 *
 * Takes users through some basic steps to setup their headless store.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin
 * @since   3.1.0 Introduced.
 * @version 4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use CoCart\Help;
use CoCart\Logger;
use CoCart\Admin\Notices;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SetupWizard {

	/**
	 * Current step
	 *
	 * @access private
	 *
	 * @var string
	 */
	private $step = '';

	/**
	 * Steps for the setup wizard
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Tweets user can optionally send after setup.
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $tweets = array(
		'Cha ching. I just set up a headless store with @WooCommerce and @cocartapi!',
		'Someone give me high five, I just set up a headless store with @WooCommerce and @cocartapi!',
		'Want to build a fast headless store like me? Checkout @cocartapi - Designed for @WooCommerce.',
		'Build headless stores, without building an API. Checkout @cocartapi - Designed for @WooCommerce.',
	);

	/**
	 * Setup Wizard.
	 *
	 * @access public
	 */
	public function __construct() {
		if ( apply_filters( 'cocart_enable_setup_wizard', true ) && current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// Run transfer sessions in the background when called.
		add_action( 'cocart_run_transfer_sessions', 'cocart_transfer_sessions' );
	} // END __construct()

	/**
	 * Add admin menus/screens.
	 *
	 * @access public
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'cocart-setup', '' );
	} // END admin_menus()

	/**
	 * Register/enqueue scripts and styles for the Setup Wizard.
	 *
	 * Hooked onto 'admin_enqueue_scripts'.
	 *
	 * @access public
	 */
	public function enqueue_scripts() {
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = COCART_VERSION;

		wp_enqueue_style( 'cocart-setup', COCART_ADMIN_URL_PATH . '/assets/css/admin/cocart-setup.css', array( 'dashicons', 'install' ), $version );
		wp_style_add_data( 'cocart-setup', 'rtl', 'replace' );
		if ( $suffix ) {
			wp_style_add_data( 'cocart-setup', 'suffix', '.min' );
		}
	} // END enqueue_scripts()

	/**
	 * Show the setup wizard.
	 *
	 * @access public
	 *
	 * @since 3.1.0 Introduced.
	 * @since 4.0.0 Added settings step.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'cocart-setup' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$default_steps = array(
			'store_setup' => array(
				'name'    => __( 'Store setup', 'cart-rest-api-for-woocommerce' ),
				'view'    => array( $this, 'cocart_setup_wizard_store_setup' ),
				'handler' => array( $this, 'cocart_setup_wizard_store_setup_save' ),
			),
			'sessions'    => array(
				'name'    => __( 'Sessions', 'cart-rest-api-for-woocommerce' ),
				'view'    => array( $this, 'cocart_setup_wizard_sessions' ),
				'handler' => array( $this, 'cocart_setup_wizard_sessions_save' ),
			),
			'settings'    => array(
				'name'    => __( 'Settings', 'cart-rest-api-for-woocommerce' ),
				'view'    => array( $this, 'cocart_setup_wizard_settings' ),
				'handler' => array( $this, 'cocart_setup_wizard_settings_save' ),
			),
			'ready'       => array(
				'name'    => __( 'Ready!', 'cart-rest-api-for-woocommerce' ),
				'view'    => array( $this, 'cocart_setup_wizard_ready' ),
				'handler' => '',
			),
		);

		$this->steps = apply_filters( 'cocart_setup_wizard_steps', $default_steps );
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	} // END setup_wizard()

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @access public
	 *
	 * @param string $step slug (default: current step).
	 *
	 * @return string URL for next step if a next step exists.
	 *                Admin URL if it's the last step.
	 *                Empty string on failure.
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );

		if ( end( $keys ) === $step ) {
			return add_query_arg( 'step', end( $keys ) );
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ] );
	} // END get_next_step_link()

	/**
	 * Setup Wizard Header.
	 *
	 * @access public
	 */
	public function setup_wizard_header() {
		// Same as default WP from wp-admin/admin-header.php.
		$wp_version_class = 'branch-' . str_replace( array( '.', ',' ), '-', floatval( get_bloginfo( 'version' ) ) );

		$campaign_args = Help::cocart_campaign(
			array(
				'utm_content' => 'setup-wizard',
			)
		);
		$store_url     = Help::build_shortlink( add_query_arg( $campaign_args, COCART_STORE_URL ) );

		set_current_screen( 'cocart-setup-wizard' );
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title>
			<?php
			printf(
				/* translators: %s: CoCart */
				esc_html__( '%s &rsaquo; Setup Wizard', 'cart-rest-api-for-woocommerce' ),
				'CoCart'
			);
			?>
			</title>
			<?php do_action( 'admin_enqueue_scripts' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="cocart-setup-wizard wp-core-ui <?php echo esc_attr( 'cocart-setup-step__' . $this->step ); ?> <?php echo esc_attr( $wp_version_class ); ?>">
		<h1 class="cocart-logo">
			<a href="<?php echo esc_url( $store_url ); ?>" target="_blank">
				<img src="<?php echo esc_url( COCART_ADMIN_URL_PATH . '/assets/images/brand/header-logo.png' ); ?>" alt="CoCart Logo" />
			</a>
		</h1>
		<?php
	} // END setup_wizard_header

	/**
	 * Setup Wizard Footer.
	 *
	 * @access public
	 */
	public function setup_wizard_footer() {
		$current_step = $this->step;

		switch ( $current_step ) {
			case 'store_setup':
				echo '<a class="cocart-setup-wizard-footer-links" href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Not right now. Go back to Dashboard.', 'cart-rest-api-for-woocommerce' ) . '</a>';
				break;
			case 'sessions':
			case 'settings':
				echo '<a class="cocart-setup-wizard-footer-links" href="' . esc_url( $this->get_next_step_link() ) . '">' . esc_html__( 'Skip this step.', 'cart-rest-api-for-woocommerce' ) . '</a>';
				break;
		}
		?>

			<?php do_action( 'cocart_setup_wizard_footer' ); ?>

			</body>
		</html>
		<?php
	} // END setup_wizard_footer()

	/**
	 * Output the steps.
	 *
	 * @access public
	 */
	public function setup_wizard_steps() {
		$output_steps = $this->steps;
		?>
		<ol class="cocart-setup-wizard-steps">
			<?php
			foreach ( $output_steps as $step_key => $step ) {
				$is_completed = array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true );

				if ( $step_key === $this->step ) {
					?>
					<li class="active"><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				} elseif ( $is_completed ) {
					?>
					<li class="done"><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				} else {
					?>
					<li><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				}
			}
			?>
		</ol>
		<?php
	} // END setup_wizard_steps()

	/**
	 * Output the content for the current step.
	 *
	 * @access public
	 */
	public function setup_wizard_content() {
		echo '<div class="cocart-setup-wizard-content">';
		if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
			call_user_func( $this->steps[ $this->step ]['view'], $this );
		}
		echo '</div>';
	} // END setup_wizard_content()

	/**
	 * Initial "store setup" step.
	 *
	 * New Store, Multiple Domains, JWT Authentication.
	 *
	 * @access public
	 *
	 * @since 3.1.0 Introduced.
	 * @since 4.0.0 Added option to install JWT Authentication.
	 */
	public function cocart_setup_wizard_store_setup() {
		$sessions_transferred = get_transient( 'cocart_setup_wizard_sessions_transferred' );

		$product_count = array_sum( (array) wp_count_posts( 'product' ) );

		$new_store = ( 0 === $product_count ) ? 'yes' : 'no';

		// If setup wizard has nothing left to setup, redirect to ready step.
		if ( $sessions_transferred && class_exists( 'CoCart_CORS' ) ) {
			wp_safe_redirect( esc_url_raw( $this->get_next_step_link( 'ready' ) ) );
			exit;
		}
		?>
		<form method="post" class="store-step">
			<input type="hidden" name="save_step" value="store_setup" />
			<?php wp_nonce_field( 'cocart-setup' ); ?>

			<h1>
			<?php
			printf(
				/* translators: %s: CoCart */
				esc_html__( 'Welcome to %s', 'cart-rest-api-for-woocommerce' ),
				'CoCart'
			);
			?>
			</h1>

			<p>
			<?php
			printf(
				/* translators: 1: CoCart, 2: WooCommerce */
				esc_html__( 'Thank you for choosing %1$s - the #1 customizable WordPress REST API for %2$s that lets you build headless ecommerce using your favorite technologies.', 'cart-rest-api-for-woocommerce' ),
				'CoCart',
				'WooCommerce'
			);
			?>
			</p>

			<p>
			<?php
				esc_html_e( 'This quick setup wizard will help you to configure the basic settings and you will have the API ready in no time.', 'cart-rest-api-for-woocommerce' );
			?>
			</p>

			<p>
			<?php
			printf(
				/* translators: 1: CoCart */
				esc_html__( 'It’s completely optional as %1$s is already ready to start using. The wizard is here to help you configure %1$s to your needs.', 'cart-rest-api-for-woocommerce' ),
				'CoCart'
			);
			?>
			</p>

			<p><?php esc_html_e( 'If you don’t want to go through the wizard right now, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind!', 'cart-rest-api-for-woocommerce' ); ?></p>

			<?php if ( ! $sessions_transferred ) { ?>
			<label for="store_new"><?php esc_html_e( 'Is this a new store?', 'cart-rest-api-for-woocommerce' ); ?></label>
			<select id="store_new" name="store_new" aria-label="<?php esc_attr_e( 'New Store', 'cart-rest-api-for-woocommerce' ); ?>" class="select-input dropdown">
				<option value="no"<?php selected( $new_store, 'no' ); ?>><?php echo esc_html__( 'No', 'cart-rest-api-for-woocommerce' ); ?></option>
				<option value="yes"<?php selected( $new_store, 'yes' ); ?>><?php echo esc_html__( 'Yes', 'cart-rest-api-for-woocommerce' ); ?></option>
			</select>
			<?php } ?>

			<label for="multiple_domains"><?php esc_html_e( 'Will your headless setup use multiple domains?', 'cart-rest-api-for-woocommerce' ); ?></label>
			<select id="multiple_domains" name="multiple_domains" aria-label="<?php esc_attr_e( 'Multiple Domains', 'cart-rest-api-for-woocommerce' ); ?>" class="select-input dropdown">
				<option value="no"><?php echo esc_html__( 'No', 'cart-rest-api-for-woocommerce' ); ?></option>
				<option value="yes"><?php echo esc_html__( 'Yes', 'cart-rest-api-for-woocommerce' ); ?></option>
			</select>

			<label for="jwt_authentication"><?php esc_html_e( 'Do you require support for JWT Authentication?', 'cart-rest-api-for-woocommerce' ); ?></label>
			<select id="jwt_authentication" name="jwt_authentication" aria-label="<?php esc_attr_e( 'JWT Authentication', 'cart-rest-api-for-woocommerce' ); ?>" class="select-input dropdown">
				<option value="no"><?php echo esc_html__( 'No', 'cart-rest-api-for-woocommerce' ); ?></option>
				<option value="yes"><?php echo esc_html__( 'Yes', 'cart-rest-api-for-woocommerce' ); ?></option>
			</select>

			<p class="cocart-setup-wizard-actions step">
				<button class="button button-primary button-large" value="<?php esc_attr_e( 'Continue', 'cart-rest-api-for-woocommerce' ); ?>" name="save_step"><?php esc_html_e( 'Continue', 'cart-rest-api-for-woocommerce' ); ?></button>
			</p>
		</form>
		<?php
	} // END cocart_setup_wizard_store_setup()

	/**
	 * Determine the next step to take based on the choices made.
	 *
	 * @access public
	 */
	public function cocart_setup_wizard_store_setup_save() {
		check_admin_referer( 'cocart-setup' );

		$is_store_new       = get_transient( 'cocart_setup_wizard_store_new' );
		$store_new          = isset( $_POST['store_new'] ) ? ( 'yes' === wc_clean( wp_unslash( $_POST['store_new'] ) ) ) : $is_store_new;
		$multiple_domains   = isset( $_POST['multiple_domains'] ) && ( 'yes' === wc_clean( wp_unslash( $_POST['multiple_domains'] ) ) );
		$jwt_authentication = isset( $_POST['jwt_authentication'] ) && ( 'yes' === wc_clean( wp_unslash( $_POST['jwt_authentication'] ) ) );

		$next_step = ''; // Next step.

		if ( $store_new ) {
			set_transient( 'cocart_setup_wizard_store_new', 'yes', MINUTE_IN_SECONDS * 10 );
			$next_step = apply_filters( 'cocart_setup_wizard_store_save_next_step_override', 'ready' );
		}

		// If true and CoCart Cors is not already installed then it will be installed in the background.
		if ( $multiple_domains ) {
			$this->install_cocart_cors();
		}

		// If true and CoCart JWT Authentication is not already installed then it will be installed in the background.
		if ( $jwt_authentication ) {
			$this->install_cocart_jwt();
		}

		// Redirect to next step.
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link( $next_step ) ) );
		exit;
	} // END cocart_setup_wizard_store_setup_save()

	/**
	 * Sessions step.
	 *
	 * For those who are not installing a fresh WooCommerce store,
	 * this step will transfer any current sessions.
	 *
	 * @access public
	 */
	public function cocart_setup_wizard_sessions() {
		?>
		<form method="post" class="session-step">
			<input type="hidden" name="save_step" value="session_setup" />
			<?php wp_nonce_field( 'cocart-setup' ); ?>

			<h1><?php esc_html_e( 'Sessions', 'cart-rest-api-for-woocommerce' ); ?></h1>

			<p><?php esc_html_e( 'Your current WooCommerce sessions will be transferred over to CoCart session table. This will run in the background until completed. Once transferred, all customers carts will be accessible again.', 'cart-rest-api-for-woocommerce' ); ?></p>

			<p class="cocart-setup-wizard-actions step">
				<button class="button button-primary button-large" value="<?php esc_attr_e( 'Transfer Sessions', 'cart-rest-api-for-woocommerce' ); ?>" name="save_step"><?php esc_html_e( 'Transfer Sessions', 'cart-rest-api-for-woocommerce' ); ?></button>
			</p>
		</form>
		<?php
	} // END cocart_setup_wizard_sessions()

	/**
	 * Triggers in the background transferring of sessions and redirects to the next step.
	 *
	 * @access public
	 */
	public function cocart_setup_wizard_sessions_save() {
		check_admin_referer( 'cocart-setup' );

		// Add transfer sessions to queue.
		WC()->queue()->schedule_single( time(), 'cocart_run_transfer_sessions', array(), 'cocart-transfer-sessions' );

		// Redirect to next step.
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	} // END cocart_setup_wizard_sessions_save()

	/**
	 * Settings step.
	 *
	 * Configures a few settings for the frontend and security.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 */
	public function cocart_setup_wizard_settings() {
		?>
		<form method="post" class="settings-step">
			<input type="hidden" name="save_step" value="setting_setup" />
			<?php wp_nonce_field( 'cocart-setup' ); ?>

			<h1><?php esc_html_e( 'Settings', 'cart-rest-api-for-woocommerce' ); ?></h1>

			<?php do_action( 'cocart_settings_page_general' ); ?>

			<p><em>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: Link to settings page. */
					__( 'You can fetch your encrypted salt key from the <a href="%s" target="_blank">settings page</a> any time. You can also update the salt key and immediately get the new encryption salt key once saved.', 'cart-rest-api-for-woocommerce' ),
					esc_url( admin_url( 'admin.php?page=cocart&section=settings' ) )
				)
			);
			?>
			</em></p>

			<p class="cocart-setup-wizard-actions step">
				<button class="button button-primary button-large" value="<?php esc_attr_e( 'Save Settings', 'cart-rest-api-for-woocommerce' ); ?>" name="save_step"><?php esc_html_e( 'Save Settings', 'cart-rest-api-for-woocommerce' ); ?></button>
			</p>
		</form>
		<?php
	} // END cocart_setup_wizard_settings()

	/**
	 * Triggers in the background transferring of sessions and redirects to the next step.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 */
	public function cocart_setup_wizard_settings_save() {
		check_admin_referer( 'cocart-setup' );

		$request = new \WP_REST_Request( 'POST', '/cocart/settings/save' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_query_params(
			array(
				'form' => 'post',
			)
		);
		$request->set_body( wp_json_encode( $_POST ) );
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Logger::log( sprintf( esc_html__( 'Something went wrong saving the settings during the CoCart Setup Wizard. Reason: %s', 'cart-rest-api-for-woocommerce' ), $error_message ), 'error' );
		}

		// Redirect to next step.
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	} // END cocart_setup_wizard_sessions_save()

	/**
	 * Helper method to queue the background install of a plugin.
	 *
	 * @access protected
	 *
	 * @param string $plugin_id   Plugin id used for background install.
	 * @param array  $plugin_info Plugin info array containing name and repo-slug,
	 *                            and optionally file if different from [repo-slug].php.
	 */
	protected function install_plugin( $plugin_id, $plugin_info ) {
		$plugin_file = isset( $plugin_info['file'] ) ? $plugin_info['file'] : $plugin_info['repo-slug'] . '.php';
		if ( is_plugin_active( $plugin_info['repo-slug'] . '/' . $plugin_file ) ) {
			return;
		}

		\WC_Install::background_installer( $plugin_id, $plugin_info );
	} // END install_plugin()

	/**
	 * Helper method to install CoCart CORS.
	 *
	 * @access protected
	 */
	protected function install_cocart_cors() {
		// Only those who can install plugins will be able to install CoCart Cors.
		if ( current_user_can( 'install_plugins' ) ) {
			$this->install_plugin(
				'cocart-cors',
				array(
					'name'      => 'CoCart - CORS Support',
					'repo-slug' => 'cocart-cors',
				)
			);
		}
	} // END install_cocart_cors()

	/**
	 * Helper method to install CoCart JWT Authentication.
	 *
	 * @access protected
	 *
	 * @since 4.0.0 Introduced.
	 */
	protected function install_cocart_jwt() {
		// Only those who can install plugins will be able to install CoCart JWT Autentication.
		if ( current_user_can( 'install_plugins' ) ) {
			$this->install_plugin(
				'cocart-jwt-authentication',
				array(
					'name'      => 'CoCart - JWT Authentication',
					'repo-slug' => 'cocart-jwt-authentication',
				)
			);
		}
	} // END install_cocart_jwt()

	/**
	 * Helper method to retrieve the current user's email address.
	 *
	 * @access protected
	 *
	 * @return string Email address
	 */
	protected function get_current_user_email() {
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;

		return $user_email;
	} // END get_current_user_email()

	/**
	 * Final step.
	 *
	 * @access public
	 */
	public function cocart_setup_wizard_ready() {
		// We've made it! Don't prompt the user to run the wizard again.
		Notices::remove_notice( 'setup_wizard', true );

		$campaign_args = Help::cocart_campaign(
			array(
				'utm_content' => 'setup-wizard',
			)
		);

		$tweet = array_rand( $this->tweets );

		$user_email = $this->get_current_user_email();
		$docs_url   = 'https://cocart.dev/';
		$help_text  = sprintf(
			/* translators: %1$s: link to docs */
			__( 'Visit CoCart.dev to access <a href="%1$s" target="_blank">developer resources</a>.', 'cart-rest-api-for-woocommerce' ),
			$docs_url
		);
		?>
		<h1><?php esc_html_e( "You're ready!", 'cart-rest-api-for-woocommerce' ); ?></h1>

		<p>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: CoCart */
				__( 'Now that you have %s installed your ready to start developing your headless store. We recommend that you have <code>WP_DEBUG</code> enabled to help you while testing.', 'cart-rest-api-for-woocommerce' ),
				'CoCart'
			)
		);
		?>
		</p>

		<p><?php esc_html_e( 'In the API reference you will find the API routes available with examples in a few languages.', 'cart-rest-api-for-woocommerce' ); ?></p>

		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: Developers Hub link, 2: CoCart */
					__( 'At the <a href="%1$s" target="_blank">developers hub</a> you can find all the resources you need to be productive with %2$s and keep track of everything that is happening with the plugin including development decisions and scoping of future versions.', 'cart-rest-api-for-woocommerce' ),
					$docs_url,
					'CoCart'
				)
			);
			?>
		</p>

		<p>
			<?php
			esc_html_e( 'It also provides answers to most common questions should you find that you need help and is the best place to look first before contacting support.', 'cart-rest-api-for-woocommerce' );
			?>
		</p>

		<p>
			<?php
			printf(
				/* translators: 1: CoCart, 2: WooCommerce */
				esc_html__( 'If you do need support or simply want to talk to other developers about taking your %2$s store headless, come join the %s community.', 'cart-rest-api-for-woocommerce' ),
				'CoCart',
				'WooCommerce'
			);
			?>
		</p>

		<p><?php esc_html_e( 'Thank you and enjoy!', 'cart-rest-api-for-woocommerce' ); ?></p>

		<p><?php esc_html_e( 'regards,', 'cart-rest-api-for-woocommerce' ); ?></p>

		<div class="founder-row">
			<div class="founder-image">
				<img src="<?php echo 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( 'mailme@sebastiendumont.com' ) ) ) . '?d=mp&s=60'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" width="60px" height="60px" alt="Photo of Founder" />
			</div>

			<div class="founder-details">
				<p>Sébastien Dumont<br>
				<?php
				echo sprintf(
					/* translators: %s: CoCart */
					esc_html__( 'Founder of %s', 'cart-rest-api-for-woocommerce' ),
					'CoCart'
				);
				?>
				</p>
			</div>
		</div>

		<div class="cocart-newsletter">
			<p><?php esc_html_e( 'Get product updates, tutorials and more straight to your inbox.', 'cart-rest-api-for-woocommerce' ); ?></p>
			<form action="https://xyz.us1.list-manage.com/subscribe/post?u=48ead612ad85b23fe2239c6e3&amp;id=d462357844&amp;SIGNUPPAGE=plugin" method="post" target="_blank" novalidate>
				<div class="newsletter-form-container">
					<input
						class="newsletter-form-email"
						type="email"
						value="<?php echo esc_attr( $user_email ); ?>"
						name="EMAIL"
						placeholder="<?php esc_attr_e( 'Email address', 'cart-rest-api-for-woocommerce' ); ?>"
						required
					>
					<p class="cocart-setup-wizard-actions step newsletter-form-button-container">
						<button
							type="submit"
							value="<?php esc_attr_e( 'Yes please!', 'cart-rest-api-for-woocommerce' ); ?>"
							name="subscribe"
							id="mc-embedded-subscribe"
							class="button button-primary newsletter-form-button"
						><?php esc_html_e( 'Yes please!', 'cart-rest-api-for-woocommerce' ); ?></button>
					</p>
				</div>
			</form>
		</div>

		<ul class="cocart-setup-wizard-next-steps">
			<li class="cocart-setup-wizard-next-step-item">
				<div class="cocart-setup-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'Next step', 'cart-rest-api-for-woocommerce' ); ?></p>
					<h3 class="next-step-description"><?php esc_html_e( 'Start Developing', 'cart-rest-api-for-woocommerce' ); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( "You're ready to develop your headless store.", 'cart-rest-api-for-woocommerce' ); ?></p>
				</div>
				<div class="cocart-setup-wizard-next-step-action">
					<p class="cocart-setup-wizard-actions step">
						<a class="button button-primary button-large" href="<?php echo esc_url( COCART_DOCUMENTATION_URL ); ?>" target="_blank">
							<?php esc_html_e( 'View API Reference', 'cart-rest-api-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</li>
			<li class="cocart-setup-wizard-next-step-item">
				<div class="cocart-setup-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'Need something else?', 'cart-rest-api-for-woocommerce' ); ?></p>
					<h3 class="next-step-description"><?php esc_html_e( 'Install Plugins', 'cart-rest-api-for-woocommerce' ); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( 'Checkout plugin suggestions by CoCart.', 'cart-rest-api-for-woocommerce' ); ?></p>
				</div>
				<div class="cocart-setup-wizard-next-step-action">
					<p class="cocart-setup-wizard-actions step">
						<a class="button button-large" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=cocart' ) ); ?>" target="_blank">
							<?php esc_html_e( 'View Plugin Suggestions', 'cart-rest-api-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</li>
			<li class="cocart-setup-wizard-additional-steps">
				<div class="cocart-setup-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'You can also', 'cart-rest-api-for-woocommerce' ); ?></p>
				</div>
				<div class="cocart-setup-wizard-next-step-action">
					<p class="cocart-setup-wizard-actions step">
						<a class="button" href="<?php echo esc_url( admin_url() ); ?>">
							<?php esc_html_e( 'Visit Dashboard', 'cart-rest-api-for-woocommerce' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( 'https://www.npmjs.com/package/@cocart/cocart-rest-api' ); ?>" target="_blank">
							<?php esc_html_e( 'Download CoCart JS', 'cart-rest-api-for-woocommerce' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( 'https://marketplace.visualstudio.com/items?itemName=sebastien-dumont.cocart-vscode' ); ?>" target="_blank">
							<?php esc_html_e( 'Install CoCart VSCode Extension', 'cart-rest-api-for-woocommerce' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( Help::build_shortlink( add_query_arg( $campaign_args, esc_url( COCART_STORE_URL . 'community/' ) ) ) ); ?>" target="_blank">
							<?php esc_html_e( 'Join Community', 'cart-rest-api-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</li>
		</ul>

		<p class="tweet-share">
			<a href="https://twitter.com/share" class="twitter-share-button" data-size="large" data-text="<?php echo esc_html( $this->tweets[ $tweet ] ); ?>" data-url="https://cocart.xyz/" data-hashtags="WooCommerce" data-related="WooCommerce" data-show-count="false">Tweet</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
		</p>

		<p class="next-steps-help-text"><?php echo wp_kses_post( $help_text ); ?></p>
		<?php
	} // END cocart_setup_wizard_ready()

} // END class

return new SetupWizard();
