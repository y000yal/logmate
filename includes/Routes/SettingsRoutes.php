<?php
/**
 * Settings Routes for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate\Routes;

use LogMate\Controllers\SettingsController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Settings Routes class for managing settings REST endpoints.
 *
 * @package LogMate
 */
class SettingsRoutes extends AbstractRoutes {

	/**
	 * The base route name.
	 *
	 * @var string
	 */
	protected string $rest_base = 'settings';

	/**
	 * The controller class for this route.
	 *
	 * @var string
	 */
	public string $controller = SettingsController::class;

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
					'callback'            => array( new SettingsController(), 'index' ),
				),
			)
		);

		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/toggle-logging',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new SettingsController(), 'toggle_logging' ),
				),
			)
		);

		register_rest_route(
			$this->namespace . '/' . $this->version,
			$this->rest_base . '/update',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this->middleware, 'authorize' ),
					'callback'            => array( new SettingsController(), 'update' ),
				),
			)
		);
	}
}
