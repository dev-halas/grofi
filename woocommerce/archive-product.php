<?php
defined('ABSPATH') || exit;

get_header();
?>

<?php do_action('woocommerce_before_main_content'); ?>

<?php get_template_part( 'woocommerce/template-parts/shop-title' ); ?>

<div class="shop-layout">

	<aside class="shop-layout__sidebar">

    <!-- Category Tree & Shop Filters -->
		<?php 
			get_template_part( 'woocommerce/template-parts/category-tree' );
			get_template_part( 'woocommerce/template-parts/shop-filters' );
		?>

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
        woocommerce_pagination();
			?>

		<?php else : ?>
			<?php do_action( 'woocommerce_no_products_found' ); ?>
		<?php endif; ?>

	</div>

</div>

<p class="shop-layout__description"><?php the_content(); ?></p>

<?php do_action('woocommerce_after_main_content'); ?>

<?php get_footer(); ?>