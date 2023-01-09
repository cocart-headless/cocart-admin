<?php
/**
 * Tracks CoCart usage.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\PluginTracker
 * @since   4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use \CoCart\Help;
use \CoCart\Status;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PluginTracker {

	/**
	 * Insights class
	 *
	 * @var \Appsero\Insights
	 */
	public $insights = null;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->appsero_init_tracker();
	}

	/**
	 * Initialize the Appsero plugin tracker.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function appsero_init_tracker() {
		if ( ! class_exists( '\Appsero\Client' ) ) {
			require_once untrailingslashit( plugin_dir_path( COCART_FILE ) ) . '/vendor/appsero/Client.php';
		}

		// Should WooCommerce be deactivated by mistake, prevent site from crashing by ignoring the tracker.
		if ( ! defined( '\WC_VERSION' ) ) {
			return;
		}

		$client = Help::appsero_client();

		$this->insights = $client->insights();

		// WordPress 5.5+ environment type specification.
		// 'production' is the default in WP, thus using it as a default here, too.
		$environment_type = 'production';
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$environment_type = wp_get_environment_type();
		}

		$this->insights->add_extra(
			array(
				'products'          => $this->insights->get_post_count( 'product' ),
				'orders'            => $this->get_order_count(),
				'cocart_version'    => COCART_VERSION,
				'is_pro'            => class_exists( 'CoCart_Pro' ) ? 'Yes' : 'No',
				'wc_version'        => function_exists( 'WC' ) ? WC()->version : WC_VERSION,
				'user_language'     => Help::get_user_language(),
				'multisite'         => Status::is_multi_network() ? 'Yes' : 'No',
				'environment_type'  => $environment_type,
				'days_active'       => Help::get_days_active(),
				'is_offline_mode'   => Status::is_offline_mode() ? 'Yes' : 'No',
				'is_local_site'     => Status::is_local_site() ? 'Yes' : 'No',
				'is_staging_site'   => Status::is_staging_site() ? 'Yes' : 'No',
				'is_vip_site'       => Status::is_vip_site() ? 'Yes' : 'No',
				'is_white_labelled' => Help::is_white_labelled() ? 'Yes' : 'No',
			)
		);

		if ( class_exists( 'CoCart_Pro' ) ) {
			$this->insights->hide_notice()->init();
			$this->insights->optin();
		} else {
			$this->insights->init();
		}

		$client->set_textdomain( 'cart-rest-api-for-woocommerce' );
	} // END appsero_init_tracker()

	/**
	 * Get number of orders
	 *
	 * @access protected
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return int Number of orders.
	 */
	protected function get_order_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT count(ID) FROM $wpdb->posts WHERE post_type = 'shop_order' and post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded');" );
	} // END get_order_count()

} // END class

return new PluginTracker();
