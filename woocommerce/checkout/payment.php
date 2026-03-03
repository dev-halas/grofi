<?php
/**
 * Checkout Payment Section – tylko metody płatności
 *
 * Przycisk "Złóż zamówienie", nonce i termin renderowane są w form-checkout.php
 * (poza #payment), dzięki czemu przycisk może być w pasku akcji na dole strony.
 * Nonce zostaje w #payment, aby WooCommerce AJAX mógł go odświeżyć.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>

<div id="payment" class="woocommerce-checkout-payment">

	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>

		<ul class="wc_payment_methods payment_methods methods">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', [ 'gateway' => $gateway ] );
				}
			} else {
				echo '<li>';
				wc_print_notice(
					apply_filters(
						'woocommerce_no_available_payment_methods_message',
						WC()->customer->get_billing_country()
							? esc_html__( 'Brak dostępnych metod płatności. Skontaktuj się z nami, jeśli potrzebujesz pomocy.', 'woocommerce' )
							: esc_html__( 'Uzupełnij dane powyżej, aby zobaczyć dostępne metody płatności.', 'woocommerce' )
					),
					'notice'
				);
				echo '</li>';
			}
			?>
		</ul>

	<?php endif; ?>

	<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

	<?php
	/*
	 * Nonce MUSI pozostać w #payment – WooCommerce AJAX odświeża zawartość tego diva
	 * i generuje nowy nonce po każdej aktualizacji zamówienia.
	 */
	wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );
	?>

	<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

</div>

<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
