<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/public
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Ebay_Classifieds_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $basename The ID of this plugin.
	 */
	private $basename;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Store plugin main class to allow public access.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var Wp_Ebay_Classifieds $main The main class.
	 */
	private Wp_Ebay_Classifieds $main;

	private array $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $basename The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $basename, string $version, Wp_Ebay_Classifieds $main ) {

		$this->basename = $basename;
		$this->version  = $version;
		$this->main = $main;
		$this->settings = get_option( $this->basename . '_settings' );

	}

	/**
	 * @throws Exception
	 */
	public function public_ajax_PublicEbayImporter(): void
	{
		check_ajax_referer('wp_ebay_importer_public_handle');
		require 'Ajax/class_wp_ebay_importer_public_ajax.php';
		$publicAjaxHandle = WP_Ebay_Importer_Public_Ajax::instance($this->basename, $this->main);
		wp_send_json($publicAjaxHandle->public_ajax_handle());
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Ebay_Classifieds_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Ebay_Classifieds_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( $this->settings['bootstrap_css_aktiv'] ) {
			wp_enqueue_style( $this->basename . '-bs-style', plugin_dir_url( __FILE__ ) . 'css/bs/bootstrap.min.css', array(), $this->version, 'all' );
		}
		wp_enqueue_style( $this->basename, plugin_dir_url( __FILE__ ) . 'css/wp-ebay-classifieds-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Ebay_Classifieds_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Ebay_Classifieds_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( $this->settings['bootstrap_js_aktiv'] ) {
			wp_enqueue_script( $this->basename . '-bs-script', plugin_dir_url( __FILE__ ) . 'js/bs/bootstrap.bundle.min.js', array(), $this->version, true );
		}

		$title_nonce = wp_create_nonce( 'wp_ebay_importer_public_handle' );
		wp_enqueue_script( $this->basename, plugin_dir_url( __FILE__ ) . 'js/wp-ebay-classifieds-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->basename, 'ecp_ajax_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => $title_nonce,
			));
	}

}
