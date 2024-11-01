<?php

class StacksNotificationFunctions {

    const ANDROID_SENDER_ID = 'android_sender_id';
    const ANDROID_AUTH_KEY = 'android_auth_key';
    const ANDROID_JSON_FILE = 'google_service_json_file';

    /**
     * get google Auth key parameter for push notification
     * 
     * @return boolean
     */
    public static function get_auth_key() {
        if ($GLOBALS['builder_api']->stacks_get_multisite_option('app_settings')) {
            $app_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('app_settings');
        }

        $android_auth_key = $app_settings->android_server_key;

        return ($android_auth_key == '' || is_null($android_auth_key) || !$android_auth_key) ? false : $android_auth_key;
    }

    /**
     * get google sender id parameter for push notification 
     * 
     * @return string|boolean
     */
    public static function get_google_sender_id() {
        $android_sender_id = '';
        if ($GLOBALS['builder_api']->stacks_get_multisite_option('app_settings')) {
            $app_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('app_settings');
            $android_sender_id = $app_settings->android_sender_id;
        }

        return ($android_sender_id == '' || is_null($android_sender_id) || !$android_sender_id) ? false : $android_sender_id;
    }

    /**
     * get google service file 
     * 
     * @return string|boolean
     */
    public static function get_google_service_file() {
        $uploads_dir    = wp_upload_dir();
        $google_service_json_file = file_get_contents($uploads_dir['basedir'] . '/google-services.json');

        $uploaded_file_valid = (new JsonParserService($google_service_json_file))->is_valid_file();
        return ($google_service_json_file == '' || is_null($google_service_json_file) || !$google_service_json_file) || !$uploaded_file_valid ? false : $google_service_json_file;
    }

    /**
     * check if user has inserted all required parameters 
     * 
     * @return boolean
     */
    public static function is_user_inserted_required_details() {
        if (!self::get_google_sender_id() || !self::get_google_service_file() || !self::get_auth_key()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * check if manual notifications is applied
     * 
     * first is manual notifications allowed on this server or not
     * second is user entered all required fields on theme options or not
     * 
     * @return boolean
     */
    public static function is_manual_notifications_applied() {
        // if trial do not show the manual notification page 
        $applied = self::is_manual_notifications_allowed();

        // if user did not entere required configuration in theme options
        if ($applied) {
            $applied = self::is_user_inserted_required_details();
        }

        return $applied;
    }

    /**
     * check if manual notifications allowed on this site or not 
     * 
     * @return boolean
     */
    public static function is_manual_notifications_allowed() {
        $applied = true;

        return apply_filters('show_manual_notification_on_trial', $applied);
    }
}
