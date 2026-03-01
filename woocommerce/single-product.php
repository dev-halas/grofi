<?php
/**
 * Single Product – custom template.
 *
 * Fully custom layout: gallery + summary grid, breadcrumbs, tabs, related products.
 * Quantity stepper rendered by woocommerce/global/quantity-input.php (no JS injection).
 */
defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
	the_post();

	/** @var WC_Product $product */
	global $product;
	$product = wc_get_product(get_the_ID());

	if (!$product) :
		continue;
	endif;
?>

<div class="woocommerceMain container">

	<?php woocommerce_breadcrumb(); ?>
	<?php woocommerce_output_all_notices(); ?>

	<div id="product-<?php the_ID(); ?>" <?php wc_product_class('product', $product); ?>>

		<?php // ── Galeria ──────────────────────────────────────────── ?>
		<div class="woocommerce-product-gallery">
			<?php woocommerce_show_product_images(); ?>
		</div>

		<?php // ── Kolumna informacji ───────────────────────────────── ?>
		<div class="summary entry-summary">

			<?php woocommerce_template_single_meta(); ?>

			<h1 class="product_title entry-title">
				<?php the_title(); ?>
			</h1>

			<?php woocommerce_template_single_rating(); ?>

			<?php
			$short_desc = $product->get_short_description();
			if ($short_desc) : ?>
				<div class="woocommerce-product-details__short-description">
					<?php echo apply_filters('woocommerce_short_description', $short_desc); ?>
				</div>
			<?php endif; ?>

			<div class="price-and-cart">
				<div class="price-wrapper">
					<div class="price"><?php echo $product->get_price_html(); ?></div>
					<?php get_template_part( 'woocommerce/template-parts/lowest-price', null, [ 'product_id' => get_the_ID() ] ); ?>
				</div>

				<?php get_template_part( 'woocommerce/template-parts/cart-button' ); ?>
				
			</div>

			<?php get_template_part( 'woocommerce/template-parts/single-product-additional' ); ?>
			<?php get_template_part( 'woocommerce/template-parts/single-product-contact' ); ?>

		</div>

		<?php // ── Zakładki: opis, atrybuty, recenzje ─────────────── ?>
		<?php woocommerce_output_product_data_tabs(); ?>

	</div>

	<?php // ── Produkty powiązane ───────────────────────────────────── ?>
	<?php woocommerce_output_related_products(); ?>

</div>

<?php endwhile; ?>

<?php get_footer(); ?>
