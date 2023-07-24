<?php
/**
 * Admin View: Settings.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Views
 * @since   4.0.0
 * @license GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sections        = CoCart\Admin\Settings::get_settings();
$has_sections    = count( $sections );
$header_position = ( $has_sections > 1 ) ? ' style="top: 36px;"' : '';
?>
<div class="wrap cocart settings-page">

	<header<?php echo $header_position; ?>>
		<div class="page-title">
			<div class="logo-image">
				<?php
				printf(
					'<img src="%1$s" srcset="%2$s 2x" alt="%3$s"/>',
					esc_url( COCART_ADMIN_URL_PATH . '/assets/images/brand/logo.png' ),
					esc_url( COCART_ADMIN_URL_PATH . '/assets/images/brand/logo@2x.png' ),
					'CoCart Logo'
				)
				?>
			</div>

			<?php if ( $has_sections > 1 ) { ?>
			<div class="logo-sep">
				<img src="<?php echo esc_url( COCART_ADMIN_URL_PATH . '/assets/images/sep.png' ); ?>" />
			</div>

				<?php
				foreach ( $sections as $page => $settings ) {
					?>
					<a href="#<?php echo $settings->get_id(); ?>" class="tab" data-target="<?php echo $settings->get_id(); ?>"><?php echo $settings->get_label(); ?></a>
					<?php
				}
			}
			?>
		</div>
	</header>

	<div class="container">

		<div class="content">

			<form method="post" id="settings-form" action="" enctype="multipart/form-data">

			<div class="save-results"></div>

			<div class="loading-settings"><?php _e( 'Loading settings', 'cart-rest-api-for-woocommerce' ); ?></div>

			<?php
			foreach ( $sections as $page => $settings ) {
				echo '<h2 id="' . $settings->get_id() . '-settings">' . $settings->get_label() . ' ' . esc_html__( 'Settings', 'cart-rest-api-for-woocommerce' ) . '</h2>';

				do_action( 'cocart_settings_page_' . $page );
			}
			?>

			<?php submit_button( esc_attr__( 'Save Changes', 'cart-rest-api-for-woocommerce' ), 'primary', 'save-cocart', true ); ?>
			<?php wp_nonce_field( 'cocart-settings' ); ?>

			</form>

		</div>
	</div>

	<footer>
	<?php
	if ( ! CoCart\Help::is_cocart_pro_activated() ) {
		$url = 'https://wordpress.org/support/plugin/cart-rest-api-for-woocommerce/reviews/?filter=5#new-post';

		echo sprintf(
			wp_kses( /* translators: $1$s - CoCart plugin name; $2$s - WP.org review link; $3$s - WP.org review link. */
				__( 'Please rate %1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%3$s" target="_blank" rel="noopener">WordPress.org</a> to help us spread the word.', 'cart-rest-api-for-woocommerce' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			'<strong>CoCart</strong>',
			$url,
			$url
		);
	}
	?>
	</footer>

</div>
