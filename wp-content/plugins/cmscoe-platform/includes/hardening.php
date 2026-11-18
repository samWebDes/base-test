<?php
/**
 * Small, Pantheon-compatible hardening (security.md).
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\Hardening;

defined( 'ABSPATH' ) || exit;

/**
 * Hide user endpoints from anonymous REST requests (user enumeration) without affecting the editor.
 *
 * @param array $endpoints Endpoints.
 * @return array
 */
function rest_endpoints( array $endpoints ): array {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', __NAMESPACE__ . '\\rest_endpoints' );

/**
 * Block ?author=N enumeration redirects for anonymous visitors.
 */
function block_author_scan(): void {
	if ( ! is_user_logged_in() && isset( $_GET['author'] ) && ! is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\\block_author_scan', 1 );

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' ); // Pantheon also blocks /xmlrpc.php via pantheon.yml protected_web_paths.
remove_action( 'wp_head', 'wp_generator' );
