<?php
/**
 * Uninstall WPMU DEV Plugin Test
 *
 * @package wpmudev-plugin-test
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
// Clean up options
delete_option( 'wpmudev_drive_test_settings' );
delete_option( 'wpmudev_drive_test_credentials' );
delete_option( 'wpmudev_drive_test_auth_token' );

// Clean up transients
$transients = array(
    'wpmudev_drive_test_files_cache',
    'wpmudev_drive_test_auth_status'
);

foreach ( $transients as $transient ) {
    delete_transient( $transient );
}

*/