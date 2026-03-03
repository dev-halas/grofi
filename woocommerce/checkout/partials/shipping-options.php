<?php
/**
 * Checkout partial – Sekcja 3: Opcje wysyłki
 *
 * Renderowane poza #order_review, żeby Alpine nie tracił stanu
 * po update_checkout. Radio inputs wewnątrz <form> wystarczą
 * do uruchomienia WC-AJAX (nasłuchuje na shipping_method change).
 *
 * @var WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

$packages = WC()->shipping()->get_packages();
?>
<div class="checkout-card">
	<h2 class="checkout-section__title">
		<span class="checkout-section__step">3</span>
		<?php esc_html_e( 'Opcje wysyłki', 'grofi' ); ?>
	</h2>

	<?php foreach ( $packages as $i => $package ) :
		$chosen    = WC()->session->chosen_shipping_methods[ $i ] ?? '';
		$available = $package['rates'];
		if ( empty( $available ) ) {
			continue;
		}
	?>
	<ul class="checkout-shipping-list">
		<?php foreach ( $available as $method ) :
			$is_chosen = ( $chosen === $method->id );
			$input_id  = 'shipping_method_' . absint( $i ) . '_' . sanitize_title( $method->id );
			$meta      = $method->get_meta_data();
			$desc      = ! empty( $meta ) ? reset( $meta ) : '';
		?>
		<li
			class="checkout-shipping-item"
			x-data="{ selected: <?php echo $is_chosen ? 'true' : 'false'; ?> }"
			:class="{ 'checkout-shipping-item--selected': selected }"
			@shipping-selected.window="selected = ($event.detail === '<?php echo esc_js( $method->id ); ?>')"
		>
			<label class="checkout-shipping-item__label" for="<?php echo esc_attr( $input_id ); ?>">
				<input
					type="radio"
					name="shipping_method[<?php echo absint( $i ); ?>]"
					data-index="<?php echo absint( $i ); ?>"
					id="<?php echo esc_attr( $input_id ); ?>"
					value="<?php echo esc_attr( $method->id ); ?>"
					<?php checked( $is_chosen ); ?>
					class="shipping_method"
					@change="$dispatch('shipping-selected', '<?php echo esc_js( $method->id ); ?>')"
				/>
				<span class="checkout-shipping-item__content">
					<span class="checkout-shipping-item__name">
						<?php echo wp_kses_post( $method->get_label() ); ?>
					</span>
					<?php if ( $desc ) : ?>
						<small class="checkout-shipping-item__desc">
							<?php echo wp_kses_post( $desc ); ?>
						</small>
					<?php endif; ?>
				</span>
				<span class="checkout-shipping-item__price">
					<?php if ( 0 == $method->cost ) : ?>
						<strong><?php esc_html_e( 'BEZPŁATNE', 'grofi' ); ?></strong>
					<?php else : ?>
						<?php echo wc_price( $method->cost ); // phpcs:ignore ?>
					<?php endif; ?>
				</span>
			</label>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endforeach; ?>
</div>