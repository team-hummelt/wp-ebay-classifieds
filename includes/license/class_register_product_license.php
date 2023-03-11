<?php

namespace Wp_Ebay_Classifieds\License;


use Wp_Ebay_Classifieds;


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
class Register_Product_License {

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
	 * Store plugin main class to allow public access.
	 *
	 * @param string $basename
	 * @param string $version
	 * @param object $config
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @var Wp_Ebay_Classifieds $main
	 */

	public function __construct( string $basename, string $version, object $config, Wp_Ebay_Classifieds $main ) {

		$produkt_dir      = '';
		$this->basename   = $basename;
		$this->version    = $version;
		$this->config     = $config;
		$this->main       = $main;
		$this->plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR;
		$this->load_license_dependencies();
		$this->register_license_wp_remote();

		add_action( 'init', array( $this, 'load_license_textdomain' ) );



		if ( $config->type == 'plugin' ) {
			$produkt_dir = $this->plugin_dir;
		}
		if ( $config->type == 'theme' ) {
			$produkt_dir = get_template_directory() . DIRECTORY_SEPARATOR;
		}

		if ( ! get_option( "{$this->basename}_server_api" )  ) {
			update_option( "{$this->basename}_server_api", $this->config );
		}

		if ( ! get_option( "{$this->basename}_product_install_authorize" ) || get_option("{$this->basename}_server_api")->settings->show_license_menu == '1' ) {
			add_action( 'admin_menu', array( $this, 'register_product_license_menu' ) );
		}

		if ( ! get_option( "{$this->basename}_product_install_authorize" ) ) {
			$file = $produkt_dir . $this->config->aktivierungs_file_path . DIRECTORY_SEPARATOR . $this->config->aktivierungs_file;
			if ( is_file( $file ) ) {
				delete_option( $this->basename . '_client_secret' );
				delete_option( $this->basename . '_client_id' );
				@unlink( $file );
			}
		}


		$this->add( function () {
			check_ajax_referer( 'register_license_handle' );
			require_once 'admin/class_register_license_ajax.php';
			$adminAjaxHandle = new Register_License_Ajax( $this->basename, $this->version, $this->main, $this->config );
			wp_send_json( $adminAjaxHandle->product_admin_ajax_handle() );
		}, "prefix_ajax_{$this->basename}_LicenceHandle" );

		add_action( "wp_ajax_{$this->basename}_LicenceHandle", array(
			$this,
			"prefix_ajax_{$this->basename}_LicenceHandle"
		) );

		$this->add( function () {
			if ( get_transient( "$this->basename-admin-notice-error-panel-" . get_current_user_id() . "" ) ) {
				$class   = 'notice notice-error is-dismissible';
				$message = sprintf( __( '%s invalid license: To activate, enter your credentials.', 'licenseLanguage' ), $this->config->name );
				echo sprintf( '<div class="%s"><p>%s</p></div>', $class, $message );
			}
		}, "{$this->basename}_admin_error_notice" );

		$this->add( function () {
			if ( get_transient( "$this->basename-admin-notice-success-panel-" . get_current_user_id() . "" ) ) {
				$class   = 'notice notice-success is-dismissible';
				$message = sprintf( __( 'The %s plugin has been successfully activated.', 'licenseLanguage' ), $this->config->name );
				echo sprintf( '<div class="%s"><p>%s</p></div>', $class, $message );
				//delete_transient( "$this->basename-admin-notice-success-panel-" . get_current_user_id() . "" );
			}
		}, "{$this->basename}_admin_success_notice" );

		add_action( "admin_notices", array( $this, "{$this->basename}_admin_error_notice" ) );
		add_action( "admin_notices", array( $this, "{$this->basename}_admin_success_notice" ) );
	}

	private function load_license_dependencies(): void {
		require_once 'admin/class_register_exec_license.php';
		require_once 'admin/class_register_api_wp_remote.php';


	}

	private function register_license_wp_remote() {
		global $license_wp_remote;
		$license_wp_remote = new Register_Api_WP_Remote( $this->basename, $this->version, $this->config, $this->main );
		$license_wp_remote->init_register_license_wp_remote_api();
	}

	/**
	 * @param $func
	 * @param $name
	 */
	public function add( $func, $name ) {
		$this->{$name} = $func;
	}

	/**
	 * @param $func
	 * @param $arguments
	 */
	public function __call( $func, $arguments ) {
		call_user_func_array( $this->{$func}, $arguments );
	}

	public function register_product_license_menu() {
		$hook_suffix = add_menu_page(
			__( $this->config->name, 'licenseLanguage' ),
			__( $this->config->name, 'licenseLanguage' ),
			'manage_options',
			$this->basename . '-license',
			array( $this, 'hupa_license_admin_menu_page' ),
			'dashicons-lock', 2
		);
		add_action( 'load-' . $hook_suffix, array( $this, 'license_load_ajax_admin_options_script' ) );
	}

	public function load_license_textdomain(): void {
		load_plugin_textdomain( 'licenseLanguage', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
	}

	public function hupa_license_admin_menu_page(): void {
		require_once 'partials/license-activate-product.php';
	}

	public function license_load_ajax_admin_options_script(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_license_register_admin_style' ) );
		$title_nonce = wp_create_nonce( 'register_license_handle' );
		wp_register_script( $this->config->basename . '-ajax-script', '', [], '', true );
		wp_enqueue_script( $this->config->basename . '-ajax-script' );
		wp_localize_script( $this->config->basename . '-ajax-script', 'license_obj', array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => $title_nonce,
			'licenseHandle' => $this->basename . '_LicenceHandle'
		) );
	}

	public function license_site_trigger_check(): void {
		global $wp;
		$wp->add_query_var( $this->basename );
	}

	public function license_callback_site_trigger_check(): void {
		if ( get_query_var( $this->basename ) === $this->basename ) {
			require 'admin/class_api_request_exec.php';
			new Api_Request_Exec( $this->basename, $this->version, $this->config, $this->main, $this->plugin_dir );
			exit;
		}
	}

	public function load_license_register_admin_style(): void {
		wp_enqueue_style( $this->basename . '-license-style', plugins_url( $this->basename ) . '/includes/license/assets/css/license-backend.css', array(), '' );
		wp_enqueue_script( $this->basename . '-license-script', plugins_url( $this->basename ) . '/includes/license/assets/js/license.js', array(), '', true );
	}
}