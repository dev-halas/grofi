<?php get_header(); ?>

<main>
  <div class="container">
    <?php the_post();?>
    <?php the_content(); ?>
  </div>
</main>
<?php get_footer(); ?>