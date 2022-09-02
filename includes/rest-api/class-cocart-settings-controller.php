<?php
/**
 * REST API: CoCart Settings Controller.
 *
 * Handles requests to the /settings/ endpoint.
 *
 * @author  Sébastien Dumont
 * @package CoCart\RESTAPI\Admin\Settings
 * @since   4.0.0 Introduced.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CoCart REST API v2 - Products controller class.
 *
 * @package CoCart Products/API
 * @extends WP_Rest_Controller
 */
class CoCart_REST_Settings_Controller extends \WP_Rest_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'cocart/settings';

	/**
	 * Store errors here.
	 *
	 * @var object
	 */
	protected $errors;

	/**
	 * Get controller started.
	 */
	public function __construct() {
		// Create a new instance of WP_Error
		$this->errors = new \WP_Error();

		add_filter( 'cocart_settings_sanitize_text', array( $this, 'sanitize_text_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_textarea', array( $this, 'sanitize_textarea_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_radio', array( $this, 'sanitize_text_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_select', array( $this, 'sanitize_text_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_checkbox', array( $this, 'sanitize_checkbox_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_multiselect', array( $this, 'sanitize_multiple_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_multicheckbox', array( $this, 'sanitize_multiple_field' ), 3, 10 );
		add_filter( 'cocart_settings_sanitize_file', array( $this, 'sanitize_file_field' ), 3, 10 );
	}

	/**
	 * Register new routes for the settings page.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/save', array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'save_options' ),
				'permission_callback' => array( $this, 'get_options_permission' ),
			),
		) );

		register_rest_route( $this->namespace, '/get', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_options' ),
				'permission_callback' => array( $this, 'get_options_permission' ),
			),
		) );
	}

	/**
	 * Detect if the user can submit or get options.
	 *
	 * @access public
	 *
	 * @return bool|\WP_Error
	 */
	public function get_options_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'cocart_rest_permission_denied', __( 'Permission Denied.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Sanitize the text field.
	 *
	 * @access public
	 *
	 * @param string $input
	 * @param object $errors
	 * @param array  $setting
	 *
	 * @return string
	 */
	public function sanitize_text_field( $input, $errors, $setting ) {
		return trim( wp_strip_all_tags( $input, true ) );
	} // END sanitize_text_field()

	/**
	 * Sanitize textarea field.
	 *
	 * @access public
	 *
	 * @param string $input
	 * @param object $errors
	 * @param array  $setting
	 *
	 * @return string
	 */
	public function sanitize_textarea_field( $input, $errors, $setting ) {
		return stripslashes( wp_kses_post( wp_unslash( trim( $input ) ) ) );
	} // END sanitize_textarea_field()

	/**
	 * Sanitize multiselect and multicheck field.
	 *
	 * @access public
	 *
	 * @param mixed  $input
	 * @param object $errors
	 * @param array  $setting
	 *
	 * @return array
	 */
	public function sanitize_multiple_field( $input, $errors, $setting ) {
		$new_input = array();

		if ( is_array( $input ) && ! empty( $input ) ) {
			foreach ( $input as $key => $value ) {
				$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		if ( ! empty( $input ) && ! is_array( $input ) ) {
			$input = explode( ',', $input );
			foreach ( $input as $key => $value ) {
				$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $new_input;
	} // END sanitize_multiple_field()

	/**
	 * Sanitize urls for the file field.
	 *
	 * @access public
	 *
	 * @param string $input
	 * @param object $errors
	 * @param array  $setting
	 *
	 * @return void
	 */
	public function sanitize_file_field( $input, $errors, $setting ) {
		return esc_url( $input );
	} // END sanitize_file_field()

	/**
	 * Sanitize the checkbox field.
	 *
	 * @access public
	 *
	 * @param string $input
	 * @param object $errors
	 * @param array  $setting
	 *
	 * @return void
	 */
	public function sanitize_checkbox_field( $input, $errors, $setting ) {
		$pass = false;

		if ( $input == 'true' ) {
			$pass = true;
		}

		return $pass;
	} // END sanitize_checkbox_field()

	/**
	 * Get the settings.
	 *
	 * @access public
	 *
	 * @param string $settings_group The settings group to get.
	 *
	 * @return array The settings.
	 */
	public function get_settings( string $settings_group ) {
		return CoCart\Admin\Settings::get_settings( $settings_group );
	} // END get_settings()

	/**
	 * Save options to the database. Sanitize them first.
	 *
	 * @access public
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return void
	 */
	public function save_options( \WP_REST_Request $request ) {
		if ( ! check_ajax_referer( 'wp_rest', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'cart-rest-api-for-woocommerce' ) ) );
		}

		$settings_group = $request->get_param( 'settings' ); // The parameter will determine which settings to validate against.
		$settings       = $this->get_settings( $settings_group );

		if ( $request->get_param( 'form' ) === 'post' ) {
			$settings_received = json_decode( $request->get_body() );
		} else {
			$settings_received = $request->get_json_params();
		}

		/**
		 * Remove certain posted data since we can't unset it.
		 */
		$data = array();
		foreach( $settings_received as $field => $value ) {
			if ( in_array( $field, array( 'save_step', '_wpnonce', '_wp_http_referer') ) ) {
				continue;
			}

			$data[$field] = $value;
		}

		$data_to_save = get_option( 'cocart_settings', array() );

		if ( is_array( $settings ) && ! empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				// Skip if no setting type.
				if ( ! $setting['type'] || $setting['type'] === 'title' || $setting['type'] === 'sectionend' ) {
					continue;
				}

				// Skip if the ID doesn't exist in the data received.
				if ( ! array_key_exists( $setting['id'], $data ) ) {
					continue;
				}

				// Sanitize the input.
				$setting_type = $setting['type'];
				$output       = apply_filters( 'cocart_settings_sanitize_' . $setting_type, $data[ $setting['id'] ], $this->errors, $setting );
				// $output       = $this->sanitize_{$setting_type}( $data[ $setting['id'] ], $this->errors, $setting );
				// $output       = apply_filters( 'cocart_settings_sanitize_' . $setting['id'], $output, $this->errors, $setting );

				if ( $setting_type == 'checkbox' && $output == false ) {
					continue;
				}

				// Encrypt salt key.
				if ( $setting['id'] === 'salt_key' ) {
					$output = md5( $output );
				}

				// Add the option to the list of ones that we need to save.
				if ( ! empty( $output ) && ! is_wp_error( $output ) ) {
					$data_to_save[ $settings_group ][ $setting['id'] ] = $output;
				}
			}
		}

		if ( ! empty( $this->errors->get_error_codes() ) ) {
			return new \WP_REST_Response( $this->errors, 422 );
		}

		update_option( 'cocart_settings', $data_to_save );

		return rest_ensure_response( $data_to_save );
	} // END save_options()

	/**
	 * Get options from the database.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function get_options() {
		return rest_ensure_response( get_option( 'cocart_settings', array() ) );
	} // END get_options()

}
