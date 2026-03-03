<?php
/**
 * Checkout partial – Sekcja 2: Adres do wysyłki (billing + opcjonalny shipping)
 *
 * @var WC_Checkout $checkout
 * @var callable    $field_as_placeholder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="checkout-card" x-data="{ sameAddress: true }">
	<h2 class="checkout-section__title">
		<span class="checkout-section__step">2</span>
		<?php esc_html_e( 'Adres do wysyłki', 'grofi' ); ?>
	</h2>

	<?php /* Pola billing (bez emaila i telefonu – wyrenderowane w sekcji 1) */ ?>
	<div class="woocommerce-billing-fields">
		<div class="woocommerce-billing-fields__field-wrapper">
			<?php
			$billing_fields = $checkout->get_checkout_fields( 'billing' );
			foreach ( $billing_fields as $key => $field ) {
				if ( in_array( $key, [ 'billing_email', 'billing_phone' ], true ) ) {
					continue;
				}
        
				$field_floating_label( $key, $field, $checkout->get_value( $key ) );
			}
			?>
		</div>
	</div>

	<?php /* Toggle: ten sam adres do płatności */ ?>
	<div class="checkout-same-address">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input
				type="checkbox"
				class="woocommerce-form__input-checkbox"
				x-model="sameAddress"
			/>
			<span><?php esc_html_e( 'Użyj tego samego adresu do rozliczeń płatności', 'grofi' ); ?></span>
		</label>
		<input type="hidden" name="ship_to_different_address" :value="sameAddress ? '0' : '1'" />
	</div>

	<?php /* Alternatywny adres dostawy (Alpine) */ ?>
	<div x-show="!sameAddress" x-cloak class="checkout-shipping-address-extra">
		<p class="checkout-section__label checkout-section__label--alt">
			<?php esc_html_e( 'Adres dostawy', 'grofi' ); ?>
		</p>
		<div class="woocommerce-shipping-fields">
			<div class="woocommerce-shipping-fields__field-wrapper">
				<?php
				foreach ( $checkout->get_checkout_fields( 'shipping' ) as $key => $field ) {
					$field_floating_label( $key, $field, $checkout->get_value( $key ) );
				}
				?>
			</div>
		</div>
	</div>
</div>