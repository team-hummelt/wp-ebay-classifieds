<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Ebay_Classifieds_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain(): void {

		load_plugin_textdomain(
			'wp-ebay-classifieds',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);

	}



}
