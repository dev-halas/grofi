<?php

// -------------------------------------------------------
// Pomocnik: wczytaj plik SVG z dysku motywu (z cache'em)
// -------------------------------------------------------
/**
 * Zwraca zawartość pliku SVG z cache'owaniem w pamięci procesu.
 * Plik pochodzi z dysku motywu — traktujemy go jako zaufane źródło.
 *
 * @param  string $relative_path Ścieżka względna od katalogu motywu.
 * @return string                Zawartość SVG lub pusty string gdy plik nie istnieje.
 */

// -------------------------------------------------------
// Category Sidebar Tree
// -------------------------------------------------------
require_once THEME_DIR . 'woocommerce/inc/category-sidebar-tree.php';


// -------------------------------------------------------
// Helper: chevron SVG dla nagłówków sekcji filtrów
// -------------------------------------------------------
function theme_shop_filter_chevron(): string {
	return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
	     fill="none" stroke="currentColor" stroke-width="2.5"
	     stroke-linecap="round" stroke-linejoin="round"
	     :class="{ \'is-open\': open }" aria-hidden="true">
		<polyline points="6 9 12 15 18 9"></polyline>
	</svg>';
}


// -------------------------------------------------------
// Natywne filtrowanie atrybutów WooCommerce (lookup table)
//
// WC_Query::filter_by_attribute_post_clauses() obsługuje ?filter_{attr}=val1,val2
// i poprawnie filtruje produkty zmienne (po dostępnych wariantach).
// Wymaga tabeli wc_product_attributes_lookup.
//
// JEDNORAZOWA KONFIGURACJA:
//   WooCommerce → Status → Narzędzia → "Regeneruj tabelę atrybutów produktów"
//   Nowe produkty będą dodawane automatycznie po zapisaniu.
// -------------------------------------------------------
add_filter( 'option_woocommerce_attribute_lookup_enabled', fn (): string => 'yes' );


// -------------------------------------------------------
// Filtr: ?brand=slug1,slug2 → tax_query
//
// Marka (product_brand) to NIE jest atrybut pa_*, więc WC lookup table jej
// nie obsługuje. Filtrujemy ją ręcznie przez standardowe tax_query.
// -------------------------------------------------------
add_action( 'woocommerce_product_query', function ( WP_Query $query ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$brand_raw = sanitize_text_field( wp_unslash( $_GET['brand'] ?? '' ) );

	if ( empty( $brand_raw ) || ! taxonomy_exists( 'product_brand' ) ) {
		return;
	}

	$slugs = array_values( array_filter(
		array_map( 'sanitize_title', explode( ',', $brand_raw ) )
	) );

	if ( empty( $slugs ) ) {
		return;
	}

	$tax_query   = (array) $query->get( 'tax_query' );
	$tax_query[] = [
		'taxonomy' => 'product_brand',
		'field'    => 'slug',
		'terms'    => $slugs,
		'operator' => 'IN',
	];
	$query->set( 'tax_query', $tax_query );
} );


// =======================================================
// OPTYMALIZACJA FILTRÓW DLA DUŻEGO KATALOGU (3k+ produktów)
// =======================================================

/**
 * Pobiera terminy danej taksonomii (marka / atrybut) które mają produkty
 * w podanych kategoriach — JEDNYM zapytaniem SQL z JOIN zamiast
 * get_posts(posts_per_page:-1) + get_terms(object_ids:[...]).
 *
 * Bez tego PHP musiałby załadować tysiące ID produktów z MySQL do pamięci
 * i odesłać je z powrotem w klauzuli IN() — podwójny round-trip.
 *
 * @param string $taxonomy  Np. 'product_brand', 'pa_kolor'.
 * @param int[]  $cat_ids   ID kategorii (bieżąca + dzieci).
 *
 * @return object[]  stdClass z polami: term_id, name, slug, count.
 */
