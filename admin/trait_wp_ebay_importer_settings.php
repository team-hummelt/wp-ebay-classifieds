<?php

namespace Wp_Ebay_Classifieds\Importer;


trait WP_Ebay_Importer_Settings {
	protected string $import_table = 'ebay_imports';
	protected array $wp_ebay_importer_settings_defaults;
	protected string $plugin_min_role = 'manage_options';
	protected bool $bootstrap_css_aktiv = false;
	protected bool $bootstrap_js_aktiv = false;
	protected int $cron_aktiv = 1;
	protected int $cron_update_post = 0;
	protected int $delete_duplicate = 1;
	protected string $selected_cron_sync_interval = 'daily';

	protected string $regex_image = '%background-image:.+?url.?\'(.*?)?\'%';
	protected string $regex_check_tags = '%<li class="checktag">(.*?)</li>%';
	protected string $regex_description = '%<p id="viewad-description-text".+?>(.*?)</p>%';
	protected string $regex_street = '%street-address.+?>(.+?),.+?<%';
	protected string $regex_split_street = '/(.+?)(\d{1,10}?.*)/';
	protected string $regex_location = '%viewad-locality.+?>(.*?)<%';
	protected string $regex_split_location = '/(\d{1,6})(.+)/';
	protected string $regex_detail_list = '%<li class="addetailslist--detail">.*?</li>%';
	protected string $regex_detail_list_extract = '@(.+)<.*?>.+?(.+)?</span>@';
	protected string $regex_api_url = 'ads?pageSize=%d&pageNum=%d';
	//Standard USER
	protected string $regex_std_ebay_id = '@data-adid="(.+?)"@';
	protected string $regex_std_ebay_url = '@data-href=".*?/(s-anzeige.+?)"@';
	protected string $regex_std_ebay_titel_img = '@<img src="(.*?)"@';
	protected string $regex_std_ebay_titel_img_check = '@.+?prod-ads.+?rule.+\.?[jpgJPGnN]@';
	protected string $regex_std_ebay_titel = '@text-module-begin">.*?>(.*?)<@';
	protected string $regex_std_ebay_beschreibung = '@<p class=".*?description">(.+?)<@';
	protected string $regex_std_ebay_location = '@icon-pin"></i>(.*?)<@';
	protected string $regex_std_ebay_date = '@icon-calendar-open"></i>(.*?)<@';
	protected string $regex_std_ebay_price = '@shipping--price">(.*?)<@';
	protected string $regex_std_url = '/s-bestandsliste.html?userId=%s&pageNum=%d&sortingField=SORTING_DATE';

	protected function get_wp_ebay_importer_settings_defaults( $args = '', $id = null ): array {

		$this->wp_ebay_importer_settings_defaults = [
			'settings' => [
				'plugin_min_role'     => $this->plugin_min_role,
				'bootstrap_css_aktiv' => $this->bootstrap_css_aktiv,
				'bootstrap_js_aktiv'  => $this->bootstrap_js_aktiv,
				'cron_aktiv' => $this->cron_aktiv,
				'cron_update_post' => $this->cron_update_post,
				'delete_duplicate' => $this->delete_duplicate,
				'selected_cron_sync_interval' => $this->selected_cron_sync_interval,
				'regex_image' => $this->regex_image,
				'regex_check_tags' => $this->regex_check_tags,
				'regex_description' => $this->regex_description,
				'regex_street' => $this->regex_street,
				'regex_split_street' => $this->regex_split_street,
				'regex_location' => $this->regex_location,
				'regex_split_location' => $this->regex_split_location,
				'regex_detail_list' => $this->regex_detail_list,
				'regex_detail_list_extract' => $this->regex_detail_list_extract,
				'regex_api_url' => $this->regex_api_url,

				'regex_std_ebay_id' => $this->regex_std_ebay_id,
				'regex_std_ebay_url' => $this->regex_std_ebay_url,
				'regex_std_ebay_titel_img' => $this->regex_std_ebay_titel_img,
				'regex_std_ebay_titel_img_check' => $this->regex_std_ebay_titel_img_check,
				'regex_std_ebay_titel' => $this->regex_std_ebay_titel,
				'regex_std_ebay_beschreibung' => $this->regex_std_ebay_beschreibung,
				'regex_std_ebay_location' => $this->regex_std_ebay_location,
				'regex_std_ebay_date' => $this->regex_std_ebay_date,
				'regex_std_ebay_price' => $this->regex_std_ebay_price,
				'regex_std_url' => $this->regex_std_url
			],
			'select_user_role' => [
				"0" => [
					'capabilities' => 'subscriber',
					'value' => 'read',
					'name' =>'Abonnent'
				],
				"1" => [
					'capabilities' => 'contributor',
					'value' => 'edit_posts',
					'name' => 'Mitarbeiter'
				],
				"2" => [
					'capabilities' => 'subscriber',
					'value' => 'publish_posts',
					'name' => 'Autor'
				],
				"3" => [
					'capabilities' => 'editor',
					'value' => 'publish_pages',
					'name' => 'Redakteur'
				],
				"4" => [
					'capabilities' => 'administrator',
					'value' => 'manage_options',
					'name' => __('Administrator', 'wp-ebay-classifieds')
				],
			],

			'select_api_sync_interval' => [
				"0" => [
					"id" => 'hourly',
					"bezeichnung" => 'Stündlich',
				],
				"1" => [
					'id' => 'twicedaily',
					"bezeichnung" => 'Zweimal Täglich',
				],
				"3" => [
					'id' => 'daily',
					"bezeichnung" => 'Einmal täglich',
				],
				"4" => [
					'id' => 'weekly',
					"bezeichnung" => 'Einmal wöchentlich',
				],
			],
		];
		if ($args) {
			if($id) {
				foreach ($this->wp_ebay_importer_settings_defaults[$args] as $tmp){
					if(isset($tmp['id']) && $tmp['id'] == $id) {
						return $tmp;
					}
				}
			}
			return $this->wp_ebay_importer_settings_defaults[$args];
		}
		return $this->wp_ebay_importer_settings_defaults;
	}

	protected function js_language(): array {
		return [
			'checkbox_delete_label' => __( 'Delete all imported posts?', 'wp-ebay-classifieds' ),
			'Cancel'                => __( 'Cancel', 'wp-ebay-classifieds' ),
			'delete_title'          => __( 'Really delete membership?', 'wp-ebay-classifieds' ),
			'delete_subtitle'       => __( 'The deletion cannot be undone.', 'wp-ebay-classifieds' ),
			'delete_btn_txt'        => __( 'Delete membership', 'wp-ebay-classifieds' ),
			'delete_file_title'     => __( 'Really delete file?', 'wp-ebay-classifieds' ),
			'delete_file_btn'       => __( 'Delete file', 'wp-ebay-classifieds' ),
			'delete'                => __( 'Delete', 'wp-ebay-classifieds' ),
			'clock'                 => __( 'Clock', 'wp-ebay-classifieds' ),
			'delete_group_title'    => __( 'Delete group really?', 'wp-ebay-classifieds' ),
			'delete_group_btn'      => __( 'Delete group', 'wp-ebay-classifieds' ),
			'delete_group_subtitle' => __( 'All documents in this group will be moved to the default group.', 'wp-ebay-classifieds' )
		];

	}
}