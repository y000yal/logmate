<?php
/**
 * Debug Log Service for processing log files.
 *
 * @since 1.0.0
 * @package LogMate\Services
 */

namespace LogMate\Services;

/**
 * DebugLogService class for processing and parsing log files.
 *
 * @package LogMate
 */
class DebugLogService {

	/**
	 * Process log entries from file.
	 *
	 * @param string $log_file_path Path to log file.
	 * @param int    $limit Maximum number of entries to process.
	 * @return array
	 */
	public function get_processed_entries( string $log_file_path, int $limit = 100000 ): array {
		$filesystem = logmate_get_filesystem();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $log_file_path ) || ! is_readable( $log_file_path ) ) {
			return array();
		}

		// Read file in chunks to handle large files.
		$log_content = $this->read_log_file( $log_file_path );

		if ( empty( $log_content ) ) {
			return array();
		}

		// Parse log entries.
		$entries = $this->parse_log_entries( $log_content );

		// Group duplicate entries.
		$grouped_entries = $this->group_duplicate_entries( $entries );

		// Limit results.
		return array_slice( $grouped_entries, 0, $limit );
	}

	/**
	 * Read log file efficiently.
	 *
	 * @param string $file_path Path to log file.
	 * @return string
	 */
	private function read_log_file( string $file_path ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize
		$file_size = filesize( $file_path );
		$filesystem = logmate_get_filesystem();

		// For very large files, read only the last portion.
		// Note: WP_Filesystem doesn't support fseek/fread operations needed for large file reading.
		if ( $file_size > 10 * 1024 * 1024 ) { // 10MB
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$handle = fopen( $file_path, 'r' );
			if ( false === $handle ) {
				return '';
			}

			// Read last 5MB.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
			fseek( $handle, -5 * 1024 * 1024, SEEK_END );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$content = fread( $handle, 5 * 1024 * 1024 );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );

			// Remove partial first line.
			$first_newline = strpos( $content, "\n" );
			if ( false !== $first_newline ) {
				$content = substr( $content, $first_newline + 1 );
			}

			return $content;
		}

		// For smaller files, use WP_Filesystem if available.
		if ( $filesystem ) {
			return $filesystem->get_contents( $file_path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		return file_get_contents( $file_path );
	}

	/**
	 * Parse log entries from content.
	 *
	 * @param string $content Log file content.
	 * @return array
	 */
	private function parse_log_entries( string $content ): array {
		// Normalize line endings.
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );

		// Split by timestamp pattern: [DD-MMM-YYYY HH:MM:SS UTC].
		$pattern = '/\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} [^\]]+)\]/';
		$parts   = preg_split( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		$entries = array();
		$i       = 1;
		$parts_count = count( $parts );

		while ( $i < $parts_count ) {
			if ( isset( $parts[ $i ] ) && isset( $parts[ $i + 1 ] ) ) {
				$timestamp = $parts[ $i ];
				$message   = trim( $parts[ $i + 1 ] );

				if ( ! empty( $message ) ) {
					$entries[] = array(
						'timestamp' => $timestamp,
						'message'   => $message,
						'type'      => $this->detect_error_type( $message ),
						'source'    => $this->detect_error_source( $message ),
					);
				}
			}
			$i += 2;
		}

		return $entries;
	}

	/**
	 * Detect error type from message.
	 *
	 * @param string $message Error message.
	 * @return string
	 */
	private function detect_error_type( string $message ): string {
		$message_lower = strtolower( $message );

		if ( strpos( $message_lower, 'fatal' ) !== false || strpos( $message_lower, 'e_error' ) !== false ) {
			return 'PHP Fatal';
		} elseif ( strpos( $message_lower, 'warning' ) !== false || strpos( $message_lower, 'e_warning' ) !== false ) {
			return 'PHP Warning';
		} elseif ( strpos( $message_lower, 'notice' ) !== false || strpos( $message_lower, 'e_notice' ) !== false ) {
			return 'PHP Notice';
		} elseif ( strpos( $message_lower, 'deprecated' ) !== false ) {
			return 'PHP Deprecated';
		} elseif ( strpos( $message_lower, 'parse' ) !== false || strpos( $message_lower, 'e_parse' ) !== false ) {
			return 'PHP Parse';
		} elseif ( strpos( $message_lower, 'exception' ) !== false ) {
			return 'PHP Exception';
		} elseif ( strpos( $message_lower, 'database' ) !== false ) {
			return 'Database';
		} elseif ( strpos( $message_lower, 'javascript' ) !== false ) {
			return 'JavaScript';
		}

		return 'Other';
	}

	/**
	 * Detect error source (Core/Plugin/Theme).
	 *
	 * @param string $message Error message.
	 * @return string
	 */
	private function detect_error_source( string $message ): string {
		// Extract file path from error message (common patterns).
		$file_path = '';

		// Pattern 1: "in /path/to/file.php on line X".
		if ( preg_match( '/\s+in\s+([^\s]+\.php)\s+on\s+line\s+\d+/i', $message, $matches ) ) {
			$file_path = $matches[1];
		} elseif ( preg_match( '/\s+in\s+([^\s]+\.php):(\d+)/i', $message, $matches ) ) { // Pattern 2: "in /path/to/file.php:X".
			$file_path = $matches[1];
		} elseif ( preg_match( '/([\/\\\\][^\s]+\.php)\s+on\s+line\s+\d+/i', $message, $matches ) ) { // Pattern 3: "/path/to/file.php on line X".
			$file_path = $matches[1];
		} elseif ( strpos( $message, ABSPATH ) !== false ) { // Pattern 4: Check for ABSPATH patterns directly in message.
			// Extract path after ABSPATH.
			$abspath_pos = strpos( $message, ABSPATH );
			$path_start = substr( $message, $abspath_pos + strlen( ABSPATH ) );
			if ( preg_match( '/([^\s]+\.php)/', $path_start, $matches ) ) {
				$file_path = ABSPATH . $matches[1];
			}
		} elseif ( preg_match( '/\s+in\s+([^\s]+\.js)/i', $message, $matches ) ) { // Pattern 5: JavaScript errors - check for script paths.
			$file_path = $matches[1];
		}

		// If we found a file path, determine source.
		if ( ! empty( $file_path ) ) {
			// Normalize path separators.
			$file_path = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $file_path );
			$abspath_normalized = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, ABSPATH );
			$wp_plugin_dir_normalized = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, WP_PLUGIN_DIR );
			$theme_root_normalized = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, get_theme_root() );

			// Check for WordPress Core.
			if ( strpos( $file_path, $abspath_normalized . 'wp-admin' ) !== false ||
				 strpos( $file_path, $abspath_normalized . 'wp-includes' ) !== false ) {
				return 'WordPress Core';
			} elseif ( strpos( $file_path, $wp_plugin_dir_normalized ) !== false ) { // Check for Plugin.
				// Try to extract plugin name.
				$plugin_path = str_replace( $wp_plugin_dir_normalized . DIRECTORY_SEPARATOR, '', $file_path );
				$plugin_dir = explode( DIRECTORY_SEPARATOR, $plugin_path );
				if ( ! empty( $plugin_dir[0] ) ) {
					$plugins = get_plugins();
					foreach ( $plugins as $plugin_file => $plugin_data ) {
						if ( strpos( $plugin_file, $plugin_dir[0] . '/' ) === 0 ) {
							return 'Plugin: ' . $plugin_data['Name'];
						}
					}
					return 'Plugin: ' . $plugin_dir[0];
				}
				return 'Plugin';
			} elseif ( strpos( $file_path, $theme_root_normalized ) !== false ) { // Check for Theme.
				// Try to extract theme name.
				$theme_path = str_replace( $theme_root_normalized . DIRECTORY_SEPARATOR, '', $file_path );
				$theme_dir = explode( DIRECTORY_SEPARATOR, $theme_path );
				if ( ! empty( $theme_dir[0] ) ) {
					$theme = wp_get_theme( $theme_dir[0] );
					if ( $theme->exists() ) {
						return 'Theme: ' . $theme->get( 'Name' );
					}
					return 'Theme: ' . $theme_dir[0];
				}
				return 'Theme';
			}
		}

		// Fallback: Check message content for common indicators.
		$message_lower = strtolower( $message );

		// Check for JavaScript errors.
		if ( strpos( $message_lower, 'javascript' ) !== false ||
			 strpos( $message, '.js' ) !== false ||
			 strpos( $message, 'window.' ) !== false ||
			 strpos( $message, 'document.' ) !== false ) {
			return 'JavaScript';
		}

		// Check for database errors.
		if ( strpos( $message_lower, 'database' ) !== false ||
			 strpos( $message_lower, 'mysql' ) !== false ||
			 strpos( $message_lower, 'wpdb' ) !== false ) {
			return 'Database';
		}

		// Check for plugin/theme names in message (common patterns).
		if ( preg_match( '/\b(brutefort|logmate|debug-log-manager|woocommerce|elementor|yoast)\b/i', $message, $matches ) ) {
			return 'Plugin: ' . ucfirst( $matches[1] );
		}

		return 'Unknown';
	}

	/**
	 * Group duplicate entries by message.
	 *
	 * @param array $entries Log entries.
	 * @return array
	 */
	private function group_duplicate_entries( array $entries ): array {
		$grouped = array();

		foreach ( $entries as $entry ) {
			$key = md5( $entry['message'] );

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array(
					'id'          => $key,
					'type'        => $entry['type'],
					'message'     => $entry['message'],
					'source'      => $entry['source'],
					'occurrences' => array(),
					'count'       => 0,
				);
			}

			$grouped[ $key ]['occurrences'][] = $entry['timestamp'];
			$grouped[ $key ]['count']++;
		}

		// Sort by most recent occurrence.
		usort(
			$grouped,
			function ( $a, $b ) {
				$a_latest = end( $a['occurrences'] );
				$b_latest = end( $b['occurrences'] );
				return strcmp( $b_latest, $a_latest );
			}
		);

		return array_values( $grouped );
	}

	/**
	 * Clear log file.
	 *
	 * @param string $log_file_path Path to log file.
	 * @return bool
	 */
	public function clear_log_file( string $log_file_path ): bool {
		$filesystem = logmate_get_filesystem();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $log_file_path ) || ! is_writable( $log_file_path ) ) {
			return false;
		}

		if ( $filesystem ) {
			return $filesystem->put_contents( $log_file_path, '', FS_CHMOD_FILE );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return false !== file_put_contents( $log_file_path, '' );
	}

	/**
	 * Get log file size.
	 *
	 * @param string $log_file_path Path to log file.
	 * @return string
	 */
	public function get_log_file_size( string $log_file_path ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
		if ( ! file_exists( $log_file_path ) ) {
			return '0 B';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize
		return size_format( filesize( $log_file_path ) );
	}
}
