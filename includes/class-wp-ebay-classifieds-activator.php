<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Ebay_Classifieds_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {
		self::register_wp_ebay_importer_post_type();
		self::register_wp_ebay_importer_taxonomy();
		flush_rewrite_rules();
	}
	public static function register_wp_ebay_importer_post_type(): void {
		register_post_type(
			'anzeigen',
			array(
				'labels' => array(
					'name' => __('Anzeigen', 'wp-ebay-classifieds'),
					'singular_name' => __('Anzeigen', 'wp-ebay-classifieds'),
					'menu_name' => __('eBay Anzeigen', 'wp-ebay-classifieds'),
					'parent_item_colon' => __('Parent Item:', 'wp-ebay-classifieds'),
					'edit_item' => __('Bearbeiten', 'wp-ebay-classifieds'),
					'update_item' => __('Aktualisieren', 'wp-ebay-classifieds'),
					'all_items' => __('Alle Anzeigen', 'wp-ebay-classifieds'),
					'items_list_navigation' => __('Anzeigen Posts navigation', 'wp-ebay-classifieds'),
					'add_new_item' => __('neuen Beitrag hinzufügen', 'wp-ebay-classifieds'),
					'archives' => __('Anzeigen Archiv', 'wp-ebay-classifieds'),
				),
				'public' => true,
				'publicly_queryable' => true,
				'show_in_rest' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'has_archive' => true,
				'query_var' => true,
				'show_in_nav_menus' => true,
				'exclude_from_search' => false,
				'hierarchical' => false,
				'capability_type' => 'post',
				'menu_icon' => self::get_svg_icon('cart4'),
				'menu_position' => 209,
				'can_export' => true,
				'show_in_admin_bar' => true,
				'supports' => array(
					'title', 'excerpt', 'page-attributes', 'editor', 'thumbnail', 'custom-fields'
				),
				'taxonomies' => array('anzeigen-kategorie'),
			)
		);
	}

	public static function register_wp_ebay_importer_taxonomy(): void {
		$labels = array(
			'name' => __('Anzeigen Kategorie', 'wp-ebay-classifieds'),
			'singular_name' => __('Anzeigen Kategorie', 'wp-ebay-classifieds'),
			'search_items' => __('Anzeigen suchen', 'wp-ebay-classifieds'),
			'all_items' => __('Alle Anzeigen Kategorien', 'wp-ebay-classifieds'),
			'parent_item' => __('Eltern-Kategorie', 'wp-ebay-classifieds'),
			'parent_item_colon' => __('Eltern-Kategorie:', 'wp-ebay-classifieds'),
			'edit_item' => __('Kategorie bearbeiten', 'wp-ebay-classifieds'),
			'update_item' => __('Kategorie aktualisieren', 'wp-ebay-classifieds'),
			'add_new_item' => __('neue Kategorie hinzufügen', 'wp-ebay-classifieds'),
			'new_item_name' => __('Neue Kategorie', 'wp-ebay-classifieds'),
			'menu_name' => __('Kategorien', 'wp-ebay-classifieds'),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'public' => false,
			'show_ui' => true,
			'sort' => true,
			'show_in_rest' => true,
			'query_var' => true,
			'args' => array('orderby' => 'term_order'),
			'show_admin_column' => true,
			'publicly_queryable' => true,
			'show_in_nav_menus' => true,
		);
		register_taxonomy('anzeigen-kategorie', array('attachment', 'anzeigen'), $args);

		$terms = [
			'0' => [
				'name' => __('Anzeigen', 'wp-ebay-classifieds'),
				'slug' => __('anzeigen', 'wp-ebay-classifieds')
			]
		];

		foreach ($terms as $term) {
			if (!term_exists($term['name'], 'anzeigen-kategorie')) {
				wp_insert_term(
					$term['name'],
					'anzeigen-kategorie',
					array(
						'description' => __('Anzeigen Kategorie', 'wp-ebay-classifieds'),
						'slug' => $term['slug']
					)
				);
			}
		}
	}

	private static function get_svg_icon($type):string
	{
		switch ($type){
			case'cart4':
				 $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-cart4" viewBox="0 0 16 16">
  						  <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l.5 2H5V5H3.14zM6 5v2h2V5H6zm3 0v2h2V5H9zm3 0v2h1.36l.5-2H12zm1.11 3H12v2h.61l.5-2zM11 8H9v2h2V8zM8 8H6v2h2V8zM5 8H3.89l.5 2H5V8zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/>
							</svg>';
				break;
			case'cart':
				$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-cart" viewBox="0 0 16 16">
    			 <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
				 </svg>';
				break;
			default:
				$icon = '';
		}

		return 'data:image/svg+xml;base64,' . base64_encode($icon);
	}

}
