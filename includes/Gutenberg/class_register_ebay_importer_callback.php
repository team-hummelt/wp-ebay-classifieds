<?php

namespace Wp_Ebay_Classifieds\Importer;


use Wp_Ebay_Classifieds;

class Register_Ebay_Importer_Callback {
	private static $instance;
	use WP_Ebay_Importer_Settings;

	/**
	 * The ID of this Plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $basename The ID of this Plugin.
	 */
	protected string $basename;

	/**
	 * The version of this Plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this theme.
	 */
	protected string $version;

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
	public static function instance( string $basename, string $version, Wp_Ebay_Classifieds $main ): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $basename, $version, $main );
		}

		return self::$instance;
	}

	public function __construct( string $basename, string $version, Wp_Ebay_Classifieds $main ) {
		$this->main     = $main;
		$this->version  = $version;
		$this->basename = $basename;
	}

	public function callback_ebay_importer_block_type( $attributes ) {

		global $posts;
		$term   = [];
		$importArr = [];
		$metaArr   = [];
		isset( $attributes['className'] ) && $attributes['className'] ? $className = $attributes['className'] : $className = '';
		isset( $attributes['selectedImport'] ) && $attributes['selectedImport'] ? $selectedImport = $attributes['selectedImport'] : $selectedImport = '';
		isset( $attributes['selectedContent'] ) && $attributes['selectedContent'] ? $selectedContent = $attributes['selectedContent'] : $selectedContent = 'content';
		isset( $attributes['selectedOrder'] ) && $attributes['selectedOrder'] ? $selectedOrder = $attributes['selectedOrder'] : $selectedOrder = 2;
		isset( $attributes['selectedCount'] ) && $attributes['selectedCount'] ? $selectedCount = $attributes['selectedCount'] : $selectedCount = - 1;

		$attr = [
			'className'       => $className,
			'selectedContent' => $selectedContent,
			'selectedOrder'   => $selectedOrder,
			'selectedCount'   => $selectedCount,
		];

		if ( $selectedImport ) {
			$args   = sprintf( 'WHERE i.id=%d', (int) $selectedImport );
			$import = apply_filters( $this->basename . '/get_ebay_import', $args, false );
			if ( $import->status ) {
				switch ( $selectedOrder ) {
					case 1:
						$orderBy = 'date';
						$order   = 'DESC';
						break;
					case 2:
						$orderBy = 'date';
						$order   = 'ASC';
						break;
					case 3:
						$orderBy = 'menu_order';
						$order   = 'DESC';
						break;
					default:
						$order   = 'date';
						$orderBy = 'DESC';
				}
				$import   = $import->record;
				$importArr = (array) $import;
				$term     = get_term( $import->term_id );
				$postArgs = [
					'post_type'   => 'anzeigen',
					'numberposts' => $selectedCount,
					'orderby'     => $orderBy,
					'order'       => $order,
					'tax_query'   => [
						[
							'taxonomy' => $term->taxonomy,
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						]
					]
				];
				$posts    = get_posts( $postArgs );
				if($posts){
					foreach ($posts as $tmp) {
						$meta = apply_filters($this->basename.'/get_ebay_import_meta', $tmp->ID);
						$metaArr[] = $meta;
					}
				}
			}
		}
		return apply_filters('gutenberg_block_ebay_importer_callback', $posts, $attr, $metaArr, $term, $importArr);
	}

	/**
	 * @return false|string
	 */
	public function gutenberg_block_ebay_importer_filter() {
		ob_start();
		return ob_get_clean();
	}
}
