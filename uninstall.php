<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @package    Wp_Crawler
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// DB table deletion
// TODO: Add some security checks
global $wpdb;

$table_name = $wpdb->prefix . WP_CRAWLER_TABLE;

$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query( $sql );


// Clean up our settings
$setting_options = [
	'wpc_last_crawl',
	'wpc_homepage_static_url',
	'wpc_notification'
];

foreach ( $setting_options as $option ) {
	delete_option( $option );
}

// Clean up the cron
if ( wp_next_scheduled( 'wpc_crawl' ) ) {
	$timestamp = wp_next_scheduled( 'wpc_crawl' );
	wp_unschedule_event( $timestamp, 'wpc_crawl' );
}

// Delete the sitemap.html.
$wp_dir = trailingslashit( get_home_path() );

if ( file_exists( $wp_dir . 'sitemap.html' ) ) {
	unlink( $wp_dir . 'sitemap.html' );
}