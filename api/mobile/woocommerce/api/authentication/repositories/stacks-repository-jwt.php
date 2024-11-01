<?php

/**
 * Requires JWT library.
 */

use Firebase\JWT\JWT;

/**
 * Class Stacks_Repository_JWT
 * Responsible of all the Interactions between the app and JWT
 */
final class Stacks_Repository_JWT {

    protected static $instance = null;

    const JWT_CONFIG = array(
        'secret'    => '!@()*&jwT$rEPOSitory#@!',
        'hash_alg'  => 'HS256'
    );

    const ANONYMOUS_USER_CREDENTIALS = array(
        'id'        => 'anonymous',
        'username'  => 'anonymous'
    );

    /**
     * @var string|false
     */
    protected $token = null;

    /**
     * @var \WP_User
     */
    protected $user = null;

    /**
     * @var array
     */
    public $errors = [];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * All authentication Services should have a way to authenticate user and set define him once request comes 
     * Define user for wordpress when get_current_user function called
     */
    public function authenticate() {
        if ($this->validate_user_token() && null !== $this->user) {
            return $this->user->ID;
        }
        return false;
    }

    /**
     * @param array $payload
     * @return array|string
     */
    private function generate_token($payload = array()) {
        $not_before = time();

        $expire = time() + (DAY_IN_SECONDS * apply_filters('stacks_jwt_expire_num_days', 360));

        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => time(),
            'nbf' => $not_before,
            'exp' => $expire,
            'data' => $payload,
        );

        $applied_token = apply_filters('before_encoding_token', $token);

        /**
         * Let the user modify the token data before the sign.
         */
        $encoded_token = JWT::encode($applied_token, self::JWT_CONFIG['secret'], self::JWT_CONFIG['hash_alg']);