function grofi_get_filter_terms_in_cat( string $taxonomy, array $cat_ids ): array {
	global $wpdb;

	if ( empty( $cat_ids ) ) {
		return [];
	}

	$cat_ids_sql = implode( ',', array_map( 'intval', $cat_ids ) );

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT t.term_id, t.name, t.slug,
			        COUNT( DISTINCT tr_attr.object_id ) AS count
			 FROM      {$wpdb->terms}            t
			 INNER JOIN {$wpdb->term_taxonomy}    tt
			            ON  t.term_id           = tt.term_id
			            AND tt.taxonomy         = %s
			 INNER JOIN {$wpdb->term_relationships} tr_attr
			            ON  tt.term_taxonomy_id = tr_attr.term_taxonomy_id
			 INNER JOIN {$wpdb->term_relationships} tr_cat
			            ON  tr_attr.object_id   = tr_cat.object_id
			 INNER JOIN {$wpdb->term_taxonomy}    tt_cat
			            ON  tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
			            AND tt_cat.taxonomy     = 'product_cat'
			            AND tt_cat.term_id      IN ( {$cat_ids_sql} )
			 GROUP BY t.term_id, t.name, t.slug
			 ORDER BY t.name ASC",
			$taxonomy
		)
	);
	// phpcs:enable

	return $rows ?: [];
}


/**
 * Zwraca dane filtrów (marki + atrybuty) dla bieżącej strony sklepu.
 * Wynik jest cache'owany w transiencie przez 6h.
 *
 * Klucz transientu: grofi_sf_{cat_id}  (0 = strona główna sklepu).
 *
 * @param WP_Term|null $cat_term  Bieżąca kategoria lub null dla głównej strony sklepu.
 *
 * @return array{brands: object[], attributes: array[]}
 */
function grofi_get_filter_data( ?WP_Term $cat_term ): array {
	$cat_id    = $cat_term ? $cat_term->term_id : 0;
	$cache_key = 'grofi_sf_' . $cat_id;
	$cached    = get_transient( $cache_key );

	if ( $cached !== false ) {
		return $cached;
	}

	// ── Rozwiń kategorie: bieżąca + wszystkie dzieci ──────────────────────
	if ( $cat_term ) {
		$child_ids = get_term_children( $cat_term->term_id, 'product_cat' );
		$cat_ids   = array_merge(
			[ $cat_term->term_id ],
			is_array( $child_ids ) ? array_map( 'intval', $child_ids ) : []
		);
	} else {
		$cat_ids = [];
	}

	// ── Marki ─────────────────────────────────────────────────────────────
	$brands = [];

	if ( taxonomy_exists( 'product_brand' ) ) {
		if ( $cat_term && empty( $cat_ids ) ) {
			$brands = []; // pusta kategoria
		} elseif ( $cat_ids ) {
			$brands = grofi_get_filter_terms_in_cat( 'product_brand', $cat_ids );
		} else {
			$result = get_terms( [
				'taxonomy'               => 'product_brand',
				'hide_empty'             => true,
				'orderby'                => 'name',
				'update_term_meta_cache' => false,
			] );
			$brands = is_wp_error( $result ) ? [] : $result;
		}
	}

	// ── Atrybuty WooCommerce ──────────────────────────────────────────────
	$attributes  = [];
	$wc_attrs    = function_exists( 'wc_get_attribute_taxonomies' )
		? wc_get_attribute_taxonomies()
		: [];

	foreach ( $wc_attrs as $attr ) {
		$taxonomy = wc_attribute_taxonomy_name( $attr->attribute_name );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		if ( $cat_term && empty( $cat_ids ) ) {
			continue; // pusta kategoria
		}

		if ( $cat_ids ) {
			$terms = grofi_get_filter_terms_in_cat( $taxonomy, $cat_ids );
		} else {
			$result = get_terms( [
				'taxonomy'               => $taxonomy,
				'hide_empty'             => true,
				'orderby'                => 'name',
				'update_term_meta_cache' => false,
			] );
			$terms = is_wp_error( $result ) ? [] : $result;
		}

		if ( empty( $terms ) ) {
			continue;
		}

		$attributes[] = [
			'label' => $attr->attribute_label,
			'param' => 'filter_' . $attr->attribute_name,
			'type'  => $attr->attribute_type,
			'terms' => $terms,
		];
	}

	$data = [ 'brands' => $brands, 'attributes' => $attributes ];
	set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );

	return $data;
}


// -------------------------------------------------------
// Invalidacja cache filtrów
// Wywołana przy zapisie produktu i zmianie kategorii.
// -------------------------------------------------------

/**
 * Usuwa wszystkie transienty filtrów (grofi_sf_*).
 * Używa $wpdb zamiast delete_transient() by nie musieć znać wszystkich kluczy.
 */
function grofi_flush_filter_cache(): void {
	global $wpdb;

	// Escape special LIKE chars in the prefix, potem dodaj wildcard
	$like_val     = $wpdb->esc_like( '_transient_grofi_sf_' ) . '%';
	$like_timeout = $wpdb->esc_like( '_transient_timeout_grofi_sf_' ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$like_val,
			$like_timeout
		)
	);
}

