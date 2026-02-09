<?php // phpcs:ignore

/**
 * Plugin Name: LogMate
 * Plugin URI: https://brutefort.com/#/products/debug-master
 * Description: Modern log management and export for WordPress with purging, filtering, and export. by BruteFort
 * Version: 1.0.0
 * Author: Y0000el
 * Author URI: https://yoyallimbu.com.np
 * Text Domain: debug-monitor
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DebugMaster
 */

use DebugMaster\Routes\Routes;
use DebugMaster\Settings;

defined( 'ABSPATH' ) || exit;

// Autoload composer.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Plugin main class.
 *
 * @package DebugMaster
 */
final class DebugMaster {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public string $version = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @var DebugMaster|null
	 */
	protected static ?DebugMaster $_instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return DebugMaster
	 */
	public static function instance(): DebugMaster {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor for DebugMaster.
	 */
	private function __construct() {
		add_filter( 'doing_it_wrong_trigger_error', array( $this, 'filter_doing_it_wrong' ), 10, 4 );
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		$this->show_notices();
	}

	/**
	 * Prevent cloning.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cheating; huh?', '1.0' );
	}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheating; huh?', '1.0' );
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants(): void {
		$upload_dir = apply_filters( 'debugm_upload_dir', wp_upload_dir() );

		$this->define( 'DEBUGM_UPLOAD_PATH', $upload_dir['basedir'] . '/debug-master/' );
		$this->define( 'DEBUGM_UPLOAD_URL', $upload_dir['baseurl'] . '/debug-master/' );
		$this->define( 'DEBUGM_DS', DIRECTORY_SEPARATOR );
		$this->define( 'DEBUGM_PLUGIN_FILE', __FILE__ );
		$this->define( 'DEBUGM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'DEBUGM_ASSETS_URL', DEBUGM_PLUGIN_URL . 'assets' );
		$this->define( 'DEBUGM_ABSPATH', __DIR__ . DEBUGM_DS );
		$this->define( 'DEBUGM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'DEBUGM_VERSION', $this->version );
	}

	/**
	 * Define a constant if not already defined.
	 *
	 * @param string      $name  Constant name.
	 * @param bool|string $value Constant value.
	 */
	private function define( string $name, $value ): void {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required files and initialize classes.
	 *
	 * @return void
	 */
	private function includes(): void {
		// Load admin routes.
		new Routes();

		// Always load Settings (needed for frontend JS error logging).
		new Settings();
	}

	/**
	 * Check if the current request is of a specific type.
	 *
	 * @param string $type admin, ajax, cron, frontend.
	 * @return bool
	 */
	private function is_request( string $type ): bool {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}

		return false;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Init DebugMaster when WordPress initializes.
	 *
	 * @return void
	 */
	public function init(): void {
		// Before init action.
		do_action( 'debugm_before_init' );

		// Init action.
		do_action( 'debugm_init' );
	}

	/**
	 * Filter doing_it_wrong calls.
	 *
	 * @param bool   $trigger Whether to trigger the error.
	 * @param string $function_name The function that was called.
	 * @param string $message A message explaining what has been done incorrectly.
	 * @param string $version The version of WordPress where the message was added.
	 * @return bool
	 */
	public function filter_doing_it_wrong( bool $trigger, string $function_name, string $message, string $version ): bool {
		return $trigger;
	}

	/**
	 * Show admin notices.
	 *
	 * @return void
	 */
	private function show_notices(): void {
		// Add notices if needed.
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function plugin_url(): string {
		return untrailingslashit( plugins_url( '/', DEBUGM_PLUGIN_FILE ) );
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public function plugin_path(): string {
		return untrailingslashit( plugin_dir_path( DEBUGM_PLUGIN_FILE ) );
	}
}

// Include helper functions.
require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';

// Set global.
$GLOBALS['debugmaster'] = debugm_get_instance();
