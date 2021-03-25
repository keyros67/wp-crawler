<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @package    Wp_Crawler
 * @subpackage Wp_Crawler/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Crawler
 * @subpackage Wp_Crawler/includes
 * @author     Hervé Villemin <herve@villemin.co>
 */
class Wp_Crawler_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// Database creation
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . WP_CRAWLER_TABLE;

		$sql = "CREATE TABLE $table_name (
	    		page_id bigint(20) NOT NULL AUTO_INCREMENT,
	    		parent_page_id bigint(20) NULL,
	    		title text NULL,
	    		url  text NOT NULL,
	    		PRIMARY KEY (page_id),
	    		CONSTRAINT FK_ParentPage FOREIGN KEY (parent_page_id) REFERENCES $table_name(page_id)
	    ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		maybe_create_table( $table_name, $sql );

	}
}
      