/**
 * Usuwa cache drzewa kategorii (bieżący + legacy klucze).
 */
function grofi_flush_cat_tree_cache(): void {
	delete_transient( 'grofi_cat_tree' );    // stary klucz (przed _v2)
	theme_flush_cat_tree_cache();            // aktualny klucz, def. w category-sidebar-tree.php
}

// Produkty
add_action( 'save_post_product',                           'grofi_flush_filter_cache' );
add_action( 'woocommerce_product_import_inserted_or_updated',
	fn ( \WC_Product $p ) => grofi_flush_filter_cache() );

// Kategorie produktów — flush obu cache'ów
add_action( 'edited_product_cat',  function (): void { grofi_flush_filter_cache(); grofi_flush_cat_tree_cache(); } );
add_action( 'created_product_cat', function (): void { grofi_flush_filter_cache(); grofi_flush_cat_tree_cache(); } );
add_action( 'delete_product_cat',  function (): void { grofi_flush_filter_cache(); grofi_flush_cat_tree_cache(); } );

// Marki
add_action( 'edited_product_brand',  'grofi_flush_filter_cache' );
add_action( 'created_product_brand', 'grofi_flush_filter_cache' );
add_action( 'delete_product_brand',  'grofi_flush_filter_cache' );


 function grofi_get_theme_svg( string $relative_path ): string {
	static $cache = [];

	if ( isset( $cache[ $relative_path ] ) ) {
		return $cache[ $relative_path ];
	}

	$absolute_path = get_theme_file_path( $relative_path );

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$cache[ $relative_path ] = file_exists( $absolute_path )
		? file_get_contents( $absolute_path )
		: '';

	return $cache[ $relative_path ];
}


// -------------------------------------------------------
// Deklaracja wsparcia WooCommerce w motywie
// -------------------------------------------------------
add_action('after_setup_theme', function () {
	add_theme_support('woocommerce', [
		'thumbnail_image_width' => 400,
		'single_image_width'    => 800,
		'product_grid'          => [
			'default_rows'    => 4,
			'min_rows'        => 2,
			'max_rows'        => 8,
			'default_columns' => 3,
			'min_columns'     => 2,
			'max_columns'     => 4,
		],
	]);
	/*
	add_theme_support('wc-product-gallery-zoom');
	add_theme_support('wc-product-gallery-lightbox');
	add_theme_support('wc-product-gallery-slider');
	*/
});


// -------------------------------------------------------
// Wyłącz domyślne style WooCommerce – używamy własnych
// -------------------------------------------------------
add_filter('woocommerce_enqueue_styles', '__return_empty_array');


// -------------------------------------------------------
// Shared template: przycisk koszyka w nagłówku
//
// Używany zarówno w header.php jak i w fragment AJAX,
// żeby HTML był zawsze identyczny (jQuery replaceWith
// wymaga dokładnego dopasowania selektora do outerHTML).
// -------------------------------------------------------
function grofi_cart_button(): void {
	$cart  = WC()->cart;
	$count = (int) $cart->get_cart_contents_count();
	$total = strip_tags($cart->get_cart_total());
	$url   = wc_get_cart_url();
	?>
	<a href="<?php echo esc_url($url); ?>"
	   class="cartButton"
	   aria-label="<?php esc_attr_e('Koszyk', 'grofi'); ?>">
		<div class="cartIcon">
			<span class="cartCount<?php echo $count > 0 ? ' cartCount--visible' : ''; ?>">
				<?php echo esc_html($count); ?>
			</span>
			<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="currentColor" aria-hidden="true">
				<path d="M8.81543 19.6338C10.4723 19.634 11.8203 20.9818 11.8203 22.6387C11.8203 24.2956 10.4723 25.6434 8.81543 25.6436C7.15839 25.6436 5.80957 24.2957 5.80957 22.6387C5.80963 20.9817 7.15843 19.6338 8.81543 19.6338ZM20.8359 19.6338C22.4928 19.6339 23.8408 20.9818 23.8408 22.6387C23.8408 24.2956 22.4929 25.6434 20.8359 25.6436C19.1789 25.6436 17.8311 24.2957 17.8311 22.6387C17.8311 20.9817 19.1789 19.6338 20.8359 19.6338ZM8.81543 21.6367C8.26312 21.6367 7.81354 22.0864 7.81348 22.6387C7.81348 23.191 8.26308 23.6406 8.81543 23.6406C9.36763 23.6405 9.81641 23.1909 9.81641 22.6387C9.81635 22.0865 9.3676 21.6369 8.81543 21.6367ZM20.8359 21.6367C20.2836 21.6367 19.834 22.0864 19.834 22.6387C19.834 23.191 20.2836 23.6406 20.8359 23.6406C21.3882 23.6405 21.8379 23.1909 21.8379 22.6387C21.8378 22.0864 21.3881 21.6368 20.8359 21.6367ZM2.3877 0C3.79517 0.000214296 5.03027 0.999714 5.3252 2.37598C5.32698 2.38433 5.32946 2.39297 5.33105 2.40137L5.59473 3.80664H24.6426C25.259 3.80684 25.7317 4.36045 25.6299 4.97266L23.9062 15.3193C23.6638 16.7744 22.4165 17.8311 20.9414 17.8311H8.67969C7.23039 17.8311 5.98775 16.7963 5.72461 15.3711L3.36426 2.78516C3.26197 2.33188 2.85316 2.00412 2.3877 2.00391H1.00195C0.448715 2.00391 1.74075e-05 1.55519 0 1.00195C0 0.448705 0.448705 0 1.00195 0H2.3877ZM7.69434 15.0049C7.78272 15.4826 8.19667 15.8271 8.67969 15.8271H20.9414C21.4331 15.8271 21.8488 15.4752 21.9297 14.9902L23.46 5.81055H5.96973L7.69434 15.0049Z"/>
			</svg>
		</div>
		<span class="cartValue"><?php echo esc_html($total); ?></span>
	</a>
	<?php
}


