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

$campaign_args = CoCart\Help::cocart_campaign(
	array(
		'utm_content' => 'settings-page',
	)
);
?>
<div class="wrap cocart settings-page" style="margin: 0; padding: 0; position: relative; top: 0; width: 100%;">

	<header class="logo" style="height: 120px; width: 100%; background: #6032af url(<?php echo esc_url( COCART_ADMIN_URL_PATH . '/assets/images/brand/header-logo-small.png' ); ?>) 2rem center no-repeat;">
	</header>

	<div class="container" style="border: none; margin: 0; max-width: 100%; width:100%;">

		<h2 style="border-bottom: 1px solid #ddd;
font-size: 23px;
font-weight: 300;
margin-bottom: 10px;
margin-left: 40px;
margin-top: 30px;
padding-bottom: 30px;"><?php echo apply_filters( 'cocart_setting_label_' . $current_tab, esc_html__( 'Headless Settings', 'cart-rest-api-for-woocommerce' ) ); ?></h2>

		<div class="content" style="padding: 30px 38px;">

			<form method="post" id="settings-form" action="" enctype="multipart/form-data">

			<?php
			CoCart\Admin\Settings::show_messages();

			do_action( 'cocart_settings_' . $current_tab );
			?>

			<?php
			if ( ! empty( CoCart\Admin\Settings::get_settings( $current_tab ) ) ) {
				submit_button( esc_attr__( 'Save Changes', 'cart-rest-api-for-woocommerce' ), 'primary', 'save', true );
			}
			?>
			<?php wp_nonce_field( 'cocart-settings' ); ?>

			</form>

		</div>
	</div>

</div>
