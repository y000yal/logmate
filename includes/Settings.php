<?php
/**
 * Settings management for LogMate plugin.
 *
 * @package LogMate
 */

namespace LogMate;

use LogMate\Routes\Routes;

/**
 * Settings class for managing LogMate plugin settings.
 *
 * @package LogMate
 */
class Settings {

	/**
	 * Constructor for Settings class.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_init', array( $this, 'redirect_after_activation' ) );
		register_deactivation_hook( LOGMATE_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
		register_activation_hook( LOGMATE_PLUGIN_FILE, array( $this, 'on_activation' ) );
	}

	/**
	 * Returns a base64 URL for the SVG for use in the menu.
	 *
	 * @param  bool $base64 Whether or not to return base64-encoded SVG.
	 * @return string
	 */
	private function get_icon_svg( $base64 = true ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">' .
			'<text x="16" y="22" font-family="Arial, sans-serif" font-size="18" ' .
			'font-weight="bold" fill="#82878c" text-anchor="middle">LM</text></svg>';

		if ( $base64 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}

	/**
	 * Register the admin menu for LogMate.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'LogMate', 'logmate' ),
			__( 'LogMate', 'logmate' ),
			'manage_options',
			'logmate',
			array( $this, 'render_page' ),
			$this->get_icon_svg(),
			60
		);
	}

	/**
	 * Render the admin page content.
	 */
	public function render_page(): void {
		// Ensure JS log file exists for existing installations.
		$js_log_file_path = get_option( 'debugm_js_log_file_path', '' );
		if ( empty( $js_log_file_path ) ) {
			$php_log_file_path = get_option( 'debugm_log_file_path', '' );
			if ( ! empty( $php_log_file_path ) ) {
				$js_log_file_path = str_replace( '_debug.log', '_js_debug.log', $php_log_file_path );
				update_option( 'debugm_js_log_file_path', $js_log_file_path, false );

				// Create the file if it doesn't exist.
				$filesystem = logmate_get_filesystem();
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_file
				if ( ! is_file( $js_log_file_path ) ) {
					if ( $filesystem ) {
						$filesystem->put_contents( $js_log_file_path, '', FS_CHMOD_FILE );
					} else {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
						file_put_contents( $js_log_file_path, '' );
					}
				}
			}
		}

		echo '<div id="logmate-admin-app"></div>';
	}

	/**
	 * Enqueue admin assets for LogMate.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( $hook ): void {

		if ( 'toplevel_page_logmate' !== $hook ) {
			return;
		}

		// Check if webpack dev server is running (hot reload mode).
		// Set LOGMATE_HOT_RELOAD constant to true in wp-config.php when running 'npm run hot'.
		$is_hot = defined( 'LOGMATE_HOT_RELOAD' ) && LOGMATE_HOT_RELOAD && defined( 'WP_DEBUG' ) && WP_DEBUG;

		if ( $is_hot ) {
			// Load from webpack dev server for hot reload.
			$dev_server_url = 'http://localhost:5433';

			wp_enqueue_script(
				'logmate-admin',
				$dev_server_url . '/admin.js',
				array( 'wp-element', 'wp-api-fetch' ),
				time(), // Use timestamp for cache busting in dev mode.
				true
			);
			// Styles are injected by webpack dev server via style-loader.
		} else {
			// Load from built assets - production mode.
			wp_enqueue_script(
				'logmate-admin',
				logmate_get_instance()->plugin_url() . '/assets/build/admin.js',
				array( 'wp-element', 'wp-api-fetch' ),
				LOGMATE_VERSION,
				true
			);

			wp_enqueue_style(
				'logmate-admin',
				logmate_get_instance()->plugin_url() . '/assets/css/admin.css',
				array(),
				LOGMATE_VERSION
			);
		}

		$log_file_path = get_option( 'debugm_log_file_path', '' );
		$log_status    = get_option( 'debugm_log_status', 'disabled' );

		$logmate_data = array(
			'restUrl'     => esc_url_raw( rest_url( 'logmate/v1/' ) ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'logFilePath' => esc_html( $log_file_path ),
			'logStatus'   => esc_html( $log_status ),
		);
		wp_localize_script(
			'logmate-admin',
			'LogMateData',
			$logmate_data
		);
		// Backward compatibility: old cached admin.js may still reference DebugMasterData.
		wp_localize_script(
			'logmate-admin',
			'DebugMasterData',
			$logmate_data
		);
	}

	/**
	 * Enqueue public assets for JavaScript error logging.
	 *
	 * @return void
	 */
	public function enqueue_public_assets(): void {
		// Check if debug logging and JS error logging are enabled.
		$log_status        = get_option( 'debugm_log_status', 'disabled' );
		$js_error_logging  = get_option( 'debugm_js_error_logging', 'enabled' );

		// Check if debug logging and JS error logging are enabled.
		if ( 'enabled' !== $log_status || 'enabled' !== $js_error_logging ) {
			return;
		}

		// Ensure JS log file path exists (for existing installations).
		$js_log_file_path = get_option( 'debugm_js_log_file_path', '' );
		if ( empty( $js_log_file_path ) ) {
			// Create JS log file path based on PHP log file path.
			$php_log_file_path = get_option( 'debugm_log_file_path', '' );
			if ( ! empty( $php_log_file_path ) ) {
				$js_log_file_path = str_replace( '_debug.log', '_js_debug.log', $php_log_file_path );
				update_option( 'debugm_js_log_file_path', $js_log_file_path, false );

				// Create the file if it doesn't exist.
				$filesystem = logmate_get_filesystem();
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_file
				if ( ! is_file( $js_log_file_path ) ) {
					if ( $filesystem ) {
						$filesystem->put_contents( $js_log_file_path, '', FS_CHMOD_FILE );
					} else {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
						file_put_contents( $js_log_file_path, '' );
					}
				}
			}
		}

		wp_enqueue_script(
			'logmate-public',
			logmate_get_instance()->plugin_url() . '/assets/js/public.js',
			array(),
			LOGMATE_VERSION . '.' . time(), // Add timestamp for cache busting during testing.
			true
		);

		$rest_url = esc_url_raw( rest_url( 'logmate/v1/' ) );
		$js_error_url = esc_url_raw( rest_url( 'logmate/v1/logs/js-error' ) );
		$nonce = wp_create_nonce( 'wp_rest' );

		wp_localize_script(
			'logmate-public',
			'LogMateData',
			array(
				'restUrl'         => $rest_url,
				'nonce'           => $nonce,
				'jsErrorLogging'  => array(
					'status' => $js_error_logging,
					'url'    => $js_error_url,
				),
			)
		);
	}

	/**
	 * Redirect to plugin admin page after activation.
	 *
	 * @return void
	 */
	public function redirect_after_activation(): void {
		// Check if we should redirect.
		if ( ! get_transient( 'debugm_activation_redirect' ) ) {
			return;
		}

		// Delete the transient so we only redirect once.
		delete_transient( 'debugm_activation_redirect' );

		// Don't redirect if doing AJAX, cron, or if user doesn't have permission.
		if ( wp_doing_ajax() || wp_doing_cron() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't redirect if already on the plugin page.
		if ( isset( $_GET['page'] ) && 'logmate' === $_GET['page'] ) {
			return;
		}

		// Redirect to plugin admin page.
		wp_safe_redirect( admin_url( 'admin.php?page=logmate' ) );
		exit;
	}

	/**
	 * Activation hook - setup plugin.
	 *
	 * @return void
	 */
	public static function on_activation(): void {
		$wp_config_service = new \LogMate\Services\WpConfigService();

		// Store original wp-config.php state before modifications.
		$wp_config_service->store_original_state();

		// Create log directory and file.
		self::create_log_file();

		// Initialize default options.
		self::init_default_options();

		// Set transient to redirect after activation.
		set_transient( 'debugm_activation_redirect', true, 30 );
	}

	/**
	 * Deactivation hook - restore wp-config.php.
	 *
	 * @return void
	 */
	public static function on_deactivation(): void {
		$wp_config_service = new \LogMate\Services\WpConfigService();

		// Restore original wp-config.php state so normal debug logging works.
		$wp_config_service->restore_original_state();
	}

	/**
	 * Create log file in custom location.
	 *
	 * @return void
	 */
	private static function create_log_file(): void {
		$uploads_path = LOGMATE_UPLOAD_PATH;
		$filesystem = logmate_get_filesystem();

		if ( ! is_dir( $uploads_path ) ) {
			wp_mkdir_p( $uploads_path );
			// Create empty index to prevent directory browsing.
			if ( $filesystem ) {
				$filesystem->put_contents( $uploads_path . '/index.php', '<?php // Nothing to show here', FS_CHMOD_FILE );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $uploads_path . '/index.php', '<?php // Nothing to show here' );
			}
		}

		$plain_domain = str_replace( array( '.', '-' ), '', sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ?? 'localhost' ) ) );
		$unique_key   = gmdate( 'YmdHi' ) . wp_rand( 12345678, 87654321 );
		$log_file_path = trailingslashit( $uploads_path ) . $plain_domain . '_' . $unique_key . '_debug.log';
		$js_log_file_path = trailingslashit( $uploads_path ) . $plain_domain . '_' . $unique_key . '_js_debug.log';

		// Store log file paths in options.
		update_option( 'debugm_log_file_path', $log_file_path, false );
		update_option( 'debugm_js_log_file_path', $js_log_file_path, false );

		// Create empty log files if they don't exist.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_file
		if ( ! is_file( $log_file_path ) ) {
			if ( $filesystem ) {
				$filesystem->put_contents( $log_file_path, '', FS_CHMOD_FILE );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $log_file_path, '' );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_file
		if ( ! is_file( $js_log_file_path ) ) {
			if ( $filesystem ) {
				$filesystem->put_contents( $js_log_file_path, '', FS_CHMOD_FILE );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $js_log_file_path, '' );
			}
		}
	}

	/**
	 * Initialize default options.
	 *
	 * @return void
	 */
	private static function init_default_options(): void {
		$default_options = array(
			'debugm_log_status'              => 'disabled',
			'debugm_autorefresh'             => 'enabled',
			'debugm_js_error_logging'        => 'enabled',
			'debugm_modify_script_debug'     => 'enabled',
			'debugm_process_non_utc_timezones' => 'enabled',
		);

		foreach ( $default_options as $option_name => $default_value ) {
			if ( false === get_option( $option_name ) ) {
				update_option( $option_name, $default_value, false );
			}
		}
	}
}
