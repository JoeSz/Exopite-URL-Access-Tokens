<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.joeszalai.org/
 * @since      1.0.0
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/includes
 * @author     Joe Szalai <joe@joeszalai.org>
 */
class Exopite_Url_Access_Tokens_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'exopite-url-access-tokens',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
