<?php

namespace Wp_Ebay_Classifieds\License;

use Wp_Ebay_Classifieds;
use stdClass;

defined( 'ABSPATH' ) or die();
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
class Register_Api_WP_Remote {

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
	 */
	public function __construct( string $basename, string $version, object $config, Wp_Ebay_Classifieds $main ) {

		$this->basename = $basename;
		$this->version  = $version;
		$this->config   = $config;
		$this->main     = $main;
	}

	public function init_register_license_wp_remote_api() {
		add_filter( 'get_license_api_urls', array( $this, 'hupa_license_api_urls' ) );

	//	add_filter( "$this->basename/resource_authorization_code", array( $this, 'Activate_By_Authorization_Code' ) );
	}

	public function hupa_license_api_urls( $scope ): string {
		$url = get_option( 'hupa_server_url' );
		$uri = '';
		switch ( $scope ) {
			case 'authorize_url':
				$uri = 'authorize?response_type=code&client_id=' . get_option( "{$this->basename}_client_id" );
				break;
			case 'token':
				$uri = 'token';
				break;
			case 'install':
				$uri = 'install';
				break;
			case'download':
				$uri = 'download';
				break;
			case'update-url':
				$uri = 'hupa-update/url';
				break;
		}

		return $url . $uri;
	}

	public function Activate_By_Authorization_Code( $authorization_code ) {

		$record         = new stdClass();
		$record->status = false;
		$client_id      = get_option( "{$this->basename}_client_id" );
		$client_secret  = get_option( "{$this->basename}_client_secret" );
		$token_url      = $this->hupa_license_api_urls( 'token' );
		$authorization  = base64_encode( "$client_id:$client_secret" );

		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => "Basic $authorization"
			),
			'body'    => [
				'grant_type' => "authorization_code",
				'code'       => $authorization_code
			]
		);

		$response = wp_remote_post( $token_url, $args );
		if ( is_wp_error( $response ) ) {
			$record->message = $response->get_error_message();

			return $record;
		}

		if ( ! is_array( $response ) ) {
			$record->message = 'ungültige Serverantwort';

			return $record;
		}

		$data = json_decode( $response['body'] );
		if ( ! is_object( $data ) || isset( $data->error ) ) {
			$record->message = 'ungültige Serverantwort';

			return $record;
		}
		update_option( "{$this->basename}_access_token", $data->access_token );
		$body = [
			'version' => $this->version,
		];

		return $this->Post_License_Api_Resource('install', $body);
	}

	public function Post_License_Api_Resource( $scope, $body = [] ) {

		$response = wp_remote_post( $this->hupa_license_api_urls( $scope ), $this->WP_Remote_Post_Args( $body ) );
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}
		if ( is_array( $response ) ) {
			$query = json_decode( $response['body'] );
			if ( isset( $query->error ) && $query->error ) {
				if ( $this->get_error_message( $query ) ) {
					$this->LicenseGetApiClientCredentials();
				}
				$response = wp_remote_post( $this->hupa_license_api_urls( $scope ), $this->WP_Remote_Post_Args( $body ) );
				if (is_array($response)) {
					return json_decode($response['body']);
				}
			} else {
				return $query;
			}
		}
		return false;
	}

	private function LicenseGetApiClientCredentials(): void {

		$token_url     = $this->hupa_license_api_urls( 'token' );
		$client_id     = get_option( "{$this->basename}_client_id" );
		$client_secret = get_option( "{$this->basename}_client_secret" );
		$authorization = base64_encode( "$client_id:$client_secret" );

		$args = [
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'sslverify'   => true,
			'blocking'    => true,
			'headers'     => [
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => "Basic $authorization"
			],
			'body'        => [
				'grant_type' => 'client_credentials'
			]
		];

		$response = wp_remote_post( $token_url, $args );
		if ( ! is_wp_error( $response ) ) {
			$apiData = json_decode( $response['body'] );
			update_option( "{$this->basename}_access_token", $apiData->access_token );
		}
	}

	protected function WP_Remote_Post_Args( $body = [] ): array {
		$bearerToken = get_option( "{$this->basename}_access_token" );

		return [
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'sslverify'   => true,
			'headers'     => [
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => "Bearer $bearerToken"
			],
			'body'        => $body

		];
	}

	public function LicenseApiDownloadFile($url, $body = []) {

		$bearerToken = get_option("{$this->basename}_access_token");
		$args = [
			'method'        => 'POST',
			'timeout'       => 45,
			'redirection'   => 5,
			'httpversion'   => '1.0',
			'blocking'      => true,
			'sslverify'     => true,
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => "Bearer $bearerToken"
			],
			'body'          => $body
		];

		$response = wp_remote_post( $url, $args );

		if (is_wp_error($response)) {
			$this->LicenseGetApiClientCredentials();
		}

		$response = wp_remote_post( $url, $args );

		if (is_wp_error($response)) {
			print_r($response->get_error_message());
			exit();
		}

		if( !is_array( $response ) ) {
			exit('Download Fehlgeschlagen!');
		}
		return $response['body'];
	}

	private function get_error_message( $error ): bool {
		$return = false;
		switch ( $error->error ) {
			case 'invalid_grant':
			case 'insufficient_scope':
			case 'invalid_request':
				$return = false;
				break;
			case'invalid_token':
				$return = true;
				break;
		}

		return $return;
	}
}