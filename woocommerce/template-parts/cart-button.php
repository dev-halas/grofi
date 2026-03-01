<?php
/**
 * Formularz add-to-cart z ikoną kosza.
 *
 * Dla każdego typu produktu (simple, variable, grouped, external)
 * wywołuje odpowiedni szablon WooCommerce, który korzysta z naszego
 * quantity-input.php (woocommerce/global/quantity-input.php).
 *
 * Ikona kosza jest wstrzykiwana do wnętrza <button> przez output buffering —
 * WooCommerce escapuje tekst przycisku przez esc_html, więc filtr tekstowy
 * nie pozwala na wstawienie HTML.
 */
defined( 'ABSPATH' ) || exit;

$cart_icon = sprintf(
	'<img src="%s" alt="" class="btn-cart-icon" aria-hidden="true">',
	esc_url( get_theme_file_uri( '_dev/assets/icons/cart-white.svg' ) )
);

ob_start();
woocommerce_template_single_add_to_cart();
$cart_form = ob_get_clean();

echo preg_replace(
	'/(<button\b[^>]*\bsingle_add_to_cart_button\b[^>]*>)/i',
	'$1' . $cart_icon,
	$cart_form
);