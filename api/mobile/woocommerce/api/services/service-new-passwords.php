<?php

class NewPasswordsService {
    /** 
     * @var array
     */
    public static $new_passwords_option = 'stacks_activate_new_passwords';

    /**
     * saved new passwords 
     * @var array 
     */
    public static $new_passwords_array = null;

    /**
     * Date Format used to be accessible across the system
     * @var string 
     */
    public static $date_format = 'Y-m-d H:i:s';

    /**
     * save new passwords 
     * 
     * @param array $passwords
     */
    public static function save_new_passwords($passwords) {
        return update_option(self::$new_passwords_option, $passwords);
    }

    /**
     * save new password
     * 
     * @param array $password
     */
    public static function save_new_password($password) {
        $new_passwords = self::get_new_passwords();

        if (!empty($new_passwords)) {
            self::remove_user_records($password['user_id']);

            $new_passwords = self::get_new_passwords();
        }

        $new_passwords[] = $password;

        return self::save_new_passwords($new_passwords);
    }

    /**
     * Get saved new passwords 
     * 
     * @return array
     */
    public static function get_new_passwords() {
        return get_option(self::$new_passwords_option, array());
    }

    /**
     * check if hash exists 
     * 
     * @param string $hash
     * @return boolean
     */
    public static function is_hash_exists($hash) {
        $new_passwords = self::get_new_passwords();

        foreach ($new_passwords as $new_password) {
            if ($hash == $new_password['hash']) {
                return $new_password;
            }
        }
        return false;
    }

    /**
     * return whether hash expired or not 
     * 
     * @param string $hash
     * @return boolean|string
     */
    public static function is_hash_expired($hash) {
        $new_password = self::is_hash_exists($hash);

        if ($new_password) {
            $expires = $new_password['expiration_date'];

            if (Stacks_Date_Manager::is_timestamp_passed($expires)) {
                return true;
            }

            return false;
        }

        return 'unvalid';
    }

    /**
     * creates new record in database 
     * @param int $user_id
     * @param string $password
     * @return boolean
     */
    public static function create_new_password_record($user_id, $password) {
        $expire_date = Stacks_Date_Manager::get_strtotime_for_date(5);

        $new_password = array(
            'expiration_date'   => $expire_date,
            'hash'              => uniqid(),
            'user_id'           => $user_id,
            'password'          => $password,
            'os'                => RequestDataService::getOS(),
            'browser'           => RequestDataService::getBrowser()
        );

        self::save_new_password($new_password);

        return $new_password;
    }

    /**
     * remove user records 
     * 
     * @param int $user_id
     * @return array
     */
    public static function remove_user_records($user_id) {
        // user can not have two hashes 
        $new_passwords = self::get_new_passwords();

        if (!empty($new_passwords)) {
            foreach ($new_passwords as $index => $new_password) {
                if ($user_id == $new_password['user_id']) {
                    unset($new_passwords[$index]);
                }
            }
        }

        return self::save_new_passwords(array_values($new_passwords));
    }

    /**
     * activate record 
     * 
     * @param string $record
     * @return boolean
     */
    public static function acrivate_record($record) {
        $user_id    = $record['user_id'];
        $password   = $record['password'];

        wp_set_password($password, $user_id);

        self::remove_user_records($user_id);

        return true;
    }
}

class Stacks_Date_Manager {
    public static function is_timestamp_passed($timestamp) {
        $datetime = new DateTime('NOW');
        $datetime->setTimezone(new DateTimeZone(self::get_timezone()));
        $nowTimestamp = $datetime->getTimestamp();

        return $timestamp < $nowTimestamp;
    }

    public static function get_timezone() {
        return wp_timezone_string();
    }

    public static function get_strtotime_for_date($num_days) {
        $date_time = new DateTime('NOW');

        $date_time->setTimezone(new DateTimeZone(self::get_timezone()));

        $modified_time = $date_time->modify(sprintf('+%s day', $num_days));

        return $modified_time->getTimestamp();
    }
}
