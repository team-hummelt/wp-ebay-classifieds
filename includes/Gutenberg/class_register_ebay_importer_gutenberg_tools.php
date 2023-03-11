<?php

class Register_Ebay_Importer_Gutenberg_Tools
{

    protected Wp_Ebay_Classifieds $main;

    /**
     * The ID of this theme.
     *
     * @since    2.0.0
     * @access   private
     * @var      string $basename The ID of this theme.
     */
    protected string $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    protected string $version;

    public function __construct(string $version,string $basename,Wp_Ebay_Classifieds $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
    }

    public function rss_importer_gutenberg_register_sidebar(): void
    {
        $plugin_asset = require WP_EBAY_CLASSIFIED_PLUGIN_DIR . '/includes/Gutenberg/Sidebar/build/index.asset.php';
         wp_register_script(
             'ebay-importer-sidebar',
	         WP_EBAY_CLASSIFIED_PLUGIN_URL . '/includes/Gutenberg/Sidebar/build/index.js',
             $plugin_asset['dependencies'], $plugin_asset['version'], true
         );

		 wp_register_style(
			 'ebay-importer-sidebar-style',
			 WP_EBAY_CLASSIFIED_PLUGIN_URL . '/includes/Gutenberg/Sidebar/build/index.css', array(), $plugin_asset['version']
		 );
    }
    /**
     * Register TAM MEMBERS REGISTER GUTENBERG BLOCK TYPE
     *
     * @since    1.0.0
     */
    public function register_rss_importer_block_type(): void
    {
        global $registerEbayImporterCallback;
        register_block_type('ebay/importer-block', array(
            'render_callback' => [$registerEbayImporterCallback, 'callback_ebay_importer_block_type'],
            'editor_script' => 'ebay-importer-gutenberg-block',
        ));
        add_filter('gutenberg_block_ebay_importer_callback', array($registerEbayImporterCallback, 'gutenberg_block_ebay_importer_filter'), 10, 5);
    }

    public function ebay_importer_block_type_scripts(): void
    {
        $plugin_asset = require WP_EBAY_CLASSIFIED_PLUGIN_DIR . '/includes/Gutenberg/ImportBlock/build/index.asset.php';
        wp_enqueue_script(
            'ebay-importer-gutenberg-block',
	        WP_EBAY_CLASSIFIED_PLUGIN_URL . '/includes/Gutenberg/ImportBlock/build/index.js',
            $plugin_asset['dependencies'], $plugin_asset['version'], true
        );

	    /*if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('ebay-importer-gutenberg-block', 'wp-ebay-classifieds', WP_EBAY_CLASSIFIED_PLUGIN_DIR . '/languages');
		}*/

        wp_localize_script('ebay-importer-gutenberg-block',
            'WEIEndpoint',
            array(
                'url' => esc_url_raw(rest_url('wp-ebay-importer/v1/')),
                'nonce' => wp_create_nonce('wp_rest')
            )
        );

        wp_enqueue_style(
            'ebay-importer-gutenberg-block',
	        WP_EBAY_CLASSIFIED_PLUGIN_URL . '/includes/Gutenberg/ImportBlock/build/index.css', array(), $plugin_asset['version']);

	    wp_enqueue_script('ebay-importer-sidebar');
	    wp_enqueue_style('ebay-importer-sidebar-style');
    }


	public function register_ebay_imports_meta_fields(): void {
		register_post_meta(
			'anzeigen',
			'_import_id',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_id',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_url',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_location',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_price',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_strassen_name',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_hnr',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_plz',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_ort',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_is_osm',
			array(
				'type' => 'boolean',
				'single' => true,
				'show_in_rest' => true,
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_search_osm',
			array(
				'type' => 'boolean',
				'single' => true,
				'show_in_rest' => true,
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'anzeigen',
			'_ebay_osm_data',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
	}


	/**
	 * Check if a given request has access.
	 *
	 * @return bool
	 */
	public function import_post_permissions_check(): bool
	{
		return current_user_can('edit_posts');
	}

}
