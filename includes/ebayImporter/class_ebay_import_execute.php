<?php

namespace Wp_Ebay_Classifieds\Importer;


use Exception;
use stdClass;
use Wp_Ebay_Classifieds;
use WP_Query;

class Ebay_Import_Execute {
	private static $instance;
	protected Wp_Ebay_Classifieds $main;
	private $settings;

	private bool $force_delete = true;
	private static bool $log_aktiv = true;

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
		$this->basename = $basename;
		$this->version  = $version;

	}


	/**
	 * @throws Exception
	 */
	public function ebay_import_synchronisation(): void {
		$this->fn_make_ebay_import_import();
	}

	/**
	 * @throws Exception
	 */
	public function fn_make_ebay_import_import( $id = null ): bool {

		if ( $id ) {
			$args = sprintf( 'WHERE i.id=%d', (int) $id );
		} else {
			$args = '';
		}

		$settings = get_option( $this->basename . '_settings' );
		$import   = apply_filters( $this->basename . '/get_ebay_import', $args );
		if ( ! $import->status ) {
			return false;
		}

		foreach ( $import->record as $tmp ) {

			$allCatPosts  = apply_filters( $this->basename . '/get_posts_by_taxonomy', $tmp->term_id );
			$page         = $tmp->import_count - 1;
			$regUrl       = sprintf( $settings['regex_api_url'], $page, $tmp->site_number );
			$importUrl    = $tmp->import_url . $regUrl;
			$importObject = [];
			if ( $tmp->pro_user == '1' ) {
				$importObject = $this->get_ebay_anzeigen( $importUrl );
			} else {
				$importObject = $this->get_std_ebay_anzeige( $importUrl, $tmp );
			}
			if ( $importObject->status ) {
				$postIds = [];
				foreach ( $importObject->record as $val ) {

					$ifPost    = apply_filters( $this->basename . '/get_post_by_ebay_id', $val['data']['id'], $tmp->term_id );
					$postIds[] = $val['data']['id'];
					if ( $ifPost ) {
						if ( $settings['cron_update_post'] ) {
							$this->delete_post_attachments( $ifPost->ID );
							$this->ebay_import_delete_post( $ifPost->ID );
						} else {
							continue;
						}
					}

					$imgArr        = [];
					$img_cover     = [];
					$ebay_osm_data = '';
					$ebay_is_osm   = false;
					if ( $tmp->osm_aktiv ) {
						$streetHnr = $val['strassen_name'] . ' ' . $val['hnr'];
						$plzOrt    = $val['plz'] . ' ' . $val['ort'];
						$query     = sprintf( '%s,%s', $streetHnr, $plzOrt );
						$query     = rawurlencode( $query );
						$geo       = apply_filters( $this->basename . '/get_curl_json_data', $query );
						if ( $geo->status ) {
							$ebay_is_osm   = true;
							$ebay_osm_data = $geo->geo_json;
						}
					}


					$term = get_term( $tmp->term_id );

					$date = date( 'Y-m-d H:i:s', strtotime( $val['data']['date'] ) );
					$args = [
						'post_type'      => 'anzeigen',
						'post_title'     => $val['data']['title'],
						'post_content'   => $val['beschreibung'],
						'post_status'    => 'publish',
						'post_category'  => array( (int) $tmp->term_id ),
						'comment_status' => 'closed',
						'post_excerpt'   => $this->string_replace_template( $val['data']['description'] ),
						'post_date'      => $date,
						'meta_input'     => [
							'_import_id'          => $tmp->id,
							'_ebay_id'            => $val['data']['id'],
							'_ebay_url'           => WP_EBAY_KLEINANZEIGEN_URL . $val['data']['url'],
							'_ebay_location'      => $val['data']['location'],
							'_ebay_price'         => $val['data']['price'],
							'_ebay_tags'          => $val['checkTags'],
							'_ebay_strassen_name' => $val['strassen_name'],
							'_ebay_hnr'           => $val['hnr'],
							'_ebay_plz'           => $val['plz'],
							'_ebay_ort'           => $val['ort'],
							'_ebay_is_osm'        => $ebay_is_osm,
							'_ebay_osm_data'      => $ebay_osm_data
						]
					];

					$postId = wp_insert_post( $args, true );
					if ( is_wp_error( $postId ) ) {
						$errMsg = 'import-error|' . $postId->get_error_message() . '|ID|' . $val['_import_id'] . '|line|' . __LINE__;
						self::ebay_import_log( $errMsg );
					} else {
						wp_set_object_terms( $postId, array( $term->term_id ), $term->taxonomy );
					}
					//$insertUpdate->last_import = current_time('timestamp');
					if ( ! $val['data']['retinaImage'] ) {
						$img_cover = [
							'filename' => basename( $val['data']['retinaImage'] ),
							'type'     => 'cover',
							'catId'    => $tmp->term_id,
							'url'      => $val['data']['retinaImage']
						];
					} else {
						if ( $val['data']['image'] ) {

							$img_cover = [
								'filename' => basename( $val['data']['image'] ),
								'type'     => 'cover',
								'catId'    => $tmp->term_id,
								'url'      => $val['data']['image']
							];
						}
					}

					if ( $val['images'] ) {
						foreach ( $val['images'] as $img ) {
							$img_item = [
								'filename' => basename( $img ),
								'type'     => 'attachment',
								'catId'    => $tmp->term_id,
								'url'      => $img
							];
							$imgArr[] = $img_item;
						}
					}
					if ( $img_cover ) {
						$imgArr[] = $img_cover;
					}
					if ( $imgArr ) {
						$postTitle = $val['data']['title'];
						$this->set_import_attachment_images( $imgArr, $postId, $postTitle );
					}
				}
				if ( $allCatPosts ) {
					foreach ( $allCatPosts as $all ) {
						$meta = get_post_meta( $all->ID, '_ebay_id', true );
						if ( ! in_array( $meta, $postIds ) ) {
							$this->delete_post_attachments( $all->ID );
							$this->ebay_import_delete_post( $all->ID );
						}
					}
				}
			}

		}

		return true;
	}


	public function ebay_import_delete_post( $postId ): void {
		wp_delete_post( $postId, true );
	}

	private function get_ebay_anzeigen( $url ): object {

		$settings = get_option( $this->basename . '_settings' );

		$return         = new stdClass();
		$return->status = false;
		$items          = [];
		$ch             = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $ch );
		curl_close( $ch );
		if ( curl_errno( $ch ) ) {
			$return->error = 'Curl-Fehler: ' . curl_error( $ch );

			return $return;
		}

		if ( $result ) {
			$items = json_decode( $result, true );
		}
		if ( ! $items ) {
			return $return;
		}
		$dataArr = [];
		foreach ( $items['ads'] as $tmp ) {

			$siteData = file_get_contents( 'https://www.ebay-kleinanzeigen.de' . $tmp['url'] );
			$siteData = $this->string_replace_template( $siteData );
			preg_match_all( $settings['regex_image'], $siteData, $matches );
			$data   = $tmp;
			$images = $matches[1] ?? [];
			preg_match_all( $settings['regex_check_tags'], $siteData, $matchTags );
			$checkTags         = $matchTags[1] ?? [];
			$return->checkTags = json_encode( $checkTags );
			preg_match( $settings['regex_description'], $siteData, $matchBeschreibung );
			$beschreibung  = $matchBeschreibung[1] ?? '';
			$strassen_name = '';
			$hnr           = '';
			$ort           = '';
			$plz           = '';
			preg_match_all( $settings['regex_street'], $siteData, $matchStreet );
			if ( isset( $matchStreet[1][1] ) ) {
				$street = $this->clean_string( $matchStreet[1][1] );
			} elseif ( isset( $matchStreet[1][0] ) ) {
				$street = $this->clean_string( $matchStreet[1][0] );
			} else {
				$street = '';
			}
			if ( $street ) {
				preg_match( $settings['regex_split_street'], $street, $matchStrasse );
				if ( $matches ) {
					$strassen_name = $matchStrasse[1] ?? '';
					$hnr           = $matchStrasse[2] ?? '';
				}
			}
			preg_match_all( $settings['regex_location'], $siteData, $matchOrt );
			if ( isset( $matchOrt[1][0] ) ) {
				$ort = $this->clean_string( strip_tags( $matchOrt[1][0] ) );
			} elseif ( isset( $matchOrt[1][1] ) ) {
				$ort = $this->clean_string( strip_tags( $matchOrt[1][1] ) );
			}
			if ( $ort ) {
				preg_match( $settings['regex_split_location'], $ort, $matchOrtPlz );
				if ( $matchOrtPlz ) {
					$plz = $matchOrtPlz[1] ?? '';
					$ort = $matchOrtPlz[2] ?? '';
				}
			}

			preg_match_all( $settings['regex_detail_list'], $siteData, $matchDetailList, PREG_SET_ORDER, 0 );
			$detailArr = [];
			if ( is_array( $matchDetailList ) ) {
				foreach ( $matchDetailList as $val ) {
					if ( $val[0] ) {
						$strip = strip_tags( $val[0], '<span>' );
						preg_match( $settings['regex_detail_list_extract'], $strip, $matches );
						$items       = [
							'label' => $this->clean_string( $matches[1] ) ?? '',
							'value' => $this->clean_string( $matches[2] ) ?? ''
						];
						$detailArr[] = $items;
					}
				}
			}
			$item      = [
				'data'          => $data,
				'images'        => $images,
				'beschreibung'  => $beschreibung,
				'detail_list'   => json_encode( $detailArr ),
				'strassen_name' => $this->clean_string( strip_tags( $strassen_name ) ),
				'hnr'           => $this->clean_string( strip_tags( $hnr ) ),
				'plz'           => $this->clean_string( strip_tags( $plz ) ),
				'ort'           => $this->clean_string( strip_tags( $ort ) )
			];
			$dataArr[] = $item;
		}
		if ( $dataArr ) {
			$return->status = true;
			$return->record = $dataArr;

			return $return;
		}

		return $return;
	}

	private function get_std_ebay_anzeige( $url, $import ): object {

		$return         = new stdClass();
		$return->status = false;
		$settings       = get_option( $this->basename . '_settings' );
		$reg            = '/(\d{1,10})/';
		preg_match( $reg, $import->import_url, $iUrlMatch );

		$s         = sprintf( $settings['regex_std_url'], $iUrlMatch[0], $import->site_number );
		$importUrl = WP_EBAY_KLEINANZEIGEN_URL . $s;
		$f         = file_get_contents( $importUrl );
		$f         = $this->string_replace_template( $f );

		preg_match_all( $settings['regex_std_ebay_id'], $f, $idMatches );
		$ids = $idMatches[1] ?? [];

		preg_match_all( $settings['regex_std_ebay_url'], $f, $urlMatches );
		$url = $urlMatches[1] ?? [];

		preg_match_all( $settings['regex_std_ebay_titel_img'], $f, $imgMatches );
		$imageArr = [];
		if ( $imgMatches[1] ) {
			foreach ( $imgMatches[1] as $tmp ) {
				if ( preg_match( $settings['regex_std_ebay_titel_img_check'], $tmp ) ) {
					$imageArr[] = $tmp;
				}
			}
		}
		$image = $imageArr ?? [];
		preg_match_all( $settings['regex_std_ebay_titel'], $f, $titleMatches );
		$title = $titleMatches[1] ?? [];
		preg_match_all( $settings['regex_std_ebay_date'], $f, $dateMatches );
		$date = $dateMatches[1] ?? [];
		preg_match_all( $settings['regex_std_ebay_location'], $f, $locationMatches );
		$location = $locationMatches[1] ?? [];
		preg_match_all( $settings['regex_std_ebay_beschreibung'], $f, $descriptionMatches );
		$description = $descriptionMatches[1] ?? [];
		preg_match_all( $settings['regex_std_ebay_price'], $f, $priceMatches );
		$price = $priceMatches[1] ?? [];
		$data  = [];
		if ( $ids && is_array( $ids ) ) {
			for ( $i = 0; $i < count( $ids ); $i ++ ) {

				$artikelUrl = WP_EBAY_KLEINANZEIGEN_URL . '/' . $url[ $i ];
				$siteData   = file_get_contents( $artikelUrl );
				$siteData   = $this->string_replace_template( $siteData );

				preg_match_all( $settings['regex_image'], $siteData, $matches );
				$images = $matches[1] ?? [];

				preg_match_all( $settings['regex_check_tags'], $siteData, $matchTags );
				$checkTags         = $matchTags[1] ?? [];
				$return->checkTags = json_encode( $checkTags );
				preg_match( $settings['regex_description'], $siteData, $matchBeschreibung );
				$beschreibung = $matchBeschreibung[1] ?? '';

				preg_match_all( $settings['regex_detail_list'], $siteData, $matchDetailList, PREG_SET_ORDER, 0 );
				$detailArr = [];
				if ( is_array( $matchDetailList ) ) {
					foreach ( $matchDetailList as $val ) {
						if ( $val[0] ) {
							$strip = strip_tags( $val[0], '<span>' );
							preg_match( $settings['regex_detail_list_extract'], $strip, $matches );
							$items       = [
								'label' => $this->clean_string( $matches[1] ) ?? '',
								'value' => $this->clean_string( $matches[2] ) ?? ''
							];
							$detailArr[] = $items;
						}
					}
				}

				$strassen_name = '';
				$hnr           = '';
				$ort           = '';
				$plz           = '';
				preg_match_all( $settings['regex_street'], $siteData, $matchStreet );
				if ( isset( $matchStreet[1][1] ) ) {
					$street = $this->clean_string( $matchStreet[1][1] );
				} elseif ( isset( $matchStreet[1][0] ) ) {
					$street = $this->clean_string( $matchStreet[1][0] );
				} else {
					$street = '';
				}
				if ( $street ) {
					preg_match( $settings['regex_split_street'], $street, $matchStrasse );
					if ( $matches ) {
						$strassen_name = $matchStrasse[1] ?? '';
						$hnr           = $matchStrasse[2] ?? '';
					}
				}

				preg_match_all( $settings['regex_location'], $siteData, $matchOrt );
				if ( isset( $matchOrt[1][0] ) ) {
					$ort = $this->clean_string( strip_tags( $matchOrt[1][0] ) );
				} elseif ( isset( $matchOrt[1][1] ) ) {
					$ort = $this->clean_string( strip_tags( $matchOrt[1][1] ) );
				}
				if ( $ort ) {
					preg_match( $settings['regex_split_location'], $ort, $matchOrtPlz );
					if ( $matchOrtPlz ) {
						$plz = $matchOrtPlz[1] ?? '';
						$ort = $matchOrtPlz[2] ?? '';
					}
				}
				$dataItem = [
					'data'          => [
						'id'          => $ids[ $i ],
						'url'         => '/' . $url[ $i ] ?? '',
						'image'       => $image[ $i ] ?? '',
						'retinaImage' => $image[ $i ] ?? '',
						'title'       => $title[ $i ] ?? '',
						'date'        => $date[ $i ] ?? '',
						'location'    => $location[ $i ] ?? '',
						'description' => $description[ $i ] ?? '',
						'price'       => $price[ $i ] ?? '',
						'tags'        => ''
					],
					'images'        => $images,
					'beschreibung'  => $beschreibung,
					'detail_list'   => json_encode( $detailArr ),
					'strassen_name' => $this->clean_string( strip_tags( $strassen_name ) ),
					'hnr'           => $this->clean_string( strip_tags( $hnr ) ),
					'plz'           => $this->clean_string( strip_tags( $plz ) ),
					'ort'           => $this->clean_string( strip_tags( $ort ) )
				];

				$data[] = $dataItem;
			}
		}
		if ( $data ) {
			$return->status = true;
			$return->record = $data;
			return $return;
		}
		return $return;
	}

	private function import_post_exists( $id ): object {
		$return         = new stdClass();
		$return->status = false;

		$posts = get_posts( array(
			'post_type'   => 'anzeigen',
			'numberposts' => 1,
			'meta_query'  => array(
				array(
					'key'     => '_ebay_id',
					'value'   => $id,
					'compare' => '==',
				)
			)
		) );

		if ( isset( $posts[0] ) && $posts[0]->ID ) {
			$return->status = true;
			$return->postId = $posts[0]->ID;

			return $return;
		}

		return $return;
	}

	private function set_import_attachment_images( $images, $postID, $postTitle ): bool {
		if ( ! $images || ! is_array( $images ) ) {
			return false;
		}
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$i             = 1;
		$wp_upload_dir = wp_upload_dir();

		foreach ( $images as $img ) {
			if ( ! $img['url'] ) {
				continue;
			}
			$term            = get_term( $img['catId'] );
			$post_title      = preg_replace( '/\.[^.]+$/', '', $postTitle ) . '-' . $i;
			$remote_file     = file_get_contents( $img['url'] );
			$upload_filename = substr( $img['filename'], strrpos( $img['filename'], '.' ) );
			$filename        = md5( uniqid() ) . $upload_filename;
			$destination     = $wp_upload_dir['path'] . '/' . $filename;
			file_put_contents( $destination, $remote_file );
			$wp_filetype = wp_check_filetype( $destination );
			$attachment  = array(
				'guid'           => $wp_upload_dir['url'] . '/' . $filename,
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => $post_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_category'  => array( (int) $img['catId'] ),
			);
			$attach_id   = wp_insert_attachment( $attachment, $destination, $postID, true );
			if ( is_wp_error( $attach_id ) ) {
				if ( is_file( $destination ) ) {
					unlink( $destination );
				}
				continue;
			}
			wp_set_object_terms( $attach_id, array( $term->term_id ), $term->taxonomy );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $destination );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			if ( $img['type'] == 'cover' ) {
				set_post_thumbnail( $postID, $attach_id );
			}
			$i ++;
		}

		return true;
	}

	/**
	 * @throws Exception
	 */
	public function delete_post_attachments( $id ): void {
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'post_parent'    => $id
		) );

		foreach ( $attachments as $attachment ) {
			if ( ! wp_delete_attachment( $attachment->ID, $this->force_delete ) ) {
				throw new Exception( 'Anhang konnte nicht gelöscht werden.(' . __LINE__ . ')' );
			}
		}
	}

	private function object2array_recursive( $object ) {
		return json_decode( json_encode( $object ), true );
	}

	public static function ebay_import_log( $msg, $type = 'import_error.log' ): void {
		if ( self::$log_aktiv ) {
			$logDir = WP_EBAY_CLASSIFIED_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
			if ( ! $logDir ) {
				mkdir( $logDir, 0777, true );
			}
			if ( ! is_file( $logDir . '.htaccess' ) ) {
				$htaccess = 'Require all denied';
				file_put_contents( $logDir . '.htaccess', $htaccess );
			}

			$log = 'LOG: ' . current_time( 'mysql' ) . '|' . $msg . "\r\n";
			$log .= '-------------------' . "\r\n";
			file_put_contents( $logDir . $type, $log, FILE_APPEND | LOCK_EX );
		}
	}

	private function clean_string( $string ): string {
		if ( ! $string ) {
			return '';
		}

		return trim( preg_replace( '/\s+/', ' ', $string ) );
	}

	private function string_replace_template( $string ): string {
		if ( ! $string ) {
			return '';
		}

		return preg_replace( [ '/<!--(.*)-->/Uis', "/[[:blank:]]+/" ], [ '', ' ' ], str_replace( [
			"\n",
			"\r",
			"\t"
		], '', $string ) );
	}
}

