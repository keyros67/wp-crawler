<?php

namespace WP_Crawler\Core;

/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @author     Hervé Villemin
 **/
class Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		// Clean up the cron.
		if ( wp_next_scheduled( 'wpc_crawl' ) ) {
			$timestamp = wp_next_scheduled( 'wpc_crawl' );
			wp_unschedule_event( $timestamp, 'wpc_crawl' );
		}
	}

}
