<?php
/**
 * Template Part: Tytuł archiwum sklepu z ikoną głównej kategorii
 *
 * Użycie: get_template_part( 'woocommerce/template-parts/shop-title' );
 */

defined( 'ABSPATH' ) || exit;

$_title_term   = is_tax( 'product_cat' ) ? get_queried_object() : null;
$_icon_term_id = 0;

if ( $_title_term instanceof WP_Term ) {
	// Przejdź do korzenia drzewa (główna kategoria nadrzędna)
	$_ancestors    = get_ancestors( $_title_term->term_id, 'product_cat', 'taxonomy' );
	$_icon_term_id = $_ancestors ? (int) end( $_ancestors ) : (int) $_title_term->term_id;
}

$_icon_url = $_icon_term_id ? get_product_cat_icon_url( $_icon_term_id ) : '';
?>
<h1 class="shop-layout__title">
	<?php if ( $_icon_url ) : ?>
		<img class="shop-layout__title-icon"
		     src="<?php echo esc_url( $_icon_url ); ?>"
		     alt=""
		     aria-hidden="true"
		     width="32"
		     height="32">
	<?php endif; ?>
	<?php woocommerce_page_title(); ?>
	<?php if ( $_title_term instanceof WP_Term ) : ?>
		<span class="shop-layout__title-count">(<?php echo (int) $_title_term->count; ?>)</span>
	<?php endif; ?>
</h1>