        return $encoded_token;
    }

    /**
     * Generates Anonymous Token Which can be used within the system
     *
     * @return array|string
     */
    public function generate_anonymous_token() {
        $payload = array(
            'user' => array(
                'id' => self::ANONYMOUS_USER_CREDENTIALS['id'],
                'username' => self::ANONYMOUS_USER_CREDENTIALS['username']
            ),
        );

        return $this->generate_token($payload);
    }

    /**
     * Generates Token for User
     * @param $user
     * @return array|string
     */
    public function generate_user_token($user) {
        if (!$user || !($user instanceof \WP_User)) {
            $this->errors[] = 'invalid_user';
            return $this->errors;
        }

        $payload = array(
            'user' => array(
                'id' => $user->data->ID,
                'username' => $user->data->user_login
            ),
        );

        return $this->generate_token($payload);
    }

    /**
     * Main Token Validation Function
     *
     * @param $validation_callback
     * @return array|bool
     */
    protected function validate_token($validation_callback) {
        $token = $this->token;

        if ($token) {

            try {
                $decoded_token = JWT::decode($token, self::JWT_CONFIG['secret'], array(self::JWT_CONFIG['hash_alg']));
                /** The Token is decoded now validate the iss */

                $GLOBALS['active_token'] = $decoded_token;

                if (call_user_func([$this, $validation_callback], $decoded_token) && apply_filters('_stacks_validate_token', true)) {
                    return true;
                }
                return false;
            } catch (\Firebase\JWT\ExpiredException $expired) {
                /** Something is wrong trying to decode the token, send back the error */
                $this->errors[] = 'token_expired';
            } catch (\DomainException $e) {
                $this->errors[] = 'domain_not_valid';
            } catch (\Exception $e) {
                $this->errors[] =  $e->getMessage();
            }
        } else {
            $this->errors[] = 'token not exits';
        }


        return $this->errors;
    }

    /**
     * Validate User Token
     *
     * @return array|bool
     */
    public function validate_user_token() {
        $this->empty_errors();

        $this->get_token();

        $validation_result = $this->validate_token('is_token_has_valid_user');

        if (!empty($this->errors) || !$validation_result) {
            return false;
        }

        return true;
    }

    /**
     * Validate Anonymous Token
     *
     * @return bool|array
     */
    public function validate_anonymous_token() {
        $this->empty_errors();

        $this->get_token();

        $validation_result = $this->validate_token('is_token_anonymous');

        if (!empty($this->errors) || !$validation_result) {
            return false;
        }

        return true;
    }

    /**
     * empty errors 
     */
    private function empty_errors() {
        $this->errors = [];
    }

    /**
     * Checks if the user in token is valid and has valid credentials
     * @param $token
     * @return bool
     */
    public function is_token_has_valid_user($token) {
        if ($token !== '' && $token && is_object($token)) {
            if (
                isset($token->data->user->id) &&
                isset($token->data->user->username) &&
                $this->is_valid_user($token->data->user)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function is_valid_user($user) {
        $user_id = $user->id;

        $user_name = $user->username;

        $user = get_userdata($user_id);

        // if user
        if ($user !== false) {
            // with the same username
            if ($user->user_login == $user_name) {
                $this->user = $user;
                return true;
            }
        }

        return false;
    }

    /**
     * check if token is anonymous or for user
     * @param $token
     * @return bool
     */
    protected function is_token_anonymous($token) {
        if ($token !== '' && $token && is_object($token)) {
            if ($token->data->user->id == self::ANONYMOUS_USER_CREDENTIALS['id'] && $token->data->user->username == self::ANONYMOUS_USER_CREDENTIALS['username']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract Authorization header from request
     * @return bool | string
     */
    public function get_token() {
        if (is_null($this->token) || $this->token == false) {
            $token = isset($_SERVER['HTTP_AUTHORIZATION']) ?  sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']) : false;

            /* Double check for different auth header string (server dependent) */
            if (!$token) {
                $token = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ?  sanitize_text_field($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) : false;
            }

            // user did not send token
            if ($token) {
                list($token) = sscanf($token, 'Bearer %s');
            } else {
                if (function_exists('apache_request_headers')) {
                    $apache_headers = apache_request_headers();
                    $token = !empty($apache_headers['Authorization']) ? $apache_headers['Authorization'] : false;
                    $token = str_replace('Bearer ', '', $token);
                } else {
                    $token = false;
                }
            }

            $this->token = apply_filters('stacks_wc_rest_api_token', $token);
        }

        if (is_null($this->token) || $this->token == false) {
            $token = isset($_SERVER['HTTP_STACKSAUTHORIZATION']) ?  sanitize_text_field($_SERVER['HTTP_STACKSAUTHORIZATION']) : false;

            /* Double check for different auth header string (server dependent) */
            if (!$token) {
                $token = isset($_SERVER['REDIRECT_HTTP_STACKSAUTHORIZATION']) ?  sanitize_text_field($_SERVER['REDIRECT_HTTP_STACKSAUTHORIZATION']) : false;
            }

            // user did not send token
            if ($token) {
                list($token) = sscanf($token, 'Bearer %s');
            } else {
                if (function_exists('apache_request_headers')) {
                    $apache_headers = apache_request_headers();
                    $token = !empty($apache_headers['stacksAuthorization']) ? $apache_headers['stacksAuthorization'] : false;
                    $token = str_replace('Bearer ', '', $token);
                } else {
                    $token = false;
                }
            }

            $this->token = apply_filters('stacks_wc_rest_api_token', $token);
        }

        if (is_null($this->token) || $this->token == false) {
            if( !empty($_SERVER['REDIRECT_QUERY_STRING']) ) {
                $query_string = $_SERVER['REDIRECT_QUERY_STRING'];
            } else {
                $query_string = $_SERVER['QUERY_STRING'];
            }
            
            parse_str($query_string, $parts);
            $token = !empty( $parts['stacksAuthorization'] ) ? $parts['stacksAuthorization'] : null;
            // $token = isset($_SERVER['HTTP_STACKSAUTHORIZATION']) ?  sanitize_text_field($_SERVER['HTTP_STACKSAUTHORIZATION']) : false;

            /* Double check for different auth header string (server dependent) */
            if (!$token) {
                $token = isset($_SERVER['REDIRECT_HTTP_STACKSAUTHORIZATION']) ?  sanitize_text_field($_SERVER['REDIRECT_HTTP_STACKSAUTHORIZATION']) : false;
            }

            // user did not send token
            if ($token) {
                list($token) = sscanf($token, 'Bearer %s');
            } else {
                if (function_exists('apache_request_headers')) {
                    $apache_headers = apache_request_headers();
                    $token = !empty($apache_headers['stacksAuthorization']) ? $apache_headers['stacksAuthorization'] : false;
                    $token = str_replace('Bearer ', '', $token);
                } else {
                    $token = false;
                }
            }

            $this->token = apply_filters('stacks_wc_rest_api_token', $token);
        }

        return $this->token;
    }

    public function set_token($token) {
        $this->token = $token;
    }
}
