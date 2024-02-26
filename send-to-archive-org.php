<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.fedegomez.es
 * @since             1.0.0
 * @package           Send_To_Archive_Org
 *
 * @wordpress-plugin
 * Plugin Name:       Send to Archive.org
 * Plugin URI:        https://www.fedegomez.es/plugins/send-to-archive-org/
 * Description:       Automatically send the public contents of your website to Archive.org to have always available a history with backups of them.
 * Version:           1.0.0
 * Author:            Fede Gómez
 * Author URI:        https://www.fedegomez.es/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       send-to-archive-org
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
define( 'SEND_TO_ARCHIVE_ORG_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-send-to-archive-org.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_send_to_archive_org() {

	$plugin = new Send_To_Archive_Org();
	$plugin->run();

}
run_send_to_archive_org();