// -------------------------------------------------------
// MiniCart: renderowanie treści panelu bocznego
// -------------------------------------------------------
function grofi_minicart_content(): void {
	$cart  = WC()->cart;
	$items = $cart->get_cart();
	?>
	<div class="minicart__content">
		<div class="minicart__body">
			<?php if (empty($items)) : ?>
				<p class="minicart__empty"><?php esc_html_e('Twój koszyk jest pusty.', 'grofi'); ?></p>
			<?php else : ?>
				<ul class="minicart__list">
					<?php foreach ($items as $cart_item_key => $cart_item) :
						$product    = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
						$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
						$quantity   = $cart_item['quantity'];
						$thumbnail  = apply_filters('woocommerce_cart_item_thumbnail', $product->get_image('woocommerce_thumbnail'), $cart_item, $cart_item_key);
						$name       = apply_filters('woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key);
						$subtotal   = apply_filters('woocommerce_cart_item_subtotal', $cart->get_product_subtotal($product, $quantity), $cart_item, $cart_item_key);
						$remove_url = wc_get_cart_remove_url($cart_item_key);
					?>
					<li class="minicart__item">
						<a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="minicart__item-img">
							<?php echo $thumbnail; ?>
						</a>
						<div class="minicart__item-details">
							<a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="minicart__item-name">
								<?php echo esc_html($name); ?>
							</a>
							<div class="minicart__item-meta">
								<span class="minicart__item-qty"><?php echo esc_html($quantity); ?></span>
								<span class="minicart__item-x">×</span>
								<span class="minicart__item-price"><?php echo $subtotal; ?></span>
							</div>
						</div>
						<a href="<?php echo esc_url($remove_url); ?>"
						   class="minicart__item-remove"
						   aria-label="<?php esc_attr_e('Usuń produkt', 'grofi'); ?>"
						   data-item-key="<?php echo esc_attr($cart_item_key); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<line x1="18" y1="6" x2="6" y2="18"></line>
								<line x1="6" y1="6" x2="18" y2="18"></line>
							</svg>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="minicart__footer">
			<div class="minicart__total">
				<span><?php esc_html_e('Łącznie:', 'grofi'); ?></span>
				<strong><?php echo $cart->get_cart_total(); ?></strong>
			</div>
			<div class="minicart__actions">
				<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="button button--light">
					<?php esc_html_e('Zobacz koszyk', 'grofi'); ?>
				</a>
				<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="button button--orange">
					<?php esc_html_e('Przejdź do płatności', 'grofi'); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
}


// -------------------------------------------------------
// Odrejestruj wc-cart-fragments – fragmenty odświeżamy sami
// w cart.js przez natywny fetch (fetchFragments).
// -------------------------------------------------------
add_action('wp_enqueue_scripts', function () {
	wp_dequeue_script('wc-cart-fragments');
	wp_deregister_script('wc-cart-fragments');
}, 100);


// -------------------------------------------------------
// Fragment AJAX koszyka + minicart
//
// Selektor musi pasować do outerHTML elementu w DOM.
// Używamy 'a.cartButton' i 'div.minicart__content' –
// cart.js wywołuje /?wc-ajax=get_refreshed_fragments
// i podmienia elementy przez applyFragments().
// -------------------------------------------------------
add_filter('woocommerce_add_to_cart_fragments', function (array $fragments): array {
	ob_start();
	grofi_cart_button();
	$fragments['a.cartButton'] = ob_get_clean();

	ob_start();
	grofi_minicart_content();
	$fragments['div.minicart__content'] = ob_get_clean();

	return $fragments;
});


// -------------------------------------------------------
// AJAX: usuń produkt z koszyka + zwróć fragmenty w jednym żądaniu
//
// Endpoint: /?wc-ajax=grofi_remove_cart_item (POST)
// Obsługuje zarówno gości jak i zalogowanych użytkowników.
// Weryfikuje nonce wygenerowany przez wc_get_cart_remove_url().
// -------------------------------------------------------
add_action('wc_ajax_grofi_remove_cart_item', function (): void {
	$nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));

	if (!wp_verify_nonce($nonce, 'woocommerce-cart')) {
		wp_send_json_error(['message' => 'Invalid nonce'], 403);
	}

	$cart_item_key = sanitize_text_field(wp_unslash($_POST['cart_item_key'] ?? ''));

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Missing cart_item_key'], 400);
	}

	WC()->cart->remove_cart_item($cart_item_key);

	$fragments = apply_filters('woocommerce_add_to_cart_fragments', []);

	wp_send_json_success([
		'fragments'  => $fragments,
		'cart_hash'  => WC()->cart->get_cart_hash(),
	]);
});




