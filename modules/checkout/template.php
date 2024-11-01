<?php
/*
 * Template Name: Mobile Checkout
 */
session_start();
wp_head();
$_SESSION["mobile_checkout"] = true;
// echo do_shortcode("[woocommerce_checkout]");
?>
<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined('ABSPATH') || exit;

$checkout = WC()->checkout();
?>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
	.woocommerce-checkout,
	.woocommerce-form-coupon-toggle {
		width: 75%;
		margin: 30px auto;
	}

	#order_review_heading,
	#order_review {
		width: 100%;
	}
</style>
<div class="woocommerce">
	<?php


	// If checkout registration is disabled and not logged in, the user cannot checkout.
	if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
		echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
		return;
	}

	?>

	<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

		<?php if ($checkout->get_checkout_fields()) : ?>

			<?php do_action('woocommerce_checkout_before_customer_details'); ?>

			<div id="customer_details">
				<div>
					<?php do_action('woocommerce_checkout_billing'); ?>
				</div>

				<div>
					<?php do_action('woocommerce_checkout_shipping'); ?>
				</div>
			</div>

			<?php do_action('woocommerce_checkout_after_customer_details'); ?>

		<?php endif; ?>

		<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

		<h3 id="order_review_heading"><?php esc_html_e('Your order', 'woocommerce'); ?></h3>

		<?php do_action('woocommerce_checkout_before_order_review'); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php do_action('woocommerce_checkout_order_review'); ?>
		</div>

		<?php do_action('woocommerce_checkout_after_order_review'); ?>

	</form>

	<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
</div>
<?php
wp_footer();
?>
<!-- <script>
jQuery(document).ready(function() {
    jQuery('a, button, input').each(function(index,element) {
        if( !jQuery(element).parents().hasClass('woocommerce-checkout') ) {
            jQuery(element).remove();
        }
    });
});
</script> -->