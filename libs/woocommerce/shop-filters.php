<?php

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
			$brands = [];
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
	$attributes = [];
	$wc_attrs   = function_exists( 'wc_get_attribute_taxonomies' )
		? wc_get_attribute_taxonomies()
		: [];

	foreach ( $wc_attrs as $attr ) {
		$taxonomy = wc_attribute_taxonomy_name( $attr->attribute_name );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		if ( $cat_term && empty( $cat_ids ) ) {
			continue;
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
	delete_transient( 'grofi_cat_tree' );
	theme_flush_cat_tree_cache();
}

// Produkty
add_action( 'save_post_product', 'grofi_flush_filter_cache' );
add_action( 'woocommerce_product_import_inserted_or_updated',
	fn ( \WC_Product $p ) => grofi_flush_filter_cache() );

// Kategorie produktów
add_action( 'edited_product_cat',  function (): void { grofi_flush_filter_cache(); grofi_flush_cat_tree_cache(); } );
add_action( 'created_product_cat', function (): void { grofi_flush_filter_cache(); grofi_flush_cat_tree_cache(); } );
add_action( 'delete_product_cat',  function (): void { grofi_flush_filter_cache(); grofi_flush_cat_tree_cache(); } );

// Marki
add_action( 'edited_product_brand',  'grofi_flush_filter_cache' );
add_action( 'created_product_brand', 'grofi_flush_filter_cache' );
add_action( 'delete_product_brand',  'grofi_flush_filter_cache' );
