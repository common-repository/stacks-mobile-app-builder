<?php

/**
 * Description of Mailing Service
 *
 * @author creiden
 */
class MailingService {

	/**
	 * @var string
	 */
	protected $from = null;

	/**
	 * @var string
	 */
	protected $message = null;

	/**
	 * @var string
	 */
	protected $subject = null;

	/**
	 * @var string
	 */
	protected $to = null;


	/**
	 * Set email from 
	 * 
	 * @param string $from
	 * 
	 * @return $this
	 */
	public function set_email_from($from) {
		$this->from = $from;
		return $this;
	}


	/**
	 * Set message 
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
	 * Set subject 
	 * 
	 * @param string $subject
	 * 
	 * @return $this
	 */
	public function set_subject($subject) {
		$this->subject = $subject;
		return $this;
	}


	/**
	 * set to parameter
	 * 
	 * @param string $to
	 * 
	 * @return $this
	 */
	public function set_to($to) {
		$this->to = $to;
		return $this;
	}


	public function __construct() {
		// some filters for wordpress
		add_filter('wp_mail_content_type', array($this, "mail_content_type"));
		add_filter('wp_mail_from', array($this, "mail_from"));
		add_filter('wp_mail_from_name', array($this, "email_from_name"));
	}

	/**
	 * Gets Admin email 
	 * 
	 * @return string
	 */
	public function get_admin_email() {
		return get_option('admin_email');
	}

	/**
	 * Gets forgot password message template 
	 * 
	 * @param array $data
	 * 
	 * @return string
	 */
	public function get_forgot_password_message_template($data) {
		return $this->getMessageFromTemplate('reset-password', $data);
	}

	/**
	 * Gets contact us message template
	 * 
	 * @param array $data
	 * 
	 * @return string
	 */
	public function get_contact_us_message_template($data) {
		return $this->getMessageFromTemplate('contact-us', $data);
	}


	/**
	 * get message from a template
	 * 
	 * @param string $template
	 * @param array $data additional data that template need to work
	 * 
	 * @return string
	 */
	private function getMessageFromTemplate($template, $data = array()) {
		if (!empty($data)) {
			extract($data, EXTR_PREFIX_SAME, '');
		}

		ob_start();

		require_once __DIR__ . "/mailing-templates/" . $template . ".php";
		$message = ob_get_contents();

		ob_end_clean();

		return $message;
	}

	/**
	 * Send mail to mail 
	 * 
	 * @return boolean
	 */
	public function sendMailToEmail() {
		if (is_null($this->to) || is_null($this->subject) || is_null($this->message)) {
			return new WP_Error('invalid_parameter_subblied', __('Some Required data is missing', 'plates'));
		}

		return wp_mail($this->to, $this->subject, $this->message);
	}


	/**
	 * Extract user email and send him an email
	 * 
	 * @param string $subject
	 * @param string $message
	 * @param int $user_id
	 * 
	 * @return boolean
	 */
	public function sendMailToUser($subject, $message, $user_id) {
		$this->set_to($this->getUserEmail($user_id))->set_message($message)->set_subject($subject);

		return $this->sendMailToEmail();
	}

	/**
	 * Gets user email 
	 * 
	 * @param int $id
	 * 
	 * @return string
	 */
	private function getUserEmail($id) {
		$data = get_userdata($id);
		return $data->user_email;
	}

	/**
	 * set from email parameter 
	 * 
	 * @return string
	 */
	public function email_from_name() {
		return apply_filters("change_from_email_name", get_bloginfo('name'));
	}

	/**
	 * content type of email 
	 * 
	 * @return string
	 */
	public function mail_content_type() {
		return "text/html";
	}

	/**
	 * set mail from 
	 * 
	 * @return string
	 */
	public function mail_from() {
		$from = is_null($this->from) ? $this->get_admin_email() : $this->from;

		return apply_filters("change_from_email", $from);
	}
}
