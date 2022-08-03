<?php
/**
 * This file is designed to be used to load as package NOT a WP plugin!
 *
 * @version 4.0.0
 * @package CoCart Admin Package
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COCART_ADMIN_PACKAGE_FILE' ) ) {
	define( 'COCART_ADMIN_PACKAGE_FILE', __FILE__ );
}

// Include the main CoCart Admin Package class.
if ( ! class_exists( 'CoCart\Admin\Package', false ) ) {
	include_once untrailingslashit( plugin_dir_path( COCART_ADMIN_PACKAGE_FILE ) ) . '/includes/class-cocart-admin.php';
}

/**
 * Returns the main instance of cocart_admin_package and only runs if it does not already exists.
 *
 * @return cocart_admin_package
 */
if ( ! function_exists( 'cocart_admin_package' ) ) {
	function cocart_admin_package() {
		return CoCart\Admin\Package::init();
	}

	cocart_admin_package();
}
