<?php
/**
 * Debug Log Controller for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate\Controllers;

use LogMate\Controllers\Controller as BaseController;
use LogMate\Services\DebugLogService;
use WP_Rest_Request;
use WP_REST_Response;

/**
 * Debug Log Controller for managing log entries.
 *
 * @package LogMate
 */
class DebugLogController extends BaseController {

	/**
	 * Debug log service instance.
	 *
	 * @var DebugLogService
	 */
	protected DebugLogService $log_service;

	/**
	 * Constructor for DebugLogController.
	 */
	public function __construct() {
		$this->log_service = new DebugLogService();
	}

	/**
	 * Get all log entries.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing logs data.
	 */
	public function index( WP_Rest_Request $request ): WP_REST_Response {
		$log_type = $request->get_param( 'type' ); // 'php', 'js', or 'all'

		$php_log_file_path = get_option( 'debugm_log_file_path', '' );
		$js_log_file_path  = get_option( 'debugm_js_log_file_path', '' );

		$all_entries = array();
		$total_file_size_bytes = 0;
		$php_count = 0;
		$js_count = 0;

		// Get PHP logs.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ( 'all' === $log_type || 'php' === $log_type || empty( $log_type ) ) && ! empty( $php_log_file_path ) && file_exists( $php_log_file_path ) ) {
			$php_entries = $this->log_service->get_processed_entries( $php_log_file_path );
			$php_count = count( $php_entries );
			$total_file_size_bytes += filesize( $php_log_file_path );

			// Mark entries as PHP type.
			foreach ( $php_entries as $key => $entry ) {
				$php_entries[ $key ]['log_type'] = 'php';
			}
			$all_entries = array_merge( $all_entries, $php_entries );
		}

		// Get JS logs.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ( 'all' === $log_type || 'js' === $log_type || empty( $log_type ) ) && ! empty( $js_log_file_path ) && file_exists( $js_log_file_path ) ) {
			$js_entries = $this->log_service->get_processed_entries( $js_log_file_path );
			$js_count = count( $js_entries );
			$total_file_size_bytes += filesize( $js_log_file_path );

