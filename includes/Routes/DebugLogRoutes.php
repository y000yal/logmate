<?php
/**
 * Debug Log Routes for Debug Master plugin.
 *
 * @package DebugMaster
 */

namespace DebugMaster\Routes;

use DebugMaster\Controllers\DebugLogController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Debug Log Routes class for managing log REST endpoints.
 *
 * @package DebugMaster
 */
class DebugLogRoutes extends AbstractRoutes {

	/**
	 * The base route name.
	 *
	 * @var string
	 */
	protected string $rest_base = 'logs';

	/**
	 * The controller class for this route.
	 *
	 * @var string
	 */
	public string $controller = DebugLogController::class;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {

		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new DebugLogController(), 'index' ),
				),
			)
		);

		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/clear',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new DebugLogController(), 'clear' ),
				),
			)
		);

		// JavaScript error logging endpoint (no authentication required for frontend).
		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/js-error',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true', // Allow public access.
					'callback'            => array( new DebugLogController(), 'log_js_error' ),
				),
			)
		);

		// Export logs endpoint.
		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/export',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new DebugLogController(), 'export' ),
				),
			)
		);
	}
}
