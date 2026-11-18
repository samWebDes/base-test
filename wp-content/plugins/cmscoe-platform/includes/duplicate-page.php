<?php
/**
 * "Duplicate as draft" for pages, limited to roles with cmscoe_duplicate_pages
 * (Administrator, Editor, Reviewer). Content-type/taxonomy duplication for Administrators is
 * provided by the content-model builder's own Duplicate action, which is gated by
 * cmscoe_manage_content_model.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\Duplicate;

defined( 'ABSPATH' ) || exit;

const ACTION = 'cmscoe_duplicate_page';

/**
 * Can the current user duplicate this post?
 *
 * @param \WP_Post $post Post.
 * @return bool
 */
function can_duplicate( \WP_Post $post ): bool {
	return 'page' === $post->post_type
		&& current_user_can( 'cmscoe_duplicate_pages' )
		&& current_user_can( 'read_post', $post->ID )
		&& current_user_can( 'edit_pages' );
}

/**
 * Row action link.
 *
 * @param array    $actions Actions.
 * @param \WP_Post $post    Post.
 * @return array
 */
function row_action( array $actions, \WP_Post $post ): array {
	if ( can_duplicate( $post ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=' . ACTION . '&post=' . $post->ID ), ACTION . '_' . $post->ID );
		/* translators: %s: page title. */
		$label                       = sprintf( __( 'Duplicate &#8220;%s&#8221; as a new draft', 'cmscoe-platform' ), get_the_title( $post ) );
		$actions['cmscoe_duplicate'] = '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( wp_strip_all_tags( $label ) ) . '">' . esc_html__( 'Duplicate', 'cmscoe-platform' ) . '</a>';
	}
	return $actions;
}
add_filter( 'page_row_actions', __NAMESPACE__ . '\\row_action', 10, 2 );

/**
 * Duplicate a page as a draft owned by the current user. Returns the new ID or WP_Error.
 *
 * @param int $post_id Source page ID.
 * @return int|\WP_Error
 */
function duplicate( int $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! can_duplicate( $post ) ) {
		return new \WP_Error( 'cmscoe_forbidden', __( 'You are not allowed to duplicate this page.', 'cmscoe-platform' ) );
	}
	$new_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				/* translators: %s: original title. */
				'post_title'   => sprintf( __( '%s (copy)', 'cmscoe-platform' ), $post->post_title ),
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_parent'  => $post->post_parent,
				'menu_order'   => $post->menu_order,
				'post_author'  => get_current_user_id(),
			)
		),
		true
	);
	if ( is_wp_error( $new_id ) ) {
		return $new_id;
	}
	$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_trash_meta_status', '_wp_trash_meta_time' );
	foreach ( get_post_meta( $post->ID ) as $key => $values ) {
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		foreach ( $values as $value ) {
			add_post_meta( $new_id, $key, wp_slash( maybe_unserialize( $value ) ) );
		}
	}
	return $new_id;
}

/**
 * Handle the admin-post duplicate request.
 */
function handle(): void {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( ACTION . '_' . $post_id );
	$result = duplicate( $post_id );
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 403 ) );
	}
	wp_safe_redirect( get_edit_post_link( $result, 'raw' ) );
	exit;
}
add_action( 'admin_post_' . ACTION, __NAMESPACE__ . '\\handle' );
