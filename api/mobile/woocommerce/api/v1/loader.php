<?php

class Stacks_ControllersLoader {

	public function __construct() {
		do_action('stacks_woo_api_before_loading_controllers');
	}

	public function get_controllers() {
		$controllers_location = __DIR__ . '/';
		$data = [
			// abstract controllers 

			$controllers_location . '/authentication/registration-login-abstract-controller.php',
			$controllers_location . '/pages/abstract-pages-controller.php',

			// user-details
			$controllers_location . '/user-details/addresses-controller.php',
			$controllers_location . '/user-details/change-password-controller.php',
			$controllers_location . '/user-details/user-details-controller.php',
			$controllers_location . '/user-details/user-add-device-id.php',

			// Auth Controllers 
			$controllers_location . '/authentication/registration-controller.php',
			$controllers_location . '/authentication/login-controller.php',
			$controllers_location . '/authentication/forgot-password-controller.php',
			$controllers_location . '/authentication/anonymous-token-controller.php',



			// Contact Endpoints
			$controllers_location . '/pages/contact/contact-controller.php',

			// Home Controllers
			$controllers_location . '/pages/home/home-page-controller.php',
			$controllers_location . '/pages/views/views-controller.php',
			$controllers_location . '/pages/login/login-page-controller.php',
			$controllers_location . '/pages/signup/signup-page-controller.php',
			$controllers_location . '/pages/points-and-rewards/points-and-rewards-page-controller.php',
			$controllers_location . '/pages/about/about-page-controller.php',

			// Stacks_AppConfig
			$controllers_location . '/app-separate-requests/config.php',

			// Guest Device
			$controllers_location . '/app-separate-requests/guest-user.php',

			// GDPR
			$controllers_location . '/gdpr/gdbr-request-controller.php',

			// Translations
			$controllers_location . '/translation/translation-controller.php'
		];
		$woocommerce_data = [];
		if (is_stacks_woocommerce_active()) {
			$woocommerce_data = [
				$controllers_location . '/abstracts/abstract-products-controller.php',
				$controllers_location . '/abstracts/abstract-cart-wishlist-controller.php',
				$controllers_location . '/abstracts/abstract-order-controller.php',
				//product data related controllers 
				$controllers_location . '/taxonomies/categories-controller.php',

				// search controllers 
				$controllers_location . '/search/filter-controller.php',

				// product controllers 
				$controllers_location . '/product/products-controller.php',
				$controllers_location . '/product/products-single-controller.php',
				$controllers_location . '/product/reviews-controller.php',
				$controllers_location . '/product/variations-controller.php',
				// addons Endpoints
				$controllers_location . '/product/addons-controller.php',

				// Wishlist Endpoints
				$controllers_location . '/wishlist/wishlist-validation-trait.php',
				$controllers_location . '/wishlist/wishlist-abstract-controller.php',

				// wishlist controllers
				$controllers_location . '/wishlist/get-user-wishlists-controller.php',
				$controllers_location . '/wishlist/wishlist-controller.php',
				$controllers_location . '/wishlist/wishlist-product-controller.php',

				// cart Endpoints
				$controllers_location . '/cart/cart-abstract-controller.php',
				$controllers_location . '/cart/cart-controller.php',
				$controllers_location . '/cart/coupon-controller.php',
				$controllers_location . '/cart/update-product-quantity-controller.php',
				$controllers_location . '/product/product-variation-attributes-validator.php',
				$controllers_location . '/cart/calculate-shipping.php',

				// get all supported countries 
				$controllers_location . '/cart/supported-countries-controller.php',

				// Checkout Endpoints
				$controllers_location . '/checkout/stacks-checkout-page.php',
				$controllers_location . '/checkout/payment-options-controller.php',
				$controllers_location . '/checkout/order-controller.php',
				$controllers_location . '/checkout/edit-order-controller.php',
				$controllers_location . '/checkout/order-change-payment-method-controller.php',

				$controllers_location . '/user-details/user-orders-controller.php',
				$controllers_location . '/user-details/user-points-controller.php',
			];
		}
		foreach ($woocommerce_data as $value) {
			$data[] = $value;
		}

		return $data;
	}

	public function get_controllers_class_names() {

		$data = [

			// Auth Controllers 
			'Stacks_RegistrationController',
			'Stacks_LoginController',
			'Stacks_TokenController',
			'Stacks_ForgotPasswordController',

			'Stacks_AddressesController',
			'Stacks_ChangePasswordController',
			'Stacks_UserDataController',
			'Stacks_DeviceIDController',
			'Stacks_GuestDeviceIDController',


			// Contact controllers
			'Stacks_ContactController',

			// Home controllers
			'Stacks_HomePageController',
			'Stacks_ViewsController',
			'Stacks_LoginPageController',
			'Stacks_SignUpPageController',
			'Stacks_PointsAndRewardsPageController',
			'Stacks_AboutPageController',

			// Stacks_AppConfig
			'Stacks_AppConfig',

			// gdpr
			'Stacks_GDPR_Request_Controller',

			// Translation
			'Stacks_TranslationController',
		];
		$woocommerce_data = [];
		if (is_stacks_woocommerce_active()) {
			$woocommerce_data = [
				// controllers.
				'Stacks_CategoriesController',
				'Stacks_FilterController',
				'Stacks_ProductsController',
				'Stacks_ProductSingleController',

				'Stacks_UserOrdersController',
				'Stacks_GetUserWishlists',
				'Stacks_ProductVariationAttributesValidator',
				'Stacks_VariationsController',
				'Stacks_UserPointsController',

				// wishlist controllers 
				'Stacks_WishlistController',
				'Stacks_WishlistProductController',

				// cart controllers 
				'Stacks_CartController',
				'Stacks_CouponController',
				'Stacks_UpdateProductQuantityController',

				// addons controllers
				'Stacks_AddonsController',

				// shipping controllers
				'Stacks_SupportedCountriesController',
				'Stacks_CalculateShipping',

				// checkout controllers
				'Stacks_get_checkout_page',
				'Stacks_PaymentOptionsController',
				'Stacks_OrderController',
				'Stacks_EditOrderController',
				'Stacks_ChangeOrderPaymentMethodController',
				'Stacks_ReviewsController',
			];
		}
		foreach ($woocommerce_data as $value) {
			$data[] = $value;
		}
		return $data;
	}
}
