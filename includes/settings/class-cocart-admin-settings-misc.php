<?php
/**
 * CoCart Settings: Misc Settings.
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

class MiscSettings extends Page {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->id    = 'misc';
		$this->label = esc_html__( 'Misc', 'cart-rest-api-for-woocommerce' );

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
			'title'   => esc_html__( 'Hide Announcements', 'cart-rest-api-for-woocommerce' ),
			'id'      => 'hide_announcements',
			'type'    => 'checkbox',
			'default' => 'no',
			'desc'    => esc_html__( 'Check this option to hide plugin announcements.', 'cart-rest-api-for-woocommerce' ),
		);

		$settings[] = array(
			'title'   => esc_html__( 'Uninstall Data', 'cart-rest-api-for-woocommerce' ),
			'id'      => 'uninstall_data',
			'type'    => 'checkbox',
			'default' => 'no',
			'desc'    => esc_html__( 'Check this option to uninstall ALL plugin data when the plugin is uninstalled.', 'cart-rest-api-for-woocommerce' ),
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

return new MiscSettings();
