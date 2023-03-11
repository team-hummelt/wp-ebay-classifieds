<?php
/**
 * The Ajax admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 */

defined( 'ABSPATH' ) or die();

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Wp_Ebay_Classifieds\Importer\WP_Ebay_Importer_Settings;

class WP_Ebay_Importer_Admin_Ajax {
	use WP_Ebay_Importer_Settings;

	private static $instance;
	private string $method;
	private object $responseJson;
	/**
	 * Store plugin main class to allow child access.
	 *
	 * @var Environment $twig TWIG autoload for PHP-Template-Engine
	 */
	protected Environment $twig;

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
	public static function instance( string $basename, Wp_Ebay_Classifieds $main, Environment $twig ): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $basename, $main, $twig );
		}

		return self::$instance;
	}

	public function __construct( string $basename, Wp_Ebay_Classifieds $main, Environment $twig ) {
		$this->main         = $main;
		$this->twig         = $twig;
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
	public function admin_ajax_handle() {
		if ( ! method_exists( $this, $this->method ) ) {
			throw new Exception( "Method not found!#Not Found" );
		}

		return call_user_func_array( self::class . '::' . $this->method, [] );
	}

	private function get_import_overview_template(): object {
		$this->responseJson->target = filter_input( INPUT_POST, 'target', FILTER_UNSAFE_RAW );
		global $wpEbayImporterHelper;
		$importe    = apply_filters( $this->basename . '/get_ebay_import', '' );
		$importData = [];
		if ( $importe->status ) {
			$importData = $wpEbayImporterHelper->object2array_recursive( $importe->record );
		}
		$data = [
			'data' => $importData
		];
		try {
			$template                     = $this->twig->render( '@loop/importe-overview.html.twig', $data );
			$this->responseJson->template = $this->html_compress_template( $template );
		} catch ( LoaderError|SyntaxError|RuntimeError $e ) {
			$this->responseJson->msg = $e->getMessage();

			return $this->responseJson;
		} catch ( Throwable $e ) {
			$this->responseJson->msg = $e->getMessage();

			return $this->responseJson;
		}

		$this->responseJson->status = true;

		return $this->responseJson;
	}

	private function ebay_import_template_handle(): object {
		$handle = filter_input( INPUT_POST, 'handle', FILTER_UNSAFE_RAW );
		$id     = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( $handle == 'update' && ! $id ) {
			$this->responseJson->msg = __( 'Ajax transmission error.', 'wp-ebay-classifieds' ) . ' (Ajx - ' . __LINE__ . ')';
		}
		$importData = [];
		if ( $handle == 'update' ) {
			$args   = sprintf( 'WHERE i.id=%d', $id );
			$import = apply_filters( $this->basename . '/get_ebay_import', $args, false, true );
			if ( ! $import->status ) {
				$this->responseJson->msg = 'Es wurden keine Daten gefunden. (Ajx - ' . __LINE__ . ')';

				return $this->responseJson;
			}
			$importData = $import->record;
		}
		$taxonomy = apply_filters( $this->basename . '/get_import_taxonomy', 'anzeigen-kategorie', 'anzeigen' );
		if ( strtolower( PHP_OS ) == 'linux' ) {
			$system = 1;
		} else {
			$system = '';
		}
		$data = [
			'id'       => $id,
			'handle'   => $handle,
			'd'        => $importData,
			'taxonomy' => $taxonomy,
			'system'   => $system
		];
		try {
			$template                     = $this->twig->render( '@loop/import-handle.html.twig', $data );
			$this->responseJson->template = $this->html_compress_template( $template );
		} catch ( LoaderError|SyntaxError|RuntimeError $e ) {
			$this->responseJson->msg = $e->getMessage();

			return $this->responseJson;
		} catch ( Throwable $e ) {
			$this->responseJson->msg = $e->getMessage();

			return $this->responseJson;
		}

		$this->responseJson->status = true;

		return $this->responseJson;
	}

	private function import_db_handle(): object {
		$record       = new stdClass();
		$handle       = filter_input( INPUT_POST, 'handle', FILTER_UNSAFE_RAW );
		$id           = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$pro_user     = filter_input( INPUT_POST, 'pro_user', FILTER_VALIDATE_INT );
		$term_id      = filter_input( INPUT_POST, 'term_id', FILTER_VALIDATE_INT );
		$url          = filter_input( INPUT_POST, 'import_url', FILTER_VALIDATE_URL );
		$bezeichnung  = filter_input( INPUT_POST, 'bezeichnung', FILTER_UNSAFE_RAW );
		$site_number  = filter_input( INPUT_POST, 'site_number', FILTER_VALIDATE_INT );
		$import_count = filter_input( INPUT_POST, 'import_count', FILTER_VALIDATE_INT );
		filter_input( INPUT_POST, 'bilder_importieren', FILTER_UNSAFE_RAW ) ? $record->bilder_importieren = 1 : $record->bilder_importieren = 0;
		filter_input( INPUT_POST, 'osm_aktiv', FILTER_UNSAFE_RAW ) ? $record->osm_aktiv = 1 : $record->osm_aktiv = 0;
		if ( ! $handle ) {
			$this->responseJson->msg = __( 'Ajax transmission error.', 'wp-ebay-classifieds' ) . ' (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}
		$this->responseJson->title = 'Fehler';
		if ( $handle == 'update' && ! $id ) {
			$this->responseJson->msg = __( 'Ajax transmission error.', 'wp-ebay-classifieds' ) . ' (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}
		if ( ! $url ) {
			$this->responseJson->msg = 'Ungültige Url. (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}


		preg_match( '@.*/$@', $url, $matches );
		if ( ! $matches ) {
			$url = $url . '/';
		}

		if ( ! $bezeichnung ) {
			$this->responseJson->msg = 'Keine Bezeichnung eingegeben. (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}

		if ( ! $term_id ) {
			$this->responseJson->msg = 'Ungültige Kategorie. (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}
		$record->pro_user     = (int) $pro_user;
		$record->term_id      = (int) $term_id;
		$record->import_url   = $this->fnPregWhitespace( $url );
		$record->bezeichnung  = $this->fnPregWhitespace( $bezeichnung );
		$record->site_number  = (int) $site_number ?? 1;
		$record->import_count = (int) $import_count ?? 25;
		if ( $handle == 'insert' ) {
			$insert                     = apply_filters( $this->basename . '/set_ebay_import', $record );
			$this->responseJson->msg    = $insert->msg;
			$this->responseJson->title  = $insert->title;
			$this->responseJson->status = $insert->status;

			return $this->responseJson;
		}
		$record->id                 = (int) $id;
		$update                     = apply_filters( $this->basename . '/update_ebay_import', $record );
		$this->responseJson->status = $update->status;

		return $this->responseJson;
	}

	private function delete_import(): object {
		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( ! $id ) {
			$this->responseJson->msg = __( 'Ajax transmission error.', 'wp-ebay-classifieds' ) . ' (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}
		$imports = apply_filters( $this->basename . '/get_posts_by_import_id', (int) $id );
		if ( $imports ) {
			foreach ( $imports as $tmp ) {
				apply_filters( $this->basename . '/delete_post_attachments', (int) $tmp->ID );
				apply_filters( $this->basename . '/ebay_import_delete_post', (int) $tmp->ID );
			}
		}

		apply_filters( $this->basename . '/delete_ebay_import', (int) $id );
		$this->responseJson->title  = 'Import gelöscht!';
		$this->responseJson->msg    = 'Der Import und alle Daten erfolgreich gelöscht.';
		$this->responseJson->id     = $id;
		$this->responseJson->status = true;

		return $this->responseJson;
	}

	private function get_import_settings_template(): object {
		$this->responseJson->toggle = filter_input( INPUT_POST, 'toggle', FILTER_VALIDATE_INT );
		$nextTime                   = apply_filters( $this->basename . '/get_next_cron_time', 'ebay_import_sync' );
		$next_time                  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $nextTime );
		$next_date                  = date( 'd.m.Y', strtotime( $next_time ) );
		$next_clock                 = date( 'H:i:s', strtotime( $next_time ) );
		$data                       = [
			's'        => get_option( $this->basename . '_settings' ),
			'dateTime' => $next_time,
			'select'   => [
				'select_api_sync_interval' => $this->get_wp_ebay_importer_settings_defaults( 'select_api_sync_interval' ),
				'select_user_role'         => $this->get_wp_ebay_importer_settings_defaults( 'select_user_role' )
			]
		];

		try {
			$template                     = $this->twig->render( '@loop/importe-settings.html.twig', $data );
			$this->responseJson->template = $this->html_compress_template( $template );
		} catch ( LoaderError|SyntaxError|RuntimeError $e ) {
			$this->responseJson->msg = $e->getMessage();

			return $this->responseJson;
		} catch ( Throwable $e ) {
			$this->responseJson->msg = $e->getMessage();

			return $this->responseJson;
		}
		$this->responseJson->next_date  = $next_date;
		$this->responseJson->next_clock = $next_clock;
		$this->responseJson->next_time  = $next_time;
		$this->responseJson->status     = true;

		return $this->responseJson;
	}

	private function update_import_settings(): object {
		$selected_cron_sync_interval = filter_input( INPUT_POST, 'selected_cron_sync_interval', FILTER_UNSAFE_RAW );
		$plugin_min_role             = filter_input( INPUT_POST, 'plugin_min_role', FILTER_UNSAFE_RAW );
		filter_input( INPUT_POST, 'cron_aktiv', FILTER_UNSAFE_RAW ) ? $cron_aktiv = 1 : $cron_aktiv = 0;
		filter_input( INPUT_POST, 'bootstrap_css_aktiv', FILTER_UNSAFE_RAW ) ? $bootstrap_css_aktiv = 1 : $bootstrap_css_aktiv = 0;
		filter_input( INPUT_POST, 'bootstrap_js_aktiv', FILTER_UNSAFE_RAW ) ? $bootstrap_js_aktiv = 1 : $bootstrap_js_aktiv = 0;
		filter_input( INPUT_POST, 'cron_update_post', FILTER_UNSAFE_RAW ) ? $cron_update_post = 1 : $cron_update_post = 0;

		if ( ! $plugin_min_role ) {
			$plugin_min_role = 'manage_options';
		}
		if ( ! $selected_cron_sync_interval ) {
			$selected_cron_sync_interval = 'daily';
		}

		if ( ! $cron_aktiv ) {
			wp_clear_scheduled_hook( 'ebay_import_sync' );
		}
		$settings = get_option( $this->basename . '_settings' );
		if ( $settings['selected_cron_sync_interval'] != $selected_cron_sync_interval ) {
			wp_clear_scheduled_hook( 'ebay_import_sync' );
			apply_filters( $this->basename . '/ebay_run_schedule_task', false );
		}
		$settings['plugin_min_role']             = $plugin_min_role;
		$settings['selected_cron_sync_interval'] = $selected_cron_sync_interval;
		$settings['cron_aktiv']                  = $cron_aktiv;
		$settings['bootstrap_css_aktiv']         = $bootstrap_css_aktiv;
		$settings['bootstrap_js_aktiv']          = $bootstrap_js_aktiv;
		$settings['cron_update_post']            = $cron_update_post;
		update_option( $this->basename . '_settings', $settings );

		$this->responseJson->msg    = 'Einstellungen erfolgreich gespeichert.';
		$this->responseJson->title  = 'Gespeichert!';
		$this->responseJson->status = true;

		return $this->responseJson;
	}

	private function update_expert_settings(): object {
		$regex_image               = filter_input( INPUT_POST, 'regex_image', FILTER_UNSAFE_RAW );
		$regex_check_tags          = filter_input( INPUT_POST, 'regex_check_tags', FILTER_UNSAFE_RAW );
		$regex_description         = filter_input( INPUT_POST, 'regex_description', FILTER_UNSAFE_RAW );
		$regex_street              = filter_input( INPUT_POST, 'regex_street', FILTER_UNSAFE_RAW );
		$regex_split_street        = filter_input( INPUT_POST, 'regex_split_street', FILTER_UNSAFE_RAW );
		$regex_location            = filter_input( INPUT_POST, 'regex_location', FILTER_UNSAFE_RAW );
		$regex_split_location      = filter_input( INPUT_POST, 'regex_split_location', FILTER_UNSAFE_RAW );
		$regex_detail_list         = filter_input( INPUT_POST, 'regex_detail_list', FILTER_UNSAFE_RAW );
		$regex_detail_list_extract = filter_input( INPUT_POST, 'regex_detail_list_extract', FILTER_UNSAFE_RAW );
		$regex_api_url             = filter_input( INPUT_POST, 'regex_api_url', FILTER_UNSAFE_RAW );

		$regex_std_ebay_id              = filter_input( INPUT_POST, 'regex_std_ebay_id', FILTER_UNSAFE_RAW );
		$regex_std_ebay_url             = filter_input( INPUT_POST, 'regex_std_ebay_url', FILTER_UNSAFE_RAW );
		$regex_std_ebay_titel_img       = filter_input( INPUT_POST, 'regex_std_ebay_titel_img', FILTER_UNSAFE_RAW );
		$regex_std_ebay_titel_img_check = filter_input( INPUT_POST, 'regex_std_ebay_titel_img_check', FILTER_UNSAFE_RAW );
		$regex_std_ebay_titel           = filter_input( INPUT_POST, 'regex_std_ebay_titel', FILTER_UNSAFE_RAW );
		$regex_std_ebay_beschreibung    = filter_input( INPUT_POST, 'regex_std_ebay_beschreibung', FILTER_UNSAFE_RAW );
		$regex_std_ebay_location        = filter_input( INPUT_POST, 'regex_std_ebay_location', FILTER_UNSAFE_RAW );
		$regex_std_ebay_date            = filter_input( INPUT_POST, 'regex_std_ebay_date', FILTER_UNSAFE_RAW );
		$regex_std_ebay_price           = filter_input( INPUT_POST, 'regex_std_ebay_price', FILTER_UNSAFE_RAW );
		$regex_std_url                  = filter_input( INPUT_POST, 'regex_std_url', FILTER_UNSAFE_RAW );


		$default                               = $this->get_wp_ebay_importer_settings_defaults( 'settings' );
		$settings                              = get_option( $this->basename . '_settings' );
		$settings['regex_image']               = $regex_image ?? $default['regex_image'];
		$settings['regex_check_tags']          = $regex_check_tags ?? $default['regex_check_tags'];
		$settings['regex_description']         = $regex_description ?? $default['regex_description'];
		$settings['regex_street']              = $regex_street ?? $default['regex_street'];
		$settings['regex_split_street']        = $regex_split_street ?? $default['regex_split_street'];
		$settings['regex_location']            = $regex_location ?? $default['regex_location'];
		$settings['regex_split_location']      = $regex_split_location ?? $default['regex_split_location'];
		$settings['regex_detail_list']         = $regex_detail_list ?? $default['regex_detail_list'];
		$settings['regex_detail_list_extract'] = $regex_detail_list_extract ?? $default['regex_detail_list_extract'];
		$settings['regex_api_url']             = $regex_api_url ?? $default['regex_api_url'];

		$settings['regex_std_ebay_id']              = $regex_std_ebay_id ?? $default['regex_std_ebay_id'];
		$settings['regex_std_ebay_url']             = $regex_std_ebay_url ?? $default['regex_std_ebay_url'];
		$settings['regex_std_ebay_titel_img']       = $regex_std_ebay_titel_img ?? $default['regex_std_ebay_titel_img'];
		$settings['regex_std_ebay_titel_img_check'] = $regex_std_ebay_titel_img_check ?? $default['regex_std_ebay_titel_img_check'];
		$settings['regex_std_ebay_titel']           = $regex_std_ebay_titel ?? $default['regex_std_ebay_titel'];
		$settings['regex_std_ebay_beschreibung']    = $regex_std_ebay_beschreibung ?? $default['regex_std_ebay_beschreibung'];
		$settings['regex_std_ebay_location']        = $regex_std_ebay_location ?? $default['regex_std_ebay_location'];
		$settings['regex_std_ebay_date']            = $regex_std_ebay_date ?? $default['regex_std_ebay_date'];
		$settings['regex_std_ebay_price']           = $regex_std_ebay_price ?? $default['regex_std_ebay_price'];
		$settings['regex_std_url']                  = $regex_std_url ?? $default['regex_std_url'];

		update_option( $this->basename . '_settings', $settings );
		$this->responseJson->status = true;
		$this->responseJson->msg    = 'Einstellungen erfolgreich gespeichert.';
		$this->responseJson->title  = 'Gespeichert!';

		return $this->responseJson;
	}

	private function load_default_expression(): object {
		$default                               = $this->get_wp_ebay_importer_settings_defaults( 'settings' );
		$settings                              = get_option( $this->basename . '_settings' );
		$settings['regex_image']               = $default['regex_image'];
		$settings['regex_check_tags']          = $default['regex_check_tags'];
		$settings['regex_description']         = $default['regex_description'];
		$settings['regex_street']              = $default['regex_street'];
		$settings['regex_split_street']        = $default['regex_split_street'];
		$settings['regex_location']            = $default['regex_location'];
		$settings['regex_split_location']      = $default['regex_split_location'];
		$settings['regex_detail_list']         = $default['regex_detail_list'];
		$settings['regex_detail_list_extract'] = $default['regex_detail_list_extract'];
		$settings['regex_api_url']             = $default['regex_api_url'];

		$settings['regex_std_ebay_id']              = $default['regex_std_ebay_id'];
		$settings['regex_std_ebay_url']             = $default['regex_std_ebay_url'];
		$settings['regex_std_ebay_titel_img']       = $default['regex_std_ebay_titel_img'];
		$settings['regex_std_ebay_titel_img_check'] = $default['regex_std_ebay_titel_img_check'];
		$settings['regex_std_ebay_titel']           = $default['regex_std_ebay_titel'];
		$settings['regex_std_ebay_beschreibung']    = $default['regex_std_ebay_beschreibung'];
		$settings['regex_std_ebay_location']        = $default['regex_std_ebay_location'];
		$settings['regex_std_ebay_date']            = $default['regex_std_ebay_date'];
		$settings['regex_std_ebay_price']           = $default['regex_std_ebay_price'];
		$settings['regex_std_url']                  = $default['regex_std_url'];

		update_option( $this->basename . '_settings', $settings );
		$this->responseJson->status = true;
		$this->responseJson->msg    = 'Einstellungen erfolgreich geladen.';
		$this->responseJson->title  = 'Gespeichert!';

		return $this->responseJson;
	}

	private function search_osm_map(): object {
		$street = filter_input( INPUT_POST, 'street', FILTER_UNSAFE_RAW );
		$ort    = filter_input( INPUT_POST, 'ort', FILTER_UNSAFE_RAW );
		$query  = sprintf( '%s,%s', $street, $ort );
		$query  = rawurlencode( $query );
		$search = apply_filters( $this->basename . '/get_curl_json_data', $query );
		if ( ! $search->status ) {
			$this->responseJson->title = 'Open-Street-Maps';
			$this->responseJson->msg   = 'keine Kartendaten gefunden.';

			return $this->responseJson;
		}
		$this->responseJson->status = true;
		$this->responseJson->osm    = $search->geo_json;

		return $this->responseJson;
	}

	private function now_synchronize(): object {
		$id                         = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$this->responseJson->system = filter_input( INPUT_POST, 'system', FILTER_VALIDATE_INT );

		if ( ! $id ) {
			$this->responseJson->msg = __( 'Ajax transmission error.', 'wp-ebay-classifieds' ) . ' (Ajx - ' . __LINE__ . ')';

			return $this->responseJson;
		}

		if ( strtolower( PHP_OS ) == 'linux' ) {
			$url = site_url() . '?' . SYNC_EBAY_IMPORTER_QUERY . '=' . (int) $id;
			passthru( "curl -s " . $url . " > /dev/null 2>&1 &" );
			$this->responseJson->swalType = 'background';
			$this->responseJson->status   = true;

			return $this->responseJson;
		} else {
			apply_filters( $this->basename . '/make_ebay_import_import', (int) $id );
		}
		$this->responseJson->title    = 'Synchronisiert';
		$this->responseJson->msg      = 'Alle eBay Importe Synchronisiert.';
		$this->responseJson->swalType = 'wait';
		$this->responseJson->status   = true;

		return $this->responseJson;
	}

	private function html_compress_template( string $string ): string {
		if ( ! $string ) {
			return $string;
		}

		return preg_replace( [ '/<!--(.*)-->/Uis', "/[[:blank:]]+/" ], [ '', ' ' ], str_replace( [
			"\n",
			"\r",
			"\t"
		], '', $string ) );
	}

	private function fnPregWhitespace( $string ): string {
		if ( ! $string ) {
			return '';
		}

		return trim( preg_replace( '/\s+/', ' ', $string ) );
	}
}