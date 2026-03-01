<?php
/**
 * Karta produktu w pętli sklepu / archiwum kategorii.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0 (overridden)
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! ( $product instanceof WC_Product ) || ! $product->is_visible() ) {
    return;
}

$product_id   = $product->get_id();
$product_link = get_permalink( $product_id );
$product_name = esc_html( get_the_title( $product_id ) );
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>

    <?php if ( $product->is_on_sale() ) : ?>
        <span class="product-card__badge"><?php esc_html_e( 'Promocja', 'grofi' ); ?></span>
    <?php endif; ?>

    <a href="<?php echo esc_url( $product_link ); ?>" class="product-card__img-link" tabindex="-1" aria-hidden="true">
		<?php echo $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'product-card__img', 'alt' => '' ] ); ?>
    </a>

    <div class="product-card__body">

        <a href="<?php echo esc_url( $product_link ); ?>" class="product-card__title-link">
            <h2 class="product-card__title woocommerce-loop-product__title">
                <?php echo $product_name; ?>
            </h2>
        </a>

        <div class="product-card__footer">

            <div class="product-card__price-wrap">
                <?php woocommerce_template_loop_price(); ?>
                <?php get_template_part( 'woocommerce/template-parts/lowest-price', null, [
                    'product_id' => $product_id,
                    'label'      => __( 'Najniższa cena: ', 'grofi' ),
                ] ); ?>
            </div>

            <?php woocommerce_template_loop_add_to_cart(); ?>

        </div>

    </div>

</li>