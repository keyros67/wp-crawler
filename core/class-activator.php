<?php

namespace WP_Crawler\Core;

/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @author     Hervé Villemin
 **/
class Activator {

	/**
	 * Executed on plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// Database creation.
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_internal_page = $wpdb->prefix . WP_CRAWLER_TABLE_PREFIX . 'internal_page';

		$sql = "CREATE TABLE $table_internal_page (
		            page_id bigint(20) NOT NULL AUTO_INCREMENT,
		            parent_page_id bigint(20) NULL,
		            title text NULL,
		            url  text NOT NULL,
		            PRIMARY KEY (page_id),
		            CONSTRAINT FK_ParentPage FOREIGN KEY (parent_page_id) REFERENCES $table_internal_page(page_id)
	    		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		maybe_create_table( $table_internal_page, $sql );

	}
}

