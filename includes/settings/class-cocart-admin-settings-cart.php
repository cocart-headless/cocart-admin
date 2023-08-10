<?php
/**
 * CoCart Settings: Cart API Settings.
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

class CartAPISettings extends Page {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->id    = 'cart';
		$this->label = esc_html__( 'Cart API', 'cart-rest-api-for-woocommerce' );

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
			'id'    => $this->id,
			'type'  => 'title',
			'title' => __( 'Default Configurations', 'cart-rest-api-for-woocommerce' ),
			'desc'  => __( 'Configurations set below apply to the default behavior when accessing the cart API.', 'cart-rest-api-for-woocommerce' ),
		);

		$settings[] = array(
			'title'    => esc_html__( 'Default Response', 'cart-rest-api-for-woocommerce' ),
			'id'       => $this->id . '_response',
			'desc'     => __( 'Alternative to setting individual fields, set the default cart response.', 'cart-rest-api-for-woocommerce' ),
			'type'     => 'select',
			'default'  => 'default',
			'options'  => array(
				'default'       => __( 'Default - All fields', 'cart-rest-api-for-woocommerce' ),
				'mini'          => __( 'Mini Cart', 'cart-rest-api-for-woocommerce' ),
				'digital'       => __( 'Digital store', 'cart-rest-api-for-woocommerce' ),
				'digital_fees'  => __( 'Digital store + Fees', 'cart-rest-api-for-woocommerce' ),
				'shipping'      => __( 'Shipping store', 'cart-rest-api-for-woocommerce' ),
				'shipping_fees' => __( 'Shipping store + Fees', 'cart-rest-api-for-woocommerce' ),
			),
			'css'      => 'min-width:10em;',
			'autoload' => true,
		);

		$settings[] = array(
			'title'    => esc_html__( 'Price Format', 'cart-rest-api-for-woocommerce' ),
			'id'       => $this->id . '_prices',
			'desc'     => __( 'Return the price values in the format you prefer.', 'cart-rest-api-for-woocommerce' ),
			'type'     => 'select',
			'default'  => '',
			'options'  => array(
				'raw'       => __( 'Raw', 'cart-rest-api-for-woocommerce' ),
				'formatted' => __( 'Formatted', 'cart-rest-api-for-woocommerce' ),
			),
			'css'      => 'min-width:10em;',
			'autoload' => true,
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

return new CartAPISettings();
