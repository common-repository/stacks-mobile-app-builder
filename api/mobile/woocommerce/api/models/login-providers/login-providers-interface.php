<?php

interface Stacks_LoginProvidersInterface {

    public function set_parameters($parameters);

    public function successfully_fetched_fields();

    public function get_email();

    public function login();
}
