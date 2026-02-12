<?php
/**
 * Log Purge Controller for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate\Controllers;

use LogMate\Controllers\Controller as BaseController;
use LogMate\Services\LogPurgeService;
use WP_Rest_Request;
use WP_REST_Response;

/**
 * Log Purge Controller for managing log purging operations.
 *
 * @package LogMate
 */
class LogPurgeController extends BaseController {

	/**
	 * Log purge service instance.
	 *
	 * @var LogPurgeService
	 */
	protected LogPurgeService $purge_service;

	/**
	 * Constructor for LogPurgeController.
	 */
	public function __construct() {
		$this->purge_service = new LogPurgeService();
	}

	/**
	 * Purge logs before a specific date.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing result.
	 */
	public function purge_before_date( WP_Rest_Request $request ): WP_REST_Response {
		$params        = $request->get_json_params();
		$before_date   = isset( $params['before_date'] ) ? sanitize_text_field( $params['before_date'] ) : '';
		$log_type      = isset( $params['log_type'] ) ? sanitize_text_field( $params['log_type'] ) : 'all';

		if ( empty( $before_date ) ) {
			return $this->response(
				array(
					'success' => false,
					'message' => __( 'Date parameter is required.', 'logmate' ),
				),
				400
			);
		}

		$results = array();
		$messages = array();

		// Purge PHP logs.
		if ( 'all' === $log_type || 'php' === $log_type ) {
			$php_log_file_path = get_option( 'debugm_log_file_path', '' );
			if ( ! empty( $php_log_file_path ) ) {
				$result = $this->purge_service->purge_before_date( $php_log_file_path, $before_date );
				if ( $result['success'] ) {
					$results[] = $result;
					$messages[] = __( 'PHP logs purged successfully.', 'logmate' );
				}
			}
		}

		// Purge JS logs.
		if ( 'all' === $log_type || 'js' === $log_type ) {
			$js_log_file_path = get_option( 'debugm_js_log_file_path', '' );
			if ( ! empty( $js_log_file_path ) ) {
				$result = $this->purge_service->purge_before_date( $js_log_file_path, $before_date );
				if ( $result['success'] ) {
					$results[] = $result;
					$messages[] = __( 'JavaScript logs purged successfully.', 'logmate' );
				}
			}
		}

		if ( ! empty( $results ) ) {
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
				'message' => __( 'Failed to purge logs.', 'logmate' ),
			),
			500
		);
	}

	/**
	 * Keep only logs from the last N days/weeks/months.
	 *
	 * @param WP_Rest_Request $request The REST request object.
	 * @return WP_REST_Response Response containing result.
	 */
	public function keep_last_period( WP_Rest_Request $request ): WP_REST_Response {
		$params        = $request->get_json_params();
		$number        = isset( $params['number'] ) ? absint( $params['number'] ) : 7;
		$period        = isset( $params['period'] ) ? sanitize_text_field( $params['period'] ) : 'days';
		$log_type      = isset( $params['log_type'] ) ? sanitize_text_field( $params['log_type'] ) : 'all';

		$results = array();
		$messages = array();

		// Purge PHP logs.
		if ( 'all' === $log_type || 'php' === $log_type ) {
			$php_log_file_path = get_option( 'debugm_log_file_path', '' );
			if ( ! empty( $php_log_file_path ) ) {
				$result = $this->purge_service->keep_last_period( $php_log_file_path, $number, $period );
				if ( $result['success'] ) {
					$results[] = $result;
					$messages[] = __( 'PHP logs purged successfully.', 'logmate' );
				}
			}
		}

		// Purge JS logs.
		if ( 'all' === $log_type || 'js' === $log_type ) {
			$js_log_file_path = get_option( 'debugm_js_log_file_path', '' );
			if ( ! empty( $js_log_file_path ) ) {
				$result = $this->purge_service->keep_last_period( $js_log_file_path, $number, $period );
				if ( $result['success'] ) {
					$results[] = $result;
					$messages[] = __( 'JavaScript logs purged successfully.', 'logmate' );
				}
			}
		}

		if ( ! empty( $results ) ) {
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
				'message' => __( 'Failed to purge logs.', 'logmate' ),
			),
			500
		);
	}
}
