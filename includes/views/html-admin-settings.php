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

global $current_tab;
?>
<div class="wrap cocart settings-page">

	<header class="logo" style="background: #6032b0 url(<?php echo esc_url( COCART_ADMIN_URL_PATH . '/assets/images/brand/header-logo-small.png' ); ?>) 2rem center no-repeat;">
	</header>

	<div class="container">

		<h2><?php echo apply_filters( 'cocart_setting_label_' . $current_tab, esc_html__( 'Headless Settings', 'cart-rest-api-for-woocommerce' ) ); ?></h2>

		<div class="content">

			<form method="post" id="settings-form" action="" enctype="multipart/form-data">

			<div class="save-results"></div>

			<?php
			CoCart\Admin\Settings::show_messages();

			do_action( 'cocart_settings_' . $current_tab );
			?>

			<?php
			if ( ! empty( CoCart\Admin\Settings::get_settings( $current_tab ) ) ) {
				submit_button( esc_attr__( 'Save Changes', 'cart-rest-api-for-woocommerce' ), 'primary', 'save-cocart', true );
			}
			?>
			<input type="hidden" name="cocart-settings" value="<?php echo $current_tab; ?>" />
			<?php wp_nonce_field( 'cocart-settings' ); ?>

			</form>

		</div>
	</div>

</div>
