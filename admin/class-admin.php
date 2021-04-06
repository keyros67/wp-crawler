<?php

namespace WP_Crawler\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @author     Hervé Villemin <herve@villemin.co>
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The table used by the plugin.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string $table_prefix    The table name.
	 */
	private $table_prefix;

	/**
	 * The name of the cron task.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string $cron_name    The cron name.
	 */
	private $cron_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @param string $table_prefix The table prefix of this plugin.
	 *
	 * @since   1.0.0
	 */
	public function __construct( string $plugin_name, string $version, string $table_prefix ) {

		global $wpdb;

		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->table_prefix = $wpdb->prefix . $table_prefix;
		$this->cron_name    = WP_CRAWLER_CRON_NAME;

	}

	/**
	 * Add the plugin page to the settings submenu for administrators
	 *
	 * @since   1.0.0
	 */
	public function create_menu() {

		add_options_page(
			'WP Crawler Dashboard',
			'WP Crawler',
			'manage_options',
			$this->plugin_name,
			[ $this, 'page_dashboard' ]
		);
	}

	/**
	 * Load the dashboard page
	 *
	 * @since   1.0.0
	 */
	public function page_dashboard() {
		/**
		 * CODE REVIEW: Initialize the variable with a default value. Advantages:
		 * - The variable is always set, no need to use isset() in the view
		 * - It removes an unnecessary else condition
		 * - The code is easier to read
		 */
		$dashboard_message = __( 'Welcome to your WP Crawler Dashboard! Run the crawler to start enjoying this nice plugin. Once you run it, it will be automatically scheduled every hour.', 'wp-crawler' );

		// Dashboard message.
		if ( get_option( 'wpc_last_crawl' ) ) {
			$dashboard_message = __( 'Your pages are updated every hour. Click on the button to update them manually.', 'wp-crawler' );
		}

		// Crawl requested.
		if ( isset( $_POST['submit-crawl'] ) ) {
			// CODE REVIEW: values from the $_POST global should be sanitized before use.
			if ( ! isset( $_POST['nonce_crawl'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce_crawl'] ), 'submit_crawl' ) ) {

				// Notification .
				$notification = __( 'Access denied.', 'wp-crawler' );
				$this->wpc_crawl_notice_error( $notification );

				wp_die();
			}
			/**
			 * CODE REVIEW: this else is unnecessary, if you reach wp_die()
			 * the script execution stops anyway
			 */

			// Crawl the site starting on the homepage.
			$this->crawl( get_site_url() );

			// Add the cron task.
			$cron_task = $this->set_cron_task( $this->cron_name );

			// Notification on error.
			if ( false === $cron_task ) {
				// Notification on error.
				$notification = __( 'The plugin was not able to schedule your crawl. Please reinstall the plugin to fix this issue.', 'wp-crawler' );
				$this->wpc_crawl_notice_error( $notification );
			}
		}

		$webpages = $this->get_crawl_results();

		include_once WP_CRAWLER_ADMIN_PATH . '/partials/wp-crawler-admin-dashboard.php';
	}

	/**
	 * Main function that crawl the website.
	 *
	 * @param   string $page_url   Crawl starting page.
	 *
	 * @since   1.0.0
	 */
	public function crawl( string $page_url = '' ) {

		include_once WP_CRAWLER_ADMIN_PATH . 'lib/simple_html_dom.php';

		if ( empty( $page_url ) ) {
			$page_url = get_site_url();
		}

		// Delete the results of the previous crawl.
		$delete_previous_results = $this->delete_previous_results();

		// Notification on error.
		if ( false === $delete_previous_results ) {

			// Notification on error.
			$notification = __( 'The plugin table does not exist. Reinstall the plugin to solve this issue.', 'wp-crawler' );
			$this->wpc_crawl_notice_error( $notification );
			return;
		}

		/**
		 * CODE REVIEW: Returning early is a better choice here
		 * The rest of the code won't be executed anyway.
		 */

		global $wpdb;

		$table_name = $this->table_prefix . 'internal_page';
		// Insert the current page in the db.
		$wpdb->insert(
			$table_name,
			[
				'title' => $this->get_webpage_title( $page_url ),
				'url'   => $page_url,
			]
		); // db call ok; no-cache ok.

		$page_id = $wpdb->insert_id;

		// Get the html of the current page and then the links.
		$html = file_get_html( $page_url );

		/**
		 * CODE REVIEW:
		 * What happens here if file_get_html didn't return an object?
		 *
		 * The function can also return a boolean.
		 */
		$links = $html->find( 'a' );

		$parse_page_url = wp_parse_url( $page_url );

		foreach ( $links as $link ) {

			$child_page_url = $link->href;

			// Define the pattern of the website.
			$pattern  = '#^(?:https?:\/{2})?(?:www\.)?';
			/**
			 * CODE REVIEW: Are we sure $parse_page_url is always an array?
			 *
			 * - wp_parse_url() can also return false
			 * - $page_url can be an empty string (as from the method signature)
			 *
			 * What happens when trying to access an array offset on a variable
			 * that is not an array?
			 */
			$pattern .= $parse_page_url['host'];

			if ( isset( $parse_page_url['path'] ) ) {
				$pattern .= str_replace( '/', '\/', $parse_page_url['path'] );
			}

			$pattern .= '#';

			// Check if the url is from this website with or without http(s), with or without www.
			if ( preg_match( $pattern, $child_page_url ) ) {

				// Insert the child page in the db.
				$wpdb->insert(
					$table_name,
					[
						'parent_page_id' => $page_id,
						'title'          => $this->get_webpage_title( $child_page_url ),
						'url'            => $child_page_url,
					]
				); // db call ok; no-cache ok.
			}
		}

		// Call the function to save the html file.
		$static_page = $this->create_static_page( get_site_url(), 'homepage' );

		// Notification on error.
		if ( false === $static_page ) {
			// Notification on error.
			$notification = __( "The plugin was not able to write the file. Please check the plugin folder's rights.", 'wp-crawler' );
			$this->wpc_crawl_notice_error( $notification );
		}

		// Delete the sitemap.html.
		$this->delete_sitemap_html();

		// Create the sitemap.html.
		$this->create_sitemap_html();

		update_option( 'wpc_last_crawl', gmdate( 'Y-m-d H:i:s' ), 'yes' );

		if ( ! isset( $notification ) ) {
			// Notification.
			$notification = __( 'The crawl has started successfully!', 'wp-crawler' );
			$this->wpc_crawl_notice_success( $notification );
		}
	}

	/**
	 * Remove the previous results in the database.
	 *
	 * @return  bool    Return true if the data was deleted, false if not.
	 * @since   1.0.0
	 */
	private function delete_previous_results(): bool {

		global $wpdb;

		$table_name = $this->table_prefix . 'internal_page';

		$db_table_name = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		); // db call ok; no-cache ok.

		// Check if the table exists in the database.
		if ( $db_table_name === $table_name ) {

			// Delete previous results.
			$wpdb->query(
				'TRUNCATE TABLE ' . esc_sql( $table_name ) . ';'
			); // db call ok; no-cache ok.

			return true;

		}

		/**
		 * CODE REVIEW: else is not needed here, you already have a return
		 * statement in the if block.
		 */
		return false;
	}

	/**
	 * Return the title of a webpage
	 *
	 * @param   string $url Webpage url.
	 *
	 * @return  string      Return the title of the page.
	 * @since   1.0.0
	 */
	private function get_webpage_title( string $url ): string {
		$page = wp_remote_get( $url );

		/**
		 * CODE REVIEW: wp_remote_get() returns an array or a WP_Error object
		 *
		 * What happens if we try to access the 'body' key on the $page
		 * when it's a WP_Error object instead of an array?
		 */
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/ims', $page['body'], $match ) ) {
			return htmlentities( $match[1] );
		}

		return '';
	}

	/**
	 * Store the page in argument as static page
	 *
	 * @param   string $url     Url of the page.
	 * @param   string $name    Name of the page.
	 *
	 * @return  bool            Return true if the file was created, false if not.
	 * @since   1.0.0
	 */
	private function create_static_page( string $url, string $name ): bool {
		/**
		 * CODE REVIEW: This file is included multiple times in different methods
		 *
		 * It would be more performant to include it once on initialization of the plugin
		 * using the composer autoloader for example.
		 */
		include_once WP_CRAWLER_ADMIN_PATH . 'lib/simple_html_dom.php';

		$upload_dir = wp_upload_dir();
		$static_dir = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( 'wpcrawler/static' );

		$exist_folder = true;

		// Create the directory if not exist.
		if ( ! file_exists( $static_dir ) ) {
			$exist_folder = mkdir( $static_dir, 0755, true );
		}

		if ( ! $exist_folder ) {
			return false;
		}

		$file = $name . '.static.html';
		$html = file_get_html( $url );

		/**
		 * CODE REVIEW: File operations should use WP_Filesystem methods
		 * instead of direct PHP filesystem calls.
		 *
		 * https://developer.wordpress.org/reference/functions/wp_filesystem/
		 */
		$exist_file = file_put_contents( $static_dir . $file, $html );

		// path to the file.
		$file_path = trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( 'wpcrawler/static' );

		update_option( 'wpc_' . $name . '_static_url', $file_path . $file );

		return (bool) $exist_file;
	}

	/**
	 * Create the sitemap.html file
	 *
	 * @since   1.0.0
	 */
	private function create_sitemap_html() {

		// Get the template.
		$html = file_get_contents( wp_normalize_path( trailingslashit( WP_CRAWLER_ADMIN_PATH ) . 'lib/template-sitemap.html' ) );

		// Set the page html language.
		$html = str_replace( '{{ WPC_LANGUAGE }}', get_language_attributes(), $html );

		// Set the page title.
		$html = str_replace( '{{ WPC_SITEMAP_TITLE }}', __( 'Sitemap.html', 'wp-crawler' ), $html );

		// Set the CSS path.
		$html = str_replace( '{{ WPC_SITEMAP_CSS_PATH }}', WP_CRAWLER_ASSETS_URL . 'css/wp-crawler.css', $html );

		global $wpdb;

		$table_name = $this->table_prefix . 'internal_page';

		$homepage = $wpdb->get_row(
			'
			SELECT  * 
			FROM 	' . esc_sql( $table_name ) . '
			WHERE 	parent_page_id IS NULL
			;
			'
		); // db call ok; no-cache ok.

		$formatted_url    = untrailingslashit( strtolower( strtok( $homepage->url, '#' ) ) );
		$formatted_urls[] = $formatted_url;

		$content  = '<ul class="wpc-list">' . PHP_EOL;
		$content .= '<li>' . PHP_EOL;
		$content .= '<i class="bi bi-house wpc-list-icon"></i> <a class="wpc-pages-link" href="' . esc_url( $formatted_url ) . '">' . esc_html( $homepage->title ) . '</a>' . PHP_EOL;
		$content .= '<ul class="wpc-list">' . PHP_EOL;

		$webpages = $this->get_crawl_results( false );

		$formatted_pages = $this->remove_duplicate_pages( $webpages, $formatted_urls );

		foreach ( $formatted_pages as $formatted_page ) {

			$content .= '<li><i class="bi bi-link-45deg wpc-list-icon"></i> <a class="wpc-pages-link" href="' . esc_url( $formatted_page['url'] ) . '">' . esc_html( $formatted_page['anchor'] ) . '</a></li>' . PHP_EOL;

		}

		$content .= '</ul>' . PHP_EOL;
		$content .= '</li>' . PHP_EOL;
		$content .= '</ul>' . PHP_EOL;

		// Set the page content.
		$html = str_replace( '{{ WPC_SITEMAP_CONTENT }}', $content, $html );

		// Define the sitemap path and the rewrite rule on first crawl.
		if ( ! get_option( 'wpc_sitemap_path' ) ) {
			$upload_dir = wp_upload_dir();
			$sitemap    = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'wpcrawler/sitemap.html' );
			update_option( 'wpc_sitemap_path', $sitemap, 'yes' );

			// Add the rewriting rule.
			global $wp_rewrite;
			$new_non_wp_rules = [
				'sitemap.html' => 'wp-content/uploads/wpcrawler/sitemap.html',
			];

			$wp_rewrite->non_wp_rules += $new_non_wp_rules;
			$wp_rewrite->flush_rules();

		}

		$sitemap_path = get_option( 'wpc_sitemap_path' );
		file_put_contents( $sitemap_path, $html );

	}

	/**
	 * Delete the sitemap.html file
	 *
	 * @since   1.0.0
	 */
	private function delete_sitemap_html() {

		if ( get_option( 'wpc_sitemap_path' ) && file_exists( get_option( 'wpc_sitemap_path' ) ) ) {
			unlink( get_option( 'wpc_sitemap_path' ) );
		}

	}

	/**
	 * Add the crawl to the WordPress cron.
	 *
	 * @param   string $name    Name of the cron task.
	 *
	 * @return  bool            Return true if the cron task was scheduled, false if not.
	 * @since   1.0.0
	 */
	private function set_cron_task( string $name ): bool {

		// If the crawl is scheduled, remove the cron task.
		if ( wp_next_scheduled( $name ) ) {
			$timestamp = wp_next_scheduled( $name );
			wp_unschedule_event( $timestamp, $name );
		}

		// Schedule the cron task every hour.
		$is_cron_scheduled = wp_schedule_event( time() + 3600, 'hourly', $name );

		if ( true === $is_cron_scheduled ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the crawl results stored in the database.
	 *
	 * @param   bool $homepage  Set to true to include the homepage in the results.
	 *
	 * @return  array           Return an array with the pages.
	 * @since   1.0.0
	 */
	private function get_crawl_results( bool $homepage = true ): array {

		global $wpdb;

		$table_name = $this->table_prefix . 'internal_page';

		if ( false === $homepage ) {
			return $wpdb->get_results(
				'
				SELECT 		*
			    FROM 		' . esc_sql( $table_name ) . '
				WHERE       parent_page_id IS NOT NULL
				ORDER BY	parent_page_id ASC,
				            title ASC
				;'
			); // db call ok; no-cache ok.
		}

		return $wpdb->get_results(
			'
			SELECT 		*
			FROM 		' . esc_sql( $table_name ) . '
			ORDER BY	parent_page_id ASC,
						page_id ASC
			;'
		); // db call ok; no-cache ok.

	}

	/**
	 * Return an array of unique internal pages
	 *
	 * @param   array $pages    Array of pages.
	 * @param   array $urls     Array of urls already defined.
	 *
	 * @return  array           Array of unique internal pages.
	 */
	private function remove_duplicate_pages( array $pages, array $urls = [] ): array {

		$formatted_pages = [];

		foreach ( $pages as $page ) {

			$formatted_url = untrailingslashit( strtolower( strtok( $page->url, '#' ) ) );

			if ( in_array( $formatted_url, $urls, true ) ) {
				continue;
			}

			$urls[] = $formatted_url;

			$formatted_pages[ $page->page_id ]['url'] = $formatted_url;

			if ( trim( $page->title ) !== '' ) {
				$formatted_pages[ $page->page_id ]['anchor'] = $page->title;
				continue;
			}

			$formatted_pages[ $page->page_id ]['anchor'] = $page->url;
		}

		return $formatted_pages;
	}

	/**
	 * Display the crawl successful notice
	 *
	 * @param string $message   Text to display.
	 *
	 * @since   1.0.0
	 */
	public function wpc_crawl_notice_success( string $message ) {

		echo '<div class="notice notice-success is-dismissible">';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '</div>';

		add_action( 'admin_notices', 'crawl_notice_success' );
	}

	/**
	 * Display the crawl error notice
	 *
	 * @param string $message   Text to display.
	 *
	 * @since   1.0.0
	 */
	public function wpc_crawl_notice_error( string $message ) {

		echo '<div class="notice notice-error is-dismissible">';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '</div>';

		add_action( 'admin_notices', 'crawl_notice_error' );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Crawler_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Crawler_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, WP_CRAWLER_ASSETS_CSS_URL . 'wp-crawler-admin.css', [], $this->version, 'all' );
		wp_enqueue_style( 'treeviewjs', WP_CRAWLER_ASSETS_CSS_URL . 'jquery.treeView.css', [], '0.2.0', 'all' );
		wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.0/font/bootstrap-icons.css', [], '1.4.0', 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Crawler_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Crawler_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, WP_CRAWLER_ASSETS_JS_URL . 'wp-crawler-admin.js', [ 'jquery' ], $this->version, true );
		wp_enqueue_script( 'treeviewjs', WP_CRAWLER_ASSETS_JS_URL . 'jquery.treeView.js', [ 'jquery' ], '0.2.0', true );

	}

}
