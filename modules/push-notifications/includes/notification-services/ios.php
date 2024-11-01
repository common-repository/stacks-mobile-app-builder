<?php

class IosNotification {

	protected $registration_ids = [];

	/**
	 * Add Registration id 
	 * @param type $registeration_id
	 */
	public function add_registeation_id($registration_id) {
		$this->registration_ids[] = $registration_id;
	}

	public static function get_ios_certificate_dir_path() {
		$upload_dir = wp_upload_dir();

		$expected_file_location = $upload_dir['basedir'] . '/stacks-uploads/plates.pem';

		if (!file_exists($expected_file_location)) {
			return false;
		}

		return $expected_file_location;
	}

	/**
	 * send ios notification 
	 * @param string $registration_ids
	 * @param string $message
	 * @param string $title
	 * @return boolean
	 */
	public function send($registration_ids, $message, $title) {
		if (is_array($registration_ids)) {
			$registration_ids = array_unique($registration_ids);

			foreach ($registration_ids as $rid) {
				$this->contact_apns($rid, $message);
			}
		} else {
			$this->contact_apns($registration_ids, $message);
		}

		return;
	}

	/**
	 * send notification 
	 * 
	 * @param string $deviceToken
	 * @param string $message
	 * 
	 * @return boolean
	 */
	public function contact_apns($deviceToken, $message) {
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $this->get_ios_certificate_dir_path());

		// Open a connection to the APNS server
		$fp = stream_socket_client(
			'ssl://gateway.push.apple.com:2195',
			$err,
			$errstr,
			60,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
			$ctx
		);

		if (!$fp) {
			return false;
		}

		// Create the payload body
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default',
			'badge'  => '1',
		);

		// Encode the payload as JSON
		$payload = json_encode($body);

		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));

		fclose($fp);

		return;
	}
}
