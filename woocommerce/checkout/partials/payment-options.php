<?php
/**
 * Checkout partial – Sekcja 4: Opcje płatności
 *
 * Renderujemy payment.php bezpośrednio z $available_gateways,
 * omijając woocommerce_checkout_payment() (które jest zaczepione
 * do woocommerce_checkout_order_review i zostało tam usunięte).
 *
 * @var WC_Checkout $checkout
 * @var int         $payment_step  Numer kroku (3 lub 4, zależnie od wysyłki).
 */

defined( 'ABSPATH' ) || exit;

if ( WC()->cart && WC()->cart->needs_payment() ) {
	$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
	WC()->payment_gateways()->set_current_gateway( $available_gateways );
} else {
	$available_gateways = [];
}
?>
<div class="checkout-card checkout-card--payment">
	<h2 class="checkout-section__title">
		<span class="checkout-section__step"><?php echo esc_html( $payment_step ); ?></span>
		<?php esc_html_e( 'Opcje płatności', 'grofi' ); ?>
	</h2>

	<?php
	wc_get_template(
		'checkout/payment.php',
		[
			'checkout'           => $checkout,
			'available_gateways' => $available_gateways,
			'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Kupuję i płacę', 'grofi' ) ),
		]
	);
	?>
</div>