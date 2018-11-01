<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.joeszalai.org/
 * @since             1.0.0
 * @package           Exopite_Url_Access_Tokens
 *
 * @wordpress-plugin
 * Plugin Name:       Exopite URL Access Tokens
 * Plugin URI:        https://www.joeszalai.org/exopite-url-access-tokens
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           20181101
 * Author:            Joe Szalai
 * Author URI:        https://www.joeszalai.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       exopite-url-access-tokens
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
define( 'EXOPITE_URL_ACCESS_TOKENS_NAME', 'exopite-url-access-tokens' );
define( 'EXOPITE_URL_ACCESS_TOKENS_VERSION', '20181101' );
define( 'EXOPITE_URL_ACCESS_TOKENS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-exopite-url-access-tokens-activator.php
 */
function activate_exopite_url_access_tokens() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-exopite-url-access-tokens-activator.php';
	Exopite_Url_Access_Tokens_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-exopite-url-access-tokens-deactivator.php
 */
function deactivate_exopite_url_access_tokens() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-exopite-url-access-tokens-deactivator.php';
	Exopite_Url_Access_Tokens_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_exopite_url_access_tokens' );
register_deactivation_hook( __FILE__, 'deactivate_exopite_url_access_tokens' );

/*****************************************
 * CUSTOM UPDATER FOR PLUGIN
 * @tutorial custom_updater_for_plugin.php
 */
if ( is_admin() ) {

	/**
	 * A custom update checker for WordPress plugins.
	 *
	 * How to use:
	 * - Copy vendor/plugin-update-checker to your plugin OR
	 *   Download https://github.com/YahnisElsts/plugin-update-checker to the folder
	 * - Create a subdomain or a folder for the update server eg. https://updates.example.net
	 *   Download https://github.com/YahnisElsts/wp-update-server and copy to the subdomain or folder
	 * - Add plguin zip to the 'packages' folder
	 *
	 * Useful if you don't want to host your project
	 * in the official WP repository, but would still like it to support automatic updates.
	 * Despite the name, it also works with themes.
	 *
	 * @link http://w-shadow.com/blog/2011/06/02/automatic-updates-for-commercial-themes/
	 * @link https://github.com/YahnisElsts/plugin-update-checker
	 * @link https://github.com/YahnisElsts/wp-update-server
	 */
	if( ! class_exists( 'Puc_v4_Factory' ) ) {

		require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_URL_ACCESS_TOKENS_PLUGIN_DIR, 'vendor', 'plugin-update-checker', 'plugin-update-checker.php' ) );

	}

	$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://update.joeszalai.org/?action=get_metadata&slug=' . EXOPITE_URL_ACCESS_TOKENS_NAME, //Metadata URL.
		__FILE__, //Full path to the main plugin file.
		EXOPITE_URL_ACCESS_TOKENS_NAME //Plugin slug. Usually it's the same as the name of the directory.
	);

	/**
	 * add plugin upgrade notification
	 * https://andidittrich.de/2015/05/howto-upgrade-notice-for-wordpress-plugins.html
	 */
	add_action( 'in_plugin_update_message-' . EXOPITE_URL_ACCESS_TOKENS_NAME . '/' . EXOPITE_URL_ACCESS_TOKENS_NAME .'.php', 'exopite_url_access_tokens_show_upgrade_notification', 10, 2 );
	function exopite_url_access_tokens_show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) {

		/**
		 * Check "upgrade_notice" in readme.txt.
		 *
		 * Eg.:
		 * == Upgrade Notice ==
		 * = 20180624 = <- new version
		 * Notice		<- message
		 *
		 */
		if ( isset( $new_plugin_metadata->upgrade_notice ) && strlen( trim( $new_plugin_metadata->upgrade_notice ) ) > 0 ) {

			// Display "upgrade_notice".
			echo sprintf( '<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr( 'Important Upgrade Notice', 'exopite-multifilter' ), esc_html( rtrim( $new_plugin_metadata->upgrade_notice ) ) );

		}
	}


}
// END CUSTOM UPDATER FOR PLUGIN


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-exopite-url-access-tokens.php';

global $exopite_url_access_tokens;
$exopite_url_access_tokens = new Exopite_Url_Access_Tokens();
$exopite_url_access_tokens->run();