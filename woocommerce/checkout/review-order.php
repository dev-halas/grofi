<?php
/**
 * Review order table – produkty + kwoty w sidebarze
 *
 * Akordeon kuponu przeniesiony do form-checkout.php (poza #order_review),
 * dzięki czemu WC AJAX nie niszczy komponentu Alpine przy update_checkout.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="checkout-review">

	<?php /* Lista produktów */ ?>
	<ul class="checkout-review__items">
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) {
				continue;
			}
			if ( ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				continue;
			}

			$thumbnail = $_product->get_image( 'woocommerce_thumbnail' );
			$subtotal  = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
			$name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
		?>
		<li class="checkout-review__item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

			<div class="checkout-review__item-img">
				<?php echo $thumbnail; // phpcs:ignore ?>
				<span class="checkout-review__item-qty"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
			</div>

			<div class="checkout-review__item-details">
				<span class="checkout-review__item-name">
					<?php echo wp_kses_post( $name ); ?>
					<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore ?>
				</span>
				<span class="checkout-review__item-price"><?php echo $subtotal; // phpcs:ignore ?></span>
			</div>

		</li>
		<?php endforeach; ?>
		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
	</ul>

	<?php /* Kwoty */ ?>
	<div class="checkout-review__totals">

		<?php do_action( 'woocommerce_review_order_before_subtotal' ); ?>

		<div class="checkout-review__row">
			<span><?php esc_html_e( 'Kwota', 'woocommerce' ); ?></span>
			<span><?php wc_cart_totals_subtotal_html(); ?></span>
		</div>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="checkout-review__row checkout-review__row--coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

      <div class="checkout-review__row checkout-review__row--shipping">
        <span><?php esc_html_e( 'Dostawa', 'woocommerce' ); ?></span>

          <?php
          $chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', [] ) : [];
          $chosen_label   = '';

          foreach ( WC()->shipping()->get_packages() as $i => $package ) {
              if ( isset( $chosen_methods[ $i ], $package['rates'][ $chosen_methods[ $i ] ] ) ) {
                  $chosen_label = $package['rates'][ $chosen_methods[ $i ] ]->get_label();
                  break;
              }
          }

          $is_free = 0 == WC()->cart->get_shipping_total();
          ?>

          <span class="checkout-review__shipping-value">
            <?php if ( $chosen_label ) : ?>
              <?php echo esc_html( $chosen_label ); ?>:
            <?php endif; ?>

            <?php if ( $is_free ) : ?>
              <strong><?php esc_html_e( 'Bezpłatna', 'grofi' ); ?></strong>
            <?php else : ?>
              <?php echo wc_price( WC()->cart->get_shipping_total() ); // phpcs:ignore ?>
            <?php endif; ?>
          </span>
      </div>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="checkout-review__row">
				<span><?php echo esc_html( $fee->name ); ?></span>
				<span><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && WC()->cart->display_prices_including_tax() ) : ?>
			<?php $tax_totals = WC()->cart->get_tax_totals(); ?>
			<?php if ( ! empty( $tax_totals ) ) : ?>
				<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
					<?php foreach ( $tax_totals as $tax ) : ?>
						<div class="checkout-review__row checkout-review__row--tax">
							<span><?php printf( esc_html__( 'W tym %s%%', 'grofi' ), esc_html( $tax->label ) ); ?></span>
							<span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="checkout-review__row checkout-review__row--tax">
						<span><?php printf( /* translators: %s: tax total HTML */ esc_html__( 'W tym %s VAT', 'grofi' ), wc_cart_totals_taxes_total_html() ); // phpcs:ignore ?></span>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<div class="checkout-review__row checkout-review__row--total">
			<span><?php esc_html_e( 'Łącznie', 'woocommerce' ); ?></span>
			<span><?php echo WC()->cart->get_total(); // phpcs:ignore; ?></span>
		</div>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</div>

</div>
