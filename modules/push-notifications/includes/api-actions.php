<?php

class ApiActions {

	/**
	 * actions 
	 */
	const manual_notifcation_reset	= 'manual_notification_reset';
	const manual_notifcation_send	= 'manual_notification_send';
	const manual_notifcation_count	= 'manual_notification_count';
	const search_customers	= 'search_customers';

	protected $NotificationService;

	public function __construct() {
		$this->NotificationService = new NotificationPoolService();

		// reset action 
		add_action('wp_ajax_' . self::manual_notifcation_reset, array($this, 'reset'));

		// send action 
		add_action('wp_ajax_' . self::manual_notifcation_send, array($this, 'send'));

		// get all customers action
		add_action('wp_ajax_' . self::manual_notifcation_count, array($this, 'get_count'));

		// search customers action
		add_action('wp_ajax_' . self::search_customers, array($this, 'search_customers'));
	}


	public function search_customers() {
		$name = sanitize_text_field($_REQUEST['name']);

		$args = array(
			'role'		=> 'customer',
			'search'    => '*' . esc_attr($name) . '*',
			'search_columns' => array(
				'user_login',
				'user_nicename',
				'user_email',
				'user_url',
			),
		);

		$excluded_ids = explode(',', sanitize_text_field($_REQUEST['excluded_ids']));

		if (!empty($excluded_ids)) {
			$args['exclude'] = $excluded_ids;
		}

		$users = (new WP_User_Query($args))->get_results();

		if (!empty($users)) {
			$data = [];

			foreach ($users as $index => $user) {
				$data[$index]['id']		= $user->ID;
				$data[$index]['name']		= $user->display_name;
				$data[$index]['label']		= $user->display_name;
				$data[$index]['value']	= $user->display_name;
				$data[$index]['username']	= $user->user_login;
				$data[$index]['email']		= $user->user_email;
			}
		}

		wp_send_json_success($data);
	}

	/**
	 * Reset Tracker values 
	 */
	public function reset() {
		$this->NotificationService->reset_tracker();
		wp_send_json_success();
	}


	/**
	 * send notification to all users 
	 */
	public function send($title = '', $message = '', $selected = array()) {
		if (!($title && $message && $selected)) {
			$message	= sanitize_text_field($_REQUEST['message']);
			$title	= sanitize_text_field($_REQUEST['title']);
			$selected	= explode(",", sanitize_text_field($_REQUEST['selected']));
		}

		if (!empty($selected) && !(sizeof($selected) == 1 && $selected[0] == "")) {
			$result = $this->NotificationService->set_message($message)->set_title($title)->populate_notification_to_specific_users($selected);
		} else {
			$result = $this->NotificationService->set_message($message)->set_title($title)->populate_notification_to_all_users();
		}

		if ($result == 'done') {
			wp_send_json(array('success' => true, 'continue' => false));
		} else {
			wp_send_json(array('success' => true, 'continue' => true, 'offset' => $result));
		}
	}

	/**
	 * Get total count of all customers action
	 */
	public function get_count() {
		wp_send_json_success($this->NotificationService->count_total_customers());
	}
}

new ApiActions();
