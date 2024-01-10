<?php
/**
 * Abstract: CoCart Settings.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Settings
 * @since   4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use CoCart\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

abstract class SettingsPage {

	/**
	 * Setting page id.
	 *
	 * @access protected
	 *
	 * @var string $id
	 */
	protected $id = '';

	/**
	 * Setting page label.
	 *
	 * @access protected
	 *
	 * @var string $label
	 */
	protected $label = '';

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		add_filter( 'cocart_setting_label_' . $this->id, function() {
			return $this->label;
		} );
		add_action( 'cocart_settings_page_' . $this->id, array( $this, 'output' ), 10 );
	}

	/**
	 * Get settings page ID.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	} // END get_id()

	/**
	 * Get settings page label.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	} // END get_label()

	/**
	 * Add this page to settings.
	 *
	 * @access public
	 *
	 * @param  array $pages
	 *
	 * @return array $pages
	 */
	public function add_settings_page( $pages ) {
		$pages[ $this->id ] = $this->label;

		return $pages;
	} // END add_settings_page()

	/**
	 * Get settings array
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_settings() {
		return array();
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
