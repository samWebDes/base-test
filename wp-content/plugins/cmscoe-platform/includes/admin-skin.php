<?php
/**
 * Admin skin and custom two-column login page (admin-skin.md).
 * Settings: Settings -> Admin skin (cmscoe_manage_admin_skin, Administrator only).
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\AdminSkin;

defined( 'ABSPATH' ) || exit;

const OPTION = 'cmscoe_admin_skin';

/**
 * Settings with defaults that work with no configuration.
 *
 * @return array{primary:string,secondary:string,logo_id:int,background_id:int,tagline:string}
 */
function settings(): array {
	$defaults = array(
		'primary'       => '#1F2933',
		'secondary'     => '#2271B1',
		'logo_id'       => 0,
		'background_id' => 0,
		'tagline'       => '',
	);
	$saved    = get_option( OPTION, array() );
	return array_merge( $defaults, is_array( $saved ) ? array_intersect_key( $saved, $defaults ) : array() );
}

/**
 * WCAG relative luminance contrast ratio between two hex colors.
 *
 * @param string $a Hex.
 * @param string $b Hex.
 * @return float
 */
function contrast( string $a, string $b ): float {
	$lum = static function ( string $hex ): float {
		$hex = ltrim( $hex, '#' );
		$c   = array_map( static fn( $i ) => hexdec( substr( $hex, $i, 2 ) ) / 255, array( 0, 2, 4 ) );
		$c   = array_map( static fn( $v ) => $v <= 0.03928 ? $v / 12.92 : ( ( $v + 0.055 ) / 1.055 ) ** 2.4, $c );
		return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
	};
	$l   = array( $lum( $a ), $lum( $b ) );
	rsort( $l );
	return ( $l[0] + 0.05 ) / ( $l[1] + 0.05 );
}

/**
 * Sanitize settings; reject colors that fail 4.5:1 against white text.
 *
 * @param mixed $input Raw.
 * @return array
 */
function sanitize( $input ): array {
	$current = settings();
	$input   = is_array( $input ) ? $input : array();
	$out     = $current;
	foreach ( array( 'primary', 'secondary' ) as $key ) {
		$color = sanitize_hex_color( $input[ $key ] ?? '' );
		if ( $color && contrast( $color, '#ffffff' ) >= 4.5 ) {
			$out[ $key ] = $color;
		} elseif ( $color ) {
			add_settings_error(
				OPTION,
				'contrast_' . $key,
				/* translators: %s: color hex. */
				sprintf( __( '%s does not reach 4.5:1 contrast with white text and was not saved.', 'cmscoe-platform' ), $color )
			);
		}
	}
	foreach ( array( 'logo_id', 'background_id' ) as $key ) {
		$id          = absint( $input[ $key ] ?? 0 );
		$out[ $key ] = ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}
	$out['tagline'] = sanitize_text_field( $input['tagline'] ?? '' );
	return $out;
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'cmscoe_admin_skin',
			OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
				'show_in_rest'      => false,
			)
		);
	}
);

// Settings API saves via options.php which checks manage_options; tighten to our capability.
add_filter( 'option_page_capability_cmscoe_admin_skin', static fn() => 'cmscoe_manage_admin_skin' );

add_action(
	'admin_menu',
	static function () {
		add_options_page( __( 'Admin skin', 'cmscoe-platform' ), __( 'Admin skin', 'cmscoe-platform' ), 'cmscoe_manage_admin_skin', 'cmscoe-admin-skin', __NAMESPACE__ . '\\render_page' );
	}
);

/**
 * Settings page.
 */
