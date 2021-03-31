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

// Clean up the database.
global $wpdb;

$plugin_table_prefix = 'wpc_';
$table_name          = $wpdb->prefix . $plugin_table_prefix . 'internal_page';

$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" );

// Clean up the cron.
if ( wp_next_scheduled( 'wpc_crawl' ) ) {
	$timestamp = wp_next_scheduled( 'wpc_crawl' );
	wp_unschedule_event( $timestamp, 'wpc_crawl' );
}

// Clean up the uploads folder.
$upload_dir    = wp_upload_dir();
$wpcrawler_dir = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( 'wpcrawler' );

if ( is_dir( $wpcrawler_dir ) ) {

	// Remove the static directory and his content.
	$static_dir = trailingslashit( $wpcrawler_dir ) . trailingslashit( 'static' );

	if ( is_dir( $static_dir ) ) {
		$files = scandir( $static_dir );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( '.' !== $file && '..' !== $file ) {
					unlink( $static_dir . $file );
				}
			}
		}
		rmdir( $static_dir );
	}

	// Remove the wpcrawler dir and his content.
	$files = scandir( $wpcrawler_dir );

	if ( ! empty( $files ) ) {
		foreach ( $files as $file ) {
			if ( '.' !== $file && '..' !== $file ) {
				unlink( $wpcrawler_dir . $file );
			}
		}
	}
	rmdir( $wpcrawler_dir );
}

// Clean up our settings.
$setting_options = [
	'wpc_last_crawl',
	'wpc_homepage_static_url',
	'wpc_sitemap_path',
];

foreach ( $setting_options as $option ) {
	if ( get_option( $option ) ) {
		delete_option( $option );
	}
}

// Clean up the rewriting rules.
global $wp_rewrite;
$wp_rewrite->flush_rules();

