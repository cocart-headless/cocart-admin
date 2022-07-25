<?php
/**
 * Adds links for CoCart on the plugins page.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin
 * @since   1.2.0
 * @version 4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

use CoCart\Help;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PluginActionLinks {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( COCART_FILE ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );
	} // END __construct()

	/**
	 * Plugin action links.
	 *
	 * @access  public
	 * @since   2.0.0
	 * @version 3.7.2
	 * @param   array $links An array of plugin links.
	 * @return  array $links
	 */
	public function plugin_action_links( $links ) {
		if ( version_compare( get_option( 'cocart_version' ), COCART_VERSION, '<' ) ) {
			return $links;
		}

		$page = admin_url( 'admin.php' );

		if ( current_user_can( 'manage_options' ) ) {
			$action_links = array(
				'getting-started' => '<a href="' . add_query_arg(
					array(
						'page'    => 'cocart',
						'section' => 'getting-started',
					),
					$page
					/* translators: %s: CoCart */
				) . '" aria-label="' . sprintf( esc_attr__( 'Getting Started with %s', 'cart-rest-api-for-woocommerce' ), 'CoCart' ) . '" style="color: #6032b0; font-weight: 600;">' . esc_attr__( 'Getting Started', 'cart-rest-api-for-woocommerce' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		if ( Help::is_cocart_pro_activated() ) {
			// Remove 'deactivate' link if CoCart Pro is active as well.
			// We don't want users to deactivate CoCart Lite when CoCart Pro is active.
			unset( $links['deactivate'] );

			$no_deactivation_explanation = '<span style="color: #initial">' . sprintf(
				/* translators: %s expands to CoCart Pro */
				__( 'Required by %s', 'cart-rest-api-for-woocommerce' ),
				'CoCart Pro'
			) . '</span>';

			array_unshift( $links, $no_deactivation_explanation );
		}

		return $links;
	} // END plugin_action_links()

	/**
	 * Plugin row meta links
	 *
	 * @access  public
	 * @since   2.0.0
	 * @version 3.7.2
	 * @param   array  $metadata An array of the plugin's metadata.
	 * @param   string $file     Path to the plugin file.
	 * @param   array  $data     Plugin Information.
	 * @return  array  $metadata
	 */
	public function plugin_row_meta( $metadata, $file, $data ) {
		if ( version_compare( get_option( 'cocart_version' ), COCART_VERSION, '<' ) ) {
			return $metadata;
		}

		if ( plugin_basename( COCART_FILE ) === $file ) {
			/* translators: %s: URL to author */
			$metadata[1] = sprintf( __( 'Developed By %s', 'cart-rest-api-for-woocommerce' ), '<a href="' . $data['AuthorURI'] . '" aria-label="' . esc_attr__( 'View the developers site', 'cart-rest-api-for-woocommerce' ) . '">' . $data['Author'] . '</a>' );

			if ( ! Help::is_cocart_pro_activated() ) {
				$campaign_args = Help::cocart_campaign(
					array(
						'utm_content' => 'go-pro',
					)
				);
			} else {
				$campaign_args = Help::cocart_campaign(
					array(
						'utm_content' => 'has-pro',
					)
				);
			}

			$campaign_args['utm_campaign'] = 'plugins-row';

			$row_meta = array(
				'docs'      => '<a href="' . Help::build_shortlink( add_query_arg( $campaign_args, COCART_DOCUMENTATION_URL ) ) . '" aria-label="' . sprintf(
					/* translators: %s: CoCart */
					esc_attr__( 'View %s documentation', 'cart-rest-api-for-woocommerce' ),
					'CoCart'
				) . '" target="_blank">' . esc_attr__( 'Documentation', 'cart-rest-api-for-woocommerce' ) . '</a>',
				'translate' => '<a href="' . Help::build_shortlink( add_query_arg( $campaign_args, COCART_TRANSLATION_URL ) ) . '" aria-label="' . sprintf(
					/* translators: %s: CoCart */
					esc_attr__( 'Translate %s', 'cart-rest-api-for-woocommerce' ),
					'CoCart'
				) . '" target="_blank">' . esc_attr__( 'Translate', 'cart-rest-api-for-woocommerce' ) . '</a>',
				'review'    => '<a href="' . Help::build_shortlink( add_query_arg( $campaign_args, COCART_REVIEW_URL ) ) . '" aria-label="' . sprintf(
					/* translators: %s: CoCart */
					esc_attr__( 'Review %s on WordPress.org', 'cart-rest-api-for-woocommerce' ),
					'CoCart'
				) . '" target="_blank">' . esc_attr__( 'Leave a Review', 'cart-rest-api-for-woocommerce' ) . '</a>',
			);

			// Only show upgrade option if CoCart Pro is not activated.
			if ( ! Help::is_cocart_pro_activated() ) {
				$store_url = Help::build_shortlink( add_query_arg( $campaign_args, COCART_STORE_URL . 'pro/' ) );

				/* translators: %s: CoCart Pro */
				$row_meta['upgrade'] = sprintf( '<a href="%1$s" aria-label="' . sprintf( esc_attr__( 'Upgrade to %s', 'cart-rest-api-for-woocommerce' ), 'CoCart Pro' ) . '" target="_blank" style="color: #c00; font-weight: 600;">%2$s</a>', esc_url( $store_url ), esc_attr__( 'Upgrade to Pro', 'cart-rest-api-for-woocommerce' ) );
			}

			$metadata = array_merge( $metadata, $row_meta );
		}

		return $metadata;
	} // END plugin_row_meta()

} // END class

return new PluginActionLinks();
