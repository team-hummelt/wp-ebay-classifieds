<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Wp_Ebay_Classifieds\Importer\Ebay_Import_Cronjob;
use Wp_Ebay_Classifieds\Importer\Ebay_Import_Execute;
use Wp_Ebay_Classifieds\Importer\Register_Ebay_Importer_Callback;
use Wp_Ebay_Classifieds\Importer\WP_Ebay_Importer_DB_Handle;
use Wp_Ebay_Classifieds\Importer\WP_Ebay_Importer_Helper;
use Wp_Ebay_Classifieds\Importer\WP_Ebay_Importer_Rest_Endpoint;
use Wp_Ebay_Classifieds\License\Register_Api_WP_Remote;
use Wp_Ebay_Classifieds\License\Register_Product_License;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Ebay_Classifieds {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Ebay_Classifieds_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected Wp_Ebay_Classifieds_Loader $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected string $plugin_name;


	/**
	 * The PLUGIN API ID_RSA.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $id_plugin_rsa plugin API ID_RSA.
	*/
	private string $id_plugin_rsa;

	/**
	 * The PLUGIN API ID_RSA.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      object $plugin_api_config plugin API ID_RSA.
	 */
	protected object $plugin_api_config;

	/**
	 * The plugin Slug Path.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_slug plugin Slug Path.
	 */
	private string $plugin_slug;

	/**
	 * Store plugin main class to allow public access.
	 *
	 * @since    1.0.0
	 * @var object The main class.
	 */
	public object $main;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected string $version = '';

	/**
	 * The current database version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $db_version The current database version of the plugin.
	 */
	protected string $db_version;

	/**
	 * TWIG autoload for PHP-Template-Engine
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Environment $twig TWIG autoload for PHP-Template-Engine
	 */
	private Environment $twig;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @throws LoaderError
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = WP_EBAY_CLASSIFIED_BASENAME;
		$this->plugin_slug = WP_EBAY_CLASSIFIED_SLUG_PATH;
		$this->main        = $this;

		/**
		 * Currently plugin version.
		 * Start at version 1.0.0 and use SemVer - https://semver.org
		 * Rename this for your plugin and update it as you release new versions.
		 */
		$plugin = get_file_data( plugin_dir_path( dirname( __FILE__ ) ) . $this->plugin_name . '.php', array( 'Version' => 'Version' ), false );
		if ( ! $this->version ) {
			$this->version = $plugin['Version'];
		}

		if ( defined( 'WP_EBAY_CLASSIFIED_DB_VERSION' ) ) {
			$this->db_version = WP_EBAY_CLASSIFIED_DB_VERSION;
		} else {
			$this->db_version = '1.0.0';
		}


		$this->check_dependencies();
		$this->load_dependencies();

		$twigAdminDir = plugin_dir_path( dirname( __FILE__ ) ) . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR;
		$twig_loader  = new FilesystemLoader( $twigAdminDir );
		$twig_loader->addPath( $twigAdminDir . 'Templates', 'templates' );
		$twig_loader->addPath( $twigAdminDir . 'Templates'.DIRECTORY_SEPARATOR.'Layout'.DIRECTORY_SEPARATOR, 'layout' );
		$twig_loader->addPath( $twigAdminDir . 'Templates'.DIRECTORY_SEPARATOR.'Loops'.DIRECTORY_SEPARATOR, 'loop' );
		$this->twig = new Environment( $twig_loader );

		//JOB Twig Filter
		$language   = new TwigFilter( '__', function ( $value ) {
			return __( $value, 'wp-ebay-classifieds' );
		} );

		$uname   = new TwigFilter( 'get_uname', function ( $type ) {
			if($type == 'full_name'){
				return php_uname();
			} else {
				return PHP_OS;
			}
		});

		$getVersion = new TwigFilter('version', function () {
			return $this->version;
		});
		$getDbVersion = new TwigFilter('dbVersion', function () {
			return $this->db_version;
		});
		$getOption = new TwigFilter('get_option', function ($option) {
			return get_option($option);
		});

		$getTermName = new TwigFilter('get_category', function ($id) {
			$term = get_term( $id );
			if($term){
				return $term->name;
			}
			return  '';
		});

		$this->twig->addFilter( $language );
		$this->twig->addFilter( $getVersion );
		$this->twig->addFilter( $getDbVersion );
		$this->twig->addFilter( $getOption );
		$this->twig->addFilter( $uname );
		$this->twig->addFilter( $getTermName );

		$this->set_locale();
		$this->define_product_license_class();
		$this->register_wp_ebay_importer_helper_class();
		$this->register_wp_ebay_imports_database_handle();
		$this->register_cron_ebay_importer();
		$this->ebay_import_cronjob();
		$this->register_wp_ebay_importer_gutenberg_tools();
		if ( is_file( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-ebay-classifieds-admin.php' ) ) {
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Ebay_Classifieds_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Ebay_Classifieds_i18n. Defines internationalization functionality.
	 * - Wp_Ebay_Classifieds_Admin. Defines all hooks for the admin area.
	 * - Wp_Ebay_Classifieds_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies(): void {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-ebay-classifieds-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-ebay-classifieds-i18n.php';

		/**
		 * The code that runs during plugin activation.
		 * This action is documented in includes/class-hupa-teams-activator.php
		 */
		require_once plugin_dir_path(dirname(__FILE__ ) ) . 'includes/class-wp-ebay-classifieds-activator.php';

		/**
		 * The Settings Trait
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/trait_wp_ebay_importer_settings.php';

		/**
		 * Plugin WP Gutenberg Block Callback
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_register_ebay_importer_callback.php';

		/**
		 * The Helper Class
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class_wp_ebay_importer_helper.php';

		/**
		 * Plugin WP_CRON_EXECUTE
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ebayImporter/class_ebay_import_cronjob.php';

		/**
		 * Plugin WP_CRON
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ebayImporter/class_ebay_import_execute.php';

		/**
		 * Plugin WP Gutenberg Sidebar
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_register_ebay_importer_gutenberg_tools.php';

		/**
		 * The  database for the Ebay-Importer Login Plugin
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Database/class_wp_ebay_importer_db_handle.php';


		/**
		 * WP Ebay Importer Login REST-Endpoint
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_wp_ebay_importer_rest_endpoint.php';

		/**
		 * Composer-Autoload
		 * Composer Vendor for Theme|Plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/vendor/autoload.php';

		/**
		 * // JOB The class responsible for defining all actions that occur in the license area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/license/class_register_product_license.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/license/admin/class_register_api_wp_remote.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */

		if ( is_file( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-ebay-classifieds-admin.php' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-ebay-classifieds-admin.php';
		}
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-ebay-classifieds-public.php';

		$this->loader = new Wp_Ebay_Classifieds_Loader();

	}

	/**
	 * Check PHP and WordPress Version
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function check_dependencies(): void {
		global $wp_version;
		if ( version_compare( PHP_VERSION, WP_EBAY_CLASSIFIED_MIN_PHP_VERSION, '<' ) || $wp_version < WP_EBAY_CLASSIFIED_MIN_WP_VERSION ) {
			$this->maybe_self_deactivate();
		}
	}

	/**
	 * Self-Deactivate
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function maybe_self_deactivate(): void {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( $this->plugin_slug );
		add_action( 'admin_notices', array( $this, 'self_deactivate_notice' ) );
	}

	/**
	 * Self-Deactivate Admin Notiz
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function self_deactivate_notice(): void {
		echo sprintf( '<div class="notice notice-error is-dismissible" style="margin-top:5rem"><p>' . __( 'This plugin has been disabled because it requires a PHP version greater than %s and a WordPress version greater than %s. Your PHP version can be updated by your hosting provider.', 'wp-ebay-classifieds' ) , WP_EBAY_CLASSIFIED_MIN_PHP_VERSION, WP_EBAY_CLASSIFIED_MIN_WP_VERSION ).'</p></div>';
		exit();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Ebay_Classifieds_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale(): void {

		$plugin_i18n = new Wp_Ebay_Classifieds_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_product_license_class(): void {

		if(!get_option('hupa_server_url')){
			update_option('hupa_server_url', $this->get_license_config()->api_server_url);
		}

		global $wpRemoteLicense;
		$wpRemoteLicense = new Register_Api_WP_Remote($this->get_plugin_name(), $this->get_version(), $this->get_license_config(), $this->main);
		global $product_license;
		$product_license = new Register_Product_License( $this->get_plugin_name(), $this->get_version(), $this->get_license_config(), $this->main );
		$this->loader->add_action( 'init', $product_license, 'license_site_trigger_check' );
		$this->loader->add_action( 'template_redirect', $product_license, 'license_callback_site_trigger_check' );
	}

	/**
	 * Register all the hooks related to the Gutenberg Plugins functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_wp_ebay_importer_helper_class(): void {
		global $wpEbayImporterHelper;
		$wpEbayImporterHelper = WP_Ebay_Importer_Helper::instance($this->version, $this->plugin_name, $this->main);
		$this->loader->add_filter( $this->plugin_name.'/get_random_id', $wpEbayImporterHelper, 'getRandomString' );
		$this->loader->add_filter( $this->plugin_name.'/generate_random_id', $wpEbayImporterHelper, 'getGenerateRandomId',10,4 );
		$this->loader->add_filter( $this->plugin_name.'/fileSizeConvert', $wpEbayImporterHelper, 'fileSizeConvert' );
		$this->loader->add_filter( $this->plugin_name.'/ArrayToObject', $wpEbayImporterHelper, 'arrayToObject' );
		$this->loader->add_filter( $this->plugin_name.'/object2Array', $wpEbayImporterHelper, 'object2array_recursive' );
		$this->loader->add_filter( $this->plugin_name.'/date_format_language', $wpEbayImporterHelper, 'date_format_language', 10, 3 );
		$this->loader->add_filter($this->plugin_name . '/get_import_taxonomy', $wpEbayImporterHelper, 'fn_get_import_taxonomy',10,2);
		$this->loader->add_filter($this->plugin_name . '/get_next_cron_time', $wpEbayImporterHelper, 'import_get_next_cron_time');
		$this->loader->add_filter($this->plugin_name . '/get_curl_json_data', $wpEbayImporterHelper, 'get_curl_json_data',10,5);

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks(): void {


		$ebayImporterActivator = new Wp_Ebay_Classifieds_Activator();
		$this->loader->add_action('init', $ebayImporterActivator, 'register_wp_ebay_importer_post_type');
		$this->loader->add_action('init', $ebayImporterActivator, 'register_wp_ebay_importer_taxonomy');

		$plugin_admin = new Wp_Ebay_Classifieds_Admin( $this->get_plugin_name(), $this->get_version(), $this->main, $this->twig );
		$this->loader->add_action( 'init', $plugin_admin, 'set_wp_ebay_classifieds_update_checker' );
		$this->loader->add_action( 'in_plugin_update_message-' . $this->plugin_name . '/' . $this->plugin_name .'.php', $plugin_admin, 'wp_ebay_classifieds_show_upgrade_notification',10,2 );

		$this->loader->add_action('admin_menu', $plugin_admin, 'register_wp_ebay_imports_admin_menu');
		$this->loader->add_action('wp_ajax_nopriv_EbayImporter', $plugin_admin, 'admin_ajax_EbayImporter');
		$this->loader->add_action('wp_ajax_EbayImporter', $plugin_admin, 'admin_ajax_EbayImporter');

		$this->loader->add_action('init', $plugin_admin, 'set_ebay_importer_trigger_site');
		$this->loader->add_action('template_redirect', $plugin_admin, 'ebay_importer_trigger_check');

		$registerWpEbayEndpoint = new WP_Ebay_Importer_Rest_Endpoint($this->plugin_name, $this->main);
		$this->loader->add_action('rest_api_init', $registerWpEbayEndpoint, 'register_wp_ebay_importer_routes');

		global $registerEbayImporterCallback;
		$registerEbayImporterCallback = Register_Ebay_Importer_Callback::instance($this->plugin_name, $this->version, $this->main);
	}

	/**
	 * Register all the hooks related Database functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_wp_ebay_imports_database_handle(): void {
		global $wpEbayImporterDb;
		$wpEbayImporterDb = WP_Ebay_Importer_DB_Handle::instance($this->plugin_name, $this->db_version, $this->main);
		$this->loader->add_action( 'init', $wpEbayImporterDb, 'wp_ebay_importer_check_jal_install');
		$this->loader->add_filter( $this->plugin_name.'/get_ebay_import', $wpEbayImporterDb, 'getEbayImportsByArgs', 10, 3 );
		$this->loader->add_filter( $this->plugin_name.'/set_ebay_import', $wpEbayImporterDb, 'setWpEbayImport');
		$this->loader->add_filter( $this->plugin_name.'/update_ebay_import', $wpEbayImporterDb, 'updateWpEbayImport');
		$this->loader->add_filter( $this->plugin_name.'/delete_ebay_import', $wpEbayImporterDb, 'deleteWpEbayImport');
		//JOB POSTS / Attachments
		$this->loader->add_action( 'before_delete_post', $wpEbayImporterDb, 'ebay_import_delete_post_before',10,2);
		// POST Image(s)
		$this->loader->add_filter( $this->plugin_name.'/ebay_import_get_images', $wpEbayImporterDb, 'ebay_import_get_images', 10, 3 );

		$this->loader->add_filter( $this->plugin_name.'/get_post_by_ebay_id', $wpEbayImporterDb, 'get_post_by_ebay_id', 10, 2 );
		$this->loader->add_filter( $this->plugin_name.'/get_posts_by_taxonomy', $wpEbayImporterDb, 'get_posts_by_taxonomy', 10, 2 );
		$this->loader->add_filter( $this->plugin_name.'/get_posts_by_import_id', $wpEbayImporterDb, 'get_posts_by_import_id' );
		$this->loader->add_filter( $this->plugin_name.'/get_ebay_import_meta', $wpEbayImporterDb, 'get_ebay_import_meta', 10 ,2 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks(): void {

		$plugin_public = new Wp_Ebay_Classifieds_Public( $this->get_plugin_name(), $this->get_version(), $this->main );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action('wp_ajax_nopriv_PublicEbayImporter', $plugin_public, 'public_ajax_PublicEbayImporter');
		$this->loader->add_action('wp_ajax_PublicEbayImporter', $plugin_public, 'public_ajax_PublicEbayImporter');
	}

	/**
	 * Register Eby Importer Gutenberg Tools
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_wp_ebay_importer_gutenberg_tools(): void
	{
		global $gutenbergTools;
		$gutenbergTools = new Register_Ebay_Importer_Gutenberg_Tools($this->plugin_name, $this->version, $this->main);

		//META Fields
		$this->loader->add_action( 'init', $gutenbergTools, 'register_ebay_imports_meta_fields');

		$this->loader->add_action('init', $gutenbergTools, 'rss_importer_gutenberg_register_sidebar');
		$this->loader->add_action('init', $gutenbergTools, 'register_rss_importer_block_type');
		$this->loader->add_action('enqueue_block_editor_assets', $gutenbergTools, 'ebay_importer_block_type_scripts');
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_cron_ebay_importer(): void {
		if ($this->check_wp_cron()) {
			$ebayCron = new Ebay_Import_Cronjob($this->plugin_name, $this->main);
			$this->loader->add_filter($this->plugin_name . '/ebay_run_schedule_task', $ebayCron, 'fn_ebay_run_schedule_task');
			$this->loader->add_filter($this->plugin_name . '/ebay_wp_un_schedule_task', $ebayCron, 'fn_ebay_wp_un_schedule_task');
			$this->loader->add_filter($this->plugin_name . '/ebay_wp_delete_task', $ebayCron, 'fn_ebay_wp_delete_task');
		}
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * @access   private
	 */
	private function ebay_import_cronjob(): void {
		global $ebayImportExecute;
		$ebayImportExecute = Ebay_Import_Execute::instance($this->plugin_name,$this->version, $this->main);
		$this->loader->add_action('ebay_import_sync', $ebayImportExecute, 'ebay_import_synchronisation',0);
		$this->loader->add_filter($this->plugin_name . '/make_ebay_import_import', $ebayImportExecute, 'fn_make_ebay_import_import');
		$this->loader->add_filter($this->plugin_name . '/ebay_import_delete_post', $ebayImportExecute, 'ebay_import_delete_post');
		$this->loader->add_filter($this->plugin_name . '/delete_post_attachments', $ebayImportExecute, 'delete_post_attachments');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Ebay_Classifieds_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Wp_Ebay_Classifieds_Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}

	public function get_plugin_api_config(): object {
		return $this->plugin_api_config;
	}

	/**
	 * License Config for the plugin.
	 *
	 * @return    object License Config.
	 * @since     1.0.0
	 */
	public function get_license_config():object {
		$config_file = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/license/config.json';
		return json_decode(file_get_contents($config_file));
	}

	/**
	 * @return bool
	 */
	private function check_wp_cron(): bool
	{
		if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
			return false;
		} else {
			return true;
		}
	}

}
