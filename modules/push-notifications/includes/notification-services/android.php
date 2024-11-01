<?php

class AndroidNotification {

    protected $registration_ids = [];

    /**
     * Add Registration id 
     * @param type $registeration_id
     */
    public function add_registeation_id($registration_id) {
        $this->registration_ids[] = $registration_id;
    }

    /**
     * Send Message to Android Phone Using Registration Id and Message Context
     * @param string $registration_id
     * @param string $message
     * @param string $title
     * @return Boolean
     */
    public function send($registration_id, $message, $title) {
        // Prep the bundle
        $msg = array(
            'message'   => $message,
            'title'      => $title,
            'subtitle'   => $message,
            'body'      => $message,
            'largeIcon'  => 'large_icon',
            'smallIcon'  => 'small_icon'
        );

        $fields = array(
            "to"            => "$registration_id",
            "notification"    => $msg
        );

        $headers = array(
            'Authorization: key=' . StacksNotificationFunctions::get_auth_key(),
            'SENDER_ID: id=' . StacksNotificationFunctions::get_google_sender_id(),
            'Content-Type: application/json'
        );

        $result = wp_remote_post('https://fcm.googleapis.com/fcm/send', array(
            'method'      => 'POST',
            'blocking'    => true,
            'headers'     => $headers,
            'timeout'     => 450,
            'body'        => json_encode($fields),
            'cookies'     => array()
        ));
        return $result;
    }
}
