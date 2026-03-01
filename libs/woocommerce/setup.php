<?php

// -------------------------------------------------------
// Deklaracja wsparcia WooCommerce w motywie
// -------------------------------------------------------
add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce', [
		'thumbnail_image_width' => 400,
		'single_image_width'    => 800,
		'product_grid'          => [
			'default_rows'    => 4,
			'min_rows'        => 2,
			'max_rows'        => 8,
			'default_columns' => 3,
			'min_columns'     => 2,
			'max_columns'     => 4,
		],
	] );
	/*
	add_theme_support('wc-product-gallery-zoom');
	add_theme_support('wc-product-gallery-lightbox');
	add_theme_support('wc-product-gallery-slider');
	*/
} );


// -------------------------------------------------------
// Wyłącz domyślne style WooCommerce – używamy własnych
// -------------------------------------------------------
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );


// -------------------------------------------------------
// Liczba produktów i kolumn w pętli sklepu
// -------------------------------------------------------
add_filter( 'loop_shop_per_page', function (): int {
	$allowed = [ 16, 32, 48 ];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['per_page'] ) ) {
		$requested = (int) $_GET['per_page'];
		if ( in_array( $requested, $allowed, true ) ) {
			return $requested;
		}
	}
	return 16;
}, 20 );

add_filter( 'loop_shop_columns', fn () => 3 );
