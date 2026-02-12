<?php
/**
 * Settings Controller for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate\Controllers;

use LogMate\Controllers\Controller as BaseController;
use LogMate\Services\WpConfigService;
use WP_Rest_Request;
use WP_REST_Response;

/**
 * Settings Controller for managing plugin settings.
 *
 * @package LogMate
 */
class SettingsController extends BaseController {

	/**
	 * Get current settings.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing settings.
	 */
	public function index( WP_Rest_Request $request ): WP_REST_Response {
		$settings = array(
			'log_status'                  => get_option( 'debugm_log_status', 'disabled' ),
			'autorefresh'                 => get_option( 'debugm_autorefresh', 'enabled' ),
			'js_error_logging'            => get_option( 'debugm_js_error_logging', 'enabled' ),
			'modify_script_debug'         => get_option( 'debugm_modify_script_debug', 'enabled' ),
			'process_non_utc_timezones'   => get_option( 'debugm_process_non_utc_timezones', 'enabled' ),
			'log_file_path'               => get_option( 'debugm_log_file_path', '' ),
			'js_log_file_path'            => get_option( 'debugm_js_log_file_path', '' ),
		);

		return $this->response(
			array(
				'data' => $settings,
			),
			200
		);
	}

	/**
	 * Toggle debug logging status.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing result.
	 */
	public function toggle_logging( WP_Rest_Request $request ): WP_REST_Response {
		$current_status = get_option( 'debugm_log_status', 'disabled' );
		$new_status     = 'disabled' === $current_status ? 'enabled' : 'disabled';

		$wp_config_service = new WpConfigService();
		$log_file_path     = get_option( 'debugm_log_file_path', '' );

		if ( 'enabled' === $new_status ) {
			if ( empty( $log_file_path ) ) {
				return $this->response(
					array(
						'success' => false,
						'message' => __( 'Log file path not configured.', 'logmate' ),
					),
					400
				);
			}

			$modify_script_debug = 'enabled' === get_option( 'debugm_modify_script_debug', 'enabled' );

			// Ensure log file path is absolute.
			if ( ! $wp_config_service->is_absolute_path( $log_file_path ) ) {
				$log_file_path = ABSPATH . ltrim( $log_file_path, '/' );
			}

			$enabled = $wp_config_service->enable_debug_logging( $log_file_path, $modify_script_debug );

			if ( ! $enabled ) {
				return $this->response(
					array(
						'success' => false,
						'message' => __( 'Failed to enable debug logging.', 'logmate' ),
					),
					500
				);
			}
		} else {
			$wp_config_service->disable_debug_logging();
		}

		update_option( 'debugm_log_status', $new_status, false );
		update_option( 'debugm_log_status_changed', current_time( 'mysql' ), false );

		return $this->response(
			array(
				'success' => true,
				'status'  => $new_status,
				'message' => 'enabled' === $new_status
					? __( 'Debug logging enabled.', 'logmate' )
					: __( 'Debug logging disabled.', 'logmate' ),
			),
			200
		);
	}

	/**
	 * Update settings.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing result.
	 */
	public function update( WP_Rest_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();

		$allowed_settings = array(
			'autorefresh',
			'js_error_logging',
			'modify_script_debug',
			'process_non_utc_timezones',
		);

		$wp_config_service = new WpConfigService();
		$log_status = get_option( 'debugm_log_status', 'disabled' );

		foreach ( $allowed_settings as $setting ) {
			if ( isset( $params[ $setting ] ) ) {
				$value = sanitize_text_field( $params[ $setting ] );
				$old_value = get_option( 'debugm_' . $setting );

				update_option( 'debugm_' . $setting, $value, false );

				// If modify_script_debug setting changed and debug logging is enabled, update SCRIPT_DEBUG constant.
				if ( 'modify_script_debug' === $setting && 'enabled' === $log_status && $old_value !== $value ) {
					if ( 'enabled' === $value ) {
						// Enable SCRIPT_DEBUG.
						$wp_config_service->update_constant( 'SCRIPT_DEBUG', true );
					} else {
						// Disable SCRIPT_DEBUG - remove the constant we added.
						// Only remove if it's set to true (meaning we added it).
						$wp_config_service->update_constant( 'SCRIPT_DEBUG', false );
					}
				}
			}
		}

		return $this->response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully.', 'logmate' ),
			),
			200
		);
	}
}
