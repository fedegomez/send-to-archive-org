<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.fedegomez.es
 * @since      1.0.0
 *
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/includes
 * @author     Fede Gómez <hola@fedegomez.es>
 */
class Send_To_Archive_Org_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'send-to-archive-org',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
