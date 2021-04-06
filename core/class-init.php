<?php

namespace WP_Crawler\Core;

use WP_Crawler\Admin as Admin;
use WP_Crawler\Frontend as Frontend;

class Init {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The text domain of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $plugin_text_domain;

	/**
	 * The prefix of the plugin tables.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_table_prefix    The prefix of the plugin tables.
	 */
	protected $plugin_table_prefix;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version = '1.0.0';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WP_CRAWLER_VERSION' ) ) {
			$this->version = WP_CRAWLER_VERSION;
		}
		/**
		 * CODE REVIEW: the else here is unnecessary
		 * The property default value can be set on declaration directly
		 * and overloaded in the construct.
		 *
		 * Additionally, all those properties initialization could be done
		 * by passing the data to the constructor instead of using
		 * global constants.
		 */

		$this->plugin_name         = WP_CRAWLER_NAME_SLUG;
		$this->plugin_text_domain  = WP_CRAWLER_TEXT_DOMAIN;
		$this->plugin_table_prefix = WP_CRAWLER_TABLE_PREFIX;

		$this->load_dependencies();
		$this->set_locale();
		/**
		 * CODE REVIEW: Initializing hooks on the constructor should be avoided
		 *
		 * It makes your code difficult to test, and should not be a part of the
		 * class instantiation.
		 *
		 * Recommended reading: https://carlalexander.ca/designing-class-wordpress-hooks/
		 */
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * - Loader. Orchestrates the hooks of the plugin.
	 * - Internationalization_I18n. Defines internationalization functionality.
	 * - Admin. Defines all hooks for the admin area.
	 * - Public. Defines all hooks for the frontend side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * CODE REVIEW: creating new object instances inside methods should be avoided.
		 *
		 * It's creating tight coupling between objects, which makes them:
		 * - More complex
		 * - More difficult to test
		 *
		 * Ideally, object dependencies should be injected through the constructor.
		 */
		$this->loader = new Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Internationalization_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		// CODE REVIEW: see comment in load_dependencies().
		$plugin_i18n = new Internationalization_I18n( $this->plugin_text_domain );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// CODE REVIEW: see comment in load_dependencies().
		$plugin_admin = new Admin\Admin( $this->get_plugin_name(), $this->get_version(), $this->get_plugin_table_prefix() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Add the admin menu.
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'create_menu' );

		// Add the cron task.
		$this->loader->add_action( WP_CRAWLER_CRON_NAME, $plugin_admin, 'crawl' );

	}

	/**
	 * Register all of the hooks related to the frontend-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// CODE REVIEW: see comment in load_dependencies().
		$plugin_public = new Frontend\Frontend( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Retrieve the table prefix used by the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The table prefix used by the plugin.
	 */
	public function get_plugin_table_prefix(): string {
		return $this->plugin_table_prefix;
	}

}
