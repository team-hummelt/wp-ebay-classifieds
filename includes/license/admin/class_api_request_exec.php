<?php

namespace Wp_Ebay_Classifieds\License;

use Wp_Ebay_Classifieds;


/**
 * The license functionality of the plugin.
 *
 * @link       https://wwdh.de
 */

/**
 * The license functionality of the plugin.
 *
 * @author Jens Wiecker <email@jenswiecker.de>
 */
defined( 'ABSPATH' ) or die();


class Api_Request_Exec {

	/**
	 * The plugin Slug Path.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_dir plugin Slug Path.
	 */
	protected string $plugin_dir;

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
	 * @param object $config
	 * @param Wp_Ebay_Classifieds $main
	 * @param string $plugin_dir
	 */
	public function __construct( string $basename, string $version, object $config, Wp_Ebay_Classifieds $main, string $plugin_dir ) {

		$this->basename   = $basename;
		$this->version    = $version;
		$this->config     = $config;
		$this->main       = $main;
		$this->plugin_dir = $plugin_dir;
		$this->execute_data_make_exec();
	}

	private function execute_data_make_exec() {
		global $license_exec;
		$license_exec = Register_Exec_License::instance($this->basename, $this->version, $this->config, $this->main, $this->plugin_dir);
		$data = json_decode( file_get_contents( "php://input" ) );
		switch ( $data->make_id ) {
			case 'make_exec':
				$makeJob = $license_exec->make_api_exec_job($data);
				isset($makeJob->data) && !empty($makeJob->data) ? $makeJobData = $makeJob->data : $makeJobData = false;
				$backMsg =  [
					'msg' => $makeJob->msg,
					'status' => $makeJob->status,
					'data' => $makeJobData
				];
				echo json_encode($backMsg);
				exit();
			case'1':
				$message = json_decode( $data->message );
				$backMsg = [
					'client_id' => get_option("{$this->basename}_client_id"),
					'reply'     => 'Plugin deaktiviert',
					'status'    => true,
				];
				update_option( "{$this->basename}_show_activated_page", false );
				update_option("{$this->basename}_message",$message->msg);
				delete_option("{$this->basename}_product_install_authorize");
				delete_option("{$this->basename}_client_id");
				delete_option("{$this->basename}_client_secret");
				if($this->config->type == 'plugin'){
					deactivate_plugins( $this->basename.'/'.$this->basename.'.php' );
				}
				break;
			case'send_versions':
				$backMsg = [
					'status'        => true,
					'theme_version' => 'v' . $this->version,
				];
				break;
			default:
				$backMsg = [
					'status'        => false,
					'theme_version' => 'unbekannt'
				];

		}

		if($data){
			echo json_encode($backMsg);
		}
	}


}