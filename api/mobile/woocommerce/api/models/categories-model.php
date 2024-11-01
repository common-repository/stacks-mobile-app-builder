<?php

/**
 * Stacks_CategoriesModel Class 
 * 
 * Responsible for returning categories structured and send complete object to the controller 
 * and it's the controller mission to customize the return data 
 */
class Stacks_CategoriesModel extends Stacks_AbstractModel {

    /**
     * Nesting categories name 
     */
    protected $children_name = 'children';

    /**
     * Nesting Taxonomy name 
     */
    protected $taxonomy_name = 'product_cat';

    /**
     * Category you would like to get it's children
     * @var integer 
     */
    protected $cat_id = 0;

    /**
     * Whether You would like to get all categories or just categories have products 
     * 
     * @var boolean 
     */
    protected $all = false;

    /**
     * Set all parameter
     * 
     * @param string $all
     */
    public function set_all($all) {
        if (!is_null($all)) {
            $this->all = $all;
        }

        return $this;
    }

    /**
     * Get all parameter
     * 
     * @return string
     */
    public function get_all() {
        return $this->all;
    }

    /**
     * Set Children name 
     * 
     * @param string $name
     */
    public function set_children_name($name) {
        $this->children_name = $name;

        return $this;
    }

    /**
     * Get Children name 
     * 
     * @return string
     */
    public function get_children_name() {
        return $this->children_name;
    }

    /**
     * Set taxonomy name 
     * 
     * @param string $name
     */
    public function set_taxonomy_name($name) {
        $this->taxonomy_name = $name;

        return $this;
    }

    /**
     * Get taxonomy name 
     * 
     * @return string
     */
    public function get_taxonomy_name() {
        return $this->taxonomy_name;
    }


    /**
     * Set category id 
     * 
     * @param int $cat_id
     * @return $this
     */
    public function set_category_id($cat_id) {
        $this->cat_id = $cat_id;

        return $this;
    }

    /**
     * Get array of uncategorized terms 
     * 
     * @return array
     */
    public function get_uncategoriezed_term_id() {
        $terms = array_values(get_terms(['taxonomy' => 'product_cat', 'lang' => '']));

        $uncategorized_terms_ids = [];

        if (!empty($terms)) {
            foreach ($terms as $term) {
                $pos = strpos($term->slug, 'uncategorized');

                if ($pos === 0 || $pos > 0) {
                    $uncategorized_terms_ids[] = $term->term_id;
                }
            }
        }

        return $uncategorized_terms_ids;
    }

    /**
     * Get Products Categories
     * 
     * @param callable $format_callback
     * @param type $children_name
     * @return array
     */
    public function get_product_categories() {
        $uncategoriezed_term_id = $this->get_uncategoriezed_term_id();

        $args = ['taxonomy' => $this->get_taxonomy_name(), 'hide_empty' => !$this->get_all()];

        if ($uncategoriezed_term_id) {
            $args = array_merge($args, array('exclude' => $uncategoriezed_term_id));
        }

        $categories = apply_filters('api_after_getting_cats', get_terms(apply_filters('api_before_getting_taxs', $args)));

        $items = [];

        if (is_array($this->cat_id) && !empty($this->cat_id)) {
            $children_name = $this->get_children_name();

            foreach ($this->cat_id as $cat) {
                $data = [];

                $this->getCategoryChildrensRecursive($categories, $data, $cat->term_id);

                $cat = $this->apply_format_callback_item($cat);

                $cat[$children_name] = $data;

                $items[] = $cat;
            }
        } else {
            $parent_id = (int) $this->cat_id;

            $this->getCategoryChildrensRecursive($categories, $items, $parent_id);
        }

        return $items;
    }

    /**
     * Recursive Iterator over elements to nest sub categories inside categories 
     * 
     * @param array $cats
     * @param array $into
     * @param int $parentId
     * @return void
     */
    private function getCategoryChildrensRecursive(array &$cats, array &$into, $parentId = 0) {
        foreach ($cats as $i => $cat) {
            if ($cat->parent == $parentId) {
                $into[] = $this->apply_format_callback_item($cat);
                unset($cats[$i]);
            }
        }

        foreach ($into as $index => $topCat) {
            // we have a formatter applied 
            if ($this->has_callback_function()) {
                $into[$index][$this->children_name] = array();
                $this->getCategoryChildrensRecursive($cats, $into[$index][$this->children_name], $topCat['key']);
            } else {
                // we do not have a formatter apply it as it's 
                $child_name = $this->children_name;
                $topCat->$child_name = array();
                $this->getCategoryChildrensRecursive($cats, $topCat->$child_name, $topCat->term_id);
            }
        }
    }
}
