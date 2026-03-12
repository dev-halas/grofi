<?php
/**
 * Partial: lista pozycji koszyka.
 *
 * Używany zarówno przez woocommerce/cart/cart.php (render strony)
 * jak i przez grofi_render_cart_items_html() (fragment AJAX).
 * Dlatego NIE korzysta z zewnętrznych zmiennych PHP – pobiera dane
 * bezpośrednio z WC()->cart.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cart-items">
	<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
		$_product        = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$product_id      = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
		$product_name    = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
		$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

		if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) continue;
		if ( ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) continue;

		$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
	?>
	<div
		class="cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>"
		:class="{ 'cart-item--updating': $store.cart.isUpdating('<?php echo esc_js( $cart_item_key ); ?>') }"
	>

		<a class="cart-item__img" href="<?php echo esc_url( $product_permalink ?: get_permalink( $product_id ) ); ?>">
			<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>

		<div class="cart-item__details">
			<a class="cart-item__name" href="<?php echo esc_url( $product_permalink ?: get_permalink( $product_id ) ); ?>">
				<?php echo wp_kses_post( $product_name ); ?>
			</a>
			<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) : ?>
				<p class="backorder_notification"><?php esc_html_e( 'Dostępny na zamówienie', 'grofi' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="cart-item__price">
			<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<div class="cart-item__qty">
			<?php
			echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'woocommerce_cart_item_quantity',
				woocommerce_quantity_input(
					[
						'input_name'   => "cart[{$cart_item_key}][qty]",
						'input_value'  => $cart_item['quantity'],
						'max_value'    => $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity(),
						'min_value'    => $_product->is_sold_individually() ? 1 : 0,
						'product_name' => $product_name,
					],
					$_product,
					false
				),
				$cart_item_key,
				$cart_item
			);
			?>
		</div>

		<div class="cart-item__subtotal">
			<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<div class="cart-item__remove">
			<?php
			echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'woocommerce_cart_item_remove_link',
				sprintf(
					'<a role="button" href="%s" class="cart-item__remove-btn" aria-label="%s" data-product_id="%s" data-product_sku="%s">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</a>',
					esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
					/* translators: %s is the product name */
					esc_attr( sprintf( __( 'Usuń %s z koszyka', 'grofi' ), wp_strip_all_tags( $product_name ) ) ),
					esc_attr( $product_id ),
					esc_attr( $_product->get_sku() )
				),
				$cart_item_key
			);
			?>
		</div>

	</div>
	<?php endforeach; ?>
</div>
