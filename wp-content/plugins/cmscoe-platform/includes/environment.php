<?php
/**
 * Explicit environment detection (no hard-coded URLs).
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\Environment;

defined( 'ABSPATH' ) || exit;

/**
 * Current Pantheon environment name, or '' when not on Pantheon.
 *
 * @return string
 */
function pantheon_env(): string {
	return isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ? sanitize_key( (string) $_ENV['PANTHEON_ENVIRONMENT'] ) : '';
}

/**
 * True when running in DDEV.
 *
 * @return bool
 */
function is_ddev(): bool {
	return 'true' === getenv( 'IS_DDEV_PROJECT' );
}

/**
 * Whether content-model definitions may be edited here: DDEV, or Pantheon Dev/Multidev with a
 * writable code directory (SFTP mode). Test and Live are always locked.
 *
 * @param string $definitions_dir Directory the builder writes to.
 * @return bool
 */
function content_model_editable( string $definitions_dir ): bool {
	$env = pantheon_env();
	if ( '' === $env ) {
		$editable = is_ddev();
	} elseif ( in_array( $env, array( 'test', 'live' ), true ) ) {
		$editable = false;
	} else {
		$editable = wp_is_writable( $definitions_dir );
	}
	/**
	 * Filter whether the content-model builder UI is editable in this environment.
	 *
	 * @param bool   $editable Editable.
	 * @param string $env      Pantheon environment ('' outside Pantheon).
	 */
	return (bool) apply_filters( 'cmscoe_content_model_editable', $editable, $env );
}
