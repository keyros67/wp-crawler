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
 * @package           Wp_Crawler
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

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

// Constants
define( 'WP_CRAWLER_FILE',          __FILE__ );
define( 'WP_CRAWLER_PATH',          realpath( plugin_dir_path( WP_CRAWLER_FILE ) ) . '/' );
define( 'WP_CRAWLER_INC_PATH',      realpath( WP_CRAWLER_PATH . 'includes' ) . '/' );
define( 'WP_CRAWLER_ADMIN_PATH',    realpath( WP_CRAWLER_PATH . 'admin' ) . '/' );
define( 'WP_CRAWLER_I18N_PATH',    realpath( WP_CRAWLER_PATH . 'languages' ) . '/' );

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_CRAWLER_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-crawler-activator.php
 */
function activate_wp_crawler() {
	require_once WP_CRAWLER_INC_PATH . 'class-wp-crawler-activator.php';
	Wp_Crawler_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-crawler-deactivator.php
 */
function deactivate_wp_crawler() {
	require_once WP_CRAWLER_INC_PATH . 'class-wp-crawler-deactivator.php';
	Wp_Crawler_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_crawler' );
register_deactivation_hook( __FILE__, 'deactivate_wp_crawler' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WP_CRAWLER_INC_PATH . 'class-wp-crawler.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_crawler() {

	$plugin = new Wp_Crawler();
	$plugin->run();

}
run_wp_crawler();
