<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.linkedin.com/in/villemin/
 * @since      1.0.0
 *
 * @package    Wp_Crawler
 * @subpackage Wp_Crawler/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Crawler
 * @subpackage Wp_Crawler/admin
 * @author     Hervé Villemin <herve@villemin.co>
 */
class Wp_Crawler_Admin {

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
	 * @var     string $table_name    The table name.
	 */
	private $table_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 * @param   string $plugin_name   The name of this plugin.
	 * @param   string $version       The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		global $wpdb;
		$this->table_name = $wpdb->prefix . WP_CRAWLER_TABLE;

		// Add the cron hook.
		add_action( 'wpc_crawl', [ $this, 'crawl' ] );
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
	 * Crawl the website
	 *
	 * @since   1.0.0
	 */
	public function crawl() {

		include_once WP_CRAWLER_ADMIN_PATH . 'lib/simple_html_dom.php';

		global $wpdb;

		// Delete previous results.
		$wpdb->query( 'TRUNCATE TABLE ' . esc_sql( $this->table_name ) . ';' );

		if ( $wpdb->last_error ) {

			// Notification.
			$notification = __( 'The plugin table does not exist. Reinstall the plugin to solve this issue.', 'wp-crawler' );
			$this->wpc_crawl_notice_error( $notification );
			add_action( 'admin_notices', 'crawl_notice_error' );

		} else {
			$this->delete_sitemap_html();

			$page_url = get_site_url();

			// Call the function to save the html file.
			$this->create_static_page( $page_url, 'homepage' );

			// Insert the current page in the db.
			$wpdb->insert(
				$this->table_name,
				[
					'title' => $this->get_webpage_title( $page_url ),
					'url'   => $page_url,
				]
			);

			$page_id = $wpdb->insert_id;

			// Get the html of the current page and then the links.
			$html = file_get_html( $page_url );

			$links = $html->find( 'a' );

			$parse_page_url = wp_parse_url( $page_url );

			foreach ( $links as $link ) {

				$child_page_url = $link->href;

				// Check if the url is from this website with or without http(s), with or without www.
				if ( preg_match( '#^(?:https?:\/{2})?(?:www\.)?' . str_replace( '/', '\/', $parse_page_url['host'] . $parse_page_url['path'] ) . '#', $child_page_url ) ) {

					// Insert the child page in the db.
					$wpdb->insert(
						$this->table_name,
						[
							'parent_page_id' => $page_id,
							'title'          => $this->get_webpage_title( $child_page_url ),
							'url'            => $child_page_url,
						]
					);
				}
			}

			// Create the sitemap.html.
			$this->create_sitemap_html();

			update_option( 'wpc_last_crawl', gmdate( 'Y-m-d H:i:s' ), 'no' );

			// Notification.
			$notification = __( 'The crawl has started successfully!', 'wp-crawler' );
			$this->wpc_crawl_notice_success( $notification );
			add_action( 'admin_notices', 'crawl_notice_success' );
		}
	}

	/**
	 * Return the title of a webpage
	 *
	 * @param string $url   Webpage url.
	 *
	 * @return  string      Return the title of the page.
	 * @since   1.0.0
	 */
	private function get_webpage_title( string $url ): string {

		$page = file_get_contents( $url );

		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/ims', $page, $match ) ) {
			$title = html_entity_decode( $match[1] );
		} else {
			$title = '';
		}

