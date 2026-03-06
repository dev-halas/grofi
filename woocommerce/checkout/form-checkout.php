<?php
/**
 * Checkout Form – custom Shopify-style layout
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Musisz być zalogowany, aby złożyć zamówienie.', 'woocommerce' ) ) );
	return;
}

/**
 * Helper: floating label.
 *
 * Ustawia placeholder=" " (spacja) żeby CSS :placeholder-shown działał
 * do wykrywania czy pole jest puste. Label zostaje w DOM i jest
 * pozycjonowany absolutnie przez CSS — animuje się do góry po focusie
 * lub po uzupełnieniu wartości.
 *
 * @param string $key   Klucz pola, np. 'billing_first_name'.
 * @param array  $field Tablica konfiguracji pola z WC.
 * @param mixed  $value Aktualna wartość pola.
 */
$field_floating_label = static function( string $key, array $field, $value ): void {
	// Spacja jako placeholder — niewidoczna dla użytkownika,
	// ale pozwala CSS wykryć stan "puste" przez :placeholder-shown.
	if ( empty( $field['placeholder'] ) ) {
		$field['placeholder'] = ' ';
	}

	woocommerce_form_field( $key, $field, $value );
};

// Wspólne args przekazywane do każdego partiala.
$partial_args = [
	'checkout'             => $checkout,
	'field_floating_label' => $field_floating_label,
];

$needs_shipping = WC()->cart->needs_shipping() && WC()->cart->show_shipping();
$payment_step   = $needs_shipping ? 4 : 3;
?>

<div class="checkout-page container">

	<?php woocommerce_breadcrumb(); ?>

	<h1 class="checkout-page__title"><?php esc_html_e( 'Zamówienie', 'grofi' ); ?></h1>

	<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>
	<?php woocommerce_output_all_notices(); ?>

	<form
		name="checkout"
		method="post"
		class="checkout woocommerce-checkout"
		action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
		enctype="multipart/form-data"
		aria-label="<?php esc_attr_e( 'Zamówienie', 'grofi' ); ?>"
	>
		<div class="checkout-layout">

			<div class="checkout-layout__main">

				<?php if ( $checkout->get_checkout_fields() ) : ?>

					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

						<?php wc_get_template( 'checkout/partials/contact.php', $partial_args ); ?>
						<?php wc_get_template( 'checkout/partials/address.php', $partial_args ); ?>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

				<?php endif; ?>

				<?php if ( $needs_shipping ) : ?>
					<?php wc_get_template( 'checkout/partials/shipping-options.php', $partial_args ); ?>
				<?php endif; ?>

				<?php wc_get_template( 'checkout/partials/payment-options.php', array_merge( $partial_args, [ 'payment_step' => $payment_step ] ) ); ?>

				<?php wc_get_template( 'checkout/partials/note.php', $partial_args ); ?>

				<?php wc_get_template( 'checkout/partials/footer-actions.php' ); ?>

			</div>

			<?php wc_get_template( 'checkout/partials/sidebar.php' ); ?>

		</div>

	</form>

	<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

</div>