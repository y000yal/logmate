<?php
/**
 * System Info Routes for Debug Master plugin.
 *
 * @package DebugMaster
 */

namespace DebugMaster\Routes;

use DebugMaster\Controllers\SystemInfoController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * System Info Routes class for managing system info REST endpoints.
 *
 * @package DebugMaster
 */
class SystemInfoRoutes extends AbstractRoutes {

	/**
	 * The base route name.
	 *
	 * @var string
	 */
	protected string $rest_base = 'system-info';

	/**
	 * The controller class for this route.
	 *
	 * @var string
	 */
	public string $controller = SystemInfoController::class;

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
					'callback'            => array( new SystemInfoController(), 'index' ),
				),
			)
		);
	}
}
