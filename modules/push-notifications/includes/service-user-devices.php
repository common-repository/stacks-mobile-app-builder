<?php

final class UserDevicesService {

	const META_PARAMETER_NAME = 'connected_devices';

	/**
	 * user devices saved 
	 * 
	 * @var array 
	 */
	private static $user_deivces = array();

	/**
	 * init hooks
	 */
	public static function init_hooks() {
		add_action('_stacks_api_user_logged_in', array(self::class, 'save_user_device'), 10, 3);
		add_action('_stacks_api_user_registered', array(self::class, 'save_user_device'), 10, 3);
		add_action('edit_user_profile', array(self::class, 'add_device_ids_to_user_profile'), 10, 1);
	}


	/**
	 * show device id's to user profile 
	 *  
	 * @param WP_User $user
	 */
	public static function add_device_ids_to_user_profile($user) {
		$user_devices = self::get_user_devices($user->ID);

		printf('<h2>User Devices</h2>');

		if (!empty($user_devices)) {
			foreach ($user_devices as $device) {
				$type = $device['device_type'];
				$id = $device['device_id'];
				printf('<h4>Device type : %s ,Device id: %s</h4>', $type, $id);
			}
		}
	}

	/**
	 * save user devices
	 * @param int $user_id
	 * @param string $device_type
	 * @param string $device_id
	 * @return boolean
	 */
	public static function save_user_device($user_id, $device_type, $device_id) {
		$exists = self::is_device_id_exists($user_id, $device_id);

		$array_element	= array('device_type' => strtolower($device_type), 'device_id' => $device_id);
		$user_devices	= self::get_user_devices($user_id) ? self::get_user_devices($user_id) : array();

		if (!$exists) {
			self::delete_device_id_from_guests_devices_if_exists($device_id);
			$user_devices[] = $array_element;
		} elseif ($exists && $user_devices) {
			return true;
		}

		return self::save_user_devices_to_database($user_id, $user_devices);
	}

	/**
	 * save user devices to database 
	 * @param int $user_id
	 * @param array $devices
	 * @return boolean
	 */
	private static function save_user_devices_to_database($user_id, $devices) {
		// update and save 
		self::$user_deivces[$user_id] = $devices;
		return update_user_meta($user_id, self::META_PARAMETER_NAME, $devices);
	}

	/**
	 * Get user devices from database 
	 * @param integer $user_id
	 * @return array
	 */
	public static function get_user_devices($user_id) {
		if (!isset(self::$user_deivces[$user_id])) {
			self::$user_deivces[$user_id] = get_user_meta($user_id, self::META_PARAMETER_NAME, true);
		}
		return self::$user_deivces[$user_id];
	}

	/**
	 * check if device id already exists in user devices
	 * @param int $user_id
	 * @param string $device_id
	 * @return boolean
	 */
	public static function is_device_id_exists($user_id, $device_id) {
		$devices = self::get_user_devices($user_id);

		if (is_array($devices)) {
			foreach ($devices as $device) {
				if ($device_id == $device['device_id']) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Deletes device id from huests devices option if exists ( on registration )
	 * @param integer $device_id
	 */
	public static function delete_device_id_from_guests_devices_if_exists($device_id) {
		$guests_devices = $GLOBALS['builder_api']->stacks_get_multisite_option('guests_devices');

		$exists = false;
		if (is_array($guests_devices)) {
			foreach ($guests_devices as $index => $device) {
				if ($device_id == $device['device_id']) {
					//delete $device_id from $guests_devices
					unset($guests_devices[$index]);
					break;
				}
			}
			$GLOBALS['builder_api']->stacks_update_multisite_options(get_current_blog_id(), 'guests_devices', $guests_devices);
		}
	}
}

$GLOBALS['userDevices'] = new UserDevicesService;
$GLOBALS['userDevices']::init_hooks();

class rest_user_devices_app extends WP_REST_Controller {

	public function __construct() {
	}

	public function register_routes() {
		$namespace = 'v4';

		register_rest_route(
			$namespace,
			'/stacks-user-devices/',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'user_devices'),
				'args' => array(
					'user_id' => array(
						'description' => __('Id of the users', 'wp-rest-user'),
						'required'     => true,
						'validate_callback' => function ($param, $request, $key) {
						}
					),
				),
			)
		);
	}
	public function user_devices() {
		return $GLOBALS['userDevices']->get_user_devices(sanitize_text_field($_POST['user_id']));
	}
}

add_action('rest_api_init', function () {
	$rest_user_devices_controller = new rest_user_devices_app();
	$rest_user_devices_controller->register_routes();
});
