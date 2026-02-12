<?php
/**
 * Abstract Routes.
 *
 * @since 1.0.0
 * @package LogMate\Routes
 */

namespace LogMate\Routes;

use LogMate\Middlewares\PermissionMiddleware;

/**
 * Abstract class for defining routes.
 *
 * @since 1.0.0
 */
abstract class AbstractRoutes {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $namespace = 'logmate';

	/**
	 * The version of the API.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $version = 'v1';

	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $rest_base;

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	abstract public function register_routes(): void;

	/**
	 * The middleware class for permission checking.
	 *
	 * @since 1.0.0
	 *
	 * @var string|PermissionMiddleware
	 */
	public $middleware = PermissionMiddleware::class;
}
