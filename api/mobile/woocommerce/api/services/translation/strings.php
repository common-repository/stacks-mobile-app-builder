<?php

class Translatable_Strings {

	/**
	 * get code message 
	 * 
	 * @param string $code
	 * 
	 * @return boolean|string
	 */
	public static function get_code_message($code) {
		if (in_array($code, array_keys(self::get_messages()))) {
			$strings = self::get_messages();

			return $strings[$code];
		}

		return false;
	}

	/**
	 * Translatable Strings
	 * 
	 * @return array
	 */
	public static function get_messages() {
		return [
			'coupon_does_not_exist'				=> __('Requested Coupon does not exist', 'plates'),
			'coupon_does_not_applied'				=> __('this coupon is not applied', 'plates'),
			'coupon_might_be_expired'				=> __('this coupon might be expired', 'plates'),
			'invalid_parameters_message'			=> __('Unexpected Error Happened please if this issue persists contact us', 'plates'),
			'unexpected_error_message'				=> __('Unexpected Error while we were trying to process your request please try again or contact our support', 'plates'),
			'some_addons_are_required'				=> __('Some Add ons are required', 'plates'),
			'order_do_not_belong_user'				=> __('this order do not belong to you if you think this is a mistake do not hesitate to contact us', 'plates'),
			'order_does_not_exist'				=> __('this order identifier is not valid', 'plates'),
			'product_out_of_stock'				=> __('sorry this product might be out of stock', 'plates'),
			'unauthorized_access'				=> __('Sorry, you cannot list resources.', 'plates'),
			'invalid_product_id'				=> __('invalid product id', 'plates'),
			'not_valid_variation_id_message'			=> __('invalid variation id', 'plates'),
			'addon_not_activated'				=> __('this addon not activated', 'plates'),
			'you_do_not_have_points'				=> __('you don\'t have points to redeem', 'plates'),
			'no_variations_for_simple_product'			=> __('Simple Product does not have variations', 'plates'),
			'points_discount_already_applied_message'		=> __('You already has points discount applied', 'plates'),
			'coupons_disabled_message'				=> __('Sorry but we do not accept coupons right now', 'plates'),
			'coupon_already_applied_message'			=> __('this coupon is already applied', 'plates'),
			'coupons_does_no_exist_message'			=> __('this coupon does not exist', 'plates'),
			'partial_redemption_not_enabled'			=> __('sorry but we do not allow partial Redemptions', 'plates'),
			'points_redeemed_successfully'			=> __('Discount Applied successfully', 'plates'),
			'invalid_postal_code'				=> __('Please enter a valid postcode / ZIP', 'plates'),
			'success_retireve_shipping_methods'			=> __('Here\'s shipping methods associated to this location', 'plates'),
			'no_shipping_methods_associated'			=> __('No shipping methods Associated with this location', 'plates'),
			'login_successfull'					=> __('User logged in successfully', 'plates'),
			'registeration_successfull'				=> __('You have Registered successfully', 'plates'),
			'invalid_credentials'				=> __('Invalid Credentials', 'plates'),
			'duplicate_email_registeration_error'		=> __('Another user registered with the same email please choose forgot password or add another email', 'plates'),
			'email_already_registered'				=> __('This Email already registered before.', 'plates'),
			'forgot_password_token_expired'			=> __('This record has expired.', 'plates'),
			'forgot_password_token_not_found'			=> __('No records Match Your token.', 'plates'),
			'forgot_password_no_users_found'			=> __('No Users Found matching this mail or phone.', 'plates'),
			'forgot_password_error_Sending_email'		=> __('Sorry we could not send email to this mail please try again.', 'plates'),
			'apply_coupon_not_yours'				=> __('This coupon can\'t be applied for your order, please signup/login first and try again', 'plates'),
			'coupons_is_not_active'				=> __('Sorry but coupons is not activated.', 'plates'),
			'coupon_is_not_valid'				=> __('Sorry this coupon is not valid.', 'plates'),
			'variation_does_not_exist'				=> __('Sorry this combination does not exist', 'plates'),
			'login_failed_fetch_data'				=> __('Sorry we couldn\'t fetch your data', 'plates'),
			'registeration_method_mismatch'			=> __('This user is registered using another method', 'plates'),
			'wrong_password'					=> __('the provided password is wrong.', 'plates'),
			'no_users_found_matching'				=> __('No Users Found matching this mail or phone.', 'plates'),
			'could_not_fetch_data'				=> __('Sorry We could not fetch your data from registration service', 'plates'),
			'data_returned_successfully'			=> __('Data Returned Successfully', 'plates'),
			'change_password_subject'				=> __('Request Change Password', 'plates'),
			'forgot_password_activate_mail_sent'		=> __('Password reset email was sent to you. Click on the link in it to activate the new password', 'plates'),
			'gdpr_request_waiting_confirmation'			=> __('An email has been sent to your mail please confirm your Request', 'plates'),
		];
	}
}
