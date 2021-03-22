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
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The table used by the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The table name.
	 */
	private $table_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name   The name of this plugin.
	 * @param      string    $version       The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		global $wpdb;
		$this->table_name = $wpdb->prefix . WP_CRAWLER_TABLE;
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
	        array( $this, 'page_dashboard' )
        );
    }

	/**
	 * Crawl the website
	 *
	 * @since   1.0.0
	 */
	private function crawl() {

	    include_once( WP_CRAWLER_ADMIN_PATH. 'lib/simple_html_dom.php' );

	    global $wpdb;
	    //$table_name = $wpdb->prefix . WP_CRAWLER_TABLE;

	    $wpdb->query( "TRUNCATE TABLE $this->table_name" );

	    $page_url = get_site_url();

	    // Insert the current page in the db
	    $wpdb->insert($this->table_name, array(
	            'url'   => $page_url,
            )
        );

	    $page_id = $wpdb->insert_id;

        // Get the html of the current page and then the links
	    $html = file_get_html( $page_url );

	    $links = $html->find( 'a' );

	    $parse_page_url = parse_url( $page_url );

	    foreach( $links as $link ) {

		    $child_page_url = $link->href;

		    // Check if the url is from this website with or without http(s), with or without www.
		    if ( preg_match( '#^(?:https?:\/\/)?(?:www\.)?' . str_replace( '/', '\/', $parse_page_url['host']  . $parse_page_url['path'] ) . '#', $child_page_url) ) {

		        // Insert the child page in the db
		        $wpdb->insert($this->table_name, array(
		                'parent_page_id'    => $page_id,
                        'url'               => $child_page_url,
                    )
                );

		    }
	    }
    }

	/**
	 * Load the dashboard page
	 *
	 * @since   1.0.0
	 */
	public function load_page_dashboard() {

		include_once WP_CRAWLER_ADMIN_PATH . '/partials/wp-crawler-admin-dashboard.php';

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-crawler-admin.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-crawler-admin.js', array( 'jquery' ), $this->version, false );

	}

}
