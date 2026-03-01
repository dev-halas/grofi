<?php

// -------------------------------------------------------
// Odrejestruj wc-cart-fragments – fragmenty odświeżamy sami
// w cart.js przez natywny fetch (fetchFragments).
// -------------------------------------------------------
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_script( 'wc-cart-fragments' );
	wp_deregister_script( 'wc-cart-fragments' );
}, 100 );


// -------------------------------------------------------
// Shared template: przycisk koszyka w nagłówku
//
// Używany zarówno w header.php jak i w fragment AJAX,
// żeby HTML był zawsze identyczny (jQuery replaceWith
// wymaga dokładnego dopasowania selektora do outerHTML).
// -------------------------------------------------------
function grofi_cart_button(): void {
	$cart  = WC()->cart;
	$count = (int) $cart->get_cart_contents_count();
	$total = strip_tags( $cart->get_cart_total() );
	$url   = wc_get_cart_url();
	?>
	<a href="<?php echo esc_url( $url ); ?>"
	   class="cartButton"
	   aria-label="<?php esc_attr_e( 'Koszyk', 'grofi' ); ?>">
		<div class="cartIcon">
			<span class="cartCount<?php echo $count > 0 ? ' cartCount--visible' : ''; ?>">
				<?php echo esc_html( $count ); ?>
			</span>
			<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="currentColor" aria-hidden="true">
				<path d="M8.81543 19.6338C10.4723 19.634 11.8203 20.9818 11.8203 22.6387C11.8203 24.2956 10.4723 25.6434 8.81543 25.6436C7.15839 25.6436 5.80957 24.2957 5.80957 22.6387C5.80963 20.9817 7.15843 19.6338 8.81543 19.6338ZM20.8359 19.6338C22.4928 19.6339 23.8408 20.9818 23.8408 22.6387C23.8408 24.2956 22.4929 25.6434 20.8359 25.6436C19.1789 25.6436 17.8311 24.2957 17.8311 22.6387C17.8311 20.9817 19.1789 19.6338 20.8359 19.6338ZM8.81543 21.6367C8.26312 21.6367 7.81354 22.0864 7.81348 22.6387C7.81348 23.191 8.26308 23.6406 8.81543 23.6406C9.36763 23.6405 9.81641 23.1909 9.81641 22.6387C9.81635 22.0865 9.3676 21.6369 8.81543 21.6367ZM20.8359 21.6367C20.2836 21.6367 19.834 22.0864 19.834 22.6387C19.834 23.191 20.2836 23.6406 20.8359 23.6406C21.3882 23.6405 21.8379 23.1909 21.8379 22.6387C21.8378 22.0864 21.3881 21.6368 20.8359 21.6367ZM2.3877 0C3.79517 0.000214296 5.03027 0.999714 5.3252 2.37598C5.32698 2.38433 5.32946 2.39297 5.33105 2.40137L5.59473 3.80664H24.6426C25.259 3.80684 25.7317 4.36045 25.6299 4.97266L23.9062 15.3193C23.6638 16.7744 22.4165 17.8311 20.9414 17.8311H8.67969C7.23039 17.8311 5.98775 16.7963 5.72461 15.3711L3.36426 2.78516C3.26197 2.33188 2.85316 2.00412 2.3877 2.00391H1.00195C0.448715 2.00391 1.74075e-05 1.55519 0 1.00195C0 0.448705 0.448705 0 1.00195 0H2.3877ZM7.69434 15.0049C7.78272 15.4826 8.19667 15.8271 8.67969 15.8271H20.9414C21.4331 15.8271 21.8488 15.4752 21.9297 14.9902L23.46 5.81055H5.96973L7.69434 15.0049Z"/>
			</svg>
		</div>
		<span class="cartValue"><?php echo esc_html( $total ); ?></span>
	</a>
	<?php
}


