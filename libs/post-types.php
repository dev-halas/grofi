<?php
add_action('init', 'theme_init_posttypes');

function theme_init_posttypes()
{
  // === CUSTOM POST TYPE: Portfolio ===
  register_post_type('portfolio', array(
    'labels' => array(
      'name'               => __('Portfolio', 'MSG'),
      'singular_name'      => __('Projekt', 'MSG'),
      'menu_name'          => __('Portfolio', 'MSG'),
      'name_admin_bar'     => __('Projekt', 'MSG'),
      'add_new'            => __('Dodaj nowy', 'MSG'),
      'add_new_item'       => __('Dodaj nowy projekt', 'MSG'),
      'edit_item'          => __('Edytuj projekt', 'MSG'),
      'new_item'           => __('Nowy projekt', 'MSG'),
      'view_item'          => __('Zobacz projekt', 'MSG'),
      'search_items'       => __('Szukaj w portfolio', 'MSG'),
      'not_found'          => __('Brak projektów', 'MSG'),
      'not_found_in_trash' => __('Brak projektów w koszu', 'MSG'),
      'all_items'          => __('Wszystkie projekty', 'MSG'),
    ),
    'public'             => true,
    'has_archive'        => true,
    'menu_icon'          => 'dashicons-portfolio',
    'menu_position'      => 20,
    'hierarchical'       => false,
    'show_in_rest'       => true, // Gutenberg + REST API
    'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author'),
    'rewrite'            => array('slug' => 'portfolio', 'with_front' => false),
  ));

  // === Taksonomia hierarchiczna: Kategorie portfolio ===
  register_taxonomy('portfolio_category', array('portfolio'), array(
    'labels' => array(
      'name'              => __('Kategorie portfolio', 'MSG'),
      'singular_name'     => __('Kategoria portfolio', 'MSG'),
      'search_items'      => __('Szukaj kategorii', 'MSG'),
      'all_items'         => __('Wszystkie kategorie', 'MSG'),
      'parent_item'       => __('Kategoria nadrzędna', 'MSG'),
      'parent_item_colon' => __('Kategoria nadrzędna:', 'MSG'),
      'edit_item'         => __('Edytuj kategorię', 'MSG'),
      'update_item'       => __('Zaktualizuj kategorię', 'MSG'),
      'add_new_item'      => __('Dodaj nową kategorię', 'MSG'),
      'new_item_name'     => __('Nazwa nowej kategorii', 'MSG'),
      'menu_name'         => __('Kategorie', 'MSG'),
    ),
    'hierarchical'  => true,
    'show_in_rest'  => true,
    'rewrite'       => array('slug' => 'portfolio-kategoria', 'with_front' => false),
    'public'        => true,
  ));

}


add_action('after_setup_theme', function () {
  add_theme_support('post-thumbnails', array('post', 'page', 'portfolio'));
});

