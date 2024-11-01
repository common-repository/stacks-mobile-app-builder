<?php

/**
 * Base class
 **/
if (!class_exists('StacksCustomTaxonomyAdditionalFields')) {

    abstract class StacksCustomTaxonomyAdditionalFields {

        protected $taxonomy_slug;

        public function __construct($taxonomy_slug) {
            $this->taxonomy_slug = $taxonomy_slug;

            $this->init();
        }

        /*
		 * Initialize the class and start calling our hooks and filters
		 * @since 1.0.0
		*/
        public function init() {
            add_action("{$this->taxonomy_slug}_add_form_fields", array($this, 'add_additional_fields'), 10, 2);
            add_action("created_{$this->taxonomy_slug}", array($this, 'save_additional_fields'), 10, 2);
            add_action("{$this->taxonomy_slug}_edit_form_fields", array($this, 'update_taxonomy_add_additional_fields'), 10, 2);
            add_action("edited_{$this->taxonomy_slug}", array($this, 'update_taxonomy_save_additional_fields'), 10, 2);

            add_action('admin_enqueue_scripts', array($this, 'load_media'));
            add_action('admin_footer', array($this, 'add_script'));
        }

        public function load_media() {
            wp_enqueue_media();
        }

        /*
		 * Add a form field in the new category page
		 * @since 1.0.0
		*/
        public function add_additional_fields($taxonomy) {
            do_action("create_{$this->taxonomy_slug}_term_add_additional_form_fields", $taxonomy);
        }

        /*
		 * Save the form field
		 * @since 1.0.0
		*/
        public function save_additional_fields($term_id, $t_id) {
            do_action("create_{$this->taxonomy_slug}_term_save_additional_form_fields", $term_id, $t_id);
        }

        /*
		 * Edit the form field
		 * @since 1.0.0
		*/
        public function update_taxonomy_add_additional_fields($term, $taxonomy) {
            do_action("update_{$this->taxonomy_slug}_term_add_additional_form_fields", $term, $taxonomy);
        }

        /*
		 * Update the form field value
		 * @since 1.0.0
		 */
        public function update_taxonomy_save_additional_fields($term_id, $t_id) {
            do_action("update_{$this->taxonomy_slug}_term_save_additional_form_fields", $term_id, $t_id);
        }

        /*
		 * Add script
		 * @since 1.0.0
		 */
        public function add_script() {
            do_action("create_or_update_{$this->taxonomy_slug}_custom_scripts");
        }
    }
}