// -------------------------------------------------------
// MiniCart: renderowanie treści panelu bocznego
// -------------------------------------------------------
function grofi_minicart_content(): void {
	$cart  = WC()->cart;
	$items = $cart->get_cart();
	?>
	<div class="minicart__content">
		<div class="minicart__body">
			<?php if ( empty( $items ) ) : ?>
				<p class="minicart__empty"><?php esc_html_e( 'Twój koszyk jest pusty.', 'grofi' ); ?></p>
			<?php else : ?>
				<ul class="minicart__list">
					<?php foreach ( $items as $cart_item_key => $cart_item ) :
						$product    = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						$quantity   = $cart_item['quantity'];
						$thumbnail  = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
						$name       = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key );
						$subtotal   = apply_filters( 'woocommerce_cart_item_subtotal', $cart->get_product_subtotal( $product, $quantity ), $cart_item, $cart_item_key );
						$remove_url = wc_get_cart_remove_url( $cart_item_key );
					?>
					<li class="minicart__item">
						<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="minicart__item-img">
							<?php echo $thumbnail; ?>
						</a>
						<div class="minicart__item-details">
							<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="minicart__item-name">
								<?php echo esc_html( $name ); ?>
							</a>
							<div class="minicart__item-meta">
								<span class="minicart__item-qty"><?php echo esc_html( $quantity ); ?></span>
								<span class="minicart__item-x">×</span>
								<span class="minicart__item-price"><?php echo $subtotal; ?></span>
							</div>
						</div>
						<a href="<?php echo esc_url( $remove_url ); ?>"
						   class="minicart__item-remove"
						   aria-label="<?php esc_attr_e( 'Usuń produkt', 'grofi' ); ?>"
						   data-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<line x1="18" y1="6" x2="6" y2="18"></line>
								<line x1="6" y1="6" x2="18" y2="18"></line>
							</svg>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="minicart__footer">
			<div class="minicart__total">
				<span><?php esc_html_e( 'Łącznie:', 'grofi' ); ?></span>
				<strong><?php echo $cart->get_cart_total(); ?></strong>
			</div>
			<div class="minicart__actions">
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="button button--light">
					<?php esc_html_e( 'Zobacz koszyk', 'grofi' ); ?>
				</a>
				<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button button--orange">
					<?php esc_html_e( 'Przejdź do płatności', 'grofi' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
}


// -------------------------------------------------------
// Fragment AJAX koszyka + minicart
//
// Selektor musi pasować do outerHTML elementu w DOM.
// Używamy 'a.cartButton' i 'div.minicart__content' –
// cart.js wywołuje /?wc-ajax=get_refreshed_fragments
// i podmienia elementy przez applyFragments().
// -------------------------------------------------------
add_filter( 'woocommerce_add_to_cart_fragments', function ( array $fragments ): array {
	ob_start();
	grofi_cart_button();
	$fragments['a.cartButton'] = ob_get_clean();

	ob_start();
	grofi_minicart_content();
	$fragments['div.minicart__content'] = ob_get_clean();

	return $fragments;
} );


// -------------------------------------------------------
// AJAX: usuń produkt z koszyka + zwróć fragmenty w jednym żądaniu
//
// Endpoint: /?wc-ajax=grofi_remove_cart_item (POST)
// Obsługuje zarówno gości jak i zalogowanych użytkowników.
// Weryfikuje nonce wygenerowany przez wc_get_cart_remove_url().
// -------------------------------------------------------
add_action( 'wc_ajax_grofi_remove_cart_item', function (): void {
	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, 'woocommerce-cart' ) ) {
		wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
	}

	$cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ?? '' ) );

	if ( empty( $cart_item_key ) ) {
		wp_send_json_error( [ 'message' => 'Missing cart_item_key' ], 400 );
	}

	WC()->cart->remove_cart_item( $cart_item_key );

	$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', [] );

	wp_send_json_success( [
		'fragments' => $fragments,
		'cart_hash' => WC()->cart->get_cart_hash(),
	] );
} );
