<?php
/**
 * Server render for cmscoe/entry-fields. Reads the field groups that apply to the current entry from
 * the builder and outputs each non-empty field, escaped late for its type. Unsupported field types are
 * skipped rather than echoed raw.
 *
 * @package CmsCoe\Platform
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'acf_get_field_groups' ) ) {
	return;
}

$cmscoe_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
if ( ! $cmscoe_post_id ) {
	return;
}

/**
 * Field names that should never be shown publicly. Sites can add more.
 *
 * @param string[] $hidden Field names.
 * @param int      $post_id Post ID.
 */
$cmscoe_hidden = (array) apply_filters( 'cmscoe_entry_fields_hidden', array(), $cmscoe_post_id );

$cmscoe_date = static function ( string $raw, bool $with_time ): string {
	$ts = $with_time ? strtotime( $raw ) : strtotime( preg_replace( '/^(\d{4})(\d{2})(\d{2})$/', '$1-$2-$3', $raw ) );
	if ( ! $ts ) {
		return '';
	}
	$format = get_option( 'date_format' ) . ( $with_time ? ' ' . get_option( 'time_format' ) : '' );
	return sprintf( '<time datetime="%1$s">%2$s</time>', esc_attr( gmdate( $with_time ? 'Y-m-d\TH:i' : 'Y-m-d', $ts ) ), esc_html( date_i18n( $format, $ts ) ) );
};

$cmscoe_links = static function ( array $ids ): string {
	$items = array();
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id && 'publish' === get_post_status( $id ) ) {
			$items[] = '<a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
		}
	}
	return implode( ', ', $items );
};

/**
 * Render one field value to safe HTML, or '' when empty/unsupported.
 */
$cmscoe_value = static function ( array $field ) use ( $cmscoe_post_id, $cmscoe_date, $cmscoe_links ): string {
	$raw = get_field( $field['name'], $cmscoe_post_id, false );
	if ( null === $raw || '' === $raw || array() === $raw || ( false === $raw && 'true_false' !== $field['type'] ) ) {
		return '';
	}
	switch ( $field['type'] ) {
		case 'text':
		case 'number':
		case 'range':
			return esc_html( (string) $raw );
		case 'textarea':
			return nl2br( esc_html( (string) $raw ) );
		case 'wysiwyg':
			return wp_kses_post( wpautop( (string) $raw ) );
		case 'email':
			return is_email( $raw ) ? '<a href="' . esc_url( 'mailto:' . $raw ) . '">' . esc_html( $raw ) . '</a>' : '';
		case 'url':
			return '<a href="' . esc_url( (string) $raw ) . '">' . esc_html( wp_parse_url( (string) $raw, PHP_URL_HOST ) ? (string) $raw : __( 'Link', 'cmscoe-platform' ) ) . '</a>';
		case 'date_picker':
			return $cmscoe_date( (string) $raw, false );
		case 'date_time_picker':
			return $cmscoe_date( (string) $raw, true );
		case 'true_false':
			return $raw ? esc_html__( 'Yes', 'cmscoe-platform' ) : '';
		case 'select':
		case 'radio':
		case 'checkbox':
		case 'button_group':
			$labels = array();
			foreach ( (array) $raw as $choice ) {
				$labels[] = isset( $field['choices'][ $choice ] ) ? (string) $field['choices'][ $choice ] : (string) $choice;
			}
			if ( count( $labels ) > 1 ) {
				return '<ul class="cmscoe-entry-fields__tags"><li>' . implode( '</li><li>', array_map( 'esc_html', $labels ) ) . '</li></ul>';
			}
			return esc_html( (string) reset( $labels ) );
		case 'image':
			$img = wp_get_attachment_image( (int) $raw, 'medium_large', false, array( 'loading' => 'lazy' ) );
			return $img ? $img : '';
		case 'file':
			$url = wp_get_attachment_url( (int) $raw );
			if ( ! $url ) {
				return '';
			}
			$path = get_attached_file( (int) $raw );
			$size = ( $path && file_exists( $path ) ) ? size_format( (int) filesize( $path ) ) : '';
			$ext  = strtoupper( (string) pathinfo( $url, PATHINFO_EXTENSION ) );
			/* translators: 1: file title, 2: file type such as PDF, 3: file size. */
			$text = trim( sprintf( __( '%1$s (%2$s %3$s)', 'cmscoe-platform' ), get_the_title( (int) $raw ), $ext, $size ) );
			return '<a href="' . esc_url( $url ) . '">' . esc_html( str_replace( ' ()', '', $text ) ) . '</a>';
		case 'post_object':
		case 'relationship':
		case 'page_link':
			return $cmscoe_links( array_filter( (array) $raw, 'is_numeric' ) );
		case 'taxonomy':
			$items = array();
			foreach ( (array) $raw as $term_id ) {
				$term = get_term( (int) $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$link    = get_term_link( $term );
					$items[] = is_wp_error( $link ) ? esc_html( $term->name ) : '<a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
				}
			}
			return implode( ', ', $items );
		case 'link':
			$link = is_array( $raw ) ? $raw : array();
			return empty( $link['url'] ) ? '' : '<a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['title'] ? $link['title'] : $link['url'] ) . '</a>';
		default:
			return ''; // Unsupported (repeater, group, flexible content, maps): render with a site block.
	}
};

$cmscoe_sections = array();
foreach ( acf_get_field_groups( array( 'post_id' => $cmscoe_post_id ) ) as $cmscoe_group ) {
	$cmscoe_rows = array();
	foreach ( (array) acf_get_fields( $cmscoe_group ) as $cmscoe_field ) {
		if ( in_array( $cmscoe_field['name'], $cmscoe_hidden, true ) ) {
			continue;
		}
		$cmscoe_html = $cmscoe_value( $cmscoe_field );
		if ( '' !== $cmscoe_html ) {
			$cmscoe_rows[] = array( $cmscoe_field['label'], $cmscoe_html );
		}
	}
	if ( $cmscoe_rows ) {
		$cmscoe_sections[] = array( $cmscoe_group['title'], $cmscoe_rows );
	}
}
if ( ! $cmscoe_sections ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-entry-fields' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php foreach ( $cmscoe_sections as list( $cmscoe_title, $cmscoe_rows ) ) : ?>
		<?php if ( ! empty( $attributes['showGroupHeadings'] ) ) : ?>
			<h2 class="cmscoe-entry-fields__heading"><?php echo esc_html( $cmscoe_title ); ?></h2>
		<?php endif; ?>
		<dl class="cmscoe-entry-fields__list">
			<?php foreach ( $cmscoe_rows as list( $cmscoe_label, $cmscoe_html ) ) : ?>
				<div class="cmscoe-entry-fields__row">
					<dt><?php echo esc_html( $cmscoe_label ); ?></dt>
					<dd><?php echo $cmscoe_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per field type above. ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endforeach; ?>
</div>
