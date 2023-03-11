<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/admin
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Wp_Ebay_Classifieds\Importer\WP_Ebay_Importer_Settings;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/admin
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Ebay_Classifieds_Admin {

	use WP_Ebay_Importer_Settings;

	/**
	 * Store plugin main class to allow admin access.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var Wp_Ebay_Classifieds $main The main class.
	 */
	protected Wp_Ebay_Classifieds $main;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $basename    The ID of this plugin.
	 */
	private string $basename;

	/**
	 * TWIG autoload for PHP-Template-Engine
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Environment $twig TWIG autoload for PHP-Template-Engine
	 */
	protected Environment $twig;


	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private string $version;
	/**
	 * The default Settings
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array $settings The current version of this plugin.
	 */
	protected array $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $basename       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct(string $basename, string $version, Wp_Ebay_Classifieds $main, Environment $twig) {

		$this->basename = $basename;
		$this->version = $version;
		$this->main = $main;
		$this->twig = $twig;
		$this->settings = $this->get_wp_ebay_importer_settings_defaults();

	}

	public function register_wp_ebay_imports_admin_menu(): void
	{
		//delete_option($this->basename . '_settings');
		if (!get_option($this->basename . '_settings')) {
			update_option($this->basename . '_settings', $this->settings['settings']);
		}

		add_menu_page(
			__('eBay Import', 'wp-ebay-classifieds'),
			__('eBay Import', 'wp-ebay-classifieds'),
			get_option($this->basename . '_settings')['plugin_min_role'],
			'wp-ebay-importer',
			'',
			$this->get_svg_icons('cart3')
			, 210
		);

		$hook_suffix = add_submenu_page(
			'wp-ebay-importer',
			__('Importe', 'wp-ebay-classifieds'),
			__('Importe', 'wp-ebay-classifieds'),
			get_option($this->basename . '_settings')['plugin_min_role'],
			'wp-ebay-importer',
			array($this, 'wp_eby_importer_startseite'));

		add_action('load-' . $hook_suffix, array($this, 'wp_ebay_importer_load_ajax_admin_script'));

		$hook_suffix = add_submenu_page(
			'wp-ebay-importer',
			__('Kartendaten suchen', 'wp-ebay-classifieds'),
			__('Kartendaten suchen', 'wp-ebay-classifieds'),
			get_option($this->basename . '_settings')['plugin_min_role'],
			'wp-ebay-importer-osm',
			array($this, 'wp_eby_importer_osm'));

		add_action('load-' . $hook_suffix, array($this, 'wp_ebay_importer_load_ajax_admin_script'));
	}

	public function wp_eby_importer_startseite():void
	{


		//$t = apply_filters($this->basename.'/make_ebay_import_import', '');
		//$s = get_post(371, 5);
		//$s = apply_filters($this->basename.'/get_posts_by_taxonomy', 5);

		$data = [
			'plugin_url' => plugin_dir_url( __FILE__ ),
			'select' => $this->get_wp_ebay_importer_settings_defaults('select_user_role'),
		];
		try {
			$template = $this->twig->render('@templates/wp-ebay-importer-startseite.html.twig', $data);
			echo $this->html_compress_template($template);
		} catch (LoaderError|SyntaxError|RuntimeError $e) {
			echo $e->getMessage();
		} catch (Throwable $e) {
			echo $e->getMessage();
		}
	}

	public function wp_eby_importer_osm() :void
	{
		$data = [
			'plugin_url' => plugin_dir_url( __FILE__ ),
		];
		try {
			$template = $this->twig->render('@templates/wp-ebay-importer-kartendaten.html.twig', $data);
			echo $this->html_compress_template($template);
		} catch (LoaderError|SyntaxError|RuntimeError $e) {
			echo $e->getMessage();
		} catch (Throwable $e) {
			echo $e->getMessage();
		}
	}

	public function wp_ebay_importer_load_ajax_admin_script(): void {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		$title_nonce = wp_create_nonce('wp_ebay_importer_admin_handle');
		wp_register_script($this->basename . '-admin-ajax-script', '', [], '', true);
		wp_enqueue_script($this->basename . '-admin-ajax-script');
		wp_localize_script($this->basename . '-admin-ajax-script', 'wei_ajax_obj',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => $title_nonce,
				'data_table' => plugin_dir_url(__FILE__) . 'assets/js/tools/DataTablesGerman.json',
				'js_lang' => $this->js_language()
			));
	}

	/**
	 * @throws Exception
	 */
	public function admin_ajax_EbayImporter(): void
	{
		check_ajax_referer('wp_ebay_importer_admin_handle');
		require 'Ajax/wp_ebay_importer_admin_ajax.php';
		$adminAjaxHandle = WP_Ebay_Importer_Admin_Ajax::instance($this->basename, $this->main, $this->twig);
		wp_send_json($adminAjaxHandle->admin_ajax_handle());
	}

	/**
	 * Register the Update-Checker for the Plugin.
	 *
	 * @since    1.0.0
	 */
	public function set_wp_ebay_classifieds_update_checker(): void {
		if (get_option("{$this->basename}_server_api") && get_option($this->basename . '_server_api')->update->update_aktiv) {
			$ebayImporterUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
				get_option("{$this->basename}_server_api")->update->update_url_git,
				WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR . $this->basename . '.php',
				$this->basename
			);

			if (get_option("{$this->basename}_server_api")->update->update_type == '1') {
				if (get_option("{$this->basename}_server_api")->update->update_branch == 'release') {
					$ebayImporterUpdateChecker->getVcsApi()->enableReleaseAssets();
				} else {
					$ebayImporterUpdateChecker->setBranch(get_option("{$this->basename}_server_api")->update->branch_name);
				}
			}
		}
	}

	public function set_ebay_importer_trigger_site() :void
	{
		global $wp;
		$wp->add_query_var(SYNC_EBAY_IMPORTER_QUERY);
	}

	public function ebay_importer_trigger_check():void
	{
		if(get_query_var(SYNC_EBAY_IMPORTER_QUERY)){
			apply_filters($this->basename.'/make_ebay_import_import', (int) SYNC_EBAY_IMPORTER_QUERY);
			exit();
		}
	}

	public function wp_ebay_classifieds_show_upgrade_notification($current_plugin_metadata, $new_plugin_metadata): void {

		/**
		 * Check "upgrade_notice" in readme.txt.
		 *
		 * Eg.:
		 * == Upgrade Notice ==
		 * = 20180624 = <- new version
		 * Notice        <- message
		 *
		 */
		if (isset($new_plugin_metadata->upgrade_notice) && strlen(trim($new_plugin_metadata->upgrade_notice)) > 0) {

			// Display "upgrade_notice".
			echo sprintf('<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'wp-ebay-classifieds'), esc_html(rtrim($new_plugin_metadata->upgrade_notice)));

		}
	}


	/**
	 * Register the JavaScript for the admin area.
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

		$wp_eby_importer_current_screen = get_current_screen();

		wp_enqueue_script('jquery');
		wp_enqueue_style($this->basename . '-admin-bs-style', plugin_dir_url(__FILE__) . 'assets/css/bs/bootstrap.min.css', array(), $this->version, false);
		wp_enqueue_style($this->basename . '-animate', plugin_dir_url(__FILE__) . 'assets/css/tools/animate.min.css', array(), $this->version);
		wp_enqueue_style($this->basename . '-swal2', plugin_dir_url(__FILE__) . 'assets/css/tools/sweetalert2.min.css', array(), $this->version, false);
		wp_enqueue_style($this->basename . '-bootstrap-icons-style', WP_EBAY_CLASSIFIED_PLUGIN_URL . 'includes/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css', array(), $this->version);
		wp_enqueue_style($this->basename . '-font-awesome-icons-style', WP_EBAY_CLASSIFIED_PLUGIN_URL . 'includes/vendor/components/font-awesome/css/font-awesome.min.css', array(), $this->version);
		wp_enqueue_style($this->basename . '-admin-dashboard-style', plugin_dir_url(__FILE__) . 'assets/admin-dashboard-style.css', array(), $this->version, false);
		wp_enqueue_style($this->basename . '-admin-data-tables-bs5', plugin_dir_url(__FILE__) . 'assets/css/tools/dataTables.bootstrap5.min.css', array(), $this->version, false);
		wp_enqueue_script($this->basename . '-bs', plugin_dir_url(__FILE__) . 'assets/js/bs/bootstrap.bundle.min.js', array(), $this->version, true);
		wp_enqueue_script($this->basename . '-swal2', plugin_dir_url(__FILE__) . 'assets/js/tools/sweetalert2.all.min.js', array(), $this->version, true);
		wp_enqueue_script($this->basename . '-data-table', plugin_dir_url(__FILE__) . 'assets/js/tools/data-table/jquery.dataTables.min.js', array(), $this->version, true);
		wp_enqueue_script($this->basename . '-bs-data-table', plugin_dir_url(__FILE__) . 'assets/js/tools/data-table/dataTables.bootstrap5.min.js', array(), $this->version, true);

		wp_enqueue_script($this->basename . '_global', plugin_dir_url(__FILE__) . 'assets/js/wp-ebay-importer-global.js', array('jquery'), $this->version, false);
		wp_enqueue_script( $this->basename, plugin_dir_url( __FILE__ ) . 'assets/js/wp-ebay-classifieds-admin.js', array( 'jquery' ), $this->version, true );

	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	private static function get_svg_icons($name): string
	{
		$icon = '';
		switch ($name) {
			case'shield':
				$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
                         <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                         <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                          </svg>';
				break;
			case 'cart':
				$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
  						 <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
						 </svg>';
				break;
			case'cart3':
				$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
  						 <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
						 </svg>';
				break;

			default:
		}

		return 'data:image/svg+xml;base64,' . base64_encode($icon);

	}

	protected function html_compress_template(string $string): string
	{
		if (!$string) {
			return $string;
		}

		return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace([
			"\n",
			"\r",
			"\t"
		], '', $string));
	}

}
