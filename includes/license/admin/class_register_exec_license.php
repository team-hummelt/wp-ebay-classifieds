<?php

namespace Wp_Ebay_Classifieds\License;

use Wp_Ebay_Classifieds;
use stdClass;

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


if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	require_once ABSPATH . 'wp-includes/pluggable.php';
}

class Register_Exec_License {

	private static $instance;
	/**
	 * The plugin Slug Path.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $produkt_dir plugin Slug Path.
	 */
	protected string $produkt_dir;


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
	 * The SLUG of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $slug_path The current Slug of this plugin.
	 */
	private string $slug_path;
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
	 * @return static
	 */
	public static function instance( string $basename, string $version, object $config, Wp_Ebay_Classifieds $main, string $plugin_dir ): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $basename, $version, $config, $main, $plugin_dir );
		}

		return self::$instance;
	}

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

		if($config->type == 'plugin'){
			$this->produkt_dir = $plugin_dir;
		}
		if($config->type == 'theme'){
			$this->produkt_dir = get_template_directory() . DIRECTORY_SEPARATOR;
		}

		$this->slug_path  = $this->basename . '/' . $this->basename . '.php';
		if ( is_user_logged_in() && is_admin() ) {
			if ( site_url() !== get_option("{$this->basename}_license_url" ) ) {
				$msg = 'Version: ' . $this->version . ' ungültige Lizenz URL: ' . get_option( "{$this->basename}_license_url" );
				$this->apiSystemLog('url_error', $msg);
			}
		}
	}

	public function make_api_exec_job($data): object {
		$return = new stdClass();
		$return->status = false;
		global $license_wp_remote;
		$getJob = $this->load_post_make_exec_job($data);

		if (!$getJob->status) {
			$return->msg = 'Exec Job konnte nicht ausgeführt werden!';
			return $getJob;
		}

		$getJob = $getJob->record;
		switch ($getJob->exec_id) {
			case '1':
				update_option("{$this->basename}_license_url", site_url());
				$status = true;
				$msg = 'Lizenz Url erfolgreich geändert.';
				break;
			case '2':
				update_option("{$this->basename}_client_id", $getJob->client_id);
				$status = true;
				$msg = 'Client ID erfolgreich geändert.';
				break;
			case '3':
				update_option("{$this->basename}_client_secret", $getJob->client_secret);
				$status = true;
				$msg = 'Client Secret erfolgreich geändert.';
				break;
			case '4':
				$body = [
					'version' => $this->version,
					'type' => 'aktivierungs_file'
				];

				$datei = $license_wp_remote->LicenseApiDownloadFile($license_wp_remote->hupa_license_api_urls('download'), $body);
				if($datei){
					$file = $this->produkt_dir . $this->config->aktivierungs_file_path . DIRECTORY_SEPARATOR . $getJob->aktivierung_path;
					file_put_contents($file, $datei);
					$activate = activate_plugin( $this->slug_path );
					if ( is_wp_error( $activate ) ) {
						$status = false;
						$msg = 'Plugin konnte nicht aktiviert werden.';
					} else {
						$status = true;
						$msg = 'Plugin erfolgreich aktiviert.';
						delete_transient( "$this->basename-admin-notice-error-panel-" . get_current_user_id() );
						set_transient( "$this->basename-admin-notice-success-panel-" . get_current_user_id() , true, 5);
						update_option("{$this->basename}_client_id", $getJob->client_id);
						update_option("{$this->basename}_client_secret", $getJob->client_secret);
						update_option("{$this->basename}_license_url", site_url());
						update_option("{$this->basename}_product_install_authorize", true);
						delete_option("{$this->basename}_message");
					}
				} else {
					$status = false;
					$msg = ucfirst($this->config->type).' konnte nicht aktiviert werden!';
				}
				break;
			case '5':
				deactivate_plugins( $this->slug_path );
				set_transient( "$this->basename-admin-notice-error-panel-" . get_current_user_id() . "" , true, 5);
				delete_transient( "$this->basename-admin-notice-success-panel-" . get_current_user_id() . "" );
				delete_option("{$this->basename}_client_id");
				delete_option("{$this->basename}_client_secret");
				delete_option("{$this->basename}_license_url");
				delete_option("{$this->basename}_product_install_authorize");
				update_option("{$this->basename}_message", sprintf(__('The %s %s has been disabled. Contact the administrator.', 'licenseLanguage' ), ucfirst($this->config->type), $this->config->name));
				$status = true;
				$msg = $this->config->name . ' erfolgreich deaktiviert.';
				break;
			case '6':
				$body = [
					'version' => $this->version,
					'type' => 'aktivierungs_file'
				];

				$datei = $license_wp_remote->LicenseApiDownloadFile($license_wp_remote->hupa_license_api_urls('download'), $body);
				if($datei){
					$file = $this->produkt_dir . $this->config->aktivierungs_file_path . DIRECTORY_SEPARATOR . $getJob->aktivierung_path;
					file_put_contents($file, $datei);
					$status = true;
					$msg = 'Aktivierungs File erfolgreich kopiert.';
				} else {
					$status = false;
					$msg = 'Datei konnte nicht kopiert werden!';
				}
				break;
			case '7':
				delete_option("{$this->basename}_client_id");
				delete_option("{$this->basename}_client_secret");
				delete_option("{$this->basename}_license_url");
				delete_option("{$this->basename}_product_install_authorize");
				update_option("{$this->basename}_message", sprintf(__('The %s %s has been disabled. Contact the administrator.', 'licenseLanguage' ), ucfirst($this->config->type), $this->config->name));
				set_transient( "$this->basename-admin-notice-error-panel-" . get_current_user_id() , true, 5);

				$file = $this->produkt_dir . $this->config->aktivierungs_file_path . DIRECTORY_SEPARATOR . $getJob->aktivierung_path;
				unlink($file);
				$status = true;
				$msg = 'Aktivierungs File erfolgreich gelöscht.';
				deactivate_plugins( $this->slug_path );
				break;
			case '8':
				update_option('hupa_server_url', $getJob->server_url);
				$status = true;
				$msg = 'Server URL erfolgreich geändert.';
				break;
			case '9':
				$body = [
					'version' => $this->version,
					'type' => 'update_version'
				];

				$license_wp_remote->LicenseApiDownloadFile($getJob->uri, $body);

				$status = true;
				$msg = 'Version aktualisiert.';
				break;
			case'10':
				if($getJob->update_type == '1' || $getJob->update_type == '2'){

					$updateUrl = $license_wp_remote->LicenseApiDownloadFile($license_wp_remote->hupa_license_api_urls('update-url'));
					$url = $updateUrl->url;
					$update_aktiv = true;
				} else {
					$update_aktiv = false;
					$url = '';
				}
				$serverApi = [
					'update_aktiv' => $update_aktiv,
					'update_type' => $getJob->update_type,
					'update_url' => $url
				];

				update_option("{$this->basename}_server_api", $serverApi);
				$status = true;
				$msg    = 'Update Methode aktualisiert.';
				break;
			case'11':
				$updateUrl               = $license_wp_remote->LicenseApiDownloadFile( $license_wp_remote->hupa_license_api_urls( 'update-url' ) );
				$updOption               = get_option( "{$this->basename}_server_api" );
				$updOption['update_url'] = $updateUrl->url;
				update_option( "{$this->basename}_server_api", $updOption );
				$status = true;
				$msg    = 'URL Token aktualisiert.';
				break;
			case'12':
				$return->data = json_encode($this->config);
				$status = true;
				$msg    = 'Config Daten gesendet.';
				break;
			case'13':
				$config_file = plugin_dir_path( dirname( __FILE__ ) ) . 'config.json';
				if(!is_file($config_file)){
					$status = false;
					$msg = 'Config-File nicht gefunden.';
				} else {
					file_put_contents($config_file, $getJob->config);
					update_option( "{$this->basename}_server_api", json_decode($getJob->config) );
					$status = true;
					$msg    = 'Config File aktualisiert.';
				}

				break;
			default:
				$status = false;
				$msg    = 'Diese Option wird nicht unterstützt!';
		}

		$return->status = $status;
		$return->msg = $msg;
		return $return;
	}

	protected function load_post_make_exec_job($data, $body = []): object
	{
		$bearerToken = $data->access_token;
		$args = [
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'sslverify' => true,
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => "Bearer $bearerToken"
			],
			'body' => $body
		];

		$return = new stdClass();
		$return->status = false;
		$response = wp_remote_post($data->url, $args);
		if (is_wp_error($response)) {
			$return->msg = $response->get_error_message();
			return $return;
		}
		if (!is_array($response)) {
			$return->msg = 'API Error Response array!';
			return $return;
		}

		$return->status = true;
		$return->record = json_decode($response['body']);
		return $return;
	}

	/**
	 * @param $type
	 * @param $message
	 */
	public function apiSystemLog($type, $message)
	{

		$body = [
			'type' => $type,
			'version' => $this->version,
			'log_date' => date('m.d.Y H:i:s'),
			'message' => $message
		];
		global $license_wp_remote;
		$sendErr = $license_wp_remote->Post_License_Api_Resource('error-log', $body);
	}

}