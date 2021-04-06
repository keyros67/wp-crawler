<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.linkedin.com/in/villemin/
 * @since             1.0.0
 * @package           WP_Crawler
 *
 * @wordpress-plugin
 * Plugin Name:       WP Crawler
 * Plugin URI:        https://wp-crawler.com
 * Description:       This plugin allows you to crawl the web pages of your website to see how they are linked together.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Hervé Villemin
 * Author URI:        https://www.linkedin.com/in/villemin/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-crawler
 * Domain Path:       /languages
 */

namespace WP_Crawler;

/**
 * If this file is called directly, abort.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Define constants.
 * 
 * CODE REVIEW: constants in the global namespace like those under are to be avoided
 * - In most cases, it's better to add anything in the global namespace
 * - In a true OOP world, they make the code more difficult to test
 * - A better approach would be to use a configuration object that can be passed around
 */
define( 'WP_CRAWLER_FILE', __FILE__ );
define( 'WP_CRAWLER_PATH', realpath( plugin_dir_path( WP_CRAWLER_FILE ) ) . '/' );
define( 'WP_CRAWLER_INC_PATH', realpath( WP_CRAWLER_PATH . 'includes' ) . '/' );
define( 'WP_CRAWLER_ADMIN_PATH', realpath( WP_CRAWLER_PATH . 'admin' ) . '/' );
define( 'WP_CRAWLER_I18N_PATH', realpath( WP_CRAWLER_PATH . 'languages' ) . '/' );

define( 'WP_CRAWLER_URL', plugin_dir_url( WP_CRAWLER_FILE ) );
define( 'WP_CRAWLER_ADMIN_URL', WP_CRAWLER_URL . 'admin/' );
define( 'WP_CRAWLER_ASSETS_URL', WP_CRAWLER_URL . 'assets/' );
define( 'WP_CRAWLER_ASSETS_JS_URL', WP_CRAWLER_ASSETS_URL . 'js/' );
define( 'WP_CRAWLER_ASSETS_CSS_URL', WP_CRAWLER_ASSETS_URL . 'css/' );
define( 'WP_CRAWLER_ASSETS_IMG_URL', WP_CRAWLER_ASSETS_URL . 'img/' );

define( 'WP_CRAWLER_TABLE_PREFIX', 'wpc_' );
define( 'WP_CRAWLER_CRON_NAME', 'wpc_crawl' );
define( 'WP_CRAWLER_TEXT_DOMAIN', 'wp-crawler' );
define( 'WP_CRAWLER_NAME_SLUG', 'wp-crawler' );

define( 'WP_CRAWLER_VERSION', '1.0.0' );

/**
 * Autoload classes.
 * CODE REVIEW: I would recommend to use composer autoloader instead of a custom one.
 * Composer is widely used in the PHP world now, so it translated very well inside and outside of WP.
 */
require_once WP_CRAWLER_INC_PATH . 'lib/autoloader.php';

/**
 * Register Activation and Deactivation Hooks.
 */
register_activation_hook( __FILE__, [ __NAMESPACE__ . '\Core\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ __NAMESPACE__ . '\Core\Deactivator', 'deactivate' ] );

/**
 * Plugin Singleton Container
 *
 * Maintains a single copy of the plugin app object
 *
 * @since    1.0.0
 */
class WP_Crawler {

	/**
	 * The instance of the plugin.
	 *
	 * @since   1.0.0
	 * @var     Init $init Instance of the plugin.
	 */
	private static $init;
	/**
	 * Loads the plugin
	 *
	 * @access  public
	 *
	 * CODE REVIEW: Singleton is an anti-pattern that we really want to avoid now.
	 * Recommended reading: https://www.alainschlesser.com/singletons-shared-instances/
	 * https://carlalexander.ca/singletons-in-wordpress/
	 */
	public static function init() {

		if ( null === self::$init ) {
			self::$init = new Core\Init();
			self::$init->run();
		}

		return self::$init;
	}

}
// CODE REVIEW: Initialiazing the plugin should be done on a hook like plugins_loaded.
// Begins the execution of the plugin.
WP_Crawler::init();
