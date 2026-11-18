<?php
/**
 * Data contract for fields that code relies on (queries, structured data, platform blocks).
 * Registered with core so it does not depend on the builder alone. Keys match the builder field names.
 *
 * @package Cms\ContentModel
 */

namespace Cms\ContentModel\Meta;

use const Cms\ContentModel\PREFIX;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizer for 'Y-m-d H:i:s' date-times; anything else becomes empty.
 *
 * @param mixed $value Value.
 * @return string
 */
function datetime( $value ): string {
	return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $value ) ? (string) $value : '';
}

/**
 * Sanitizer factory for bounded floats (coordinates).
 *
 * @param float $min Min.
 * @param float $max Max.
 * @return callable
 */
function bounded_float( float $min, float $max ): callable {
	return static function ( $value ) use ( $min, $max ) {
		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			return '';
		}
		$f = (float) $value;
		return ( $f < $min || $f > $max ) ? '' : $f;
	};
}

/**
 * Register meta. All of these appear on the public page, so exposing them in REST is safe.
 */
function register(): void {
	$auth     = static fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id );
	$contract = array(
		PREFIX . '_event'    => array(
			'event_start' => array( 'string', __NAMESPACE__ . '\\datetime' ),
			'event_end'   => array( 'string', __NAMESPACE__ . '\\datetime' ),
		),
		PREFIX . '_alert'    => array(
			'alert_severity' => array( 'string', static fn( $v ) => in_array( $v, array( 'info', 'warning', 'critical' ), true ) ? $v : 'info' ),
			'alert_expires'  => array( 'string', __NAMESPACE__ . '\\datetime' ),
		),
		PREFIX . '_location' => array(
			'location_address'   => array( 'string', 'sanitize_textarea_field' ),
			'location_phone'     => array( 'string', 'sanitize_text_field' ),
			'location_latitude'  => array( 'number', bounded_float( -90, 90 ) ),
			'location_longitude' => array( 'number', bounded_float( -180, 180 ) ),
		),
	);
	foreach ( $contract as $post_type => $fields ) {
		foreach ( $fields as $key => list( $type, $sanitize ) ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => $auth,
					'show_in_rest'      => true,
				)
			);
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\register', 20 );
