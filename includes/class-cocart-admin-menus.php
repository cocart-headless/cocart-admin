<?php
/**
 * Adds CoCart to the WordPress admin menus.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Menus
 * @since   2.0.0
 * @version 4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use CoCart\Help;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Menus {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		if ( ! Help::is_white_labelled() ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
		add_filter( 'parent_file', array( $this, 'highlight_submenu' ) );
	} // END __construct()

	/**
	 * Add CoCart to the menu and register WooCommerce admin bar.
	 *
	 * @access public
	 *
	 * @since   2.0.0 Introduced.
	 * @version 3.1.0
	 */
	public function admin_menu() {
		$section = ! isset( $_GET['section'] ) ? 'getting-started' : trim( sanitize_key( wp_unslash( $_GET['section'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		switch ( $section ) {
			case 'getting-started':
				/* translators: %s: CoCart */
				$title      = sprintf( esc_attr__( 'Getting Started with %s', 'cart-rest-api-for-woocommerce' ), 'CoCart' );
				$breadcrumb = esc_attr__( 'Getting Started', 'cart-rest-api-for-woocommerce' );
				break;
			case 'settings':
				/* translators: %s: CoCart */
				$title      = sprintf( esc_attr__( '%s Settings', 'cart-rest-api-for-woocommerce' ), 'CoCart' );
				$breadcrumb = esc_attr__( 'Settings', 'cart-rest-api-for-woocommerce' );
				break;
			case 'upgrade':
				/* translators: %s: CoCart */
				$title      = sprintf( esc_attr__( 'Upgrade %s', 'cart-rest-api-for-woocommerce' ), 'CoCart' );
				$breadcrumb = $title;
				break;
			default:
				$title      = apply_filters( 'cocart_page_title_' . strtolower( str_replace( '-', '_', $section ) ), 'CoCart' );
				$breadcrumb = apply_filters( 'cocart_page_wc_bar_breadcrumb_' . strtolower( str_replace( '-', '_', $section ) ), '' );
				break;
		}

		$page = admin_url( 'admin.php' );

		// Add CoCart page.
		add_menu_page(
			$title,
			'CoCart',
			apply_filters( 'cocart_screen_capability', 'manage_options' ),
			'cocart',
			array( $this, 'cocart_page' ),
			'dashicons-cart'
		);

		// Add Settings page as sub-menu.
		add_submenu_page(
			'cocart',
			$title,
			esc_attr__( 'Settings', 'cart-rest-api-for-woocommerce' ),
			apply_filters( 'cocart_screen_capability', 'manage_options' ),
			'cocart&section=settings',
			array( $this, 'cocart_page' )
		);

		// Add Setup Wizard as sub-menu.
		if ( apply_filters( 'cocart_enable_setup_wizard', true ) ) {
			add_submenu_page(
				'cocart',
				'',
				esc_attr__( 'Setup Wizard', 'cart-rest-api-for-woocommerce' ),
				apply_filters( 'cocart_screen_capability', 'manage_options' ),
				admin_url( 'admin.php?page=cocart-setup' )
			);
		}

		// If CoCart Pro is not active then add sub-menu to upgrade.
		if ( ! Help::is_cocart_pro_activated() ) {
			add_submenu_page(
				'cocart',
				$title,
				esc_attr__( 'Upgrade', 'cart-rest-api-for-woocommerce' ),
				apply_filters( 'cocart_screen_capability', 'manage_options' ),
				'cocart&section=upgrade',
				array( $this, 'cocart_page' )
			);
		}

		// Register WooCommerce Admin Bar.
		if ( Help::is_wc_version_gte( '4.0' ) && function_exists( 'wc_admin_connect_page' ) ) {
			wc_admin_connect_page(
				array(
					'id'        => 'cocart-getting-started',
					'screen_id' => 'toplevel_page_cocart',
					'title'     => array(
						esc_html__( 'CoCart', 'cart-rest-api-for-woocommerce' ),
						$breadcrumb,
					),
					'path'      => add_query_arg(
						array(
							'page'    => 'cocart',
							'section' => $section,
						),
						$page
					),
				)
			);
		}

		/**
		 * Moves CoCart menu to the new WooCommerce Navigation Menu if it exists.
		 *
		 * @since 3.0.0
		 */
		if (
			method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_plugin_category' ) &&
			method_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu', 'add_plugin_item' ) &&
			apply_filters( 'cocart_wc_navigation', true )
		) {
			// Add Category.
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_category(
				array(
					'id'     => 'cocart-category',
					'title'  => 'CoCart',
					'parent' => 'woocommerce',
				)
			);

			// Getting Started.
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
				array(
					'id'         => 'cocart',
					'title'      => esc_attr__( 'Getting Started', 'cart-rest-api-for-woocommerce' ),
					'capability' => apply_filters( 'cocart_screen_capability', 'manage_options' ),
					'url'        => 'cocart',
					'parent'     => 'cocart-category',
				)
			);

			// Settings
			\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
				array(
					'id'         => 'cocart-settings',
					'title'      => esc_attr__( 'Settings', 'cart-rest-api-for-woocommerce' ),
					'capability' => apply_filters( 'cocart_screen_capability', 'manage_options' ),
					'url'        => 'cocart&section=settings',
					'parent'     => 'cocart-category',
				)
			);

			// Setup Wizard
			if ( apply_filters( 'cocart_enable_setup_wizard', true ) ) {
				\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
					array(
						'id'         => 'cocart-setup-wizard',
						'title'      => esc_attr__( 'Setup Wizard', 'cart-rest-api-for-woocommerce' ),
						'capability' => apply_filters( 'cocart_screen_capability', 'manage_options' ),
						'url'        => admin_url( 'admin.php?page=cocart-setup' ),
						'parent'     => 'cocart-category',
					)
				);
			}

			// Upgrade
			if ( ! Help::is_cocart_pro_activated() ) {
				\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
					array(
						'id'         => 'cocart-upgrade',
						'title'      => esc_attr__( 'Upgrade', 'cart-rest-api-for-woocommerce' ),
						'capability' => apply_filters( 'cocart_screen_capability', 'manage_options' ),
						'url'        => 'cocart&section=upgrade',
						'parent'     => 'cocart-category',
					)
				);
			}
		}
	} // END admin_menu()

	/**
	 * CoCart Page
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.1 Introduced.
	 * @since 4.0.0 Added global `$current_section` and `$current_tab`
	 */
	public static function cocart_page() {
		global $current_section, $current_tab;

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_section = empty( $_REQUEST['section'] ) ? 'getting-started' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		switch ( $current_section ) {
			case 'getting-started':
				self::get_header( 'getting-started' );
				self::getting_started_content();
				self::get_footer();
				break;

			case 'settings':
				self::settings_page();
				break;

			case 'upgrade':
				self::get_header( 'upgrade-cocart' );
				self::upgrade_cocart_content();
				self::get_footer();
				break;

			default:
				self::get_header( $current_section );

				/**
				 * Triggers when the current section specified is custom.
				 *
				 * @since 2.0.1 Introduced.
				 */
				do_action( 'cocart_page_section_' . strtolower( str_replace( '-', '_', $current_section ) ) );

				self::get_footer();
				break;
		}
	} // END cocart_page()

	/**
	 * Gets the CoCart page header.
	 *
	 * @access protected
	 *
	 * @since 4.0.0 Introduced.
	 *
	 * @param string $section The class used to identify the page.
	 */
	protected static function get_header( $section = '' ) {
		include_once dirname( __FILE__ ) . '/views/templates/page-header.php';
	} // END get_header()

	/**
	 * Gets the CoCart page footer.
	 *
	 * @access protected
	 *
	 * @since 4.0.0 Introduced.
	 */
	protected static function get_footer() {
		include_once dirname( __FILE__ ) . '/views/templates/page-footer.php';
	} // END get_footer()

	/**
	 * Getting Started content.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since   2.0.0 Introduced.
	 * @version 2.6.0
	 */
	public static function getting_started_content() {
		include_once dirname( __FILE__ ) . '/views/html-getting-started.php';
	} // END getting_started_content()

	/**
	 * CoCart settings page.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 4.0.0 Introduced.
	 */
	public static function settings_page() {
		include_once dirname( __FILE__ ) . '/views/html-admin-settings.php';
	} // END settings_page()

	/**
	 * Upgrade CoCart content.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 3.1.0 Introduced.
	 */
	public static function upgrade_cocart_content() {
		include_once dirname( __FILE__ ) . '/views/html-upgrade-cocart.php';
	} // END upgrade_cocart_content()

	/**
	 * Sets the sub-menu active if viewing a specific section.
	 *
	 * @access public
	 *
	 * @since   3.1.0 Introduced.
	 * @version 4.0.0
	 *
	 * @param $parent_file string The parent file.
	 *
	 * @return string The parent file.
	 */
	public function highlight_submenu( $parent_file ) {
		global $plugin_page;

		$section = ! isset( $_GET['section'] ) ? '' : trim( sanitize_key( wp_unslash( $_GET['section'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'cocart' === $plugin_page && 'settings' === $section ) {
			$plugin_page = 'cocart&section=settings'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( 'cocart' === $plugin_page && 'upgrade' === $section ) {
			$plugin_page = 'cocart&section=upgrade'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $parent_file;
	} // END highlight_submenu()

} // END class

return new Menus();
