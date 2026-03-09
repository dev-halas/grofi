<?php
/**
 * Loop Add to Cart — przycisk z ikoną koszyka.
 *
 * Dodaje klasę product-card__cart-btn do linku dodawania do koszyka.
 * Ikona ładowana inline z pliku SVG.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// ─── Dane ─────────────────────────────────────────────────────────────────────

$product_id  = $product->get_id();
$icon_svg    = grofi_get_theme_svg( '_dev/assets/icons/cart-white.svg' );
$btn_class   = 'product-card__cart-btn ' . ( $args['class'] ?? 'button add_to_cart_button' );
$quantity    = (int) ( $args['quantity'] ?? 1 );
$attributes  = isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '';

$aria_describedby = isset( $args['aria-describedby_text'] )
    ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product_id ) )
    : '';

// ─── Przycisk ─────────────────────────────────────────────────────────────────

/*
 * $icon_svg pochodzi wyłącznie z pliku na dysku motywu – nie z danych użytkownika.
 * Escaping celowo pominięty, by nie uszkodzić znaczników SVG.
 */
echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'woocommerce_loop_add_to_cart_link',
    sprintf(
        '<a href="%s" %s data-quantity="%s" class="%s" %s aria-label="%s">%s</a>',
        esc_url( $product->add_to_cart_url() ),
        $aria_describedby,
        esc_attr( $quantity ),
        esc_attr( $btn_class ),
        $attributes,
        esc_attr( $product->add_to_cart_description() ),
        $icon_svg
    ),
    $product,
    $args
);

// ─── Aria describedby (tekst dla czytników ekranu) ───────────────────────────

if ( isset( $args['aria-describedby_text'] ) ) : ?>
    <span
        id="woocommerce_loop_add_to_cart_link_describedby_<?php echo esc_attr( $product_id ); ?>"
        class="screen-reader-text"
    >
        <?php echo esc_html( $args['aria-describedby_text'] ); ?>
    </span>
<?php endif;