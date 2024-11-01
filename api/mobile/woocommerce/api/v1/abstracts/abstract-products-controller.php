<?php

/**
 * Base Validation Class for Products 
 */
abstract class Stacks_AbstractProductsController extends Stacks_AbstractController {

    /**
     * Allowed sorting algorithm
     */
    const ALLOWED_SORTING = array('price', 'date');

    /**
     * Allowed Ordering algorithm
     */
    const ALLOWED_ORDERING = array('asc', 'desc');

    /**
     * this default order used when user set sort but do not set order
     */
    const DEFAULT_ORDERING = 'desc';

    /**
     * Type of Taxonomy we are working on 
     * 
     * @var string 
     */
    protected $type = 'product';

    /**
     * @var Stacks_ProductsModel 
     */
    protected $model = null;

    /**
     * Get categories model instance
     * @return \Stacks_CategoriesModel
     */
    public function get_model_instance() {
        if (is_null($this->model)) {
            $this->model = new Stacks_ProductsModel();
        }
        return $this;
    }

    /**
     * check if sorting sent from user is valid
     * 
     * @return boolean
     */
    protected function is_valid_sorting($param) {
        // user sent the sort parameter
        $sort = $this->get_request_param($param);

        if ($sort) {
            // sort parameter not valid
            if (!in_array($sort, static::ALLOWED_SORTING)) {
                $this->set_error($this->invalid_parameter_exception($param)->get_error_message());
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * check if ordering sent from user is valid
     * @return void
     */
    protected function is_valid_order($param) {
        $order = $this->get_request_param($param);
        /**
         * User sent sort but he did not send order 
         * then set the default value 
         */
        if (!$order && $this->get_request_param('sort')) {
            // if user provided sort but did not provide order desc or asc set the default value 
            $this->set_request_param($param, static::DEFAULT_ORDERING);

            return true;
        } else {
            // throw error if not user pass a valid order 
            if (in_array($order, static::ALLOWED_ORDERING)) {
                return true;
            } elseif ($order !== false) {
                $this->set_error($this->invalid_parameter_exception($param)->get_error_message());
            }
        }
        return false;
    }

    /**
     * validate user sent parameter are valid 
     * 
     * @return boolean
     */
    protected function is_valid_ids($param) {
        $parameter_value = $this->get_request_param($param);

        // here we depend on the validation of wp rest api 
        return $parameter_value ? true : false;
    }

    /**
     * Validate Range have certain properties 
     * @param string $param
     * @return boolean
     */
    protected function is_valid_range($param) {
        $param_range = $this->get_request_param($param);

        if ($param_range) {
            if (
                isset($param_range['upper']) &&
                isset($param_range['lower'])
            ) {
                return true;
            } else {
                $this->set_error($this->invalid_parameter_exception($param)->get_error_message());
            }
        }
        return false;
    }

    /**
     * validate if integer parameter is valid 
     * @param string $param
     * @return boolean
     */
    protected function is_valid_integer($param) {
        $integer = absint($this->get_request_param($param));

        return $integer ? true : false;
    }

    /**
     * validate if string parameter is valid 
     * @param string $param
     * @return boolean
     */
    protected function is_valid_string($param) {
        $param_value = $this->get_request_param($param);
        if ($param_value &&  $param_value !== '' && $param_value !== false) {
            return true;
        }
        return false;
    }

    /**
     * validate if parameter is boolean
     * @param string $param
     * @return boolean
     */
    protected function is_valid_boolean($param) {
        $param_value = $this->get_request_param($param);
        if ($param_value && is_bool($param)) {
            return true;
        }
        return false;
    }

    /**
     * validate if parameter is valid array
     * 
     * @param string $param
     * @return boolean
     */
    protected function is_valid_array($param) {
        $param_value = $this->get_request_param($param);

        if (is_array($param_value) && !empty($param_value)) {
            return true;
        }

        return false;
    }

    /**
     * Validate sent Parameters 
     */
    public function validate_and_add_parameters() {
        if ($this->is_valid_sorting('sort')) {
            $this->model->set_sort($this->get_request_param('sort'));
        }

        if ($this->is_valid_order('order')) {
            $this->model->set_order($this->get_request_param('order'));
        }

        if ($this->is_valid_ids('cats')) {
            $this->model->set_categories($this->get_request_param('cats'));
        }

        if ($this->is_valid_array('custom_taxonomies')) {
            $this->model->set_custom_taxonomies($this->get_request_param('custom_taxonomies'));
        }

        if ($this->is_valid_range('calories_range')) {
            $this->model->set_calories($this->get_request_param('calories_range'));
        }

        if ($this->is_valid_range('price_range')) {
            $this->model->set_price_range($this->get_request_param('price_range'));
        }

        if ($this->is_valid_integer('per_page')) {
            $this->model->set_per_page($this->get_request_param('per_page'));
        }

        if ($this->is_valid_integer('offset')) {
            $this->model->set_offset($this->get_request_param('offset'));
        }

        if ($this->is_valid_string('featured_image_size')) {
            $this->model->set_featured_image_size($this->get_request_param('featured_image_size'));
        }

        if ($this->get_request_param('sale')) {
            $this->model->set_sale_filter($this->get_request_param('sale'));
        }

        if ($this->get_request_param('keyword')) {
            $this->model->set_keyword_filter($this->get_request_param('keyword'));
        }

        return $this;
    }


    /**
     * Options to pass to limit the response
     * 
     * @return string
     */
    protected function get_collection_params() {
        $params = array();

        $params['sort'] = array(
            'description'       => __('Define sorting algorithm you would like to use [ "price","date" ] if order parameter not set by default we will sort desc', 'woocommerce'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['order'] = array(
            'description'       => __('Determine sorting direction desc or asc', 'woocommerce'),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['categories'] = array(
            'description'  => __('List of category ids "product categories"', 'plates'),
            'type'         => 'array',
            'items'        => array('type' => 'int'),
            'context'      => array('view')
        );

        $params['price_range'] = array(
            'description'  => __('Specify the higher and lower limit for products price[ "upper","lower" ]', 'plates'),
            'type'         => 'object',
            'items'        => array('type' => 'int'),
            'context'      => array('view')
        );

        $params['featured_image_size'] = array(
            'description'  => __('Specify the size of product image you need', 'plates'),
            'type'         => 'string',
        );

        $params['sale'] = array(
            'description'  => __('Specify you want just on sale products', 'plates'),
            'type'         => 'bool',
        );

        return $params;
    }

    /**
     * the schema for the request 
     * 
     * @return string
     */
    protected function get_public_item_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => $this->type,
            'type'                 => 'object',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__('Unique identifier for the object.', 'plates'),
                    'type'         => 'integer',
                    'context'      => array('view'),
                    'readonly'     => true,
                )
            ),
        );

        return $schema;
    }
}
