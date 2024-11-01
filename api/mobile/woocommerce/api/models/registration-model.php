<?php

/**
 * RegisterationModel Class 
 * 
 * Responsible for Registering User Using different Registration Providers
 */
class Stacks_RegistrationModel extends Stacks_AbstractModel {

    /**
     * @var Stacks_RegistrationProviderInterface
     */
    protected $registeration_provider = null;

    public function __construct(Stacks_RegistrationProviderInterface $Stacks_RegistrationProviderInterface) {
        $this->registeration_provider = $Stacks_RegistrationProviderInterface;
        return $this;
    }

    public function set_params($parameters) {
        $this->registeration_provider->set_parameters($parameters);
        return $this;
    }

    /**
     * get registration method
     */
    public static function get_user_registeration_type($user_id) {
        $type = get_user_meta($user_id, 'type', true);

        if (!$type) {
            update_user_meta($user_id, 'type', 'manual');
            return 'manual';
        }

        return $type;
    }

    /**
     * collect parameters 
     * @return array
     */
    public function collect_parameters() {
        $success_fetch = $this->registeration_provider->successfully_fetched_user_params();

        if ($success_fetch === true) {
            $params = array(
                'first_name'    => $this->registeration_provider->get_first_name(),
                'last_name'     => $this->registeration_provider->get_last_name(),
                'user_email'    => $this->registeration_provider->get_email(),
                'phone'         => $this->registeration_provider->get_phone(),
                'user_login'    => $this->generate_username_from_email($this->registeration_provider->get_email()),
                'user_pass'     => $this->registeration_provider->get_password(),
                'display_name'  => sprintf('%s %s', $this->registeration_provider->get_first_name(), $this->registeration_provider->get_last_name()),
                'role'          => apply_filters('stacks_woocommerce_default_user_role', 'customer')
            );
            return array('success' => true, 'params' => $params);
        } else {
            return array('success' => false, 'errors' => $success_fetch);
        }
    }

    /**
     * Username generator 
     * @param string $email
     * @return string
     */
    private function generate_username_from_email($email) {
        $parts = explode('@', $email);

        $username = sprintf('%s_%s', $parts[0], uniqid());

        if (!username_exists($username)) {
            return $username;
        }

        return $this->generate_username_from_email($email);
    }

    /**
     * save user 
     * @param array $params
     * @return array
     */
    public function persist($primary_params) {
        $user_id = wp_insert_user($primary_params);

        if (!is_wp_error($user_id)) {
            return array('success' => true, 'user_id' => $user_id);
        }

        return array('success' => false, 'errors' => array($user_id->get_error_message()));
    }

    /**
     * save user meta 
     * @param integer $user_id
     * @param array $meta
     */
    public function persist_user_meta($user_id, $meta) {
        foreach ($meta as $index => $m) {
            add_user_meta($user_id, $index, $m);
        }
    }
}
