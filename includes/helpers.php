<?php
/**
 * Helper functions for LogMate plugin.
 *
 * @package LogMate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get main instance of LogMate.
 *
 * @return LogMate
 */
function logmate_get_instance(): LogMate {
	return LogMate::instance();
}

/**
 * Initialize WP_Filesystem and return the filesystem object.
 *
 * @return WP_Filesystem_Base|false Filesystem object or false on failure.
 */
function logmate_get_filesystem() {
	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$credentials = request_filesystem_credentials( '', '', false, false, null );
	if ( false === $credentials ) {
		return false;
	}

	if ( ! WP_Filesystem( $credentials ) ) {
		return false;
	}

	return $wp_filesystem;
}
