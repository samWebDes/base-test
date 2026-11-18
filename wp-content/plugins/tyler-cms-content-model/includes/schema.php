<?php
/**
 * Schema.org JSON-LD for the default agency content types (ai-discoverability.md). Built only from
 * data that is visible on the page. The front-page GovernmentOrganization comes from the platform.
 *
 * @package Cms\ContentModel
 */

namespace Cms\ContentModel\Schema;

use const Cms\ContentModel\PREFIX;

defined( 'ABSPATH' ) || exit;

/**
 * Publisher node.
 *
 * @return array
 */
function publisher(): array {
	return array(
		'@type' => 'GovernmentOrganization',
		'name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'url'   => home_url( '/' ),
	);
}

/**
 * Place node for a Location entry.
 *
 * @param int    $id   Location ID.
 * @param string $type Schema type.
 * @return array
 */
function place( int $id, string $type = 'Place' ): array {
	$data    = array(
		'@type' => $type,
		'name'  => wp_strip_all_tags( get_the_title( $id ) ),
		'url'   => get_permalink( $id ),
	);
	$address = (string) get_post_meta( $id, 'location_address', true );
	if ( '' !== $address ) {
		$data['address'] = wp_strip_all_tags( $address );
	}
	$phone = (string) get_post_meta( $id, 'location_phone', true );
	if ( '' !== $phone ) {
		$data['telephone'] = $phone;
	}
	$lat = get_post_meta( $id, 'location_latitude', true );
	$lng = get_post_meta( $id, 'location_longitude', true );
	if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
		$data['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lng,
		);
	}
	return $data;
}

/**
 * ISO 8601 from a site-time 'Y-m-d H:i:s' value.
 *
 * @param string $value Value.
 * @return string
 */
function iso( string $value ): string {
	$date = $value ? date_create_immutable( $value, wp_timezone() ) : false;
	return $date ? $date->format( 'c' ) : '';
}

/**
 * JSON-LD for the current singular entry, or empty.
 *
 * @return array
 */
function current_entry(): array {
	$id = get_queried_object_id();
	switch ( get_post_type( $id ) ) {
		case PREFIX . '_event':
			$data  = array(
				'@context'  => 'https://schema.org',
				'@type'     => 'Event',
				'name'      => wp_strip_all_tags( get_the_title( $id ) ),
				'url'       => get_permalink( $id ),
				'startDate' => iso( (string) get_post_meta( $id, 'event_start', true ) ),
				'organizer' => publisher(),
			);
			$end   = iso( (string) get_post_meta( $id, 'event_end', true ) );
			$where = (int) get_post_meta( $id, 'event_location', true );
			$venue = (string) get_post_meta( $id, 'event_venue', true );
			if ( $end ) {
				$data['endDate'] = $end;
			}
			if ( $where && 'publish' === get_post_status( $where ) ) {
				$data['location'] = place( $where );
			} elseif ( '' !== $venue ) {
				$data['location'] = array(
					'@type' => 'Place',
					'name'  => $venue,
				);
			}
			return $data['startDate'] ? $data : array();
		case PREFIX . '_location':
			return array_merge( array( '@context' => 'https://schema.org' ), place( $id, 'GovernmentOffice' ), array( 'parentOrganization' => publisher() ) );
		case PREFIX . '_service':
			return array(
				'@context'         => 'https://schema.org',
				'@type'            => 'GovernmentService',
				'name'             => wp_strip_all_tags( get_the_title( $id ) ),
				'url'              => get_permalink( $id ),
				'description'      => wp_strip_all_tags( get_the_excerpt( $id ) ),
				'serviceOperator'  => publisher(),
				'availableChannel' => array(
					'@type'      => 'ServiceChannel',
					'serviceUrl' => (string) get_post_meta( $id, 'service_apply_url', true ) ? esc_url_raw( (string) get_post_meta( $id, 'service_apply_url', true ) ) : get_permalink( $id ),
				),
			);
	}
	return array();
}

/**
 * FAQPage JSON-LD for the FAQ directory (the same questions and answers the FAQ archive shows).
 *
 * @return array
 */
function faq_archive(): array {
	$faqs = get_posts(
		array(
			'post_type'      => PREFIX . '_faq',
			'post_status'    => 'publish',
			'posts_per_page' => 50, // Same entries the FAQ archive's Entry accordion shows.
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	if ( ! $faqs ) {
		return array();
	}
	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(
			static fn( \WP_Post $faq ) => array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( get_the_title( $faq ) ),
				'url'            => get_permalink( $faq ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( wp_trim_words( $faq->post_content, 60 ) ),
				),
			),
			$faqs
		),
	);
}

add_action(
	'wp_head',
	static function () {
		$data = array();
		if ( is_singular() ) {
			$data = current_entry();
		} elseif ( is_post_type_archive( PREFIX . '_faq' ) ) {
			$data = faq_archive();
		}
		if ( $data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
		}
	}
);
