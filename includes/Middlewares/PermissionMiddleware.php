<?php
/**
 * Permission Middleware for REST API routes.
 *
 * @since 1.0.0
 * @package DebugMaster\Middlewares
 */

namespace DebugMaster\Middlewares;

use WP_Error;
use WP_REST_Request;

/**
 * Permission Middleware class.
 *
 * @package DebugMaster
 */
class PermissionMiddleware {

	/**
	 * Authorizes the incoming REST request.
	 *
	 * @param WP_REST_Request $request The REST request object to authorize.
	 * @return true|WP_Error
	 */
	public static function authorize( WP_REST_Request $request ) {
		if (
			! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ||
			! current_user_can( 'manage_options' )
		) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You are not allowed to perform this action.', 'debug-monitor' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
