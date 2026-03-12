<?php
/**
 * Cart Page
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<div class="cart-page">
	<div class="woocommerceMain">

		<h1 class="cart-page__title"><?php esc_html_e( 'Koszyk', 'grofi' ); ?></h1>

		<div class="cart-layout">

			<?php do_action( 'woocommerce_before_cart_table' ); ?>

			<div class="cart-layout__main">
				<form class="woocommerce-cart-form cart-card" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

					<?php if ( ! WC()->cart->is_empty() ) : ?>

						<?php include __DIR__ . '/partials/cart-items.php'; ?>

					<?php endif; ?>

					<?php do_action( 'woocommerce_cart_contents' ); ?>

					<div class="cart-card__actions">
						<?php if ( wc_coupons_enabled() ) : ?>
							<div class="cart-coupon">
								<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Kod rabatowy:', 'grofi' ); ?></label>
								<input
									type="text"
									name="coupon_code"
									class="cart-coupon__input"
									id="coupon_code"
									value=""
									placeholder="<?php esc_attr_e( 'Kod rabatowy', 'grofi' ); ?>"
								/>
								<button
									type="submit"
									class="button button--light cart-coupon__btn"
									name="apply_coupon"
									value="<?php esc_attr_e( 'Zastosuj kupon', 'grofi' ); ?>">
									<?php esc_html_e( 'Zastosuj', 'grofi' ); ?>
								</button>
								<?php do_action( 'woocommerce_cart_coupon' ); ?>
							</div>
						<?php endif; ?>

						<button
							type="submit"
							class="button button--light cart-card__update-btn"
							name="update_cart"
							value="<?php esc_attr_e( 'Aktualizuj koszyk', 'grofi' ); ?>">
							<?php esc_html_e( 'Aktualizuj koszyk', 'grofi' ); ?>
						</button>

						<?php do_action( 'woocommerce_cart_actions' ); ?>
						<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					</div>

					<?php do_action( 'woocommerce_after_cart_contents' ); ?>

				</form>
			</div>

			<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

			<div class="cart-layout__sidebar">
				<?php do_action( 'woocommerce_cart_collaterals' ); ?>
			</div>

		</div>

		<?php do_action( 'woocommerce_after_cart_table' ); ?>

	</div>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
