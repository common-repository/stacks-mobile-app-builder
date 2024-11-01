<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle data for the current customers token.
 * 
 * This class takes place when any request come to our rest api to validate token and get rid of cookies and start using token to get user information 
 * 
 * Even if user is guest or logged in this class can manage user data 
 * 
 * Implements the WC_Session abstract class.
 */
class Stacks_Session_Handler extends WC_Session {

    public $cookie_hash = null;

    const WOOCOMMERCE_CUSTOMER_ID_SESSION = 'woocommerce_customer_id_session';

    protected $_session_expiration;

    protected $_session_expiring;

    protected $_cookie;

    protected $_table;

    private $_has_cookie = false;

    private $customer = null;

    private $errors;

    /**
     * Constructor for the session class.
     */
    public function __construct() {

        global $wpdb;

        $this->_cookie = apply_filters('woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH);
        $this->_table  = $wpdb->prefix . 'woocommerce_sessions';

        $this->load_user_session_from_token();

        // Actions
        add_action('woocommerce_cleanup_sessions', array($this, 'cleanup_sessions'), 10);
        add_action('shutdown', array($this, 'save_data'), 20);
        add_action('wp_logout', array($this, 'destroy_session'));

        if (!is_user_logged_in()) {
            add_filter('nonce_user_logged_out', array($this, 'nonce_user_logged_out'));
        }

        // add key to token before sending to user 
        add_filter('before_encoding_token', array($this, 'add_woocommerce_session_handler'));
    }

    /**
     * Adds Filters
     * -- woocommerce know that this class is responsible for managing cart instead of woocommerce class 
     * -- add validation on token to ensure it has required fields 
     */
    public static function add_filters() {
        // put our session handler instead of woocommerce session handler 
        add_filter('woocommerce_session_handler', array(self::class, 'change_woocommerce_session_handler'), 10);

        // validate token is valid and has the key we registered
        add_filter('_stacks_validate_token', array(self::class, 'validate_session_exists_in_token'));
    }

    /**
     * validate session exists in token 
     * @global string $active_token
     * @param object $encoded_token
     * @return boolean
     */
    public static function validate_session_exists_in_token() {
        global $active_token;

        $arg_name = self::WOOCOMMERCE_CUSTOMER_ID_SESSION;

        if (!$active_token || !isset($active_token->$arg_name)) {
            return false;
        }

        return true;
    }

    /**
     * change Woo-commerce session handler class 
     * @param type $class_name
     * @return string
     */
    public static function change_woocommerce_session_handler($class_name) {
        if (_stacks_api_is_auth_service_jwt()) {
            $class_name = 'Stacks_Session_Handler';
        }
        return $class_name;
    }


    /**
     * Return true if the current user has an active session, i.e. a cookie to retrieve values.
     *
     * @return bool
     */
    public function has_session() {
        return $this->_has_cookie || is_user_logged_in();
    }

    /**
     * Get session cookie.
     *
     * @return bool|array
     */
    public function get_session_cookie() {

        if (is_null($this->customer)) {
            return false;
        }

        list($customer_id, $session_expiration, $session_expiring, $cookie_hash) = explode('||', $this->customer);

        // Validate hash
        $to_hash = $customer_id . '|' . $session_expiration;
        $hash    = hash_hmac('md5', $to_hash, wp_hash($to_hash));

        if (empty($cookie_hash) || !hash_equals($hash, $cookie_hash)) {
            $this->add_error(__('hash not equal in your token session', 'plates'));

            return false;
        }

        return array($customer_id, $session_expiration, $session_expiring, $cookie_hash);
    }

    /**
     * Get all errors 
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * add new error 
     * @param string $error
     */
    protected function add_error($error) {
        $this->errors[] = $error;
    }

    /**
     * add new argument to the token generated to save user session
     * @param array $token
     * @return array
     */
    public function add_woocommerce_session_handler($token) {
        $token[self::WOOCOMMERCE_CUSTOMER_ID_SESSION] = $this->get_woocommerce_session();

        return $token;
    }

    /**
     * Get Woo-commerce session customer id 
     * 
     * Used when Generating new session for user
     * 
     * @return string
     */
    public function get_woocommerce_session() {
        // Set/renew our cookie 
        $name = self::WOOCOMMERCE_CUSTOMER_ID_SESSION;

        if (isset($GLOBALS['active_token']->$name) && get_current_user_id()) {
            $session_identifier = explode('||', $GLOBALS['active_token']->$name)[0];
            $session_value = $this->get_session($session_identifier);

            if (!empty($session_value)) {
                $this->save_session_data(get_current_user_id(), $session_value);
            }
        }

        $this->set_session_expiration();
        $this->_customer_id = $this->generate_customer_id();

        $to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
        $cookie_hash       = hash_hmac('md5', $to_hash, wp_hash($to_hash));
        $cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;

        return $cookie_value;
    }

    /**
     * Set session expiration.
     */
    public function set_session_expiration() {
        $this->_session_expiring   = time() + intval(apply_filters('wc_session_expiring', 60 * 60 * 47)); // 47 Hours.
        $this->_session_expiration = time() + intval(apply_filters('wc_session_expiration', 60 * 60 * 48)); // 48 Hours.
    }

    /**
     * load user information from token 
     * @global string $active_token
     * @return void
     */
    public function load_user_session_from_token() {
        global $active_token;

        $arg_name = self::WOOCOMMERCE_CUSTOMER_ID_SESSION;

        // Validate user has a valid token and has required argument 
        if (!$active_token || !isset($active_token->$arg_name)) {
            return;
        }

        $this->customer = $active_token->$arg_name;

        if ($cookie = $this->get_session_cookie()) {
            $this->_customer_id        = $cookie[0];
            $this->_session_expiration = $cookie[1];
            $this->_session_expiring   = $cookie[2];
            $this->_has_cookie         = true;

            // Update session if its close to expiring
            if (time() > $this->_session_expiring) {
                $this->set_session_expiration();
                $this->update_session_timestamp($this->_customer_id, $this->_session_expiration);
            }
        } else {
            return false;
        }

        $this->_data = $this->get_session_data();
    }

    /**
     * Generate a unique customer ID for guests, or return user ID if logged in.
     *
     * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
     *
     * @return int|string
     */
    public function generate_customer_id() {
        if (is_user_logged_in()) {
            return get_current_user_id();
        } else {
            include_once ABSPATH . 'wp-includes/class-phpass.php';
            $hasher = new PasswordHash(8, false);
            return md5($hasher->get_random_bytes(32));
        }
    }

    /**
     * Get session data.
     *
     * @return array
     */
    public function get_session_data() {
        return $this->has_session() ? (array) $this->get_session($this->_customer_id, array()) : array();
    }

    /**
     * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
     *
     * @return string
     */
    private function get_cache_prefix() {
        return WC_Cache_Helper::get_cache_prefix(WC_SESSION_CACHE_GROUP);
    }

    /**
     * Save data.
     */
    public function save_data() {
        // Dirty if something changed - prevents saving nothing new
        if ($this->_dirty && $this->has_session()) {

            $this->save_session_data();

            // Mark session clean after saving
            $this->_dirty = false;
        }
    }


    /**
     * save session data providing options to save data 
     * @global object $wpdb
     * @param int    $customer_id
     * @param array  $value
     * @param string $session_expiration
     */
    public function save_session_data($customer_id = false, $value = false, $session_expiration = false) {
        global $wpdb;

        $customer_id        = $customer_id ? $customer_id : $this->_customer_id;
        $value              = $value ? $value : $this->_data;
        $session_expiration = $session_expiration ? $session_expiration : $this->_session_expiration;

        if (is_null($customer_id)) {
            return;
        }

        $wpdb->replace(
            $this->_table,
            array(
                'session_key' => $customer_id,
                'session_value' => maybe_serialize($value),
                'session_expiry' => $session_expiration
            ),
            array(
                '%s',
                '%s',
                '%d',
            )
        );
    }


    /**
     * Destroy all session data.
     */
    public function destroy_session() {

        $this->delete_session($this->_customer_id);

        // Clear cart
        wc_empty_cart();

        // Clear data
        $this->_data        = array();
        $this->_dirty       = false;
        $this->_customer_id = $this->generate_customer_id();
    }

    /**
     * When a user is logged out, ensure they have a unique nonce by using the customer/session ID.
     *
     * @param int $uid
     *
     * @return string
     */
    public function nonce_user_logged_out($uid) {
        return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
    }

    /**
     * Cleanup sessions.
     */
    public function cleanup_sessions() {
        global $wpdb;

        if (!defined('WP_SETUP_CONFIG') && !defined('WP_INSTALLING')) {

            // Delete expired sessions
            $wpdb->query($wpdb->prepare("DELETE FROM $this->_table WHERE session_expiry < %d", time()));

            // Invalidate cache
            WC_Cache_Helper::incr_cache_prefix(WC_SESSION_CACHE_GROUP);
        }
    }

    /**
     * Returns the session.
     *
     * @param  string $customer_id
     * @param  mixed  $default
     * @return string|array
     */
    public function get_session($customer_id, $default = false) {
        global $wpdb;

        if (defined('WP_SETUP_CONFIG')) {
            return false;
        }

        $value = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id));

        if (is_null($value)) {
            $value = $default;
        }

        return maybe_unserialize($value);
    }

    /**
     * Delete the session from the cache and database.
     *
     * @param int $customer_id
     */
    public function delete_session($customer_id) {
        global $wpdb;

        $wpdb->delete(
            $this->_table,
            array(
                'session_key' => $customer_id,
            )
        );
    }

    /**
     * Update the session expiry timestamp.
     *
     * @param string $customer_id
     * @param int    $timestamp
     */
    public function update_session_timestamp($customer_id, $timestamp) {
        global $wpdb;

        $wpdb->update(
            $this->_table,
            array(
                'session_expiry' => $timestamp,
            ),
            array(
                'session_key' => $customer_id,
            ),
            array(
                '%d'
            )
        );
    }
}
