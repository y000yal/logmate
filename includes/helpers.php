<?php
/**
 * Helper functions for Debug Master plugin.
 *
 * @package DebugMaster
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get main instance of DebugMaster.
 *
 * @return DebugMaster
 */
function debugm_get_instance(): DebugMaster {
	return DebugMaster::instance();
}

/**
 * Initialize WP_Filesystem and return the filesystem object.
 *
 * @return WP_Filesystem_Base|false Filesystem object or false on failure.
 */
function debugm_get_filesystem() {
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