// -------------------------------------------------------
// Usuń domyślny sidebar WooCommerce
// -------------------------------------------------------
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);


// -------------------------------------------------------
// Breadcrumbs: usuń z domyślnej pozycji, wstrzyknij
// wewnątrz własnego wrappera .woocommerceMain.container
// -------------------------------------------------------
// remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

add_action('woocommerce_before_main_content', function () {
	echo '<div class="woocommerceMain container">';
	woocommerce_breadcrumb();
}, 10);

add_action('woocommerce_after_main_content', function () {
	echo '</div>';
}, 10);


// -------------------------------------------------------
// Wygląd okruszków
// -------------------------------------------------------
add_filter('woocommerce_breadcrumb_defaults', function (array $defaults): array {
	$defaults['delimiter']   = '<span class="breadcrumb-sep" aria-hidden="true">/</span>';
	$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="Nawigacja okruszkowa">';
	$defaults['wrap_after']  = '</nav>';
	return $defaults;
});


// -------------------------------------------------------
// Liczba produktów i kolumn w pętli sklepu
// -------------------------------------------------------
add_filter('loop_shop_per_page', fn () => 12, 20);
add_filter('loop_shop_columns',  fn () => 3);


// -------------------------------------------------------
// Zakładka opisu produktu – wymuś wyświetlanie nawet gdy
// post_content jest pusty (opis pochodzi z ACF prod_desc)
// -------------------------------------------------------
add_filter('woocommerce_product_tabs', function ( array $tabs ): array {
	if ( ! isset( $tabs['description'] ) ) {
		$tabs['description'] = [
			'title'    => __( 'Opis', 'woocommerce' ),
			'priority' => 10,
			'callback' => 'woocommerce_product_description_tab',
		];
	}
	return $tabs;
}, 5 );


// -------------------------------------------------------
// Usuń opis kategorii ze strony sklepu
// -------------------------------------------------------
remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);


// -------------------------------------------------------
// Polskie teksty przycisków "Dodaj do koszyka"
// -------------------------------------------------------
add_filter('woocommerce_product_add_to_cart_text', function (string $text, \WC_Product $product): string {
	return ($product->is_type('simple') && $product->is_in_stock())
		? __('Dodaj do koszyka', 'grofi')
		: $text;
}, 10, 2);

add_filter('woocommerce_product_single_add_to_cart_text', function (string $text, \WC_Product $product): string {
	return $product->is_in_stock()
		? __('Dodaj do koszyka', 'grofi')
		: $text;
}, 10, 2);