			// Mark entries as JS type.
			foreach ( $js_entries as $key => $entry ) {
				$js_entries[ $key ]['log_type'] = 'js';
			}
			$all_entries = array_merge( $all_entries, $js_entries );
		}

		// Sort by timestamp (most recent first).
		usort(
			$all_entries,
			function ( $a, $b ) {
				$a_latest = ! empty( $a['occurrences'] ) ? end( $a['occurrences'] ) : '';
				$b_latest = ! empty( $b['occurrences'] ) ? end( $b['occurrences'] ) : '';
				return strcmp( $b_latest, $a_latest );
			}
		);

		return $this->response(
			array(
				'data'      => $all_entries,
				'file_size' => size_format( $total_file_size_bytes ),
				'count'     => count( $all_entries ),
				'php_count' => $php_count,
				'js_count'  => $js_count,
			),
			200
		);
	}

	/**
	 * Clear log file.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing result.
	 */
	public function clear( WP_Rest_Request $request ): WP_REST_Response {
		$log_type = $request->get_param( 'type' ); // 'php', 'js', or 'all'

		if ( empty( $log_type ) ) {
			$log_type = 'all';
		}

		$cleared = false;
		$messages = array();

		// Clear PHP logs.
		if ( 'all' === $log_type || 'php' === $log_type ) {
			$php_log_file_path = get_option( 'debugm_log_file_path', '' );
			if ( ! empty( $php_log_file_path ) ) {
				$cleared_php = $this->log_service->clear_log_file( $php_log_file_path );
				if ( $cleared_php ) {
					$cleared = true;
					$messages[] = __( 'PHP log file cleared successfully.', 'logmate' );
				}
			}
		}

		// Clear JS logs.
		if ( 'all' === $log_type || 'js' === $log_type ) {
			$js_log_file_path = get_option( 'debugm_js_log_file_path', '' );
			if ( ! empty( $js_log_file_path ) ) {
				$cleared_js = $this->log_service->clear_log_file( $js_log_file_path );
				if ( $cleared_js ) {
					$cleared = true;
					$messages[] = __( 'JavaScript log file cleared successfully.', 'logmate' );
				}
			}
		}

		if ( $cleared ) {
			return $this->response(
				array(
					'success' => true,
					'message' => implode( ' ', $messages ),
				),
				200
			);
		}

		return $this->response(
			array(
				'success' => false,
				'message' => __( 'Failed to clear log file(s).', 'logmate' ),
			),
			500
		);
	}

	/**
	 * Log JavaScript error.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing result.
	 */
	public function log_js_error( WP_Rest_Request $request ): WP_REST_Response {
		// Check if JS error logging is enabled.
		$js_error_logging = get_option( 'debugm_js_error_logging', 'enabled' );
		if ( 'enabled' !== $js_error_logging ) {
			return $this->response(
				array(
					'success' => false,
					'message' => __( 'JavaScript error logging is disabled.', 'logmate' ),
				),
				403
			);
		}

		// Verify nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return $this->response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'logmate' ),
				),
				403
			);
		}

		$params = $request->get_json_params();

		// Validate required fields.
		if ( ! isset( $params['message'] ) || ! isset( $params['script'] ) ) {
			return $this->response(
				array(
					'success' => false,
					'message' => __( 'Missing required fields.', 'logmate' ),
				),
				400
			);
		}

		// Sanitize input data.
		$message     = sanitize_text_field( $params['message'] );
		$script      = sanitize_text_field( $params['script'] );
		$line_no     = isset( $params['lineNo'] ) ? absint( $params['lineNo'] ) : 0;
		$column_no   = isset( $params['columnNo'] ) ? absint( $params['columnNo'] ) : 0;
		$page_url    = isset( $params['pageUrl'] ) ? sanitize_text_field( $params['pageUrl'] ) : '';
		$error_type  = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'front end';

		// Get JS log file path.
		$js_log_file_path = get_option( 'debugm_js_log_file_path', '' );

		if ( empty( $js_log_file_path ) ) {
			return $this->response(
				array(
					'success' => false,
					'message' => __( 'JavaScript log file path not configured.', 'logmate' ),
				),
				400
			);
		}

		// Build error message with WordPress-style timestamp format.
		// Format: [DD-MMM-YYYY HH:MM:SS UTC] to match WordPress debug.log format.
		$timestamp = current_time( 'd-M-Y H:i:s' ) . ' UTC';
		$error_message = sprintf(
			'[%s] JavaScript Error: %s in %s on line %d column %d at %s%s',
			$timestamp,
			$message,
			$script,
			$line_no,
			$column_no,
			get_site_url(),
			$page_url
		);

		// Write directly to JS log file.
		$filesystem = logmate_get_filesystem();
		if ( $filesystem ) {
			// WP_Filesystem doesn't support FILE_APPEND, so read existing content first.
			$existing_content = $filesystem->get_contents( $js_log_file_path );
			$new_content = ( $existing_content ? $existing_content . PHP_EOL : '' ) . $error_message . PHP_EOL;
			$written = $filesystem->put_contents( $js_log_file_path, $new_content, FS_CHMOD_FILE );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$written = file_put_contents( $js_log_file_path, $error_message . PHP_EOL, FILE_APPEND );
		}

		if ( false === $written ) {
			return $this->response(
				array(
					'success' => false,
					'message' => __( 'Failed to write to JavaScript log file.', 'logmate' ),
				),
				500
			);
		}

		return $this->response(
			array(
				'success' => true,
				'message' => __( 'JavaScript error logged successfully.', 'logmate' ),
			),
			200
		);
	}

	/**
	 * Export log file.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return void|WP_Error
	 */
	public function export( WP_Rest_Request $request ) {
		$log_type = $request->get_param( 'type' ); // 'php', 'js', or 'all'
		$export_type = $request->get_param( 'export_type' ); // 'date-range' or 'entire-file'
		$start_date = $request->get_param( 'start_date' );
		$end_date = $request->get_param( 'end_date' );

		if ( empty( $log_type ) ) {
			$log_type = 'all';
		}

		if ( empty( $export_type ) ) {
			$export_type = 'entire-file';
		}

		$php_log_file_path = get_option( 'debugm_log_file_path', '' );
		$js_log_file_path  = get_option( 'debugm_js_log_file_path', '' );

		$export_content = '';
		$log_type_text = '';

		// Export PHP logs.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ( 'all' === $log_type || 'php' === $log_type ) && ! empty( $php_log_file_path ) && file_exists( $php_log_file_path ) ) {
			$php_content = $this->get_export_content( $php_log_file_path, $export_type, $start_date, $end_date );
			if ( ! empty( $php_content ) ) {
				$export_content .= "=== PHP LOGS ===\n\n" . $php_content . "\n\n";
				$log_type_text .= 'php';
			}
		}

		// Export JS logs.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ( 'all' === $log_type || 'js' === $log_type ) && ! empty( $js_log_file_path ) && file_exists( $js_log_file_path ) ) {
			$js_content = $this->get_export_content( $js_log_file_path, $export_type, $start_date, $end_date );
			if ( ! empty( $js_content ) ) {
				$export_content .= "=== JAVASCRIPT LOGS ===\n\n" . $js_content . "\n\n";
				$log_type_text .= ( ! empty( $log_type_text ) ? '-' : '' ) . 'js';
			}
		}

		if ( empty( $export_content ) ) {
			return new \WP_Error(
				'no_logs',
				__( 'No logs found to export.', 'logmate' ),
				array( 'status' => 404 )
			);
		}

		// Set headers for file download.
		$filename = 'debug-logs-' . ( ! empty( $log_type_text ) ? $log_type_text : 'all' );
		if ( 'date-range' === $export_type && ! empty( $start_date ) && ! empty( $end_date ) ) {
			$filename .= '-' . $start_date . '_to_' . $end_date;
		} else {
			$filename .= '-entire';
		}
		$filename .= '.txt';

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $export_content ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Export content is raw log data.
		echo $export_content;
		exit;
	}

	/**
	 * Get export content from log file based on export type.
	 *
	 * @param string      $log_file_path Path to log file.
	 * @param string      $export_type Export type: 'date-range' or 'entire-file'.
	 * @param string|null $start_date Start date for date range export.
	 * @param string|null $end_date End date for date range export.
	 * @return string
	 */
	private function get_export_content( string $log_file_path, string $export_type, ?string $start_date = null, ?string $end_date = null ): string {
		$filesystem = logmate_get_filesystem();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $log_file_path ) || ! is_readable( $log_file_path ) ) {
			return '';
		}

		if ( 'entire-file' === $export_type ) {
			if ( $filesystem ) {
				return $filesystem->get_contents( $log_file_path );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
			return file_get_contents( $log_file_path );
		}

		// Date range export.
		if ( 'date-range' === $export_type && ! empty( $start_date ) && ! empty( $end_date ) && null !== $start_date && null !== $end_date ) {
			if ( $filesystem ) {
				$content = $filesystem->get_contents( $log_file_path );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
				$content = file_get_contents( $log_file_path );
			}
			$lines = explode( "\n", $content );
			$filtered_lines = array();

			// Convert dates to timestamps for comparison.
			$start_timestamp = strtotime( $start_date . ' 00:00:00' );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );

			foreach ( $lines as $line ) {
				// Extract timestamp from log line: [DD-MMM-YYYY HH:MM:SS UTC].
				if ( preg_match( '/\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} [^\]]+)\]/', $line, $matches ) ) {
					$log_timestamp = strtotime( $matches[1] );
					if ( $log_timestamp >= $start_timestamp && $log_timestamp <= $end_timestamp ) {
						$filtered_lines[] = $line;
					}
				}
			}

			return implode( "\n", $filtered_lines );
		}

		return '';
	}
}
