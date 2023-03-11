<?php
namespace Wp_Ebay_Classifieds\Importer;
use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use stdClass;
use Wp_Ebay_Classifieds;
use WP_Term_Query;

/**
 * ADMIN Helper
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Ebay_Classifieds
 * @subpackage Wp_Ebay_Classifieds/includes
 */

defined( 'ABSPATH' ) or die();
class WP_Ebay_Importer_Helper
{
	private static $instance;

	use WP_Ebay_Importer_Settings;

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
	public static function instance(string  $version,string $basename,  Wp_Ebay_Classifieds $main ): self
	{
		if (is_null(self::$instance)) {
			self::$instance = new self($version, $basename, $main);
		}
		return self::$instance;
	}

	public function __construct(string  $version,string $basename, Wp_Ebay_Classifieds $main)
	{
		$this->main = $main;
		$this->version = $version;
		$this->basename = $basename;
	}

	/**
	 * @param string $cron_name
	 *
	 * @return false|int|string
	 */
	public function import_get_next_cron_time(string $cron_name)
	{
		foreach (_get_cron_array() as $timestamp => $crons) {
			if (in_array($cron_name, array_keys($crons))) {
				return $timestamp - time();
			}
		}
		return false;
	}

	/**
	 * @throws Exception
	 */
	public function getRandomString(): string
	{
		if (function_exists('random_bytes')) {
			$bytes = random_bytes(16);
			$str = bin2hex($bytes);
		} elseif (function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes(16);
			$str = bin2hex($bytes);
		} else {
			$str = md5(uniqid('wp_ebay_importer_rand', true));
		}

		return $str;
	}
	public function getGenerateRandomId($passwordlength = 12, $numNonAlpha = 1, $numNumberChars = 4, $useCapitalLetter = true): string
	{
		$numberChars = '123456789';
		//$specialChars = '!$&?*-:.,+@_';
		$specialChars = '!$%&=?*-;.,+~@_';
		$secureChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
		$stack = $secureChars;
		if ($useCapitalLetter == true) {
			$stack .= strtoupper($secureChars);
		}
		$count = $passwordlength - $numNonAlpha - $numNumberChars;
		$temp = str_shuffle($stack);
		$stack = substr($temp, 0, $count);
		if ($numNonAlpha > 0) {
			$temp = str_shuffle($specialChars);
			$stack .= substr($temp, 0, $numNonAlpha);
		}
		if ($numNumberChars > 0) {
			$temp = str_shuffle($numberChars);
			$stack .= substr($temp, 0, $numNumberChars);
		}

		return str_shuffle($stack);
	}

	public function fileSizeConvert(float $bytes): string
	{
		$result = '';
		$bytes = floatval($bytes);
		$arBytes = array(
			0 => array("UNIT" => "TB", "VALUE" => pow(1024, 4)),
			1 => array("UNIT" => "GB", "VALUE" => pow(1024, 3)),
			2 => array("UNIT" => "MB", "VALUE" => pow(1024, 2)),
			3 => array("UNIT" => "KB", "VALUE" => 1024),
			4 => array("UNIT" => "B", "VALUE" => 1),
		);

		foreach ($arBytes as $arItem) {
			if ($bytes >= $arItem["VALUE"]) {
				$result = $bytes / $arItem["VALUE"];
				$result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
				break;
			}
		}
		return $result;
	}

	/**
	 * @param $array
	 *
	 * @return object
	 */
	public function arrayToObject($array): object
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = self::arrayToObject($value);
			}
		}

		return (object)$array;
	}

	public function date_format_language(DateTime $dt, string $format, string $language = 'en'): string
	{
		$curTz = $dt->getTimezone();
		if ($curTz->getName() === 'Z') {
			//INTL don't know Z
			$curTz = new DateTimeZone('Europe/Berlin');
		}

		$formatPattern = strtr($format, array(
			'D' => '{#1}',
			'l' => '{#2}',
			'M' => '{#3}',
			'F' => '{#4}',
		));
		$strDate = $dt->format($formatPattern);
		$regEx = '~\{#\d}~';
		while (preg_match($regEx, $strDate, $match)) {
			$IntlFormat = strtr($match[0], array(
				'{#1}' => 'E',
				'{#2}' => 'EEEE',
				'{#3}' => 'MMM',
				'{#4}' => 'MMMM',
			));
			$fmt = datefmt_create($language, IntlDateFormatter::FULL, IntlDateFormatter::FULL,
				$curTz, IntlDateFormatter::GREGORIAN, $IntlFormat);
			$replace = $fmt ? datefmt_format($fmt, $dt) : "???";
			$strDate = str_replace($match[0], $replace, $strDate);
		}

		return $strDate;
	}

	public function fn_get_import_taxonomy($taxonomie, $post_type): array
	{
		if (!$taxonomie || !$post_type) {
			return [];
		}

		$term_args = array(
			'post_type' => $post_type,
			'taxonomy' => $taxonomie,
			'hide_empty' => false,
			'fields' => 'all'
		);

		$term_query = new WP_Term_Query($term_args);
		$taxArr = [];

		if(!$term_query->terms){
			return $taxArr;
		}

		foreach ($term_query->terms as $tmp) {
			$item = [
				'term_id' => $tmp->term_id,
				'slug' => $tmp->slug,
				'name' => $tmp->name
			];
			$taxArr[] = $item;
		}

		return $taxArr;
	}

	/**
	 * @param $object
	 * @return array
	 */
	public function object2array_recursive($object):array
	{
		if(!$object) {
			return  [];
		}
		return json_decode(json_encode($object), true);
	}

	public function fnPregWhitespace($string): string
	{
		if (!$string) {
			return '';
		}
		return trim(preg_replace('/\s+/', ' ', $string));
	}

	/**
	 * @throws Exception
	 */
	public function get_curl_json_data($query = '', $format = 'json', $polygon_geojson = 1, $addressdetails = 0, $limit = 1): object
	{
		$return = new stdClass();
		$return->status = false;
		if(!$query){
			return $return;
		}
		$url = sprintf('https://nominatim.openstreetmap.org/search?q=%s&format=%s&polygon_geojson=%d&addressdetails=%d&state=Sachsen-Anhalt&limit=%d', $query, $format, $polygon_geojson, $addressdetails, $limit);
		$opts = array('http' => array('header' => "User-Agent: XlsxReaderAddressScript 3.7.6\r\n"));
		$context = stream_context_create($opts);
		$file = file_get_contents($url, false, $context);
		if ($file) {
			$d = json_decode($file, true);
			if($limit == 1){
				$geo_json = json_encode($d[0]);
			} else {
				$geo_json = json_encode($d);
			}
			if(!$d){
				return $return;
			}
			$return->geo_json = $geo_json;
			$return->status = true;
		}
		return $return;
	}

}