<?php

trait stacks_facebook {
	protected $token;

	protected $facebook_get_user_data_url = "https://graph.facebook.com/me?fields=id,first_name,last_name,email&access_token=";

	protected $parameters = null;

	protected $errors = array();

	/**
	 * check if response is valid 
	 * @param \WP_Error $response
	 * @return boolean
	 */
	public function debug_response($response) {

		if (is_wp_error($response)) {
			$this->errors[] = $response->get_error_message();
		} else {
			// check if error from facebook side 
			$response_code = $response['response']['code'];

			if ($response_code !== 200) {
				$response_body = (array) json_decode($response['body']);

				if (isset($response_body['error']->message)) {
					$this->errors[] = $response_body['error']->message;
				}
			}
			// every thing okay set data 
			elseif ($response_code == 200) {
				return [
					'body' => json_decode($response['body']),
					'success' => true
				];
			}
		}

		return array('success' => false);
	}

	/**
	 * Contact Facebook and get Result
	 * @return object
	 */
	public function contact_facebook() {
		return wp_remote_get($this->facebook_get_user_data_url . $this->token);
	}
}
