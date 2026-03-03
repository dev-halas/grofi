<?php

// -------------------------------------------------------
// Checkout – dodatkowe akcje i filtry
// -------------------------------------------------------


add_filter( 'woocommerce_order_button_text', function (): string {
	return __( 'Kupuję i płacę', 'grofi' );
} );

// Ukryj tytuł strony – renderowany własny w form-checkout.php
add_filter( 'the_title', function ( string $title, int $post_id = 0 ): string {
	if ( is_checkout() && ! is_wc_endpoint_url() && (int) get_option( 'woocommerce_checkout_page_id' ) === $post_id ) {
		return '';
	}
	return $title;
}, 10, 2 );


add_filter( 'woocommerce_default_address_fields', function( $fields ) {

  if ( isset( $fields['address_1'] ) ) {
      $fields['address_1']['label']       = __( 'Ulica (nazwa ulicy, numer budynku)', 'grofi' );
      $fields['address_1']['placeholder'] = ' ';
  }

  if ( isset( $fields['address_2'] ) ) {
      $fields['address_2']['label']       = __( 'Numer lokalu (opcjonalnie)', 'grofi' );
      $fields['address_2']['placeholder'] = ' ';
      $fields['address_2']['label_class'] = [];
  }

  return $fields;
} );


remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

// Lokalizuj dane dla Alpine.js w checkoucie
// UWAGA: handle musi zgadzać się z wp_enqueue_script w functions.php → 'theme-js'
add_action( 'wp_enqueue_scripts', function (): void {
	if ( ! is_checkout() ) {
		return;
	}

	wp_localize_script(
		'theme-js',
		'grofi_checkout_data',
		[
			'apply_coupon_nonce'  => wp_create_nonce( 'apply-coupon' ),
			'remove_coupon_nonce' => wp_create_nonce( 'remove-coupon' ),
		]
	);
} );
