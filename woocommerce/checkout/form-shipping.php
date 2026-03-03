<?php
/**
 * Checkout shipping information form
 *
 * Styl Gutenberg: pola adresu dostawy zawsze widoczne.
 * Checkbox "wyślij na inny adres" zastąpiony ukrytym inputem,
 * który wymusza użycie adresu dostawy przez WooCommerce.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-shipping-fields">

	<?php if ( WC()->cart->needs_shipping_address() ) : ?>

		<?php
		/*
		 * Ukryty input informuje WooCommerce, że chcemy używać
		 * oddzielnego adresu dostawy. Dzięki temu pola są zawsze
		 * widoczne bez konieczności klikania checkbox'a.
		 */
		?>
		<input type="hidden" name="ship_to_different_address" value="1" />

		<div class="shipping_address" style="display:block !important;">

			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="woocommerce-shipping-fields__field-wrapper">
				<?php
				$fields = $checkout->get_checkout_fields( 'shipping' );
				foreach ( $fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>

		</div>

	<?php else : ?>

		<?php
		/*
		 * Produkty nie wymagają adresu dostawy (np. produkty wirtualne).
		 * WooCommerce użyje adresu rozliczeniowego.
		 */
		?>
		<input type="hidden" name="ship_to_different_address" value="0" />

	<?php endif; ?>

</div>

<div class="woocommerce-additional-fields">

	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>

		<h3 class="checkout-section__label"><?php esc_html_e( 'Uwagi do zamówienia', 'grofi' ); ?></h3>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>

</div>
