<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package LogMate
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clean up options.
$options_to_delete = array(
	'debugm_log_status',
	'debugm_autorefresh',
	'debugm_js_error_logging',
	'debugm_modify_script_debug',
	'debugm_process_non_utc_timezones',
	'debugm_log_file_path',
	'debugm_js_log_file_path',
	'debugm_wp_config_backup',
	'debugm_log_status_changed',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Delete transients.
delete_transient( 'debugm_activation_redirect' );
