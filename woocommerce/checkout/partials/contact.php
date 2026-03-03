<?php
/**
 * Checkout partial – Sekcja 1: Dane kontaktowe (email + telefon)
 *
 * @var WC_Checkout $checkout
 * @var callable    $field_as_placeholder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="checkout-card">
	<h2 class="checkout-section__title">
		<span class="checkout-section__step">1</span>
		<?php esc_html_e( 'Dane kontaktowe', 'grofi' ); ?>
		<?php if ( ! is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( wp_login_url( wc_get_checkout_url() ) ); ?>" class="checkout-section__login-link">
				<?php esc_html_e( 'Zaloguj się', 'grofi' ); ?>
			</a>
		<?php endif; ?>
	</h2>

	<p class="checkout-guest-note">
		<?php esc_html_e( 'Na ten adres e-mail wyślemy szczegóły i aktualizacje twojego zamówienia.', 'grofi' ); ?>
	</p>

	<div class="checkout-contact-fields">
    <div class="checkout-contact-fields__field-wrapper">
      <?php
      $all_billing = $checkout->get_checkout_fields( 'billing' );

      foreach ( [ 'billing_email', 'billing_phone' ] as $key ) {
        if ( isset( $all_billing[ $key ] ) ) {
            $field_floating_label( $key, $all_billing[ $key ], $checkout->get_value( $key ) ); // ← ta nazwa
        }
      }
      ?>
    </div>
  </div>

	<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() && ! $checkout->is_registration_required() ) : ?>
		<p class="form-row form-row-wide create-account checkout-create-account">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input
					class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
					id="createaccount"
					<?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?>
					type="checkbox"
					name="createaccount"
					value="1"
				/>
				<span><?php esc_html_e( 'Utwórz konto za pomocą Hurtowni Grofi', 'grofi' ); ?></span>
			</label>
		</p>
	<?php endif; ?>
</div>