		return $title;
	}

	/**
	 * Store the page in argument as static page
	 *
	 * @param string $url url of the page.
	 * @param string $name name of the page.
	 *
	 * @since   1.0.0
	 */
	private function create_static_page( string $url, string $name ) {

		include_once WP_CRAWLER_ADMIN_PATH . 'lib/simple_html_dom.php';

		$upload_dir = wp_upload_dir();
		$static_dir = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( 'wpcrawler/static' );

		// Create the directory if not exist.
		if ( ! file_exists( $static_dir ) ) {
			mkdir( $static_dir, 0755, true );
		}

		$file = $name . '.static.html';
		$html = file_get_html( $url );

		file_put_contents( $static_dir . $file, $html );

		// path to the file.
		$file_path = trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( 'wpcrawler/static' );

		update_option( 'wpc_' . $name . '_static_url', $file_path . $file );
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

		global $wpdb;

		$homepage = $wpdb->get_row(
			'
			SELECT  * 
			FROM 	' . esc_sql( $this->table_name ) . '
			WHERE 	parent_page_id IS NULL
			;
			'
		);

		$formatted_url    = untrailingslashit( strtolower( strtok( $homepage->url, '#' ) ) );
		$formatted_urls[] = $formatted_url;

		$content  = '<ul class="wpc-list">' . PHP_EOL;
		$content .= '<li>' . PHP_EOL;
		$content .= '<i class="bi bi-house wpc-list-icon"></i> <a class="wpc-pages-link" href="' . $formatted_url . '">' . $homepage->title . '</a>' . PHP_EOL;
		$content .= '<ul class="wpc-list">' . PHP_EOL;

		$webpages = $wpdb->get_results(
			'
				SELECT  	*
				FROM 		' . esc_sql( $this->table_name ) . '
				WHERE		parent_page_id IS NOT NULL
				ORDER BY	parent_page_id ASC,
				            title ASC
			;'
		);

		$formatted_pages = [];

		foreach ( $webpages as $webpage ) {

			$formatted_url = untrailingslashit( strtolower( strtok( $webpage->url, '#' ) ) );

			if ( ! in_array( $formatted_url, $formatted_urls, true ) ) {

				$formatted_urls[] = $formatted_url;

				$formatted_pages[ $webpage->page_id ]['url'] = $formatted_url;

				if ( trim( $webpage->title ) !== '' ) {
					$formatted_pages[ $webpage->page_id ]['anchor'] = $webpage->title;
				} else {
					$formatted_pages[ $webpage->page_id ]['anchor'] = $webpage->url;
				}
			}
		}

		foreach ( $formatted_pages as $formatted_page ) {

			$content .= '<li><i class="bi bi-link-45deg wpc-list-icon"></i> <a class="wpc-pages-link" href="' . $formatted_page['url'] . '">' . $formatted_page['anchor'] . '</a></li>' . PHP_EOL;

		}

		$content .= '</ul>' . PHP_EOL;
		$content .= '</li>' . PHP_EOL;
		$content .= '</ul>' . PHP_EOL;

		// Set the page content.
		$html = str_replace( '{{ WPC_SITEMAP_CONTENT }}', $content, $html );

		$wp_dir = trailingslashit( get_home_path() );
		file_put_contents( $wp_dir . 'sitemap.html', $html );
	}

	/**
	 * Delete the sitemap.html file
	 *
	 * @since   1.0.0
	 */
	private function delete_sitemap_html() {

		$wp_dir = trailingslashit( get_home_path() );

		if ( file_exists( $wp_dir . 'sitemap.html' ) ) {
			unlink( $wp_dir . 'sitemap.html' );
		}

	}

	/**
	 * Load the dashboard page
	 *
	 * @since   1.0.0
	 */
	public function page_dashboard() {

		// Update the last crawl date when an crawl is requested.
		if ( isset( $_POST['submit-crawl'] ) ) {

			if ( ! wp_verify_nonce( $_POST['nonce_crawl'], 'submit_crawl' ) ) {

				// Notification .
				$notification = __( 'Access denied.', 'wp-crawler' );
				$this->wpc_crawl_notice_error( $notification );
				add_action( 'admin_notices', 'crawl_notice_error' );

				wp_die();
			} else {

				$this->crawl();

				// If the crawl is scheduled, remove the cron task.
				if ( wp_next_scheduled( 'wpc_crawl' ) ) {
					$timestamp = wp_next_scheduled( 'wpc_crawl' );
					wp_unschedule_event( $timestamp, 'wpc_crawl' );
				}

				// Schedule the cron task every hour.
				wp_schedule_event( time() + 3600, 'hourly', 'wpc_crawl' );

			}
		}

		// Display results request.
		if ( isset( $_POST['submit-results'] ) ) {

			if ( ! wp_verify_nonce( $_POST['nonce_results'], 'show_results' ) ) {

				// Notification .
				$notification = __( 'Access denied.', 'wp-crawler' );
				$this->wpc_crawl_notice_error( $notification );
				add_action( 'admin_notices', 'crawl_notice_error' );

				wp_die();

			} else {

				global $wpdb;

				$webpages = $wpdb->get_results(
					'
					SELECT 		*
					FROM 		' . esc_sql( $this->table_name ) . '
					ORDER BY	parent_page_id ASC,
								page_id ASC
					;'
				);
			}
		}

		// Dashboard message.
		if ( get_option( 'wpc_last_crawl' ) ) {
			$dashboard_message = __( 'Your pages are updated every hour. Click on the button to update them manually.', 'wp-crawler' );
		} else {
			$dashboard_message = __( 'Welcome to your WP Crawler Dashboard! Run the crawler to start enjoying this nice plugin. Once you run it, it will be automatically scheduled every hour.', 'wp-crawler' );
		}

		include_once WP_CRAWLER_ADMIN_PATH . '/partials/wp-crawler-admin-dashboard.php';
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

		wp_enqueue_script( $this->plugin_name, WP_CRAWLER_ASSETS_JS_URL . 'wp-crawler-admin.js', [ 'jquery' ], $this->version, false );
		wp_enqueue_script( 'treeviewjs', WP_CRAWLER_ASSETS_JS_URL . 'jquery.treeView.js', [ 'jquery' ], '0.2.0', false );

	}

}
