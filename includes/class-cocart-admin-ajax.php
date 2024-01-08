<?php
/**
 * Adds Ajax for CoCart.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Ajax
 * @since   4.0.0 Introduced.
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'define_ajax' ), 0 );
		$this->add_ajax_events();
	}

	/**
	 * Set CoCart AJAX constant and headers.
	 *
	 * @access public
	 */
	public function define_ajax() {
		// phpcs:disable
		if ( ! empty( $_GET['cocart-ajax'] ) ) {
			cocart_maybe_define_constant( 'DOING_AJAX', true );
			cocart_maybe_define_constant( 'COCART_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
			}
			$GLOBALS['wpdb']->hide_errors();
		}
		// phpcs:enable
	} // END define_ajax()

	/**
	 * Send headers for CoCart Ajax Requests.
	 *
	 * @access private
	 */
	private function cocart_ajax_headers() {
		if ( ! headers_sent() ) {
			send_origin_headers();
			send_nosniff_header();
			cocart_nocache_headers();
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "cocart_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
		}
	} // END cocart_ajax_headers()

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 *
	 * @access public
	 */
	public function add_ajax_events() {
		$ajax_events = array(
			'generate_access_token',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_cocart_' . $ajax_event, array( $this, $ajax_event ) );
		}
	} // END add_ajax_events()

	/**
	 * Generate access token and save it in the settings.
	 *
	 * @access public
	 */
	public function generate_access_token() {
		check_admin_referer( 'regenerate_token', 'regenerate_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			wp_die();
		}

		$token = wp_generate_uuid4();

		cocart_update_setting( 'general', 'access_token', $token );

		wp_send_json_success( $token );
	} // END cocart_generate_access_token()

} // END class

return new Ajax();