function render_page(): void {
	if ( ! current_user_can( 'cmscoe_manage_admin_skin' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to manage the Admin skin.', 'cmscoe-platform' ), '', array( 'response' => 403 ) );
	}
	$s = settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Admin skin', 'cmscoe-platform' ); ?></h1>
		<?php settings_errors( OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'cmscoe_admin_skin' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="cmscoe-primary"><?php esc_html_e( 'Primary color', 'cmscoe-platform' ); ?></label></th>
					<td><input type="color" id="cmscoe-primary" name="<?php echo esc_attr( OPTION ); ?>[primary]" value="<?php echo esc_attr( $s['primary'] ); ?>" aria-describedby="cmscoe-primary-help">
					<p class="description" id="cmscoe-primary-help"><?php esc_html_e( 'Sidebar and header background. Must reach 4.5:1 contrast with white text.', 'cmscoe-platform' ); ?></p></td></tr>
				<tr><th scope="row"><label for="cmscoe-secondary"><?php esc_html_e( 'Secondary color', 'cmscoe-platform' ); ?></label></th>
					<td><input type="color" id="cmscoe-secondary" name="<?php echo esc_attr( OPTION ); ?>[secondary]" value="<?php echo esc_attr( $s['secondary'] ); ?>" aria-describedby="cmscoe-secondary-help">
					<p class="description" id="cmscoe-secondary-help"><?php esc_html_e( 'Buttons, links, and active states. Must reach 4.5:1 contrast with white text.', 'cmscoe-platform' ); ?></p></td></tr>
				<?php
				media_field( 'logo_id', __( 'Logo', 'cmscoe-platform' ), __( 'Shown in the login page header. SVG or PNG on a transparent background works best.', 'cmscoe-platform' ), $s['logo_id'] );
				media_field( 'background_id', __( 'Login background image', 'cmscoe-platform' ), __( 'Decorative only; do not put text in the image. Keep it under 400 KB.', 'cmscoe-platform' ), $s['background_id'] );
				?>
				<tr><th scope="row"><label for="cmscoe-tagline"><?php esc_html_e( 'Login tagline', 'cmscoe-platform' ); ?></label></th>
					<td><input type="text" class="regular-text" id="cmscoe-tagline" name="<?php echo esc_attr( OPTION ); ?>[tagline]" value="<?php echo esc_attr( $s['tagline'] ); ?>"></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Media-library picker row. The hidden ID input is what gets saved; the buttons are progressive
 * enhancement, and without JavaScript the ID can still be typed into the visible number field.
 *
 * @param string $key   Setting key.
 * @param string $label Label.
 * @param string $help  Help text.
 * @param int    $id    Current attachment ID.
 */
function media_field( string $key, string $label, string $help, int $id ): void {
	$field_id = 'cmscoe-' . str_replace( '_', '-', $key );
	$preview  = $id ? wp_get_attachment_image( $id, 'medium', false, array( 'alt' => '' ) ) : '';
	?>
	<tr><th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td class="cmscoe-media-field" data-cmscoe-media>
			<div class="cmscoe-media-field__preview" aria-live="polite"><?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?></div>
			<input type="number" min="0" class="small-text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" aria-describedby="<?php echo esc_attr( $field_id ); ?>-help">
			<button type="button" class="button cmscoe-media-field__choose" hidden><?php esc_html_e( 'Choose image', 'cmscoe-platform' ); ?></button>
			<button type="button" class="button-link cmscoe-media-field__remove" hidden><?php esc_html_e( 'Remove', 'cmscoe-platform' ); ?></button>
			<p class="description" id="<?php echo esc_attr( $field_id ); ?>-help"><?php echo esc_html( $help ); ?></p>
		</td></tr>
	<?php
}

/**
 * Load the media picker on the Admin skin screen only.
 *
 * @param string $hook Admin page hook.
 */
function settings_assets( string $hook ): void {
	if ( 'settings_page_cmscoe-admin-skin' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'cmscoe-admin-skin-media', plugins_url( 'assets/js/admin-skin-media.js', __DIR__ ), array( 'jquery', 'media-editor', 'wp-i18n' ), \CmsCoe\Platform\VERSION, true );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\settings_assets' );

/**
 * CSS custom properties generated from settings (single stylesheet adapts to any values).
 *
 * @return string
 */
function css_vars(): string {
	$s   = settings();
	$bg  = $s['background_id'] ? wp_get_attachment_image_url( $s['background_id'], 'full' ) : '';
	$css = sprintf( ':root{--cmscoe-skin-primary:%s;--cmscoe-skin-secondary:%s;', $s['primary'], $s['secondary'] );
	if ( $bg ) {
		$css .= '--cmscoe-skin-bg:url("' . esc_url_raw( $bg ) . '");';
	}
	return $css . '}';
}

/**
 * Register the admin color scheme so users get the brand via core's color-scheme API.
 */
add_action(
	'admin_init',
	static function () {
		$s = settings();
		wp_admin_css_color( 'cmscoe', __( 'Agency', 'cmscoe-platform' ), admin_url( 'css/colors/modern/colors.min.css' ), array( $s['primary'], $s['secondary'], '#ffffff', '#f0f0f1' ) );
	}
);

add_filter( 'get_user_option_admin_color', static fn( $color ) => ( empty( $color ) || 'fresh' === $color ) ? 'cmscoe' : $color );

add_action(
	'admin_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'cmscoe-admin-skin', plugins_url( 'assets/admin-skin.css', __DIR__ ), array( 'colors' ), \CmsCoe\Platform\VERSION );
		wp_add_inline_style( 'cmscoe-admin-skin', css_vars() );
	}
);

add_action(
	'login_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'cmscoe-login', plugins_url( 'assets/login.css', __DIR__ ), array( 'login' ), \CmsCoe\Platform\VERSION );
		wp_add_inline_style( 'cmscoe-login', css_vars() );
		$s = settings();
		if ( $s['logo_id'] ) {
			$logo = wp_get_attachment_image_url( $s['logo_id'], 'medium' );
			if ( $logo ) {
				wp_add_inline_style( 'cmscoe-login', '.login h1 a{background-image:url("' . esc_url_raw( $logo ) . '")}' );
			}
		}
	}
);

add_filter( 'login_body_class', static fn( array $c ) => array_merge( $c, array( 'cmscoe-login' ) ) );
add_filter( 'login_headerurl', static fn() => home_url( '/' ) );
add_filter( 'login_headertext', static fn() => get_bloginfo( 'name' ) );

add_action(
	'login_header',
	static function () {
		$s = settings();
		echo '<div class="cmscoe-login__aside" aria-hidden="true">';
		if ( $s['tagline'] ) {
			echo '<p class="cmscoe-login__tagline">' . esc_html( $s['tagline'] ) . '</p>';
		}
		echo '</div>';
	}
);
