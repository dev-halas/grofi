<?php
/**
 * Category Tree Sidebar — funkcje pomocnicze
 *
 * OPTYMALIZACJA (3k+ produktów):
 *  - theme_get_product_cat_tree() wykonuje JEDNO zapytanie SQL dla wszystkich kategorii
 *    (zamiast N+1 rekurencyjnych get_terms() — po jednym na każdy poziom drzewa).
 *  - Wynik jest cache'owany w transiencie przez 12h.
 *  - Cache jest czyszczony automatycznie przy każdej zmianie kategorii.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Slugi i nazwy kategorii (lowercase) pomijane w drzewie bocznym. */
const GROFI_CAT_TREE_EXCLUDED = [ 'uncategorized', 'bez-kategorii', 'bez kategorii' ];

// ─────────────────────────────────────────────────────────────────────────────
// Publiczne API — sygnatura niezmieniona (category-tree.php woła to z depth=0)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Zwraca drzewo kategorii produktów.
 * Pierwsze wywołanie (depth=0) pobiera WSZYSTKIE kategorie w jednym zapytaniu
 * i buduje drzewo w PHP. Wynik jest cache'owany w transiencie.
 *
 * @param int $parent_id  ID kategorii nadrzędnej (0 = root).
 * @param int $depth      Aktualny poziom zagłębienia (wewnętrzny — nie używaj).
 * @param int $max_depth  Maksymalny poziom zagłębienia.
 *
 * @return array
 */
function theme_get_product_cat_tree( int $parent_id = 0, int $depth = 0, int $max_depth = 4 ): array {
	if ( $depth >= $max_depth ) {
		return [];
	}

	// Tylko wywołanie root (0,0) korzysta z cache i optymalizacji.
	// Wywołania zagnieżdżone (z depth>0) nie powinny się zdarzać — zachowujemy
	// jednak fallback dla kompatybilności wstecznej.
	if ( $depth > 0 ) {
		return _theme_cat_tree_legacy( $parent_id, $depth, $max_depth );
	}

	// ── Cache ─────────────────────────────────────────────────────────────
	// Klucz versjonowany — zmień sufiks aby wymusić rebuild po aktualizacji kodu.
	$cache_key = 'grofi_cat_tree_v3';
	$cached    = get_transient( $cache_key );

	// Walidacja: nie przyjmujemy pustej tablicy jako prawidłowego cache'u
	// (pusta tablica mogła zostać zapisana przy błędzie pierwszego żądania).
	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}

	// ── Jedno zapytanie dla WSZYSTKICH kategorii ──────────────────────────
	$all_terms = get_terms( [
		'taxonomy'               => 'product_cat',
		'hide_empty'             => false,
		'orderby'                => 'name',
		'order'                  => 'ASC',
		'number'                 => 0,
		'update_term_meta_cache' => false,
	] );

	if ( is_wp_error( $all_terms ) || empty( $all_terms ) ) {
		return [];
	}

	// ── Grupuj według parent_id → O(1) lookup w PHP ───────────────────────
	$by_parent = [];
	foreach ( $all_terms as $term ) {
		$by_parent[ (int) $term->parent ][] = $term;
	}

	// ── Buduj drzewo w PHP (bez kolejnych zapytań SQL) ────────────────────
	$tree = theme_build_cat_tree_node( $by_parent, 0, 0, $max_depth );

	// Zapisz tylko jeśli drzewo jest niepuste — zabezpieczenie przed cache'owaniem
	// błędnego wyniku z wczesnej fazy inicjalizacji WordPressa.
	if ( ! empty( $tree ) ) {
		set_transient( $cache_key, $tree, 12 * HOUR_IN_SECONDS );
	}

	return $tree;
}

/**
 * Rekurencyjny builder drzewa z już pogrupowanych termów.
 * Nie wykonuje żadnych zapytań SQL — tylko przetwarza dane w PHP.
 *
 * @internal
 */
function theme_build_cat_tree_node( array $by_parent, int $parent_id, int $depth, int $max_depth ): array {
	if ( $depth >= $max_depth || empty( $by_parent[ $parent_id ] ) ) {
		return [];
	}

	$tree = [];

	foreach ( $by_parent[ $parent_id ] as $term ) {
		if (
			in_array( mb_strtolower( $term->slug ), GROFI_CAT_TREE_EXCLUDED, true ) ||
			in_array( mb_strtolower( $term->name ), GROFI_CAT_TREE_EXCLUDED, true )
		) {
			continue;
		}

		$children = theme_build_cat_tree_node( $by_parent, $term->term_id, $depth + 1, $max_depth );

		$tree[] = [
			'id'          => $term->term_id,
			'name'        => $term->name,
			'url'         => get_term_link( $term ),
			'slug'        => $term->slug,
			'count'       => $term->count,
			'children'    => $children,
			'hasChildren' => ! empty( $children ),
		];
	}

	return $tree;
}

/**
 * Fallback dla zagnieżdżonych wywołań (kompatybilność wsteczna).
 *
 * @internal
 */
