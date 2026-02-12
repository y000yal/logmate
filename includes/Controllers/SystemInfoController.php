<?php
/**
 * System Info Controller for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate\Controllers;

use LogMate\Controllers\Controller as BaseController;
use WP_Rest_Request;
use WP_REST_Response;

/**
 * System Info Controller for managing system information.
 *
 * @package LogMate
 */
class SystemInfoController extends BaseController {

	/**
	 * Get system information.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing system info.
	 */
	public function index( WP_Rest_Request $request ): WP_REST_Response {
		global $wpdb;

		// Get PHP information.
		$php_version = PHP_VERSION;
		$php_memory_limit = ini_get( 'memory_limit' );
		$php_max_execution_time = ini_get( 'max_execution_time' );
		$php_upload_max_filesize = ini_get( 'upload_max_filesize' );
		$php_post_max_size = ini_get( 'post_max_size' );
		$php_max_input_vars = ini_get( 'max_input_vars' );

		// Get MySQL/Database information.
		$mysql_version = $wpdb->db_version();
		$mysql_charset = $wpdb->charset;
		$mysql_collate = $wpdb->collate;

		// Get database extension type.
		$db_extension = 'Unknown';
		if ( is_object( $wpdb->dbh ) ) {
			$db_extension = get_class( $wpdb->dbh );
		}

		// Get WordPress information.
		$wp_version = get_bloginfo( 'version' );
		$wp_memory_limit = WP_MEMORY_LIMIT;
		$wp_max_memory_limit = WP_MAX_MEMORY_LIMIT;

		// Get server information.
		$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown';
		$server_os = PHP_OS;

		// Get installed plugins.
		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$plugins_list = array();

		foreach ( $plugins as $plugin_path => $plugin_data ) {
			$plugins_list[] = array(
				'name'    => $plugin_data['Name'],
				'version' => $plugin_data['Version'],
				'active'  => in_array( $plugin_path, $active_plugins, true ),
			);
		}

		// Sort plugins by name.
		usort(
			$plugins_list,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		// Get active theme.
		$theme = wp_get_theme();
		$active_theme = array(
			'name'    => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'author'  => $theme->get( 'Author' ),
		);

		// Get debug log file information.
		$php_log_file_path = get_option( 'debugm_log_file_path', '' );
		$js_log_file_path  = get_option( 'debugm_js_log_file_path', '' );

		$php_log_size = 0;
		$js_log_size = 0;
		$php_log_exists = false;
		$js_log_exists = false;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! empty( $php_log_file_path ) && file_exists( $php_log_file_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize
			$php_log_size = filesize( $php_log_file_path );
			$php_log_exists = true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! empty( $js_log_file_path ) && file_exists( $js_log_file_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize
			$js_log_size = filesize( $js_log_file_path );
			$js_log_exists = true;
		}

		$total_log_size = $php_log_size + $js_log_size;

		return $this->response(
			array(
				'data' => array(
					'php'      => array(
						'version'            => $php_version,
						'memory_limit'       => $php_memory_limit,
						'max_execution_time' => $php_max_execution_time,
						'upload_max_filesize' => $php_upload_max_filesize,
						'post_max_size'      => $php_post_max_size,
						'max_input_vars'     => $php_max_input_vars,
					),
					'wordpress' => array(
						'version'         => $wp_version,
						'memory_limit'    => $wp_memory_limit,
						'max_memory_limit' => $wp_max_memory_limit,
					),
					'server'   => array(
						'software' => $server_software,
						'os'       => $server_os,
						'db_version' => $mysql_version,
						'db_charset' => $mysql_charset,
						'db_collate' => $mysql_collate,
						'db_extension' => $db_extension,
					),
					'plugins'  => $plugins_list,
					'theme'    => $active_theme,
					'debug_logs' => array(
						'php_log_size' => size_format( $php_log_size ),
						'js_log_size'  => size_format( $js_log_size ),
						'total_size'   => size_format( $total_log_size ),
						'php_log_path' => $php_log_file_path,
						'js_log_path'  => $js_log_file_path,
						'php_log_exists' => $php_log_exists,
						'js_log_exists' => $js_log_exists,
					),
				),
			),
			200
		);
	}
}
