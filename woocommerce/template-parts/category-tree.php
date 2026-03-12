<?php
/**
 * Template Part: Sidebar — Category Tree
 *
 * Użycie w sidebar.php lub innym template:
 * get_template_part( 'woocommerce/template-parts/category-tree' );
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

<h3 class="shop-sidebar__title shop-sidebar__title--toggle" aria-expanded="true">
    Kategorie produktów
    <svg class="shop-sidebar__title-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
</h3>
<nav class="cat-tree shop-sidebar__widget"
     aria-label="<?php esc_attr_e( 'Kategorie produktów', 'grofi' ); ?>">
    <?php theme_render_cat_tree( $cat_tree, $active_ids, 0 ); ?>
</nav>