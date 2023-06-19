<?php
/**
 * CoCart Settings: General Settings.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Settings
 * @since   4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use CoCart\Admin\Settings;
use CoCart\Admin\SettingsPage as Page;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeneralSettings extends Page {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = esc_html__( 'General', 'cart-rest-api-for-woocommerce' );

		parent::__construct();
	} // END __construct()

	/**
	 * Get settings array.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings[] = array(
			'id'   => $this->id,
			'type' => 'title',
		);

		$settings[] = array(
			'title'       => esc_html__( 'Front-end site URL', 'cart-rest-api-for-woocommerce' ),
			'id'          => 'frontend_url',
			'type'        => 'url',
			'default'     => '',
			'placeholder' => 'https://',
			'css'         => 'width:25em;',
			'desc'        => esc_html__( 'The full URL to your headless front-end, including https://. This is used for rewriting product permalinks to point to your front-end site.', 'cart-rest-api-for-woocommerce' ),
		);

		$settings[] = array(
			'title'   => esc_html__( 'Disable WordPress Access?', 'cart-rest-api-for-woocommerce' ),
			'id'      => 'disable_wp_access',
			'type'    => 'checkbox',
			'default' => 'no',
			'desc'    => esc_html__( 'If enabled, users who are not administrators cannot access the WordPress site. Will redirect users to "Front-end site URL" instead if set above.', 'cart-rest-api-for-woocommerce' ),
		);

		$settings[] = array(
			'title'   => esc_html__( 'Salt Key', 'cart-rest-api-for-woocommerce' ),
			'id'      => 'salt_key',
			'type'    => 'text',
			'default' => '',
			'css'     => 'width:25em;',
			'desc'    => esc_html__( 'This key is used to protect certain features from being misused. Enter a plain word or phrase.', 'cart-rest-api-for-woocommerce' ),
		);

		$settings[] = array(
			'id'   => $this->id,
			'type' => 'sectionend',
		);

		return $settings;
	} // END get_settings()

	/**
	 * Output the settings.
	 *
	 * @access public
	 */
	public function output() {
		$settings = $this->get_settings();

		Settings::output_fields( $this->id, $settings );
	} // END output()

} // END class

return new GeneralSettings();
