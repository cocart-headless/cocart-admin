<?php
/**
 * Manages plugin extras for CoCart in the WordPress dashboard.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin
 * @since   4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use CoCart\Help;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PluginExtras {

	public function __construct() {
		add_filter( 'extra_plugin_headers', array( $this, 'cocart_enabled_plugin_headers' ) );

		// Filter plugins to showing only CoCart Add-ons on the plugins page by it's own status.
		add_action( is_multisite() ? 'views_plugins-network' : 'views_plugins', array( $this, 'cocart_addons_plugin_status_link' ) );
		add_action( 'pre_current_active_plugins', array( $this, 'cocart_addons_filter_plugins_by_status' ) );
	} // END __construct()

	/**
	 * This is the header used by extensions to show requirements.
	 *
	 * @var string
	 */
	const VERSION_REQUIRED_HEADER = 'CoCart requires at least';

	/**
	 * This is the header used by extensions to show testing.
	 *
	 * @var string
	 */
	const VERSION_TESTED_HEADER = 'CoCart tested up to';

	/**
	 * Get plugins that have a valid value for a specific header.
	 *
	 * @access protected
	 *
	 * @param string $header Plugin header to search for.
	 *
	 * @return array Array of plugins that contain the searched header.
	 */
	protected function get_plugins_with_header( $header ) {
		$plugins = get_plugins();
		$matches = array();

		foreach ( $plugins as $file => $plugin ) {
			if ( ! empty( $plugin[ $header ] ) ) {
				$matches[ $file ] = $plugin;
			}
		}

		return apply_filters( 'cocart_get_plugins_with_header', $matches, $header, $plugins );
	} // END get_plugins_with_header()

	/**
	 * Get plugins which "maybe" are for CoCart.
	 *
	 * @access protected
	 *
	 * @return array of plugin info arrays
	 */
	protected function get_plugins_for_cocart() {
		$plugins = get_plugins();
		$matches = array();

		foreach ( $plugins as $file => $plugin ) {
			if ( COCART_PLUGIN_BASENAME === $file ) {
				continue;
			}

			if ( 'CoCart Pro' !== $plugin['Name'] && ( stristr( $plugin['Name'], 'cocart' ) || stristr( $plugin['Description'], 'cocart' ) ) ) {
				$matches[ $file ] = $plugin;
			}
		}

		return apply_filters( 'cocart_get_plugins_for_cocart', $matches, $plugins );
	} // END get_plugins_for_cocart()

	/**
	 * Counts the number of CoCart plugins installed and
	 * returns a list of the plugin files.
	 *
	 * This does not mean the plugin is active.
	 *
	 * @access public
	 *
	 * @return array An array of all CoCart plugins installed.
	 */
	public function get_cocart_plugins() {
		$plugins = self::get_plugins_for_cocart();
		$count   = array();

		foreach ( $plugins as $file => $headers ) {
			$count[] = $file;
		}

		return $count;
	} // END get_cocart_plugins()

	/**
	 * Read in CoCart headers when reading plugin headers.
	 *
	 * @access public
	 *
	 * @param array $headers Headers.
	 *
	 * @return array
	 */
	public function cocart_enabled_plugin_headers( $headers ) {
		// CoCart requires at least - allows developers to define which version of CoCart the plugin requires to run.
		$headers[] = self::VERSION_REQUIRED_HEADER;

		// CoCart tested up to - allows developers to define which version of CoCart they have tested up to.
		$headers[] = self::VERSION_TESTED_HEADER;

		// CoCart - This is used in CoCart addons and is picked up by the helper.
		$headers[] = 'CoCart';

		return $headers;
	} // END cocart_enabled_plugin_headers()

	/**
	 * Add a plugin status link for CoCart Add-ons only.
	 *
	 * This is modeled on `WP_Plugins_List_Table::get_views()`.
	 *
	 * @access public
	 *
	 * @param array $status_links Plugin statuses before.
	 *
	 * @return array $status_links Plugin statuses after.
	 */
	public function cocart_addons_plugin_status_link( $status_links ) {
		// If status link already exists then don't add one.
		if ( in_array( 'cocart_addons', $status_links ) ) {
			return $status_links;
		}

		$cocart_plugins = self::get_cocart_plugins();
		$count          = count( $cocart_plugins );

		$counts = array(
			'cocart_addons' => $count,
		);

		// We can't use the global $status set in WP_Plugin_List_Table::__construct() because
		// it will be 'all' for our "custom status".
		$status = isset( $_REQUEST['plugin_status'] ) ? $_REQUEST['plugin_status'] : 'all';

		foreach ( $counts as $type => $count ) {
			if ( 0 === $count ) {
				continue;
			}
			switch ( $type ) {
				case 'cocart_addons':
					$label = Help::is_white_labelled() ? esc_html__( 'Headless Add-ons', 'cart-rest-api-for-woocommerce' ) : esc_html__( 'CoCart Add-ons', 'cart-rest-api-for-woocommerce' );
					$text  = sprintf(
						'%1$s <span class="count">(%2$s)</span>',
						$label,
						$count
					);
			}

			$status_links[ $type ] = sprintf(
				"<a href='%s'%s>%s</a>",
				add_query_arg( 'plugin_status', $type, 'plugins.php' ),
				( $type === $status ) ? ' class="current" aria-current="page"' : '',
				sprintf( $text, number_format_i18n( $count ) )
			);
		}

		// Make the 'all' status link not current if our "custom status" is current.
		if ( in_array( $status, array_keys( $counts ) ) ) {
			$status_links['all'] = str_replace( ' class="current" aria-current="page"', '', $status_links['all'] );
		}

		return $status_links;
	} // END cocart_addons_plugin_status_link()

	/**
	 * Filter plugins shown in the list table when status is 'cocart_addons'.
	 *
	 * This is modeled on `WP_Plugins_List_Table::prepare_items()`.
	 *
	 * @access public
	 *
	 * @param array $plugins List of plugins before they are filtered.
	 *
	 * @global WP_Plugins_List_Table $wp_list_table The global list table object. Set in `wp-admin/plugins.php`.
	 * @global int                   $page          The current page of plugins displayed. Set in WP_Plugins_List_Table::__construct().
	 */
	public function cocart_addons_filter_plugins_by_status( $plugins ) {
		global $wp_list_table, $page;

		// If current request is not for our status then just return the plugins for actual status.
		if ( ! ( isset( $_REQUEST['plugin_status'] ) && $_REQUEST['plugin_status'] == 'cocart_addons' ) ) {
			$plugins = $wp_list_table->items;
		}

		// Clear the plugins list if it is our status.
		elseif ( isset( $_REQUEST['plugin_status'] ) && $_REQUEST['plugin_status'] === 'cocart_addons' ) {
			$plugins = array();
		}

		// Get CoCart plugins.
		$cocart_plugins = self::get_plugins_for_cocart();

		if ( empty( $plugins ) ) {
			foreach ( $cocart_plugins as $plugin_file => $plugin_data ) {
				$plugins[ $plugin_file ] = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
			}
		} else {
			foreach ( $plugins as $plugin_file => $plugin_data ) {
				// Remove CoCart plugins from all other statuses.
				if ( isset( $cocart_plugins[ $plugin_file ] ) ) {
					unset( $plugins[ $plugin_file ] );
				}
			}
		}

		// Set the list table's items array to the remaining plugins.
		$wp_list_table->items = $plugins;

		// Now, update the pagination properties of the list table accordingly.
		$total_this_page = count( $plugins );

		$plugins_per_page = $wp_list_table->get_items_per_page( str_replace( '-', '_', $wp_list_table->screen->id . '_per_page' ), 999 );

		$start = ( $page - 1 ) * $plugins_per_page;

		if ( $total_this_page > $plugins_per_page ) {
			$wp_list_table->items = array_slice( $wp_list_table->items, $start, $plugins_per_page );
		}

		$wp_list_table->set_pagination_args(
			array(
				'total_items' => $total_this_page,
				'per_page'    => $plugins_per_page,
			)
		);

		return;
	} // END cocart_addons_filter_plugins_by_status()

} // END class

return new PluginExtras();
