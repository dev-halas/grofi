<?php

// -------------------------------------------------------
// Usuń domyślny sidebar WooCommerce
// -------------------------------------------------------
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );


// -------------------------------------------------------
// Wrapper .woocommerceMain.container — breadcrumb renderowany
// jawnie w szablonie archive-product.php
// -------------------------------------------------------
add_action( 'woocommerce_before_main_content', function () {
	echo '<div class="woocommerceMain container">';
}, 10 );

add_action( 'woocommerce_after_main_content', function () {
	echo '</div>';
}, 10 );


// -------------------------------------------------------
// Wygląd okruszków
// -------------------------------------------------------
/*
add_filter( 'woocommerce_breadcrumb_defaults', function ( array $defaults ): array {
	$defaults['delimiter']   = '<span class="breadcrumb-sep" aria-hidden="true">/</span>';
	$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="Nawigacja okruszkowa">';
	$defaults['wrap_after']  = '</nav>';
	return $defaults;
} );
*/



// -------------------------------------------------------
// Usuń opis kategorii ze strony sklepu
// -------------------------------------------------------
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );


// -------------------------------------------------------
// Zakładka opisu produktu – wymuś wyświetlanie nawet gdy
// post_content jest pusty (opis pochodzi z ACF prod_desc)
// -------------------------------------------------------
add_filter( 'woocommerce_product_tabs', function ( array $tabs ): array {
	if ( ! isset( $tabs['description'] ) ) {
		$tabs['description'] = [
			'title'    => __( 'Opis', 'woocommerce' ),
			'priority' => 10,
			'callback' => 'woocommerce_product_description_tab',
		];
	}
	return $tabs;
}, 5 );


// -------------------------------------------------------
// Polskie teksty przycisków "Dodaj do koszyka"
// -------------------------------------------------------
add_filter( 'woocommerce_product_add_to_cart_text', function ( string $text, \WC_Product $product ): string {
	return ( $product->is_type( 'simple' ) && $product->is_in_stock() )
		? __( 'Dodaj do koszyka', 'grofi' )
		: $text;
}, 10, 2 );

add_filter( 'woocommerce_product_single_add_to_cart_text', function ( string $text, \WC_Product $product ): string {
	return $product->is_in_stock()
		? __( 'Dodaj do koszyka', 'grofi' )
		: $text;
}, 10, 2 );
