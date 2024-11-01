<?php

/**
 * Stacks_LoginModel Class 
 * 
 * Responsible for login Users Using different login Providers
 */
class Stacks_LoginModel extends Stacks_AbstractModel {

    /**
     * @var Stacks_LoginProvidersInterface
     */
    protected $login_provider = null;

    public function __construct(Stacks_LoginProvidersInterface $Stacks_LoginProvidersInterface) {
        $this->login_provider = $Stacks_LoginProvidersInterface;
        return $this;
    }

    /**
     * set parameters for login provider 
     * @param array $parameters
     * @return $this
     */
    public function set_params($parameters) {
        $this->login_provider->set_parameters($parameters);
        return $this;
    }


    /**
     * collect parameters 
     * @return array
     */
    public function login() {
        $success_fetch = $this->login_provider->successfully_fetched_fields();

        if ($success_fetch === true) {
            $result = $this->login_provider->login();

            return $result;
        } else {
            return array('success' => false, 'errors' => $success_fetch);
        }
    }
}
