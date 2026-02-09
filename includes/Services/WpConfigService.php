<?php
/**
 * WordPress Config Service for managing wp-config.php.
 *
 * @since 1.0.0
 * @package DebugMaster\Services
 */

namespace DebugMaster\Services;

/**
 * WpConfigService class for managing wp-config.php modifications.
 *
 * @package DebugMaster
 */
class WpConfigService {

	/**
	 * Path to wp-config.php file.
	 *
	 * @var string
	 */
	private string $wp_config_path;

	/**
	 * Original wp-config.php content backup.
	 *
	 * @var string|null
	 */
	private ?string $original_content = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->wp_config_path = $this->locate_wp_config();
	}

	/**
	 * Locate wp-config.php file.
	 *
	 * @return string
	 */
	private function locate_wp_config(): string {
		$config_path = ABSPATH . 'wp-config.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $config_path ) ) {
			$config_path = dirname( ABSPATH ) . '/wp-config.php';
		}

		return $config_path;
	}

	/**
	 * Store original wp-config.php state.
	 *
	 * @return bool
	 */
	public function store_original_state(): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $this->wp_config_path ) ) {
			return false;
		}

		// wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$this->original_content = file_get_contents( $this->wp_config_path );
		update_option( 'debugm_wp_config_backup', $this->original_content, false );

		return true;
	}

	/**
	 * Restore original wp-config.php state.
	 *
	 * @return bool
	 */
	public function restore_original_state(): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $this->wp_config_path ) ) {
			return false;
		}

		$backup = get_option( 'debugm_wp_config_backup' );

		if ( empty( $backup ) ) {
			// If no backup, remove our constants.
			return $this->remove_debug_constants();
		}

		// Restore from backup. wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $this->wp_config_path, $backup );

		if ( false !== $result ) {
			delete_option( 'debugm_wp_config_backup' );
			return true;
		}

		return false;
	}

	/**
	 * Remove debug constants from wp-config.php.
	 *
	 * @return bool
	 */
	private function remove_debug_constants(): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $this->wp_config_path ) ) {
			return false;
		}

		// wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$content = file_get_contents( $this->wp_config_path );

		// Remove our debug constants.
		$patterns = array(
			"/define\s*\(\s*['\"]WP_DEBUG['\"]\s*,\s*true\s*\);\s*/i",
			"/define\s*\(\s*['\"]WP_DEBUG_LOG['\"].*?\);\s*/i",
			"/define\s*\(\s*['\"]WP_DEBUG_DISPLAY['\"].*?\);\s*/i",
		);

		foreach ( $patterns as $pattern ) {
			$content = preg_replace( $pattern, '', $content );
		}

		return false !== file_put_contents( $this->wp_config_path, $content );
	}

	/**
	 * Update or add debug constant in wp-config.php.
	 *
	 * @param string $constant_name Constant name.
	 * @param mixed  $value Constant value.
	 * @return bool
	 */
	public function update_constant( string $constant_name, $value ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $this->wp_config_path ) ) {
			return false;
		}

		// wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$content = file_get_contents( $this->wp_config_path );

		// Format the value - handle strings, booleans, and ensure absolute paths.
		if ( is_bool( $value ) ) {
			$formatted_value = $value ? 'true' : 'false';
		} elseif ( is_string( $value ) && ! empty( $value ) ) {
			// Ensure absolute path for log files.
			if ( 'WP_DEBUG_LOG' === $constant_name && ! $this->is_absolute_path( $value ) ) {
				$value = ABSPATH . $value;
			}
			$formatted_value = "'" . addslashes( $value ) . "'";
		} else {
			$formatted_value = "'" . addslashes( (string) $value ) . "'";
		}

		$new_constant = "define( '" . $constant_name . "', " . $formatted_value . ' );';

		$escaped_name = preg_quote( $constant_name, '/' );

		// First, remove any existing definitions of this constant (handles various formats).
		// This pattern matches define statements with the constant name, handling:
		// - Single or double quotes around constant name.
		// - Various value formats (strings with escaped quotes, booleans, numbers).
		// - Multiline defines.
		// - Optional whitespace.
		$remove_patterns = array(
			// Pattern for single-line defines with single-quoted strings.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,\s*'[^']*'\s*\)\s*;/i",
			// Pattern for single-line defines with double-quoted strings.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,\s*\"[^\"]*\"\s*\)\s*;/i",
			// Pattern for booleans and numbers.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,\s*(?:true|false|\d+)\s*\)\s*;/i",
			// More aggressive pattern for multiline or complex cases.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,[^;]*?\)\s*;/is",
		);

		foreach ( $remove_patterns as $pattern ) {
			$content = preg_replace( $pattern, '', $content );
		}

		// Also remove empty if blocks that might have been left behind.
		$content = preg_replace( "/if\s*\(\s*!\s*defined\s*\(\s*['\"]" . $escaped_name . "['\"]\s*\)\s*\)\s*\{\s*\}/i", '', $content );

		// Clean up multiple consecutive blank lines.
		$content = preg_replace( "/\n\s*\n\s*\n+/", "\n\n", $content );

		// Find the best place to insert the constant - before require_once wp-settings.php.
		$wp_settings = "require_once ABSPATH . 'wp-settings.php';";
		if ( strpos( $content, $wp_settings ) !== false ) {
			// Insert before require_once wp-settings.php.
			$content = str_replace( $wp_settings, $new_constant . "\n\n" . $wp_settings, $content );
		} else {
			// Fallback: try to find "That's all, stop editing!" comment.
			$stop_editing = "/* That's all, stop editing!";
			if ( strpos( $content, $stop_editing ) !== false ) {
				$content = str_replace( $stop_editing, $new_constant . "\n\n" . $stop_editing, $content );
			} else {
				// Last resort: add at end of file before closing PHP tag if present.
				$content = rtrim( $content );
				if ( substr( $content, -2 ) === '?>' ) {
					$content = substr( $content, 0, -2 ) . "\n" . $new_constant . "\n?>";
				} else {
					$content .= "\n" . $new_constant . "\n";
				}
			}
		}

		// wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return false !== file_put_contents( $this->wp_config_path, $content );
	}

	/**
	 * Check if a path is absolute.
	 *
	 * @param string $path Path to check.
	 * @return bool
	 */
	public function is_absolute_path( string $path ): bool {
		// Windows absolute path (C:\ or \\server).
		if ( preg_match( '/^[A-Z]:\\\\/i', $path ) || preg_match( '/^\\\\/', $path ) ) {
			return true;
		}
		// Unix absolute path.
		return '/' === $path[0];
	}

	/**
	 * Enable debug logging.
	 *
	 * @param string $log_file_path Path to log file.
	 * @param bool   $modify_script_debug Whether to modify SCRIPT_DEBUG.
	 * @return bool
	 */
	public function enable_debug_logging( string $log_file_path, bool $modify_script_debug = true ): bool {
		$results = array();

		$results[] = $this->update_constant( 'WP_DEBUG', true );
		$results[] = $this->update_constant( 'WP_DEBUG_LOG', $log_file_path );
		$results[] = $this->update_constant( 'WP_DEBUG_DISPLAY', false );

		if ( $modify_script_debug ) {
			$results[] = $this->update_constant( 'SCRIPT_DEBUG', true );
		}

		return ! in_array( false, $results, true );
	}

	/**
	 * Disable debug logging.
	 *
	 * @return bool
	 */
	public function disable_debug_logging(): bool {
		// Remove only the debug constants we added, don't restore entire file.
		// This preserves any other changes made to wp-config.php.
		$results = array();

		// Remove WP_DEBUG if it was set to true by us.
		// Check if it exists and is true before removing.
		// wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$content = file_get_contents( $this->wp_config_path );
		if ( preg_match( "/define\s*\(\s*['\"]WP_DEBUG['\"]\s*,\s*true\s*\)/i", $content ) ) {
			$results[] = $this->update_constant( 'WP_DEBUG', false );
		}

		// Remove WP_DEBUG_LOG.
		$results[] = $this->remove_constant( 'WP_DEBUG_LOG' );

		// Remove WP_DEBUG_DISPLAY.
		$results[] = $this->remove_constant( 'WP_DEBUG_DISPLAY' );

		// Remove SCRIPT_DEBUG if it was set to true by us.
		if ( preg_match( "/define\s*\(\s*['\"]SCRIPT_DEBUG['\"]\s*,\s*true\s*\)/i", $content ) ) {
			$results[] = $this->remove_constant( 'SCRIPT_DEBUG' );
		}

		return ! in_array( false, $results, true );
	}

	/**
	 * Remove a constant from wp-config.php.
	 *
	 * @param string $constant_name Constant name to remove.
	 * @return bool
	 */
	private function remove_constant( string $constant_name ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $this->wp_config_path ) ) {
			return false;
		}

		// wp-config.php may be outside WordPress directory, use direct access.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$content = file_get_contents( $this->wp_config_path );
		$escaped_name = preg_quote( $constant_name, '/' );

		// Remove patterns for the constant definition.
		$remove_patterns = array(
			// Pattern for single-line defines with single-quoted strings.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,\s*'[^']*'\s*\)\s*;/i",
			// Pattern for single-line defines with double-quoted strings.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,\s*\"[^\"]*\"\s*\)\s*;/i",
			// Pattern for booleans and numbers.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,\s*(?:true|false|\d+)\s*\)\s*;/i",
			// More aggressive pattern for multiline or complex cases.
			"/define\s*\(\s*['\"]" . $escaped_name . "['\"]\s*,[^;]*?\)\s*;/is",
		);

		$changed = false;
		foreach ( $remove_patterns as $pattern ) {
			$new_content = preg_replace( $pattern, '', $content );
			if ( $new_content !== $content ) {
				$content = $new_content;
				$changed = true;
			}
		}

		// Clean up multiple consecutive blank lines.
		$content = preg_replace( "/\n\s*\n\s*\n+/", "\n\n", $content );

		if ( $changed ) {
			// wp-config.php may be outside WordPress directory, use direct access.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return false !== file_put_contents( $this->wp_config_path, $content );
		}

		return true; // Constant didn't exist, which is fine.
	}
}
