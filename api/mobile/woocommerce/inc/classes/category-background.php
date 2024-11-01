<?php

/**
 * Class to Use the base Class added actions
 *
 * Class Stacks_ProductCategoryAdditionalFields
 */
if (!class_exists('Stacks_ProductCategoryAdditionalFields')) {

    class Stacks_ProductCategoryAdditionalFields extends StacksCustomTaxonomyAdditionalFields {

        protected $taxonomy_slug = 'product_cat';

        const BACKGROUND_IMAGE_NAME = 'product-cat-bg-image-id';

        public function __construct() {
            parent::__construct($this->taxonomy_slug);
        }

        /**
         * Get image src by id 
         * @param int $id
         * @return boolean
         */
        public static function get_image_src_by_id($id, $size) {
            if ($id) {
                $image_src = wp_get_attachment_image_src($id, $size);

                if ($image_src  && is_array($image_src)) {
                    return $image_src[0];
                }
            }
            return false;
        }

        /**
         * Get term thumbnail
         * @param int $term_id
         * @return string|boolean
         */
        public static function get_term_image_id($term_id) {
            return get_term_meta($term_id, self::BACKGROUND_IMAGE_NAME, true);
        }

        /**
         * Get term background image
         * @param int $term_id
         * @return string|boolean
         */
        public static function get_term_background_image_id($term_id) {
            return get_term_meta($term_id, self::BACKGROUND_IMAGE_NAME, true);
        }

        /**
         * Save background image
         * @param int $term_id
         */
        public function save_additional_fields($term_id, $t_id) {
            if (isset($_POST[self::BACKGROUND_IMAGE_NAME]) && '' !== $_POST[self::BACKGROUND_IMAGE_NAME]) {
                $image = sanitize_text_field($_POST[self::BACKGROUND_IMAGE_NAME]);

                update_term_meta($term_id,  self::BACKGROUND_IMAGE_NAME, $image);
            } else {
                update_term_meta($term_id,  self::BACKGROUND_IMAGE_NAME, '');
            }
        }

        /**
         * update taxonomy save additional fields 
         * @param int $term_id
         */
        public function update_taxonomy_save_additional_fields($term_id, $t_id) {
            $this->save_additional_fields($term_id, $t_id);
        }

        /**
         * Update additional fields 
         * @param int $taxonomy
         */
        public function update_taxonomy_add_additional_fields($term, $taxonomy) {
?>
            <!-- this is the section responsible for rendering Background Image -->
            <tr class="form-field term-group-wrap">
                <th scope="row">
                    <label for="product-cat-bg-image-id"><?php _e('App Background Image', 'plates'); ?></label>
                </th>
                <td>
                    <?php $image_id = self::get_term_background_image_id($term->term_id); ?>
                    <input type="hidden" id="<?php echo esc_html(self::BACKGROUND_IMAGE_NAME); ?>" name="<?php echo esc_html(self::BACKGROUND_IMAGE_NAME); ?>" value="<?php echo esc_html($image_id); ?>">
                    <div id="category-image-wrapper-bg">
                        <?php if ($image_id) { ?>
                            <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                        <?php } ?>
                    </div>
                    <p>
                        <input type="button" class="button button-secondary ct_bg_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e('Add Image', 'plates'); ?>" />
                        <input type="button" class="button button-secondary ct_bg_media_remove_bg" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e('Remove Image', 'plates'); ?>" />
                    </p>
                </td>
            </tr>
        <?php
        }

        /**
         * Add form fields to 
         * @param type $taxonomy
         */
        public function add_additional_fields($taxonomy) {
        ?>
            <!-- this is the section responsible for rendering Background Image -->
            <div class="form-field term-group">
                <label for="product-cat-bg-image-id"><?php _e('App Background Image', 'plates'); ?></label>
                <input type="hidden" id="<?php echo self::BACKGROUND_IMAGE_NAME; ?>" name="<?php echo self::BACKGROUND_IMAGE_NAME; ?>" class="custom_media_url" value="">
                <div id="category-image-wrapper-bg"></div>
                <p>
                    <input type="button" class="button button-secondary ct_bg_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e('Add Image', 'plates'); ?>" />
                    <input type="button" class="button button-secondary ct_bg_media_remove_bg" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e('Remove Image', 'plates'); ?>" />
                </p>
            </div>
        <?php
        }

        /**
         * Add script to the footer
         */
        public function add_script() {
        ?>
            <style>
                #category-image-wrapper-bg {
                    vertical-align: middle;
                    background-color: #e8e6e6;
                    padding: 15px;
                    border: 1px solid #dedbdb;
                    margin-bottom: 10px;
                    margin-top: 10px;
                }
            </style>
            <script>
                jQuery(document).ready(function($) {
                    function ct_media_upload(button_class, holder_id, buttons_wrapper) {
                        var _custom_media = true,
                            _orig_send_attachment = wp.media.editor.send.attachment;
                        $('body').on('click', button_class, function(e) {
                            var button_id = '#' + $(this).attr('id');
                            var send_attachment_bkp = wp.media.editor.send.attachment;
                            var button = $(button_id);
                            _custom_media = true;
                            wp.media.editor.send.attachment = function(props, attachment) {
                                if (_custom_media) {
                                    $('#' + holder_id).val(attachment.id);
                                    $('#' + buttons_wrapper).html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                                    $('#' + buttons_wrapper + ' .custom_media_image').attr('src', attachment.url).css('display', 'block');
                                } else {
                                    return _orig_send_attachment.apply(button_id, [props, attachment]);
                                }
                            }
                            wp.media.editor.open(button);
                            return false;
                        });
                    }

                    ct_media_upload('.ct_bg_media_button.button', '<?php echo self::BACKGROUND_IMAGE_NAME; ?>', 'category-image-wrapper-bg');

                    $('body').on('click', '.ct_bg_media_remove_bg', function() {
                        $('#product-cat-bg-image-id').val('');
                        $('#category-image-wrapper-bg').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                    });

                    // Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-category-ajax-response
                    $(document).ajaxComplete(function(event, xhr, settings) {
                        var queryStringArr = settings.data.split('&');
                        if ($.inArray('action=add-tag', queryStringArr) !== -1) {
                            var xml = xhr.responseXML;
                            response = $(xml).find('term_id').text();
                            if (response != "") {
                                // Clear the thumb image
                                $('#category-image-wrapper-bg').html('');
                            }
                        }
                    });
                });
            </script>
<?php
        }
    }

    (new Stacks_ProductCategoryAdditionalFields())->init();
}
