<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wwdh.de
 * @since             1.0.0
 * @package           Wp_Ebay_Classifieds
 *
 * @wordpress-plugin
 * Plugin Name:       WP eBay-Kleinanzeigen
 * Plugin URI:        https://wwdh.de/plugins/wp-ebay-classifieds
 * Description:       Ebay-Kleinanzeigen Importer
 * Version:           1.0.0
 * Author:            Jens Wiecker
 * Author URI:        https://wwdh.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-ebay-classifieds
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
/**
 * Plugin Database-Version.
 */
const WP_EBAY_CLASSIFIED_DB_VERSION = '1.0.1';
/**
 * PHP minimum requirement for the plugin.
 */
const WP_EBAY_CLASSIFIED_MIN_PHP_VERSION = '7.4';
/**
 * WordPress minimum requirement for the plugin.
 */
const WP_EBAY_CLASSIFIED_MIN_WP_VERSION = '5.6';

const WP_EBAY_KLEINANZEIGEN_URL = 'https://www.ebay-kleinanzeigen.de';

const SYNC_EBAY_IMPORTER_QUERY = 'synchronisierung';
/**
 * PLUGIN ROOT PATH.
 */
define('WP_EBAY_CLASSIFIED_PLUGIN_DIR', dirname(__FILE__));
/**
 * PLUGIN URL.
 */
define('WP_EBAY_CLASSIFIED_PLUGIN_URL', plugins_url('wp-ebay-classifieds').'/');
/**
 * PLUGIN SLUG.
 */
define('WP_EBAY_CLASSIFIED_SLUG_PATH', plugin_basename(__FILE__));
/**
 * PLUGIN Basename.
 */
define('WP_EBAY_CLASSIFIED_BASENAME', plugin_basename(__DIR__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-ebay-classifieds-activator.php
 */
function activate_wp_ebay_classifieds(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-ebay-classifieds-activator.php';
	Wp_Ebay_Classifieds_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-ebay-classifieds-deactivator.php
 */
function deactivate_wp_ebay_classifieds() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-ebay-classifieds-deactivator.php';
	Wp_Ebay_Classifieds_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_ebay_classifieds' );
register_deactivation_hook( __FILE__, 'deactivate_wp_ebay_classifieds' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-ebay-classifieds.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_ebay_classifieds(): void {

	$plugin = new Wp_Ebay_Classifieds();
	$plugin->run();

}
run_wp_ebay_classifieds();
