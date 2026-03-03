<?php
/**
 * Checkout partial – Prawa kolumna: Podsumowanie zamówienia + Kupon
 *
 * Akordeon kuponu jest celowo POZA #order_review, żeby WC AJAX
 * nie niszczył stanu komponentu Alpine przy update_checkout.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="checkout-layout__sidebar">
	<div class="checkout-card checkout-card--summary">

		<h2 class="checkout-section__title checkout-section__title--summary">
			<?php esc_html_e( 'Podsumowanie zamówienia', 'grofi' ); ?>
		</h2>

		<?php /* AKORDEON KUPONU */ ?>
		<div
			class="checkout-coupon"
			x-data="checkoutCoupon()"
		>
			<button
				type="button"
				class="checkout-coupon__toggle"
				@click="open = !open"
				:aria-expanded="open.toString()"
			>
				<span><?php esc_html_e( 'Dodaj Kupon', 'grofi' ); ?></span>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="14" height="14"
					viewBox="0 0 24 24"
					fill="none" stroke="currentColor"
					stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					class="checkout-coupon__chevron"
					:class="{ 'checkout-coupon__chevron--open': open }"
					aria-hidden="true"
				><path d="M6 9l6 6 6-6"/></svg>
			</button>

			<div
				class="checkout-coupon__body"
				:class="{ 'checkout-coupon__body--open': open }"
			>
				<div class="checkout-coupon__inner">
					<div class="checkout-coupon__form">
						<input
							name="coupon_code"
							type="text"
							x-model="code"
							placeholder="<?php esc_attr_e( 'Wpisz kod kuponu', 'grofi' ); ?>"
							class="checkout-coupon__input"
							@keydown.enter.prevent="apply()"
						/>
						<button
							type="button"
							class="checkout-coupon__apply button button--orange"
							@click="apply()"
							:disabled="loading"
						>
							<span x-show="!loading"><?php esc_html_e( 'Zastosuj', 'grofi' ); ?></span>
							<span x-show="loading" aria-hidden="true">…</span>
						</button>
					</div>
					<div
						x-show="message"
						x-html="message"
						class="checkout-coupon__message"
						:class="{ 'checkout-coupon__message--error': isError }"
					></div>
				</div>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php
			/*
			 * Renderujemy wyłącznie review-order.php (produkty + kwoty).
			 * woocommerce_checkout_payment jest odpięte, żeby nie duplikować
			 * sekcji płatności wyrenderowanej w lewej kolumnie.
			 */
			remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
			do_action( 'woocommerce_checkout_order_review' );
			add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
			?>
		</div>

		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

	</div>
</div>