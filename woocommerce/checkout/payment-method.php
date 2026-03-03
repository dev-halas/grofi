<?php
/**
 * Single payment method – Alpine.js interactivity
 *
 * Komunikacja między metodami przez window event 'payment-method-selected',
 * dzięki czemu działa po AJAX-owym zastąpieniu #payment przez WooCommerce.
 * Usunięto x-collapse – animacja powodowała jank przy zmianie metody.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;
?>

<li
	class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>"
	x-data="{ selected: <?php echo $gateway->chosen ? 'true' : 'false'; ?> }"
	:class="{ 'payment_method_selected': selected }"
	@payment-method-selected.window="selected = ($event.detail === '<?php echo esc_js( $gateway->id ); ?>')"
>
	<div class="payment-method__header">
		<input
			id="payment_method_<?php echo esc_attr( $gateway->id ); ?>"
			type="radio"
			class="input-radio"
			name="payment_method"
			value="<?php echo esc_attr( $gateway->id ); ?>"
			<?php checked( $gateway->chosen, true ); ?>
			data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"
			@change="$dispatch('payment-method-selected', '<?php echo esc_js( $gateway->id ); ?>')"
		/>
		<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
			<?php echo wp_kses_post( $gateway->get_title() ); ?>
			<?php echo $gateway->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</label>
	</div>

	<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" x-show="selected">
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>

</li>
