<?php
/**
 * Thankyou page – własny szablon
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="thankyou-page container">

	<div class="woocommerce-order">

		<?php if ( $order ) : ?>

			<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

			<?php if ( $order->has_status( 'failed' ) ) : ?>

				<div class="thankyou-page__card thankyou-page__card--error">
					<div class="thankyou-page__icon thankyou-page__icon--error">
						<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10"></circle>
							<line x1="15" y1="9" x2="9" y2="15"></line>
							<line x1="9" y1="9" x2="15" y2="15"></line>
						</svg>
					</div>
					<h1 class="thankyou-page__title"><?php esc_html_e( 'Zamówienie nie powiodło się', 'grofi' ); ?></h1>
					<p class="thankyou-page__message"><?php esc_html_e( 'Niestety Twoje zamówienie nie mogło zostać przetworzone — bank lub sprzedawca odrzucił transakcję. Spróbuj ponownie.', 'woocommerce' ); ?></p>

					<div class="thankyou-page__actions">
						<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button button--orange">
							<?php esc_html_e( 'Spróbuj ponownie', 'woocommerce' ); ?>
						</a>
						<?php if ( is_user_logged_in() ) : ?>
							<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button button--light">
								<?php esc_html_e( 'Moje konto', 'woocommerce' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

			<?php else : ?>

				<div class="thankyou-page__card thankyou-page__card--success">

					<div class="thankyou-page__icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
							<polyline points="22 4 12 14.01 9 11.01"></polyline>
						</svg>
					</div>

					<h1 class="thankyou-page__title"><?php esc_html_e( 'Dziękujemy za zamówienie!', 'grofi' ); ?></h1>

					<p class="thankyou-page__notice woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
						<?php echo apply_filters( // phpcs:ignore
							'woocommerce_thankyou_order_received_text',
							esc_html__( 'Twoje zamówienie zostało przyjęte. Potwierdzenie wysłaliśmy na podany adres e-mail.', 'woocommerce' ),
							$order
						); ?>
					</p>

					<ul class="thankyou-page__details woocommerce-order-overview woocommerce-thankyou-order-details order_details">

						<li class="woocommerce-order-overview__order">
							<span class="thankyou-page__details-label"><?php esc_html_e( 'Numer zamówienia', 'woocommerce' ); ?></span>
							<strong><?php echo $order->get_order_number(); // phpcs:ignore ?></strong>
						</li>

						<li class="woocommerce-order-overview__date">
							<span class="thankyou-page__details-label"><?php esc_html_e( 'Data', 'woocommerce' ); ?></span>
							<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore ?></strong>
						</li>

						<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
							<li class="woocommerce-order-overview__email">
								<span class="thankyou-page__details-label"><?php esc_html_e( 'E-mail', 'woocommerce' ); ?></span>
								<strong><?php echo $order->get_billing_email(); // phpcs:ignore ?></strong>
							</li>
						<?php endif; ?>

						<li class="woocommerce-order-overview__total">
							<span class="thankyou-page__details-label"><?php esc_html_e( 'Suma', 'woocommerce' ); ?></span>
							<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore ?></strong>
						</li>

						<?php if ( $order->get_payment_method_title() ) : ?>
							<li class="woocommerce-order-overview__payment-method">
								<span class="thankyou-page__details-label"><?php esc_html_e( 'Metoda płatności', 'woocommerce' ); ?></span>
								<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
							</li>
						<?php endif; ?>

					</ul>

					<div class="thankyou-page__actions">
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button button--light">
							<?php esc_html_e( 'Wróć do sklepu', 'grofi' ); ?>
						</a>
						<?php if ( is_user_logged_in() ) : ?>
							<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button button--orange">
								<?php esc_html_e( 'Moje zamówienia', 'grofi' ); ?>
							</a>
						<?php endif; ?>
					</div>

				</div>

			<?php endif; ?>

			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

		<?php else : ?>

			<div class="thankyou-page__card thankyou-page__card--success">
				<h1 class="thankyou-page__title"><?php esc_html_e( 'Zamówienie złożone', 'grofi' ); ?></h1>
				<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
					<?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Dziękujemy. Twoje zamówienie zostało przyjęte.', 'woocommerce' ), false ); // phpcs:ignore ?>
				</p>
			</div>

		<?php endif; ?>

	</div>

</div>
