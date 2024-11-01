<?php

/**
 * Main Mobile Settings Class 
 */
abstract class Stacks_MobileAppSettings {

    public static function get_settings() {
        if (is_null(static::$settings)) {
            static::$settings = get_option(static::SETTING_NAME);
        }

        return static::$settings;
    }

    public static function get_setting($setting) {
        $settings = static::get_settings();

        if (isset($settings[$setting])) {
            return $settings[$setting];
        }
        return '';
    }

    public static function save($setting_name) {
        if (isset($_POST[$setting_name]) && !empty($_POST[$setting_name])) {
            if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $setting_name)) {
                return;
            }

            update_option(static::SETTING_NAME, sanitize_text_field($_POST[$setting_name]));
        }
    }
}

/**
 * APP Settings Data Manager
 */
class Stacks_AppSettings extends Stacks_MobileAppSettings {

    const SETTING_NAME = 'stacks_app_settings';

    protected static $settings = null;

    public static function get_facebook_app_id() {
        return self::get_setting('facebook_app_id');
    }

    public static function get_facebook_app_name() {
        return self::get_setting('facebook_app_name');
    }

    public static function get_paypal_client_id()
    {
        return self::get_setting( 'paypal_client_id' );
    }
    
    public static function get_paypal_env()
    {
	    return self::get_setting( 'paypal_env' );
    }

    public static function get_enabled_custom_taxonomies() {
        $custom_tax = self::get_setting('custom_taxonomies');

        if ($custom_tax == '') {
            return false;
        }

        return $custom_tax;
    }

    public static function save_submitted_data() {
        parent::save('app_settings');
    }

    public static function get_android_auth_key() {
        return self::get_setting(StacksNotificationFunctions::ANDROID_AUTH_KEY);
    }

    public static function get_android_sender_id() {
        return self::get_setting(StacksNotificationFunctions::ANDROID_SENDER_ID);
    }

    public static function get_google_json_file() {
        return self::get_setting(StacksNotificationFunctions::ANDROID_JSON_FILE);
    }

    public static function get_product_custom_taxonomies() {
        $public_taxonomies = get_taxonomies(array('public' => true, '_builtin' => false));

        $ignored_custom_taxonomies = ['product_cat', 'product_tag', 'product_shipping_class'];

        foreach ($ignored_custom_taxonomies as $ct) {
            unset($public_taxonomies[$ct]);
        }

        return $public_taxonomies;
    }
}


/**
 * Content Settings Data Manager
 */
class Stacks_ContentSettings extends Stacks_MobileAppSettings {

    const SETTING_NAME = 'stacks_content_settings';

    protected static $settings = null;

    const SLIDER_PRODUCTS_NAME = 'slider_products';
    const LOGING_SIGNUP_BG = 'login_signup_background_image';
    const LOGING_SIGNUP_TEXT = 'login_signup_text';
    const POINTS_AND_REWARDS_BG = 'points_rewards_background_image';
    const ABOUT_US_BG = 'about_us_background_image';
    const ABOUT_US_LOGO = 'about_us_logo';
    const ABOUT_US_TEXT = 'about_us_text';
    const CONTACT_US_EMAIL = 'contact_us_email';
    const CONTACT_US_PHONE = 'contact_us_phone';
    const POPULAR_PRODUCTS_NAME = 'home_popular_products';
    const CATEGORIES_NAME = 'home_categories';
    const NEW_PRODUCTS_NAME = 'home_new_products';
    const NEW_PRODUCTS_NAME_STYLE = 'home_new_products_style';
    const POPULAR_CATEGORIES_TITLE_NAME = 'popular_categories_title';
    const NEW_PRODUCTS_TEXT_NAME = 'new_products_text';
    const MAIN_CAT_ENABLED = 'main_cat_enabled';
    const TITLE_NUM_CHARS = 'title_num_chars';
    const TITLE_ENDING_DOTS = 'title_ending_dots';
    const STACKS_EXTRA_APP_CSS = 'stacks_extra_app_css';
    const HOME_SORTING = 'settings_home_sorting';
    const MOBILE_WEBVIEW_LINK = 'mobile_webview_link';

