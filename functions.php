<?php

	if(!defined('THEME_DIR')) {
		define('THEME_DIR',get_theme_root().'/'.get_template().'/');
	}

	if(!defined('THEME_URL')) {
		define('THEME_URL',WP_CONTENT_URL.'/themes/'.get_template().'/');
	}

	function enqueue_vite_assets() {
    	$theme_dist = get_template_directory_uri() . '/dist';

		wp_enqueue_script('theme-js', $theme_dist . '/app.js', [], null, true);
		wp_enqueue_style('theme-css', $theme_dist . '/main.css', [], null);

	}

	add_action('wp_enqueue_scripts', 'enqueue_vite_assets');

	//--------------Register menus--------------//
	if(function_exists('register_nav_menus')) { 
		register_nav_menus (array(
			'main_nav'      => 'Menu glowne',
			'footer_nav_1'  => 'Stopka Menu 1',
			'footer_nav_2'  => 'Stopka Menu 2',
			'footer_nav_3'  => 'Stopka Menu 3',
		));
	}

	require_once THEME_DIR . 'libs/post-types.php';
	require_once THEME_DIR . 'libs/nav-menu.php';
	require_once THEME_DIR . 'libs/product-cat-icon.php';
	require_once THEME_DIR . 'libs/omnibus-price.php';

	if ( class_exists('WooCommerce') ) {
		require_once THEME_DIR . 'libs/woocommerce.php';
	}

	if( function_exists('acf_add_options_page') ) {
		acf_add_options_page(array(
			'page_title' 	=> 'Główne ustawienia',
			'menu_title'	=> 'Główne ustawienia',
			'menu_slug' 	=> 'theme-general-settings',
			'capability'	=> 'edit_posts',
			'redirect'		=> false,
		));
	}


	function get_menu_name_by_location($location, $fallback = '') {
		static $locations = null;

		if ($locations === null) {
			$locations = get_nav_menu_locations();
		}

		if (!empty($locations[$location])) {
			$menu = wp_get_nav_menu_object($locations[$location]);

			if ($menu && !empty($menu->name)) {
				return esc_html($menu->name);
			}
		}

		return esc_html($fallback);
	}


	add_filter( 'dgwt/wcas/form/magnifier_ico', function () {
		return file_get_contents( THEME_URL . '_dev/assets/icons/search.svg' );
	});


	/**
	 * Rejestracja sidebara sklepu WooCommerce.
	 */
	add_action( 'widgets_init', function () {
		register_sidebar( array(
			'name'          => 'Sidebar sklepu',
			'id'            => 'shop-sidebar',
			'description'   => 'Widgety wyświetlane w sidebarze na stronie archiwum produktów WooCommerce.',
			'before_widget' => '<div class="shop-sidebar__widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="shop-sidebar__widget-title">',
			'after_title'   => '</h3>',
		) );
	} );
	
