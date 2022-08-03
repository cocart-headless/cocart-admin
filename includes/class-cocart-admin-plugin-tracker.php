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

		$client = new \Appsero\Client( '3898b319-80b0-4f93-bc96-1809486b15fd', 'CoCart - Headless ecommerce', COCART_FILE );

		$this->insights = $client->insights();

		$this->insights->add_extra(
			[
				'products'       => $this->insights->get_post_count( 'product' ),
				'orders'         => $this->get_order_count(),
				'is_pro'         => class_exists( 'CoCart_Pro' ) ? 'Yes' : 'No',
				'wc_version'     => function_exists( 'WC' ) ? WC()->version : WC_VERSION,
				'cocart_version' => COCART_VERSION,
			]
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
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return int Number of orders.
	 */
	protected function get_order_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT count(ID) FROM $wpdb->posts WHERE post_type = 'shop_order' and post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded');" );
	} // END get_order_count()

} // END class

return new PluginTracker();