function _theme_cat_tree_legacy( int $parent_id, int $depth, int $max_depth ): array {
	$terms = get_terms( [
		'taxonomy'               => 'product_cat',
		'parent'                 => $parent_id,
		'hide_empty'             => false,
		'orderby'                => 'name',
		'order'                  => 'ASC',
		'update_term_meta_cache' => false,
	] );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	$tree = [];
	foreach ( $terms as $term ) {
		if (
			in_array( mb_strtolower( $term->slug ), GROFI_CAT_TREE_EXCLUDED, true ) ||
			in_array( mb_strtolower( $term->name ), GROFI_CAT_TREE_EXCLUDED, true )
		) {
			continue;
		}

		$children = $depth + 1 < $max_depth
			? _theme_cat_tree_legacy( $term->term_id, $depth + 1, $max_depth )
			: [];

		$tree[] = [
			'id'          => $term->term_id,
			'name'        => $term->name,
			'url'         => get_term_link( $term ),
			'slug'        => $term->slug,
			'count'       => $term->count,
			'children'    => $children,
			'hasChildren' => ! empty( $children ),
		];
	}

	return $tree;
}

/**
 * Czyści cache drzewa kategorii.
 * Wołane przez hooki w woocommerce.php przy zmianie kategorii.
 */
function theme_flush_cat_tree_cache(): void {
	delete_transient( 'grofi_cat_tree_v3' );
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Zwraca ID aktualnej kategorii i wszystkich jej przodków.
 * Używane do automatycznego rozwijania ścieżki w drzewie.
 *
 * @return int[]  Tablica ID: [current_id, parent_id, grandparent_id, ...]
 */
function theme_get_active_cat_ids(): array {
	if ( ! is_tax( 'product_cat' ) ) {
		return [];
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return [];
	}

	$ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );

	return array_merge( [ $term->term_id ], $ancestors );
}

/**
 * Sprawdza rekurencyjnie, czy dany element drzewa zawiera aktywną kategorię
 * wśród swoich potomków.
 *
 * @param array $item       Element drzewa.
 * @param int[] $active_ids Lista aktywnych ID.
 *
 * @return bool
 */
function theme_cat_tree_has_active_descendant( array $item, array $active_ids ): bool {
	if ( in_array( $item['id'], $active_ids, true ) ) {
		return true;
	}

	foreach ( $item['children'] as $child ) {
		if ( theme_cat_tree_has_active_descendant( $child, $active_ids ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Renderuje rekurencyjnie HTML drzewa kategorii.
 *
 * @param array $tree       Drzewo kategorii z theme_get_product_cat_tree().
 * @param int[] $active_ids Aktywne ID z theme_get_active_cat_ids().
 * @param int   $depth      Aktualny poziom (do klas CSS).
 */
function theme_render_cat_tree( array $tree, array $active_ids, int $depth = 0 ): void {
	if ( empty( $tree ) ) {
		return;
	}
	?>
	<ul class="cat-tree__list cat-tree__list--depth-<?php echo esc_attr( $depth ); ?>"
	    role="<?php echo $depth === 0 ? 'tree' : 'group'; ?>">

		<?php foreach ( $tree as $item ) :
			$is_current  = in_array( $item['id'], $active_ids, true );
			$is_ancestor = ! $is_current && theme_cat_tree_has_active_descendant( $item, $active_ids );
			$initially_open = $item['hasChildren'] && ( $is_current || $is_ancestor ) ? 'true' : 'false';
			?>

			<li class="cat-tree__item<?php echo $item['hasChildren'] ? ' cat-tree__item--has-children' : ''; ?>"
			    x-data="{ open: <?php echo $initially_open; ?> }"
			    :class="{ 'is-open': open }"
			    role="treeitem"
			    :aria-expanded="<?php echo $item['hasChildren'] ? 'open.toString()' : 'undefined'; ?>">

				<div class="cat-tree__row<?php
					echo $is_current  ? ' is-active'   : '';
					echo $is_ancestor ? ' is-ancestor' : '';
				?>">
					<?php if ( $item['hasChildren'] ) : ?>

						<a href="<?php echo esc_url( $item['url'] ); ?>"
						   class="cat-tree__link"
						   @click="open = !open">
							<?php echo esc_html( $item['name'] ); ?>
						</a>

						<button class="cat-tree__chevron"
						        @click.stop="open = !open"
						        :aria-label="open ? 'Zwiń <?php echo esc_attr( $item['name'] ); ?>' : 'Rozwiń <?php echo esc_attr( $item['name'] ); ?>'">
							<svg xmlns="http://www.w3.org/2000/svg"
							     width="16" height="16"
							     viewBox="0 0 24 24"
							     fill="none"
							     stroke="currentColor"
							     stroke-width="2.5"
							     stroke-linecap="round"
							     stroke-linejoin="round"
							     aria-hidden="true">
								<polyline points="6 9 12 15 18 9"></polyline>
							</svg>
						</button>

					<?php else : ?>

						<a href="<?php echo esc_url( $item['url'] ); ?>"
						   class="cat-tree__link">
							<?php echo esc_html( $item['name'] ); ?>
						</a>

					<?php endif; ?>
				</div>

				<?php if ( $item['hasChildren'] ) : ?>
					<div class="cat-tree__children"
					     x-show="open"
					     x-collapse>
						<?php theme_render_cat_tree( $item['children'], $active_ids, $depth + 1 ); ?>
					</div>
				<?php endif; ?>

			</li>

		<?php endforeach; ?>
	</ul>
	<?php
}
