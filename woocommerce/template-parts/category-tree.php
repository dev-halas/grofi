<?php
/**
 * Template Part: Sidebar — Category Tree
 *
 * Użycie w sidebar.php lub innym template:
 * get_template_part( 'template-parts/sidebar/category-tree' );
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Pobierz drzewo i aktywne kategorie
$cat_tree   = theme_get_product_cat_tree( 0, 0, 4 ); // max 4 poziomy — NIE dawaj -1!
$active_ids = theme_get_active_cat_ids();

if ( empty( $cat_tree ) ) {
    return;
}
?>

<nav class="cat-tree shop-sidebar__widget"
     aria-label="<?php esc_attr_e( 'Kategorie produktów', 'grofi' ); ?>">

    <?php theme_render_cat_tree( $cat_tree, $active_ids, 0 ); ?>

</nav>