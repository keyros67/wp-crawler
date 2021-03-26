<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @package    Wp_Crawler
 * @subpackage Wp_Crawler/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wp_Crawler
 * @subpackage Wp_Crawler/includes
 * @author     Hervé Villemin <herve@villemin.co>
 */
class Wp_Crawler_Deactivator {

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
