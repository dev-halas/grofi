<?php
/**
 * Fragment Endpoint — AJAX nawigacja sklepu.
 *
 * Gdy request zawiera nagłówek "X-Shop-Ajax: 'fragments'", zamiast renderować pełną stronę
 * zwraca JSON z fragmentami HTML. Oszczędza ~85% danych względem pełnego dokumentu.
 *
 * Uruchamiane przez: shop-filter.js (fetchPage z nagłówkiem X-Shop-Ajax)
 * Aktywne na:        is_shop() | is_product_category() | is_product_tag()
 *
 * Zwracane klucze JSON:
 *   content    — innerHTML .shop-layout__content (toolbar + pętla produktów + paginacja)
 *   filters    — outerHTML #shop-filters
 *   cat_tree   — outerHTML .cat-tree
 *   title      — outerHTML .shop-layout__title
 *   page_title — tytuł karty przeglądarki (document.title)
 */
defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'grofi_shop_ajax_handler', 1 );

function grofi_shop_ajax_handler(): void {

    // ── Walidacja ──────────────────────────────────────────────────────────────
    if ( ( $_SERVER['HTTP_X_SHOP_AJAX'] ?? '' ) !== 'fragments' ) {
        return;
    }

    if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_send_json( [ 'error' => 'method_not_allowed' ], 405 );
    }

    if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
        wp_send_json( [ 'error' => 'invalid_context' ], 400 );
    }

    // ── Cache headers ──────────────────────────────────────────────────────────
    header( 'Cache-Control: public, max-age=60, stale-while-revalidate=300' );
    header( 'Vary: X-Shop-Ajax' );

    // ── Fragmenty ──────────────────────────────────────────────────────────────
    ob_start();
    get_template_part( 'woocommerce/template-parts/shop-title' );
    $title_html = ob_get_clean() ?: '';

    ob_start();
    get_template_part( 'woocommerce/template-parts/category-tree' );
    $cat_tree_html = ob_get_clean() ?: '';

    ob_start();
    get_template_part( 'woocommerce/template-parts/shop-filters' );
    $filters_html = ob_get_clean() ?: '';

    ob_start();
    get_template_part( 'woocommerce/template-parts/shop-toolbar' );

    if ( woocommerce_product_loop() ) {
        wc_set_loop_prop( 'name', 'shop' );
        wc_set_loop_prop( 'is_paginated', true );
        woocommerce_product_loop_start();
        woocommerce_product_subcategories();

        while ( have_posts() ) {
            the_post();
            wc_get_template_part( 'content', 'product' );
        }

        woocommerce_product_loop_end();
        do_action( 'woocommerce_after_shop_loop' );
        woocommerce_pagination();
    } else {
        do_action( 'woocommerce_no_products_found' );
    }

    $content_html = ob_get_clean() ?: '';

    // ── Odpowiedź ──────────────────────────────────────────────────────────────
    wp_send_json( [
        'content'    => $content_html,
        'filters'    => $filters_html,
        'cat_tree'   => $cat_tree_html,
        'title'      => $title_html,
        'page_title' => wp_get_document_title(),
    ] );
}