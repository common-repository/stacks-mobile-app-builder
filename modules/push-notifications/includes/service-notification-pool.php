<?php

/**
 * this class mainly used with manual notifications, and can be used with any service want to send notifications to all users.
 */
class NotificationPoolService {

	// populate notification tracker to keep track of last sent notification to users 
	const cookie_name = 'notification_tracker';
	const populate_notification_tracker_name = 'populate_notification_tracker';
	const populate_notification_tracker_notification_title = 'populate_notification_title';
	const populate_notification_tracker_notification_message = 'populate_notification_message';
	const users_per_page = 15;

	protected $message;
	protected $title;

	/**
	 * Set Message 
	 * 
	 * @param string $message
	 * 
	 * @return $this
	 */
	public function set_message($message) {
		$this->message = $message;
		return $this;
	}

	/**
	 * Set Title 
	 * 
	 * @param string $title
	 * 
	 * @return $this
	 */
	public function set_title($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * send notification to number of users 
	 * @return string|int
	 */
	public function populate_notification_to_all_users() {
		$offset		= $this->get_tracker(self::populate_notification_tracker_name) ?: 0;
		$users		= $this->get_customers($offset);
		$notification_service	= (new NotificationService())->set_title($this->title)->set_message($this->message);

		if (!$users) {
			$this->update_session_values('', '');
			return 'done';
		}

		$this->update_session_values($this->message, $this->title, $offset);

		$ios_devices = [];
		$android_devices = [];

		foreach ($users as $user) {
			$user_id = (int) $user;
			$user_devices = $notification_service->filter_devices($notification_service->get_user_devices($user_id));

			$ios_devices = array_merge($ios_devices, $user_devices['ios']);
			$android_devices = array_merge($android_devices, $user_devices['android']);
		}

		// Get guests devices
		$guests_devices = $notification_service->filter_devices($GLOBALS['builder_api']->stacks_get_multisite_option('guests_devices'));
		$ios_devices = array_merge($ios_devices, $guests_devices['ios']);
		$android_devices = array_merge($android_devices, $guests_devices['android']);

		$notification_service->send_notification_to_devices(['ios' => $ios_devices, 'android' => $android_devices]);
		$this->increment_tracker(sizeof($users));

		return $this->get_tracker(self::populate_notification_tracker_name);
	}

	/**
	 * Send notifications to selected devices only
	 * @param array $users
	 * @return boolean
	 */
	public function populate_notification_to_specific_users($users) {
		$notification_service	= (new NotificationService())->set_title($this->title)->set_message($this->message);

		$ios_devices = [];
		$android_devices = [];

		foreach ($users as $user) {
			$user_id = (int) $user;
			$user_devices = $notification_service->filter_devices($notification_service->get_user_devices($user_id));

			$ios_devices = array_merge($ios_devices, $user_devices['ios']);
			$android_devices = array_merge($android_devices, $user_devices['android']);
		}

		$notification_service->send_notification_to_devices(['ios' => $ios_devices, 'android' => $android_devices]);

		return 'done';
	}


	/**
	 * add session values 
	 * @param type $message
	 * @param type $title
	 */
	public function update_session_values($message, $title, $num = 0) {
		$value = json_encode(
			array(
				self::populate_notification_tracker_name => $num,
				self::populate_notification_tracker_notification_title => $title,
				self::populate_notification_tracker_notification_message => $message
			)
		);

		setcookie(self::cookie_name, $value, time() + 3600);

		$_COOKIE[self::cookie_name] = $value;
	}

	/**
	 * Reset Tracker values
	 */
	public function reset_tracker() {
		$this->update_session_values('', '');
	}

	/**
	 * get tracker value 
	 * @return int
	 */
	public function get_tracker($el = false) {
		if (!isset($_COOKIE[self::cookie_name])) {
			return false;
		}

		$data = (array) json_decode(stripcslashes($_COOKIE[self::cookie_name]));

		if ($el) {
			return $data[$el];
		}

		return $data;
	}


	/**
	 * update tracker 
	 * saving it in session to be fast 
	 */
	private function increment_tracker($num = null) {
		$tracker = $this->get_tracker(self::populate_notification_tracker_name) ? $this->get_tracker(self::populate_notification_tracker_name) : 0;

		$tracker += is_null($num) ? self::users_per_page : $num;

		$this->update_session_values(
			$this->get_tracker(self::populate_notification_tracker_notification_message),
			$this->get_tracker(self::populate_notification_tracker_notification_title),
			$tracker
		);
	}

	/**
	 * Get Customers
	 * @param int $offset
	 * @return array
	 */
	public function get_customers($offset) {
		$query_args = array(
			'fields'	=> 'ID',
			'role'		=> 'customer',
			'orderby'	=> 'registered',
			'number'	=> self::users_per_page,
			'offset'	=> $offset
		);

		return get_users($query_args);
	}

	/**
	 * return total number of customers 
	 * 
	 * @return int
	 */
	public function count_total_customers() {
		$user_query = new WP_User_Query(array('role' => 'customer', 'count_total' => true));
		return $user_query->get_total();
	}
}
