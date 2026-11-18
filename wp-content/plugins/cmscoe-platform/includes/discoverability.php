<?php
/**
 * Environment isolation for crawlers (ai-discoverability.md): non-production is noindex.
 * Live crawler matrix is an agency decision recorded in docs; robots.txt rules are added there.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\Discoverability;

defined( 'ABSPATH' ) || exit;

/**
 * Non-production environments get noindex,nofollow.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function robots( array $robots ): array {
	if ( 'production' !== wp_get_environment_type() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', __NAMESPACE__ . '\\robots' );

/**
 * Non-production robots.txt disallows everything (defense in depth; not access control).
 *
 * @param string $output Robots.txt.
 * @return string
 */
function robots_txt( string $output ): string {
	if ( 'production' !== wp_get_environment_type() ) {
		return "User-agent: *\nDisallow: /\n";
	}
	return $output;
}
add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 99 );

// Header for non-HTML responses too.
add_action(
	'send_headers',
	static function () {
		if ( 'production' !== wp_get_environment_type() ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
		}
	}
);

/**
 * Publisher JSON-LD on the front page. Name and URL come from site settings; never hard-coded.
 */
add_action(
	'wp_head',
	static function () {
		if ( ! is_front_page() ) {
			return;
		}
		$org = array(
			'@context' => 'https://schema.org',
			'@type'    => 'GovernmentOrganization',
			'name'     => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'url'      => home_url( '/' ),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
	}
);
