<?php
/**
 * Admin Template: Page Header.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Views\Templates
 * @since   4.0.0
 * @license GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap cocart <?php echo $section; ?>">

	<header>
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
		</div>
	</header>
