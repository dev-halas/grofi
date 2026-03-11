<section class="productCategories">
  <div class="container">
    <?php
      $excluded_ids = array_filter( array_map(
        fn( $slug ) => get_term_by( 'slug', $slug, 'product_cat' )->term_id ?? null,
        [ 'uncategorized', 'bez-kategorii' ]
      ) );

      $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0,
        'orderby'    => 'name', // menu_order for ordering by dashboard
        'order'      => 'ASC',
        'exclude'    => $excluded_ids,
      ]);

      if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
        <ul class="productCategories__list">
          <?php foreach ( $categories as $category ) :
            $thumbnail_id  = get_term_meta( $category->term_id, 'thumbnail_id', true );
            $image_url     = $thumbnail_id
              ? wp_get_attachment_image_url( $thumbnail_id, 'medium' )
              : wc_placeholder_img_src( 'medium' );
            $category_link = get_term_link( $category );
          ?>
            <li class="productCategories__item">
              <a href="<?php echo esc_url( $category_link ); ?>" class="productCategories__link">
                <div class="productCategories__image">
                  <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $category->name ); ?>">
                </div>
                <span class="productCategories__name"><?php echo esc_html( $category->name ); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
    <?php endif; ?>
  </div>
</section>
