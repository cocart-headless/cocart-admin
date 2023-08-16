<?php
/**
 * REST API: CoCart_REST_Settings_Controller class.
 *
 * @author  Sébastien Dumont
 * @package CoCart\RESTAPI\Admin\Settings
 * @since   4.0.0 Introduced.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller for CoCart Settings.
 *
 * This REST API controller handles requests to the "cocart/settings/" endpoint.
 *
 * @since 4.0.0 Introduced.
 */
class CoCart_REST_Settings_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $namespace = 'cocart/settings';

	/**
	 * Store errors here.
	 *
	 * @access protected
	 *
	 * @var object
	 */
	protected $errors;

	/**
	 * Get controller started.
	 *
	 * @access public
	 */
	public function __construct() {
		// Create a new instance of WP_Error
		$this->errors = new \WP_Error();

		add_filter( 'cocart_settings_sanitize_text', array( $this, 'sanitize_text_field' ) );
		add_filter( 'cocart_settings_sanitize_textarea', array( $this, 'sanitize_textarea_field' ) );
		add_filter( 'cocart_settings_sanitize_radio', array( $this, 'sanitize_radio_field' ), 10, 2 );
		add_filter( 'cocart_settings_sanitize_select', array( $this, 'sanitize_select_field' ), 10, 2 );
		add_filter( 'cocart_settings_sanitize_checkbox', array( $this, 'sanitize_checkbox_field' ) );
		add_filter( 'cocart_settings_sanitize_multiselect', array( $this, 'sanitize_multiple_field' ), 10, 2 );
		add_filter( 'cocart_settings_sanitize_multicheckbox', array( $this, 'sanitize_multiple_field' ), 10, 2 );
		add_filter( 'cocart_settings_sanitize_file', array( $this, 'sanitize_file_field' ) );
	} // END __construct()

	/**
	 * Register new routes for the settings page.
	 *
	 * @access public
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
	} // END register_routes()

	/**
	 * Detect if the user can submit or get options.
	 *
	 * @access public
	 *
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return true|WP_Error True if the request has write access, WP_Error object otherwise.
	 */
	public function get_options_permission( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'cocart_rest_permission_denied', __( 'Permission Denied.', 'cart-rest-api-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	} // END get_options_permission()

	/**
	 * Sanitize the text field.
	 *
	 * @access public
	 *
	 * @param string $value Settings value.
	 *
	 * @return string
	 */
	public function sanitize_text_field( $value ) {
		return wp_kses_post( trim( stripslashes( $value ) ) );
	} // END sanitize_text_field()

	/**
	 * Sanitize textarea field.
	 *
	 * @access public
	 *
	 * @param string $value Settings value.
	 *
	 * @return string
	 */
	public function sanitize_textarea_field( $value ) {
		return wp_kses(
			trim( stripslashes( $value ) ),
			array_merge(
				array(
					'iframe' => array(
						'src'   => true,
						'style' => true,
						'id'    => true,
						'class' => true,
					),
				),
				wp_kses_allowed_html( 'post' )
			)
		);
	} // END sanitize_textarea_field()

	/**
	 * Sanitize the select field.
	 *
	 * @access public
	 *
	 * @param string $value   Settings value.
	 * @param array  $setting Details of the settings to validate with.
	 *
	 * @return string
	 */
	public function sanitize_select_field( $value, $setting ) {
		if ( array_key_exists( $value, $setting['options'] ) ) {
			return $value;
		} else {
			return new WP_Error( 'cocart_rest_value_invalid', __( 'An invalid setting value was passed.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 400 ) );
		}
	} // END sanitize_select_field()

	/**
	 * Sanitize the radio field.
	 *
	 * @access public
	 *
	 * @param string $value   Settings value.
	 * @param array  $setting Details of the settings to validate with.
	 *
	 * @return string
	 */
	public function sanitize_radio_field( $value, $setting ) {
		return $this->sanitize_select_field( $value, $setting );
	} // END sanitize_radio_field()

	/**
	 * Sanitize the checkbox field.
	 *
	 * @access public
	 *
	 * @param string $value Settings value.
	 *
	 * @return void
	 */
	public function sanitize_checkbox_field( $value ) {
		$value = '1' === $value || 'yes' === $value ? 'yes' : 'no';

		return $value;
	} // END sanitize_checkbox_field()

	/**
	 * Sanitize multiselect and multicheck field.
	 *
	 * @access public
	 *
	 * @param string $values  Settings values.
	 * @param array  $setting Details of the settings to validate with.
	 *
	 * @return array
	 */
	public function sanitize_multiple_field( $values, $setting ) {
		if ( empty( $values ) ) {
			return array();
		}

		if ( ! is_array( $values ) ) {
			return new WP_Error( 'cocart_rest_value_invalid', __( 'An invalid setting value was passed.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 400 ) );
		}

		$final_values = array();
		foreach ( $values as $value ) {
			if ( array_key_exists( $value, $setting['options'] ) ) {
				$final_values[] = $value;
			}
		}

		return $final_values;
	} // END sanitize_multiple_field()

	/**
	 * Sanitize urls for the file field.
	 *
	 * @access public
	 *
	 * @param string $value Settings value.
	 *
	 * @return void
	 */
	public function sanitize_file_field( $value ) {
		return esc_url( $value );
	} // END sanitize_file_field()

	/**
	 * Get the settings.
	 *
	 * @access public
	 *
	 * @param string $settings_section The settings section to get.
	 *
	 * @return array The settings.
	 */
	public function get_settings( string $settings_section = '' ) {
		$settings = CoCart\Admin\Settings::get_settings( $settings_section );

		if ( empty( $settings_section ) ) {
			foreach ( $settings as $page => $options ) {
				$sections[ $page ] = $options->get_settings();
			}

			return $sections;
		}

		return $settings;
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
		$sections = $this->get_settings();

		if ( $request->get_param( 'form' ) === 'post' ) {
			$settings_received = json_decode( $request->get_body() );
		} else {
			$settings_received = $request->get_json_params();
		}

		/**
		 * Remove certain posted data since we can't unset it.
		 */
		$data = array();
		foreach ( $settings_received as $field => $value ) {
			if ( in_array( $field, array( 'save_step', '_wpnonce', '_wp_http_referer' ) ) ) {
				continue;
			}

			$data[ $field ] = $value;
		}

		$data_to_save = get_option( 'cocart_settings', array() );

		if ( is_array( $sections ) && ! empty( $sections ) ) {
			foreach ( $sections as $page => $settings ) {
				foreach ( $settings as $setting ) {
					$setting_type = $setting['type'];

					// Skip if no setting type.
					if ( ! $setting_type || $setting_type === 'title' || $setting_type === 'sectionend' ) {
						continue;
					}

					// Sanitize the input.
					$raw_data = ! empty( $data[ $setting['id'] ] ) ? $data[ $setting['id'] ] : '';

					$output = apply_filters( 'cocart_settings_sanitize_' . $setting_type, $raw_data, $setting, $this->errors );

					if ( is_wp_error( $output ) ) {
						return $output;
					}

					// Encrypt salt key.
					if ( $setting['id'] === 'salt_key' ) {
						$output = md5( $output );
					}

					// Add the option to the list of ones that we need to save.
					$data_to_save[ $page ][ $setting['id'] ] = $output;
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

} // END class
