<?php

/**
 * Description of StacksNotificationSystem
 *
 * @author Creiden
 */
class StacksNotificationSystem {

    /**
     * @var string 
     */
    const PAGE_SLUG = 'manual_notification';

    /**
     * @var NotificationPoolService
     */
    public $NotificationService = null;

    /**
     * @var string
     */
    protected $extension_dir;

    /**
     * @var string
     */
    protected $extension_uri;

    /**
     * Load Stacks Notifications System
     */
    public function __construct() {
        $this->load_includes();

        $this->define_required_parameters();

        $this->add_filters();
    }

    /**
     * Define Required Filters 
     */
    public function add_filters() {

        add_filter('plates_before_config_response', array($this, 'add_notification_settings_complete'));
    }

    /**
     * Add notification settings complete or not to the configuration request
     * 
     * @param array $config_settings
     * 
     * @return array
     */
    public function add_notification_settings_complete($config_settings) {
        $config_settings['notifications_settings_completed'] = StacksNotificationFunctions::is_manual_notifications_applied();

        return $config_settings;
    }

    /**
     * Define required parameters
     */
    public function define_required_parameters() {
        $this->extension_uri = plugin_dir_url(__FILE__) . '../push-notifications/';
        $this->extension_dir = plugin_dir_path(__FILE__) . '../push-notifications/';
    }

    /**
     * return new NotificationPoolService instance
     * 
     * @return NotificationPoolService
     */
    public function get_notification_service() {
        if (is_null($this->NotificationService)) {
            $this->NotificationService = new NotificationPoolService();
        }

        return $this->NotificationService;
    }

    /**
     * get json parser 
     * 
     * @param string $file
     * 
     * @return \JsonParserService
     */
    public function get_json_parser($file) {
        return new JsonParserService($file);
    }

    /**
     * load includes 
     */
    public function load_includes() {
        // required functions 
        require_once $this->extension_dir . 'stacks-notification-functions.php';

        // notification services( ios, android )
        require_once $this->extension_dir . 'includes/notification-services/android.php';
        require_once $this->extension_dir . 'includes/notification-services/ios.php';

        // lib
        require_once $this->extension_dir . 'includes/service-json-parser.php';
        require_once $this->extension_dir . 'includes/service-user-devices.php';
        require_once $this->extension_dir . 'includes/service-notification.php';
        require_once $this->extension_dir . 'includes/service-notification-pool.php';
        require_once $this->extension_dir . 'includes/api-actions.php';
    }

}

new StacksNotificationSystem();
