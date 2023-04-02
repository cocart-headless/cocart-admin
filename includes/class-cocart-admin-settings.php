<?php
/**
 * Manages the settings for CoCart.
 *
 * @author  Sébastien Dumont
 * @package CoCart\Admin\Settings
 * @since   4.0.0
 * @license GPL-2.0+
 */

namespace CoCart\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * Setting pages.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Error messages.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $errors = array();

	/**
	 * Update messages.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $messages = array();

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		self::prep_settings_page();
	}

	/**
	 * Include the settings page classes.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return array Returns an array of settings.
	 */
	public static function prep_settings_page() {
		if ( empty( self::$settings ) ) {
			include_once dirname( __FILE__ ) . '/abstracts/abstract-cocart-settings.php';

			$settings = array(
				'general' => include dirname( __FILE__ ) . '/settings/class-cocart-admin-settings-general.php',
			);

			self::$settings = apply_filters( 'cocart_get_settings_pages', $settings );
		}

		return self::$settings;
	} // END prep_settings_page()

	/**
	 * Returns all settings or the settings for a specific section.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $section Section of settings to get.
	 *
	 * @return array
	 */
	public static function get_settings( $section = '' ) {
		if ( ! empty( $section ) && ! empty( self::$settings[ $section ] ) ) {
			return self::$settings[ $section ]->get_settings();
		} else {
			return self::$settings;
		}
	} // END get_settings()

	/**
	 * Get a setting from the settings API.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $section The section ID to fetch option under.
	 * @param mixed  $option_name Option.
	 * @param string $default Default option.
	 *
	 * @return string
	 */
	public static function get_option( $section, $option_name, $default = '' ) {
		if ( ! $option_name ) {
			return $default;
		}

		// Array value.
		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );

			// Option name is first key.
			$option_name = current( array_keys( $option_array ) );

			// Get value.
			$option_values = get_option( 'cocart_settings', '' )[ $section ][ $option_name ];

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}
		} else {
			// Single value.
			$settings = get_option( 'cocart_settings', array() );

			$option_value = ! empty( $settings ) && isset( $settings[ $section ][ $option_name ] ) ? $settings[ $section ][ $option_name ] : null;
		}

		if ( is_array( $option_value ) ) {
			$option_value = wp_unslash( $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return ( null === $option_value ) ? $default : $option_value;
	} // END get_option()

	/**
	 * Output admin fields.
	 *
	 * Loops though the plugin options array and outputs each field.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $section The section ID for the admin fields.
	 * @param array  $options Opens array to output.
	 */
	public static function output_fields( $section, $options ) {
		foreach ( $options as $key => $value ) {

			if ( ! isset( $value['type'] ) ) {
				continue;
			}
			if ( ! isset( $value['id'] ) ) {
				$value['id'] = '';
			}
			if ( ! isset( $value['title'] ) ) {
				$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
			}
			if ( ! isset( $value['class'] ) ) {
				$value['class'] = '';
			}
			if ( ! isset( $value['css'] ) ) {
				$value['css'] = '';
			}
			if ( ! isset( $value['default'] ) ) {
				$value['default'] = '';
			}
			if ( ! isset( $value['desc'] ) ) {
				$value['desc'] = '';
			}
			if ( ! isset( $value['desc_tip'] ) ) {
				$value['desc_tip'] = false;
			}
			if ( ! isset( $value['placeholder'] ) ) {
				$value['placeholder'] = '';
			}
			if ( ! isset( $value['suffix'] ) ) {
				$value['suffix'] = '';
			}
			if ( ! isset( $value['value'] ) ) {
				$value['value'] = $value['type'] !== 'title' && $value['type'] !== 'sectionend' ? self::get_option( $section, $value['id'], $value['default'] ) : '';
			}

			$value['readonly'] = isset( $value['readonly'] ) && 'yes' === $value['readonly'] ? 'readonly' : '';

			$value['disabled'] = isset( $value['disabled'] ) ? (bool) $value['disabled'] : false;

			// Custom attribute handling.
			$custom_attributes = array();

			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			// Description handling.
			$field_description = self::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];

			// Switch based on type.
			switch ( $value['type'] ) {

				// Section Titles.
				case 'title':
					if ( ! empty( $value['title'] ) ) {
						echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
					}
					if ( ! empty( $value['desc'] ) ) {
						echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
						echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
						echo '</div>';
					}
					echo '<table class="form-table" id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-settings">' . "\n\n";
					if ( ! empty( $value['id'] ) ) {
						do_action( 'cocart_settings_' . sanitize_title( $value['id'] ) );
					}

					break;

				// Section Ends.
				case 'sectionend':
					if ( ! empty( $value['id'] ) ) {
						do_action( 'cocart_settings_' . sanitize_title( $value['id'] ) . '_end' );
					}
					echo '</table>';
					if ( ! empty( $value['id'] ) ) {
						do_action( 'cocart_settings_' . sanitize_title( $value['id'] ) . '_after' );
					}
					break;

				// Standard text inputs and subtypes like 'number'.
				case 'text':
				case 'password':
				case 'datetime':
				case 'datetime-local':
				case 'date':
				case 'month':
				case 'time':
				case 'week':
				case 'number':
				case 'email':
				case 'url':
				case 'tel':
					$option_value = $value['value'];

					$salt_key_defined = defined( 'COCART_SALT_KEY' ) && ! empty( COCART_SALT_KEY ) ? true : false;

					if ( $value['id'] === 'salt_key' ) {
						$option_value = $salt_key_defined ? md5( COCART_SALT_KEY ) : $value['value'];
					}

					$show_label = empty( $value['title'] ) ? ' style="width:0% !important;"' : '';
					$no_padding = ! empty( $show_label ) ? ' style="padding-left:0px !important; padding-right:0px !important;"' : '';
					?><tr valign="top">
						<th scope="row" class="titledesc"<?php echo $show_label; ?>>
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo esc_html( $tooltip_html ); ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>"<?php echo $no_padding; ?>>
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="<?php echo esc_attr( $value['type'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								value="<?php echo sanitize_text_field( $option_value ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								<?php
								if ( $value['disabled'] || $value['id'] === 'salt_key' && $salt_key_defined ) {
									echo 'disabled="disabled"'; }
								?>
								<?php echo implode( ' ', $custom_attributes ); ?>
								/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; ?>
						</td>
					</tr>
					<?php
					break;

				// Textarea.
				case 'textarea':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo esc_html( $tooltip_html ); ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<?php echo $description; ?>

							<textarea
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); ?>
								><?php echo esc_textarea( $option_value ); ?></textarea>
						</td>
					</tr>
					<?php
					break;

				// Select boxes.
				case 'select':
				case 'multiselect':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo esc_html( $tooltip_html ); ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<select
								name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); ?>
								<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
								<?php
								if ( $value['disabled'] ) {
									echo 'disabled="disabled"'; }
								?>
								>
								<?php
								foreach ( $value['options'] as $key => $val ) {
									?>
									<option value="<?php echo esc_attr( $key ); ?>"
										<?php

										if ( is_array( $option_value ) ) {
											selected( in_array( (string) $key, $option_value, true ), true );
										} else {
											selected( $option_value, (string) $key );
										}

										?>
									><?php echo esc_html( $val ); ?></option>
									<?php
								}
								?>
							</select> <?php echo $description; ?>
						</td>
					</tr>
					<?php
					break;

				// Radio inputs.
				case 'radio':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo esc_html( $tooltip_html ); ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<fieldset>
								<?php echo esc_html( $description ); ?>
								<ul>
								<?php
								foreach ( $value['options'] as $key => $val ) {
									?>
									<li>
										<label><input
											name="<?php echo esc_attr( $value['id'] ); ?>"
											value="<?php echo esc_attr( $key ); ?>"
											type="radio"
											style="<?php echo esc_attr( $value['css'] ); ?>"
											class="<?php echo esc_attr( $value['class'] ); ?>"
											<?php echo implode( ' ', $custom_attributes ); ?>
											<?php checked( $key, $option_value ); ?>
											/> <?php echo esc_html( $val ); ?></label>
									</li>
									<?php
								}
								?>
								</ul>
							</fieldset>
						</td>
					</tr>
					<?php
					break;

				// Checkbox input.
				case 'checkbox':
					$option_value     = $value['value'];
					$visibility_class = array();
					$show_label       = empty( $value['title'] ) ? ' style="width:0% !important;"' : '';
					$no_padding       = ! empty( $show_label ) ? ' style="padding-left:0px !important; padding-right:0px !important;"' : '';

					if ( ! isset( $value['hide_if_checked'] ) ) {
						$value['hide_if_checked'] = false;
					}
					if ( ! isset( $value['show_if_checked'] ) ) {
						$value['show_if_checked'] = false;
					}
					if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
						$visibility_class[] = 'hidden_option';
					}
					if ( 'option' === $value['hide_if_checked'] ) {
						$visibility_class[] = 'hide_options_if_checked';
					}
					if ( 'option' === $value['show_if_checked'] ) {
						$visibility_class[] = 'show_options_if_checked';
					}

					if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
						?>
							<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
								<th scope="row" class="titledesc"<?php echo $show_label; ?>><?php echo esc_html( $value['title'] ); ?></th>
								<td class="forminp forminp-checkbox"<?php echo $no_padding; ?>>
									<fieldset>
						<?php
					} else {
						?>
							<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
						<?php
					}

					if ( ! empty( $value['title'] ) ) {
						?>
							<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
						<?php
					}

					?>
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
								value="1"
								<?php checked( $option_value, 'yes' ); ?>
								<?php echo implode( ' ', $custom_attributes ); ?>
							/> <?php echo esc_html( $description ); ?>
						</label> <?php echo esc_html( $tooltip_html ); ?>
					<?php

					if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
						?>
									</fieldset>
								</td>
							</tr>
						<?php
					} else {
						?>
							</fieldset>
						<?php
					}
					break;

				case 'single_select_page_with_search':
					$option_value = $value['value'];
					$page         = get_post( $option_value );

					if ( ! is_null( $page ) ) {
						$page                = get_post( $option_value );
						$option_display_name = sprintf(
							/* translators: 1: page name 2: page ID */
							__( '%1$s (ID: %2$s)', 'cart-rest-api-for-woocommerce' ),
							$page->post_title,
							$option_value
						);
					}
					?>
					<tr valign="top" class="single_select_page">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<select
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								data-placeholder="<?php esc_attr_e( 'Search for a page&hellip;', 'cart-rest-api-for-woocommerce' ); ?>"
								data-allow_clear="true"
								data-exclude="<?php echo wc_esc_json( wp_json_encode( $value['args']['exclude'] ) ); ?>"
								>
								<option value=""></option>
								<?php
								if ( ! empty( $value['options'] ) ) {
									foreach ( $value['options'] as $option => $value ) {
										echo '<option value="' . $value . '">' . $option . '</option>';
									}
								}
								?>
								<?php if ( ! is_null( $page ) ) { ?>
									<option value="<?php echo esc_attr( $option_value ); ?>" selected="selected">
									<?php echo wp_strip_all_tags( $option_display_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</option>
								<?php } ?>
							</select> <?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
					<?php
					break;

				case 'button':
					if ( isset( $value['url'] ) && ! empty( $value['url'] ) ) {
						?>
					<tr valign="top" id="button_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<a href="<?php echo esc_html( $value['url'] ); ?>" class="button button-secondary <?php echo esc_attr( $value['class'] ); ?>">
								<?php echo esc_html( $value['value'] ); ?>
							</a>
						</th>
						<td>
							<?php echo $description; ?>
						</td>
					</tr>
						<?php
					}
					break;

				// Default: run an action.
				default:
					do_action( 'cocart_admin_field_' . $value['type'], $value );

					break;
			} // end switch()
		} // END foreach()
	} // END output_fields()

	/**
	 * Helper function to get the formatted description and tip HTML for a
	 * given form field. Plugins can call this when implementing their own custom
	 * settings types.
	 *
	 * @access public
	 *
	 * @param array $value The form field value array.
	 *
	 * @return array The description and tip as a 2 element array.
	 */
	public static function get_field_description( $value ) {
		$description  = '';
		$tooltip_html = '';

		if ( true === $value['desc_tip'] ) {
			$tooltip_html = $value['desc'];
		} elseif ( ! empty( $value['desc_tip'] ) ) {
			$description  = $value['desc'];
			$tooltip_html = $value['desc_tip'];
		} elseif ( ! empty( $value['desc'] ) ) {
			$description = $value['desc'];
		}

		if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
			$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
		} elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
			$description = wp_kses_post( $description );
		} elseif ( $description ) {
			$description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
		}

		if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
			$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
		} elseif ( $tooltip_html ) {
			$tooltip_html = wc_help_tip( $tooltip_html );
		}

		return array(
			'description'  => $description,
			'tooltip_html' => $tooltip_html,
		);
	} // END get_field_description()

} // END class.

return new Settings();
