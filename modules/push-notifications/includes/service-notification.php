<?php

class NotificationService {

	// Meta to be saved in Database
	const notifications_meta = "notifications";

	/**
	 * @var array 
	 */
	protected $errors = array();

	/**
	 * @var array 
	 */
	protected $services = array();

	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * Set notification title 
	 * 
	 * @param string $title
	 */
	public function set_title($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Set notification Message 
	 * 
	 * @param string $message
	 */
	public function set_message($message) {
		$this->message = $message;
		return $this;
	}

	/**
	 * send Notification to user 
	 * 
	 * @param int $user_id
	 * 
	 * @return boolean
	 */
	public function send_notification_to_user($user_id) {
		$devices = $this->get_user_devices($user_id);

		if ($devices) {
			$this->send_notification_to_devices($this->filter_devices($devices));

			$this->save_user_notification($user_id, $this->message, $this->title);

			return true;
		}

		return $this->errors;
	}

	/**
	 * Filter Valid Devices
	 * 
	 * @param array $devices
	 * 
	 * @return array
	 */
	public function filter_devices($devices) {
		$android_devices	= [];
		$ios_devices		= [];

		if (!empty($devices)) {
			foreach ($devices as $device) {
				if (in_array(strtolower($device['device_type']), ['ios', 'android'])) {
					if ($device['device_type'] == 'android') {
						$android_devices[] = $device['device_id'];
					} else {
						$ios_devices[] = $device['device_id'];
					}
				}
			}
		}

		return ['ios' => $ios_devices, 'android' => $android_devices];
	}

	/**
	 * Send Notification to devices expect ['ios' => array( devices ) ]
	 * 
	 * @param array $devices
	 * 
	 * @return boolean
	 */
	public function send_notification_to_devices($devices) {
		if (!empty($devices)) {
			foreach ($devices as $type => $registeration_ids) {
				$service = $this->get_notification_service($type);
				foreach ($registeration_ids as $registeration_id) {
					$service->send($registeration_id, $this->message, $this->title);
				}
			}
		}

		return true;
	}


	/**
	 * Get User devices 
	 * 
	 * @param int $user_id
	 * 
	 * @return boolean
	 */
	public function get_user_devices($user_id) {
		$devices = UserDevicesService::get_user_devices($user_id);

		if (!empty($devices)) {
			return $devices;
		}

		return [];
	}

	/**
	 * get notification service
	 * @param string $service
	 * @return IosNotification|AndroidNotification
	 */
	public function get_notification_service($service) {
		switch ($service) {
			case 'ios':
			case 'android':
				if (isset($this->services[$service])) {
					return $this->services[$service];
				} else {
					$this->services[$service] = $service == 'ios' ? new IosNotification() : new AndroidNotification();
					return $this->services[$service];
				}
			default:
				return false;
		}
	}

	/**
	 * save new notification to user 
	 * @param int $user_id
	 * @param string $message
	 * @param string $title
	 * @return boolean
	 */
	private function save_user_notification($user_id, $message, $title) {
		$notifications = $this->get_user_notifications($user_id);

		if (!$notifications) {
			$notifications = array();
		}

		$notification = array(
			'message'	=> $message,
			'title'		=> $title
		);

		$notifications[] = $notification;

		return $this->update_user_notifications($user_id, $notifications);
	}

	/**
	 * update user notifications 
	 * @param int $user_id
	 * @param array $notifications
	 * @return boolean
	 */
	private function update_user_notifications($user_id, $notifications) {
		return update_user_meta($user_id, self::notifications_meta, $notifications);
	}


	/**
	 * return user notifications 
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_notifications($user_id) {
		return get_user_meta($user_id, self::notifications_meta, true);
	}
}
