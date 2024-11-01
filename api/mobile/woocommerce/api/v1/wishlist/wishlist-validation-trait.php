<?php

/**
 * Wishlist Validation Module 
 * checks if current user can edit or delete or create new wishlist 
 * validate post type 
 */
trait Stacks_WishlistValidationTrait {

    /**
     * check if current user can edit or delete wishlist
     * @param type $wishlist_id
     * @return boolean
     */
    public function current_user_own_wishlist($wishlist_id) {

        $wishlist = new WC_Wishlists_Wishlist($this->get_request_param('wishlist_id'));

        if ($wishlist->get_wishlist_owner() == get_current_user_id()) {
            return true;
        }

        return false;
    }

    /**
     * check if wishlist is activated
     * check if this is a valid wishlist id 
     * check if user can operate on this wishlist
     * @return boolean
     */
    public function validation_middleware($wishlist_id, $product_id = null) {

        if (!self::stacks_is_wishlist_plugin_activated()) {
            return $this->return_addon_not_activated_response();
        }

        if ('wishlist' !== get_post_type($wishlist_id)) {
            return $this->not_valid_wishlist_id_error_response();
        }

        if (!$this->current_user_own_wishlist($wishlist_id)) {
            return $this->get_response_service()->return_unauthorized_user_response();
        }

        return true;
    }
}
