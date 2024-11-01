<?php

require_once 'polylang-stacks-integration.php';
require_once 'builder_translations.php';
class Polylang_Stacks_Strings_translation {

    protected $strings = [];
    protected $group = 'polylang';
    protected $issuer = 'stacks';
    protected static $instance = null;

    public function add($string) {
        $this->strings[] = $string;

        return $this;
    }

    public function get_strings() {
        return $this->strings;
    }

    public function set_group($group) {
        $this->group = $group;

        return $this;
    }

    public function set_issuer($issuer) {
        $this->issuer = $issuer;

        return $this;
    }

    public static function is_installed() {
        return Stacks_PolylangIntegration::is_installed();
    }

    public function translate_strings() {
        if (!self::is_installed()) {
            return;
        }

        foreach ($this->strings as $string) {
            pll_register_string($this->issuer, $string, $this->group);
        }
    }

    public static function get_string_translation($string) {
        if (!self::is_installed()) {
            return $string;
        }

        return pll_translate_string($string, get_locale());
    }

    /**
     * @return Polylang_Stacks_Strings_translation
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function register_string($string, $name, $group) {
        if (!is_admin()) {
            return;
        }

        $instance = self::get_instance();
        $instance->add($string)->translate_strings($string);
    }
}

Polylang_Stacks_Strings_translation::get_instance()->add(Stacks_ContentSettings::get_popular_categories_title())->set_group('content settings')->set_issuer('stacks')->translate_strings();
Polylang_Stacks_Strings_translation::get_instance()->add(Stacks_ContentSettings::get_new_products_text())->set_group('content settings')->set_issuer('stacks')->translate_strings();
