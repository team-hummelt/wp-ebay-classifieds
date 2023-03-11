<?php

namespace Wp_Ebay_Classifieds\License;

use Wp_Ebay_Classifieds;
use stdClass;

defined( 'ABSPATH' ) or die();

/**
 * Define the License AJAX functionality.
 *
 * Loads and defines the Lizense Ajax files for this plugin
 * so that it is ready for Rezensionen.
 *
 * @link       https://www.hummelt-werbeagentur.de/
 * @since      1.0.0
 */

/**
 * Define the License AJAX functionality.
 *
 * Loads and defines the License Ajax files for this plugin
 * so that it is ready for Rezensionen.
 *
 * @since      1.0.0
 * @author     Jens Wiecker <wiecker@hummelt.com>
 */
class Register_License_Ajax {

	/**
	 * The plugin Slug Path.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_dir plugin Slug Path.
	 */
	protected string $plugin_dir;

	/**
	 * The AJAX METHOD
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $method The AJAX METHOD.
	 */
	protected string $method;

	/**
	 * The AJAX DATA
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array|object $data The AJAX DATA.
	 */
	private $data;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $basename The ID of this plugin.
	 */

	private string $basename;
	/**
	 * The Version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current Version of this plugin.
	 */
	private string $version;

	/**
	 * License Config of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var  object $config License Config.
	 */
	private object $config;


	/**
	 * Store plugin main class to allow public access.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var Wp_Ebay_Classifieds $main The main class.
	 */
	private Wp_Ebay_Classifieds $main;

	/**
	 * @param string $basename
	 * @param string $version
	 * @param Wp_Ebay_Classifieds $main
	 * @param object $config
	 */
	public function __construct( string $basename, string $version, Wp_Ebay_Classifieds $main, object $config ) {

		$this->basename   = $basename;
		$this->version    = $version;
		$this->config     = $config;
		$this->plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR;
		$this->method     = '';
		if ( isset( $_POST['daten'] ) ) {
			$this->data   = $_POST['daten'];
			$this->method = filter_var( $this->data['method'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH );
		}

		if ( ! $this->method ) {
			$this->method = $_POST['method'];
		}

		$this->main = $main;
	}

	public function product_admin_ajax_handle():object {

		$responseJson         = new stdClass();
		$responseJson->status = false;
		$responseJson->time    = date( 'H:i:s', current_time( 'timestamp' ) );

		switch ( $this->method ) {
			case 'save_license_data':
				$client_id = filter_input( INPUT_POST, 'client_id', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH );
				$client_secret = filter_input( INPUT_POST, 'client_secret', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH );
				if(strlen($client_id) !== 12 || strlen($client_secret) !== 36) {
					$responseJson->status        = false;
					$responseJson->msg = 'Client ID oder Client secret sind nicht bekannt!';
					return $responseJson;
				}

				if(get_option("{$this->basename}_product_install_authorize")) {
					$responseJson->status = true;
					$responseJson->if_authorize = true;
					return $responseJson;
				}

				update_option("{$this->basename}_license_url", site_url());
				update_option( "{$this->basename}_client_id", $client_id );
				update_option( "{$this->basename}_client_secret", $client_secret );
				$responseJson->status = true;
                global $wpRemoteLicense;
                $responseJson->send_url = $wpRemoteLicense->hupa_license_api_urls('authorize_url');
				//$responseJson->send_url = apply_filters('get_license_api_urls', 'authorize_url');
				$responseJson->if_authorize = get_option("{$this->basename}_product_install_authorize");
				break;
		}

		return $responseJson;
	}
}