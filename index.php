<?php get_header();?>

<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
<?php else : ?>
	<article class="no-posts">
		<p><?php esc_html_e('Brak wpisów do wyświetlenia.', 'MSG'); ?></p>
	</article>
<?php endif; ?>

<?php get_footer();?>