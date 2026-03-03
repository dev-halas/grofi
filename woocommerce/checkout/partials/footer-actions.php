<?php
/**
 * Checkout partial – Regulamin + Akcje formularza
 * (przycisk "Wróć do koszyka" + submit)
 */

defined( 'ABSPATH' ) || exit;

$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Kupuję i płacę', 'grofi' ) );
?>
<div class="checkout-footer">

	<?php wc_get_template( 'checkout/terms.php' ); ?>

	<div class="checkout-footer__actions">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="checkout-back-link">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M19 12H5M12 19l-7-7 7-7"/>
			</svg>
			<?php esc_html_e( 'Wróć do koszyka', 'grofi' ); ?>
		</a>

		<?php
		echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'woocommerce_order_button_html',
			'<button type="submit" class="button button--orange checkout-submit" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>'
		);
		?>
	</div>

</div>