    public static function save_submitted_data() {
        $setting_name = 'content_settings';

        if (isset($_POST[$setting_name]) && !empty($_POST[$setting_name])) {
            if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $setting_name)) {
                return;
            }

            if (isset($_POST[$setting_name][self::HOME_SORTING])) {
                $home_sorting_settings = json_decode(stripslashes($_POST[$setting_name][self::HOME_SORTING]));
                $_POST[$setting_name][self::HOME_SORTING] = self::map_home_settings_sorting($home_sorting_settings);
            }

            update_option(self::SETTING_NAME, sanitize_text_field($_POST[$setting_name]));
        }
    }

    public static function update_settings() {
        static::$settings = null;

        self::get_settings();
    }

    /**
     * get products Array to show on content settings 
     * 
     * @return array
     */
    public static function get_products_array() {
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => -1
        );

        $products = get_posts(apply_filters('before_getting_content_setting_list_products', $args));

        if (!empty($products)) {
            $products_array = array();

            foreach ($products as $product) {
                $products_array[$product->ID] = $product->post_title;
            }

            return $products_array;
        }

        return array();
    }

    public static function get_categories_array() {

        $cat_args = array(
            'orderby'        => 'name',
            'order'        => 'asc',
            'hide_empty'    => false,
            'taxonomy'    => 'product_cat',
            'parent'            => 0
        );

        $product_categories = get_categories($cat_args);

        if (!empty($product_categories)) {
            $product_categories_array = array();

            foreach ($product_categories as $product_category) {
                $product_categories_array[$product_category->term_id] = $product_category->name;
            }

            return $product_categories_array;
        }

        return array();
    }

    public static function get_home_default_sorting_settings() {
        return [
            [
                'id' => 'appSlider',
                'name' => 'Slider',
                'order' => 1,
                'type' => 'home-slider',
                'visiblity' => true
            ],
            [
                'id' => 'appCategories',
                'name' => 'Popular Categories',
                'order' => 2,
                'type' => 'products-list',
                'visiblity' => true
            ],
            [
                'id' => 'appRecent',
                'name' => 'Recent products',
                'order' => 3,
                'type' => 'products-scroller',
                'visiblity' => true
            ]
        ];
    }


    public static function map_home_settings_sorting($home_sorting_settings) {
        $default_sorting = self::get_home_default_sorting_settings();

        foreach ($home_sorting_settings as $index => $setting) {
            // convert to array
            $submitted_settings = (array) $setting;
            $home_sorting_settings[$index] = $submitted_settings;

            // add order 
            $home_sorting_settings[$index]['order'] = $index + 1;

            // get default parameters
            $setting_default_parameters = array_values(array_filter($default_sorting, function ($default) use ($submitted_settings) {
                return $submitted_settings['id'] == $default['id'];
            }));

            // parse missing fields 
            $home_sorting_settings[$index] = wp_parse_args($home_sorting_settings[$index], $setting_default_parameters[0]);
        }

        return $home_sorting_settings;
    }

    public static function get_home_sorting() {
        $home_sorting = self::get_setting(self::HOME_SORTING);

        if (!$home_sorting || empty($home_sorting) ||  gettype($home_sorting) !== 'array') {
            $home_sorting = self::get_home_default_sorting_settings();
        }

        usort($home_sorting, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $home_sorting;
    }

    public static function get_home_slider() {
        $slider = self::get_setting(self::SLIDER_PRODUCTS_NAME);

        if (!$slider || $slider == '') {
            return [];
        }

        return $slider;
    }


    public static function get_home_popular_products() {
        $popular = self::get_setting(self::POPULAR_PRODUCTS_NAME);

        if (!$popular || $popular == '') {
            return [];
        }

        return $popular;
    }

    public static function get_home_categories() {
        $categories = self::get_setting(self::CATEGORIES_NAME);

        if (!$categories || $categories == '') {
            return [];
        }

        return $categories;
    }

    public static function is_main_cat_enabled() {
        $enabled = self::get_setting(self::MAIN_CAT_ENABLED);

        if (!$enabled || $enabled == '' || $enabled == 'disabled') {
            return false;
        }

        return true;
    }


    public static function get_home_new_products() {
        $new = self::get_setting(self::NEW_PRODUCTS_NAME);

        if (!$new || $new == '') {
            return [];
        }

        return $new;
    }

    public static function get_home_new_products_style() {
        $new = self::get_setting(self::NEW_PRODUCTS_NAME_STYLE);

        if (!$new || $new == '') {
            return [];
        }

        return $new;
    }

    public static function get_popular_categories_title() {
        $popular = self::get_setting(self::POPULAR_CATEGORIES_TITLE_NAME);

        if (!$popular || $popular == '') {
            return __('Popular Categories', 'stacks_admin');
        }

        return $popular;
    }

    public static function get_new_products_text() {
        $popular = self::get_setting(self::NEW_PRODUCTS_TEXT_NAME);

        if (!$popular || $popular == '') {
            return __('New Products', 'stacks_admin');
        }

        return $popular;
    }

    public static function get_mobile_webview_link_v1_v2() {
        $mobile_webview_link = self::get_setting(self::NEW_PRODUCTS_TEXT_NAME);

        return $mobile_webview_link;
    }

    public static function get_login_signup_background() {
        $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        return !empty($content_settings->login_singup_background) ? $content_settings->login_singup_background : '';
    }
    public static function get_login_signup_background_v1_v2() {
        return self::get_setting(self::LOGING_SIGNUP_BG);
    }

    public static function get_login_signup_text() {
        $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        return !empty($content_settings->login_singup_text) ? $content_settings->login_singup_text : '';
    }
    public static function get_login_signup_text_v1_v2() {
        return self::get_setting(self::LOGING_SIGNUP_TEXT);
    }


    public static function get_points_rewards_background() {
        $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        return !empty($content_settings->points_rewards_background) ? $content_settings->points_rewards_background : '';
    }
    public static function get_points_rewards_background_v1_v2() {
        return self::get_setting(self::POINTS_AND_REWARDS_BG);
    }

    public static function get_about_us_background() {
        $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        return !empty($content_settings->about_us_background) ? $content_settings->about_us_background : '';
    }
    public static function get_about_us_background_v1_v2() {
        return self::get_setting(self::ABOUT_US_BG);
    }

    public static function get_about_us_site_logo() {
        $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        return !empty($content_settings->logo) ? $content_settings->logo : '';
    }
    public static function get_about_us_site_logo_v1_v2() {
        return self::get_setting(self::ABOUT_US_LOGO);
    }

    public static function get_about_text() {
        $content_settings = $GLOBALS['builder_api']->stacks_get_multisite_option('content_settings');
        return !empty($content_settings->about_us_text) ? $content_settings->about_us_text : '';
    }
    public static function get_about_text_v1_v2() {
        return self::get_setting(self::ABOUT_US_TEXT);
    }

    public static function get_contact_us_email() {
        return self::get_setting(self::CONTACT_US_EMAIL);
    }

    public static function get_contact_us_phone() {
        return self::get_setting(self::CONTACT_US_PHONE);
    }

    public static function get_formatting_title_num_chars() {
        return self::get_setting(self::TITLE_NUM_CHARS);
    }

    public static function get_formatting_title_ending_dots() {
        return self::get_setting(self::TITLE_ENDING_DOTS);
    }

    public static function get_extra_app_css() {
        return self::get_setting(self::STACKS_EXTRA_APP_CSS);
    }


    public static function get_available_home_layouts() {
        return apply_filters('app_settings_home_layouts', [
            [
                'id' => 0,
                'title' => 'Categories Layout',
                'img'   => OUTLETS_APP_PLUGIN_URL . 'assets/img/app-builder/presentation_screens/home_categories.png'
            ],
            [
                'id' => 1,
                'title' => 'Slider Layout',
                'img'   => OUTLETS_APP_PLUGIN_URL . 'assets/img/app-builder/presentation_screens/home_slider.png'
            ]
        ]);
    }

    public static function get_home_layout() {
        $home_layout = self::get_setting('home_layout');

        if (!$home_layout || is_null($home_layout) || $home_layout == '') {
            $available_home_layouts = self::get_available_home_layouts();

            return $available_home_layouts[0]['id'];
        }

        return (int) $home_layout;
    }
}

// Save Fields if Required
Stacks_AppSettings::save_submitted_data();
Stacks_ContentSettings::save_submitted_data();
