<?php

namespace Wp_Ebay_Classifieds\Importer;

use Exception;
use stdClass;
use Wp_Ebay_Classifieds;
use WP_Post;
use WP_Query;

class WP_Ebay_Importer_DB_Handle {
	use WP_Ebay_Importer_Settings;

	private static $instance;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $basename The ID of this plugin.
	 */
	private string $basename;

	/**
	 * The current version of the DB-Version.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $db_version The current version of the database Version.
	 */
	protected string $db_version;

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
	public static function instance( string $basename, string $db_version, Wp_Ebay_Classifieds $main ): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $basename, $db_version, $main );
		}

		return self::$instance;
	}

	public function __construct( string $basename, string $db_version, Wp_Ebay_Classifieds $main ) {
		$this->main       = $main;
		$this->db_version = $db_version;
		$this->basename   = $basename;
	}

	/**
	 * @param string $args
	 * @param bool $fetchMethod
	 *
	 * @return object
	 */
	public function getEbayImportsByArgs( string $args = '', bool $fetchMethod = true ): object {
		global $wpdb;
		$return         = new stdClass();
		$return->status = false;
		$return->count  = 0;
		$fetchMethod ? $fetch = 'get_results' : $fetch = 'get_row';
		$table  = $wpdb->prefix . $this->import_table;
		$result = $wpdb->$fetch( "SELECT i.*
                                  FROM $table i 
                                  $args" );
		if ( ! $result ) {
			return $return;
		}
		$fetchMethod ? $count = count( $result ) : $count = 1;

		$return->count  = $count;
		$return->status = true;
		$return->record = $result;

		return $return;
	}

	public function setWpEbayImport( $record ): object {
		$return = new stdClass();
		global $wpdb;
		$table = $wpdb->prefix . $this->import_table;
		$wpdb->insert(
			$table,
			array(
				'bezeichnung'        => $record->bezeichnung,
				'bilder_importieren' => $record->bilder_importieren,
				'site_number'        => $record->site_number,
				'import_count'       => $record->import_count,
				'import_url'         => $record->import_url,
				'osm_aktiv'          => $record->osm_aktiv,
				'term_id'            => $record->term_id,
				'pro_user'           => $record->pro_user
			),
			array( '%s', '%d', '%d', '%d', '%s', '%d', '%d', '%d' )
		);
		if ( ! $wpdb->insert_id ) {
			$return->status = false;
			$return->msg    = 'Einstellungen konnten nicht gespeichert werden';
			$return->title  = 'Daten nicht gespeichert';

			return $return;
		}
		$return->status = true;
		$return->id     = $wpdb->insert_id;
		$return->msg    = 'Einstellungen wurden erfolgreich gespeichert';
		$return->title  = 'Einstellungen gespeichert';

		return $return;
	}

	public function updateWpEbayImport( $record ): object {
		$return = new stdClass();
		global $wpdb;
		$wpdb->show_errors();
		$table = $wpdb->prefix . $this->import_table;
		$wpdb->update(
			$table,
			array(
				'bezeichnung'        => $record->bezeichnung,
				'bilder_importieren' => $record->bilder_importieren,
				'site_number'        => $record->site_number,
				'import_count'       => $record->import_count,
				'import_url'         => $record->import_url,
				'osm_aktiv'          => $record->osm_aktiv,
				'term_id'            => $record->term_id,
				'pro_user'           => $record->pro_user
			),
			array( 'id' => $record->id ),
			array( '%s', '%d', '%d', '%d', '%s', '%d', '%d', '%d' ),
			array( '%d' )
		);

		if ( $wpdb->last_error !== '' ) {
			$return->status = false;
			$return->msg    = 'Einstellungen konnten nicht gespeichert werden';
			$return->title  = 'Daten nicht gespeichert';

			return $return;
		}

		$return->status = true;
		$return->msg    = 'Einstellungen wurden erfolgreich gespeichert';
		$return->title  = 'Einstellungen gespeichert';

		return $return;
	}

	public function deleteWpEbayImport( $id ): void {
		global $wpdb;
		$table = $wpdb->prefix . $this->import_table;
		$wpdb->delete(
			$table,
			array(
				'id' => $id
			),
			array( '%d' )
		);
	}

	public function ebay_import_get_images( $postId, $single = false, $imgId = null ): array {

		if ( $single ) {
			$args = array(
				'posts_per_page' => 1,
				'ID'             => $imgId,
				'post_type'      => 'attachment',
				'post_parent'    => $postId,
				'post_status'    => 'any'
			);
		} else {
			$args = array(
				'posts_per_page' => - 1,
				'orderby'        => 'post_date',
				'order'          => 'DESC',
				'post_type'      => 'attachment',
				'post_parent'    => $postId,
				'post_status'    => 'any'
			);
		}

		$images = get_posts( $args );
		if ( count( $images ) ) {
			return $images;
		}

		return [];

	}

	/**
	 * @param $ebayId
	 * @param $term_id
	 *
	 * @return int|void|WP_Post
	 */
	public function get_post_by_ebay_id( $ebayId, $term_id ) {
		$args = array(
			'post_type'   => 'anzeigen',
			'numberposts' => 1,
			'meta_query'  => array(
				array(
					'key'     => '_ebay_id',
					'value'   => $ebayId,
					'compare' => '==',
				)
			),
			'tax_query'   => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'anzeigen-kategorie',
					'field'    => 'term_id',
					'terms'    => $term_id,
				)
			)
		);

		$query = new WP_Query( $args );
		if ( $query->post_count ) {
			return $query->posts[0];
		}
	}

	/**
	 * @param $importId
	 *
	 * @return int[]|void|WP_Post[]
	 */
	public function get_posts_by_import_id( $importId ) {
		$args = array(
			'post_type'   => 'anzeigen',
			'numberposts' => - 1,
			'meta_query'  => array(
				array(
					'key'     => '_import_id',
					'value'   => $importId,
					'compare' => '==',
				)
			)
		);

		$query = new WP_Query( $args );
		if ( $query->post_count ) {
			return $query->posts;
		}
	}

	public function get_ebay_import_meta($postId, $meta = ''):array
	{
		$return = [
			'_import_id' => get_post_meta($postId, '_import_id', true),
			'_ebay_id' => get_post_meta($postId, '_ebay_id', true),
			'_ebay_url' => get_post_meta($postId, '_ebay_url', true),
			'_ebay_location' => get_post_meta($postId, '_ebay_location', true),
			'_ebay_price' => get_post_meta($postId, '_ebay_price', true),
			'_ebay_strassen_name' => get_post_meta($postId, '_ebay_strassen_name', true),
			'_ebay_hnr' => get_post_meta($postId, '_ebay_hnr', true),
			'_ebay_plz' => get_post_meta($postId, '_ebay_plz', true),
			'_ebay_ort' => get_post_meta($postId, '_ebay_ort', true),
			'_ebay_is_osm' => get_post_meta($postId, '_ebay_is_osm', true),
			'_ebay_osm_data' => get_post_meta($postId, '_ebay_osm_data', true)
		];

		if($meta){
			foreach ($return as $key => $val){
				if($key == $meta){
					return [$key => $val];
				}
			}
		}
		return $return;
	}

	/**
	 * @param $term_id
	 *
	 * @return int[]|void
	 */
	public function get_posts_by_taxonomy( $term_id ) {
		$args = array(
			'post_type'   => 'anzeigen',
			'numberposts' => - 1,
			'tax_query'   => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'anzeigen-kategorie',
					'field'    => 'term_id',
					'terms'    => $term_id,
				)
			)
		);

		$query = new WP_Query( $args );
		if ( $query->post_count ) {
			return $query->posts;
		}
	}


	/**
	 * @throws Exception
	 */
	public function ebay_import_delete_post_before( $postid ): void {

		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'post_parent'    => $postid
		) );

		foreach ( $attachments as $attachment ) {
			if ( ! wp_delete_attachment( $attachment->ID, true ) ) {
				throw new Exception( 'Anhang konnte nicht gelöscht werden.(' . __LINE__ . ')' );
			}
		}
	}

	public function wp_ebay_importer_check_jal_install(): void {
		if ( get_option( 'jal_wp-ebay-classifieds_db_version' ) != $this->db_version ) {
			update_option( 'jal_wp-ebay-classifieds_db_version', $this->db_version );
			$this->wp_ebay_importer_jal_install();
		}
	}

	protected function wp_ebay_importer_jal_install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$table_name      = $wpdb->prefix . $this->import_table;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
    	`id` int(11) NOT NULL AUTO_INCREMENT,
    	`bezeichnung` varchar(64) NOT NULL,
    	`bilder_importieren` tinyint(1) NOT NULL DEFAULT 1,
    	`site_number` int(2) NOT NULL DEFAULT 1,
    	`import_count` int(2) NOT NULL DEFAULT 25,
		`import_url` varchar(128) NOT NULL,
		`osm_aktiv` tinyint(1) NOT NULL DEFAULT 0,
		`pro_user` tinyint(1) NOT NULL DEFAULT 1,
		`term_id` int(11) NOT NULL,
        `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id)
     ) $charset_collate;";
		dbDelta( $sql );
	}


}