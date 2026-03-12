<?php
/**
 * Empty cart page
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="cart-empty">
	<div class="cart-empty__icon" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 26 26" fill="currentColor">
			<path d="M8.81543 19.6338C10.4723 19.634 11.8203 20.9818 11.8203 22.6387C11.8203 24.2956 10.4723 25.6434 8.81543 25.6436C7.15839 25.6436 5.80957 24.2957 5.80957 22.6387C5.80963 20.9817 7.15843 19.6338 8.81543 19.6338ZM20.8359 19.6338C22.4928 19.6339 23.8408 20.9818 23.8408 22.6387C23.8408 24.2956 22.4929 25.6434 20.8359 25.6436C19.1789 25.6436 17.8311 24.2957 17.8311 22.6387C17.8311 20.9817 19.1789 19.6338 20.8359 19.6338ZM8.81543 21.6367C8.26312 21.6367 7.81354 22.0864 7.81348 22.6387C7.81348 23.191 8.26308 23.6406 8.81543 23.6406C9.36763 23.6405 9.81641 23.1909 9.81641 22.6387C9.81635 22.0865 9.3676 21.6369 8.81543 21.6367ZM20.8359 21.6367C20.2836 21.6367 19.834 22.0864 19.834 22.6387C19.834 23.191 20.2836 23.6406 20.8359 23.6406C21.3882 23.6405 21.8379 23.1909 21.8379 22.6387C21.8378 22.0864 21.3881 21.6368 20.8359 21.6367ZM2.3877 0C3.79517 0.000214296 5.03027 0.999714 5.3252 2.37598C5.32698 2.38433 5.32946 2.39297 5.33105 2.40137L5.59473 3.80664H24.6426C25.259 3.80684 25.7317 4.36045 25.6299 4.97266L23.9062 15.3193C23.6638 16.7744 22.4165 17.8311 20.9414 17.8311H8.67969C7.23039 17.8311 5.98775 16.7963 5.72461 15.3711L3.36426 2.78516C3.26197 2.33188 2.85316 2.00412 2.3877 2.00391H1.00195C0.448715 2.00391 1.74075e-05 1.55519 0 1.00195C0 0.448705 0.448705 0 1.00195 0H2.3877Z"/>
		</svg>
	</div>

	<?php do_action( 'woocommerce_cart_is_empty' ); ?>

	<h2 class="cart-empty__title"><?php esc_html_e( 'Twój koszyk jest pusty', 'grofi' ); ?></h2>
	<p class="cart-empty__text"><?php esc_html_e( 'Wygląda na to, że nie dodałeś jeszcze żadnych produktów.', 'grofi' ); ?></p>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<a class="button button--orange cart-empty__cta" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Przejdź do sklepu', 'grofi' ) ) ); ?>
		</a>
	<?php endif; ?>
</div>
