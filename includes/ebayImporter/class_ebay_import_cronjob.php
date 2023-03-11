<?php
namespace Wp_Ebay_Classifieds\Importer;
use stdClass;
use Wp_Ebay_Classifieds;


defined( 'ABSPATH' ) or die();

class Ebay_Import_Cronjob {

	use WP_Ebay_Importer_Settings;
    /**
     * @access   private
     * @var      array $settings The FB-API Settings for this Plugin
     */
    private array $settings;

	private $basename;

    /**
     * @access   private
     * @var Wp_Ebay_Classifieds $main The main class.
     */
    private Wp_Ebay_Classifieds $main;

    public function __construct(string $basename,  Wp_Ebay_Classifieds $main ) {

        $this->main = $main;
		$this->basename = $basename;
        $this->settings = get_option($this->basename.'_settings');
        if($this->settings){
            if($this->settings['cron_aktiv']){
                if (!wp_next_scheduled('ebay_import_sync')) {
                    wp_schedule_event(time(), $this->settings['selected_cron_sync_interval'], 'ebay_import_sync');
                }
            }
        }
    }

    public function fn_ebay_wp_un_schedule_task($args): void
    {
        $timestamp = wp_next_scheduled('ebay_import_sync');
        wp_unschedule_event($timestamp, 'ebay_import_sync');
    }

    public function fn_ebay_wp_delete_task($args): void
    {
        wp_clear_scheduled_hook('ebay_import_sync');
    }

    public function fn_ebay_run_schedule_task($args): void
    {

        if($this->settings){
            $schedule = $this->settings['selected_cron_sync_interval'];
        } else {
           $schedule = 'daily';
        }
        $time = get_gmt_from_date(gmdate('Y-m-d H:i:s', current_time('timestamp')), 'U');
        $args = [
            'timestamp' => $time,
            'recurrence' => $schedule->recurrence,
            'hook' => 'ebay_import_sync'
        ];

        $this->schedule_task($args);
    }

    /**
     * @param $task
     * @return void
     */
    private function schedule_task($task): void
    {

        /* Must have task information. */
        if (!$task) {
            return;
        }

        /* Set list of required task keys. */
        $required_keys = array(
            'timestamp',
            'recurrence',
            'hook'
        );

        /* Verify the necessary task information exists. */
        $missing_keys = [];
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $task)) {
                $missing_keys[] = $key;
            }
        }

        /* Check for missing keys. */
        if (!empty($missing_keys)) {
            return;
        }

        /* Task darf nicht bereits geplant sein. */
        if (wp_next_scheduled($task['hook'])) {
            wp_clear_scheduled_hook($task['hook']);
        }

        /* Schedule the task to run. */
        wp_schedule_event($task['timestamp'], $task['recurrence'], $task['hook']);
    }

}
