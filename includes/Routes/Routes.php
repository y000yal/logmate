<?php
/**
 * Main Routes class for LogMate plugin.
 *
 * @since 1.0.0
 * @package LogMate\Routes
 */

namespace LogMate\Routes;

/**
 * Main Routes class for managing all REST API endpoints.
 *
 * @package LogMate
 */
class Routes {

	/**
	 * Hook into WordPress REST API init.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register all REST API routes by namespace and class.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes(): void {

		foreach ( $this->get_rest_classes() as $namespace => $classes ) {
			foreach ( $classes as $class_name ) {
				if ( class_exists( $class_name ) ) {
					$controller = new $class_name();
					if ( method_exists( $controller, 'register_routes' ) ) {
						$controller->register_routes();
					}
				}
			}
		}
	}

	/**
	 * Get all REST classes grouped by namespace.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function get_rest_classes(): array {
		return apply_filters(
			'logmate_api_get_rest_namespaces',
			array(
				'logmate/v1' => $this->get_routes_classes(),
			)
		);
	}

	/**
	 * All controller classes under logmate/v1.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_routes_classes(): array {
		return array(
			'logs'        => DebugLogRoutes::class,
			'settings'    => SettingsRoutes::class,
			'purge'       => LogPurgeRoutes::class,
			'system-info' => SystemInfoRoutes::class,
		);
	}
}
