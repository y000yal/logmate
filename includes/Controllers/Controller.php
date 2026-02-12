<?php
/**
 * Base Controller class.
 *
 * @since 1.0.0
 * @package LogMate\Controllers
 */

namespace LogMate\Controllers;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Response;

/**
 * Base Controller class for all controllers.
 *
 * @package LogMate
 */
abstract class Controller {

	/**
	 * Send a JSON response.
	 *
	 * @param mixed $data Response data.
	 * @param int   $status HTTP status code.
	 * @param array $headers Additional headers.
	 * @return WP_REST_Response|WP_HTTP_Response|WP_Error
	 */
	protected function response( $data, int $status = 200, array $headers = array() ) {
		return new WP_REST_Response( $data, $status, $headers );
	}

	/**
	 * Send an error response.
	 *
	 * @param string $message Error message.
	 * @param int    $status HTTP status code.
	 * @param string $code Error code.
	 * @return WP_Error
	 */
	protected function error( string $message, int $status = 400, string $code = 'error' ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
