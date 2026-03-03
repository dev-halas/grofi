<?php
/**
 * Checkout partial – Notatka do zamówienia (Alpine toggle)
 *
 * @var WC_Checkout $checkout
 * @var callable    $field_as_placeholder
 */

defined( 'ABSPATH' ) || exit;

$order_notes_enabled = apply_filters(
	'woocommerce_enable_order_notes_field',
	'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' )
);
$order_fields = $checkout->get_checkout_fields( 'order' );

if ( ! $order_notes_enabled || empty( $order_fields ) ) {
	return;
}
?>
<div class="checkout-note" x-data="{ show: false }">
	<label
		class="checkout-note__toggle woocommerce-form__label-for-checkbox checkbox"
		@click.prevent="show = !show"
	>
		<input type="checkbox" :checked="show" class="woocommerce-form__input-checkbox" readonly />
		<span><?php esc_html_e( 'Dodaj notatkę do zamówienia', 'grofi' ); ?></span>
	</label>

	<div class="checkout-note__body" x-show="show" x-collapse>
		<?php foreach ( $order_fields as $key => $field ) : ?>
			<?php $field_floating_label( $key, $field, $checkout->get_value( $key ) ); ?>
		<?php endforeach; ?>
	</div>
</div>