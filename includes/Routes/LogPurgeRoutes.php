<?php
/**
 * Log Purge Routes for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate\Routes;

use LogMate\Controllers\LogPurgeController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Log Purge Routes class for managing log purge REST endpoints.
 *
 * @package LogMate
 */
class LogPurgeRoutes extends AbstractRoutes {

	/**
	 * The base route name.
	 *
	 * @var string
	 */
	protected string $rest_base = 'purge';

	/**
	 * The controller class for this route.
	 *
	 * @var string
	 */
	public string $controller = LogPurgeController::class;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {

		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/before-date',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new LogPurgeController(), 'purge_before_date' ),
				),
			)
		);

		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/keep-last',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new LogPurgeController(), 'keep_last_period' ),
				),
			)
		);
	}
}
