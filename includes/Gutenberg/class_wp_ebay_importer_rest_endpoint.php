<?php

namespace Wp_Ebay_Classifieds\Importer;

use stdClass;
use WP_Error;
use Wp_Ebay_Classifieds;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class WP_Ebay_Importer_Rest_Endpoint {

	protected Wp_Ebay_Classifieds $main;
	protected string $basename;

	public function __construct( string $basename, Wp_Ebay_Classifieds $main ) {
		$this->main     = $main;
		$this->basename = $basename;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_wp_ebay_importer_routes(): void {
		$version   = '1';
		$namespace = 'wp-ebay-importer/v' . $version;
		$base      = '/';

		@register_rest_route(
			$namespace,
			$base . '(?P<method>[\S]+)',

			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'wp_ebay_importer_endpoint_get_response' ),
				'permission_callback' => array( $this, 'permissions_check' )
			)
		);
	}

	/**
	 * Get one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function wp_ebay_importer_endpoint_get_response( WP_REST_Request $request ) {

		$method = (string) $request->get_param( 'method' );
		if ( ! $method ) {
			return new WP_Error( 404, ' Method failed' );
		}

		return $this->get_method_item( $method );

	}

	/**
	 * GET Post Meta BY ID AND Field
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_method_item( $method ) {
		if ( ! $method ) {
			return new WP_Error( 404, ' Method failed' );
		}
		$response = new stdClass();
		switch ( $method ) {
			case 'get-data':
				$import    = apply_filters( $this->basename . '/get_ebay_import', '' );
				$importArr = [];
				if ( $import->status ) {

					foreach ( $import->record as $tmp ) {
						$item        = [
							'label' => $tmp->bezeichnung,
							'value' => $tmp->id
						];
						$importArr[] = $item;
					}
				}
				$select = [
					'0' => [
						'label' => 'auswählen ...',
						'value' => 0
					]
				];

				$sortOut = [
					'0' => [
						'label' => __('Datum absteigend', 'wp-ebay-classifieds'),
						'value' => 1
					],
					'1' => [
						'label' => __('Datum aufsteigend', 'wp-ebay-classifieds'),
						'value' => 2
					],
					'2' => [
						'label' => __('Menu Order', 'wp-ebay-classifieds'),
						'value' => 3
					],
				];

				$selectContent = [
					'0' => [
						'label' => __('Content', 'wp-ebay-classifieds'),
						'value' => 'content'
					],
					'1' => [
						'label' => __('Textauszug', 'wp-ebay-classifieds'),
						'value' => 'description'
					]
				];
				$selectCount = [
					'0' => [
						'label' => __('alle', 'wp-ebay-classifieds'),
						'value' => '-1'
					],
					'1' => [
						'label' => '5',
						'value' => '5'
					],
					'2' => [
						'label' => '10',
						'value' => '10'
					],
					'3' => [
						'label' => '15',
						'value' => '15'
					],
					'4' => [
						'label' => '20',
						'value' => '20'
					],
					'5' => [
						'label' => '30',
						'value' => '30'
					],
					'6' => [
						'label' => '50',
						'value' => '50'
					],
				];

				$response->count = $selectCount;
				$response->content = $selectContent;
				$response->order = $sortOut;
				$response->import = array_merge_recursive( $select, $importArr );

				break;
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return void
	 */
	public
	function get_items(
		WP_REST_Request $request
	) {


	}

	/**
	 * Check if a given request has access.
	 *
	 * @return bool
	 */
	public
	function permissions_check(): bool {
		return current_user_can( 'edit_posts' );
	}
}