<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header();
?>

<?php do_action('woocommerce_before_main_content'); ?>

<?php get_template_part( 'woocommerce/template-parts/shop-title' ); ?>

<div class="shop-layout">

	<aside class="shop-layout__sidebar">

		<div class="mobile-sidebar__title mobile-sidebar__toggle" aria-expanded="false">Kategorie i filtry</div>
		<div class="sidebar-content">
			<?php
				get_template_part( 'woocommerce/template-parts/category-tree' );
				get_template_part( 'woocommerce/template-parts/shop-filters' );
			?>
		</div>

		<?php if ( is_active_sidebar('shop-sidebar') ) : ?>
			<div class="shop-sidebar">
				<?php dynamic_sidebar('shop-sidebar'); ?>
			</div>
		<?php endif; ?>

	</aside>

	<div class="shop-layout__content">

		<?php get_template_part( 'woocommerce/template-parts/shop-toolbar' ); ?>

		<?php if ( woocommerce_product_loop() ) : ?>

			<?php
        woocommerce_product_loop_start();
          woocommerce_product_subcategories();
          while ( have_posts() ) :
            the_post();
            wc_get_template_part( 'content', 'product' );
          endwhile;
        woocommerce_product_loop_end();

        do_action( 'woocommerce_after_shop_loop' );
			?>

		<?php else : ?>
			<?php do_action( 'woocommerce_no_products_found' ); ?>
		<?php endif; ?>

	</div>

</div>

<p class="shop-layout__description"><?php the_content(); ?></p>

<?php do_action('woocommerce_after_main_content'); ?>

<?php get_footer(); ?>