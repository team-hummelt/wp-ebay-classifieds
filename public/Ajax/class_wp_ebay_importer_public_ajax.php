<?php
/**
 * The Ajax public-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 */

defined( 'ABSPATH' ) or die();

use Wp_Ebay_Classifieds\Importer\WP_Ebay_Importer_Settings;

class WP_Ebay_Importer_Public_Ajax
{
	use WP_Ebay_Importer_Settings;
	private static $instance;
	private string $method;
	private object $responseJson;

	/**
	 * Store plugin main class to allow public access.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var Wp_Ebay_Classifieds $main The main class.
	 */
	private Wp_Ebay_Classifieds $main;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $basename The ID of this plugin.
	 */
	private string $basename;

	/**
	 * @return static
	 */
	public static function instance( string $basename, Wp_Ebay_Classifieds $main ): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $basename, $main );
		}

		return self::$instance;
	}

	public function __construct( string $basename, Wp_Ebay_Classifieds $main ) {
		$this->main         = $main;
		$this->basename     = $basename;
		$this->method       = filter_input( INPUT_POST, 'method', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH );
		$this->responseJson = (object) [
			'status' => false,
			'msg'    => date( 'H:i:s', current_time( 'timestamp' ) ),
			'type'   => $this->method
		];
	}

	/**
	 * @throws Exception
	 */
	public function public_ajax_handle() {
		if ( ! method_exists( $this, $this->method ) ) {
			throw new Exception( "Method not found!#Not Found" );
		}

		return call_user_func_array( self::class . '::' . $this->method, [] );
	}

	private function test():object
	{
		return $this->responseJson;
	}
	private function get_post_meta_by_id(): object
	{
		return $this->responseJson;
	}
}