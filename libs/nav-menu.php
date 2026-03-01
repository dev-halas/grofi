<?php
/**
 * Navigation menus: Mega Menu Walker, mobile panels, product category injection.
 *
 * @package Grofi
 */

// ── Constants ──────────────────────────────────────────────────────────────────

/** Transient key for the cached fake nav items (product categories). */
const GROFI_NAV_CAT_TRANSIENT = 'grofi_nav_cat_items';

/**
 * Chevron SVG reused in every expandable mobile menu item.
 * Defined once — never duplicated in output.
 */
const GROFI_MENU_CHEVRON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="9 18 15 12 9 6"/></svg>';


// ── Mega Menu Walker (desktop hover) ──────────────────────────────────────────

/**
 * Renders an accessible mega-menu for the desktop header.
 * All output is built via string concatenation — no ob_start() overhead.
 */
class Mega_Menu_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= '<ul class="mm-dropdown mm-lvl-' . ( $depth + 1 ) . '">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= '</ul>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$classes      = empty( $item->classes ) ? [] : (array) $item->classes;
		$has_children = in_array( 'menu-item-has-children', $classes, true );

		$el_classes = implode( ' ', array_filter( [
			'mm-item',
			'mm-item--lvl-' . $depth,
			$has_children ? 'mm-item--has-children' : '',
		] ) );

		$icon_html = '';
		if ( $depth === 1 && ( $item->object ?? '' ) === 'product_cat' ) {
			// Prefer pre-fetched URL stored by the injection function (avoids extra DB call).
			$icon_url = $item->grofi_icon_url
				?? get_product_cat_icon_url( (int) ( $item->object_id ?? 0 ) );

			if ( $icon_url ) {
				$icon_html = '<img src="' . esc_url( $icon_url ) . '" alt="" class="mm-cat-icon" aria-hidden="true" loading="lazy">';
			}
		}

		$output .= sprintf(
			'<li class="%s"><a href="%s" class="mm-link">%s%s</a>',
			esc_attr( $el_classes ),
			esc_url( $item->url ),
			$icon_html,   // pre-escaped above
			esc_html( $item->title )
		);
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ): void {
		$output .= '</li>';
	}
}


// ── Product Category Injection ────────────────────────────────────────────────

add_filter( 'wp_nav_menu_objects', 'inject_product_categories_into_menu', 10, 2 );

/**
 * Injects WooCommerce product categories as dynamic sub-items under the
 * top-level "Kategorie" nav item, for both desktop and mobile menus.
 *
 * Built items are cached in a transient and invalidated automatically when
 * categories, term meta (icons), the nav menu, or permalinks change.
 *
 * @param WP_Post[]|\stdClass[] $items Nav menu items.
 * @param object                $args  wp_nav_menu() args or stdClass with theme_location.
 * @return WP_Post[]|\stdClass[]
 */
function inject_product_categories_into_menu( array $items, object $args ): array {

	if ( ( $args->theme_location ?? '' ) !== 'main_nav' ) {
		return $items;
	}

	// Locate the "Kategorie" anchor item by title.
	$kategorie_item = null;
	foreach ( $items as $item ) {
		if ( mb_strtolower( trim( $item->title ) ) === 'kategorie' ) {
			$kategorie_item = $item;
			break;
		}
	}

	if ( ! $kategorie_item ) {
		return $items;
	}

	$parent_db_id = (int) $kategorie_item->db_id;

	// ── Transient cache ──────────────────────────────────────────────────────
	// Stores { parent_db_id, default_icon_url, items }.
	// Any of the first two changing (menu rebuild or code edit) auto-invalidates.
	$cached          = get_transient( GROFI_NAV_CAT_TRANSIENT );
	$default_icon    = defined( 'GROFI_DEFAULT_CAT_ICON_URL' ) ? GROFI_DEFAULT_CAT_ICON_URL : '';
	$cache_is_valid  = false !== $cached
		&& isset( $cached['parent_db_id'], $cached['default_icon_url'], $cached['items'] )
		&& (int) $cached['parent_db_id']   === $parent_db_id
		&& $cached['default_icon_url']      === $default_icon;

	if ( $cache_is_valid ) {
		$fake_items = $cached['items'];
	} else {
		$fake_items = _grofi_build_product_cat_items( $parent_db_id );
		set_transient(
			GROFI_NAV_CAT_TRANSIENT,
			[
				'parent_db_id'    => $parent_db_id,
				'default_icon_url' => $default_icon,
				'items'           => $fake_items,
			],
			12 * HOUR_IN_SECONDS
		);
	}

	if ( empty( $fake_items ) ) {
		return $items;
	}

	// Mark "Kategorie" as a parent (idempotent).
	if ( ! in_array( 'menu-item-has-children', (array) $kategorie_item->classes, true ) ) {
		$kategorie_item->classes[] = 'menu-item-has-children';
	}

	// Splice fake items immediately after the "Kategorie" item.
	$result = [];
	foreach ( $items as $item ) {
		$result[] = $item;
		if ( (int) $item->db_id === $parent_db_id ) {
			array_push( $result, ...$fake_items );
		}
	}

	return $result;
}

/**
 * Builds stdClass nav-menu objects for all non-excluded product categories.
 *
 * Also pre-fetches each category's icon URL and stores it as `grofi_icon_url`
 * so Walker and mobile renderer never need an extra DB round-trip per item.
 *
 * @internal Called only by inject_product_categories_into_menu().
 *
 * @param int $parent_db_id db_id of the "Kategorie" menu item.
 * @return \stdClass[]
 */
function _grofi_build_product_cat_items( int $parent_db_id ): array {

	// Slugs / names to exclude from the menu (case-insensitive).
	$excluded_slugs_names = [ 'uncategorized', 'promocje', 'wyprzedaż' ];

	$terms = get_terms( [
		'taxonomy'               => 'product_cat',
		'hide_empty'             => false,
		'orderby'                => 'name',
		'order'                  => 'ASC',
		'update_term_meta_cache' => false, // we fetch meta ourselves below
	] );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return [];
	}

	// ── Build exclusion set via BFS (O(n) with children map) ────────────────

	$excluded_normalized = array_map( 'mb_strtolower', $excluded_slugs_names );
	$all_excluded        = [];

	foreach ( $terms as $term ) {
		if (
			in_array( mb_strtolower( $term->slug ), $excluded_normalized, true ) ||
			in_array( mb_strtolower( $term->name ), $excluded_normalized, true )
		) {
			$all_excluded[] = $term->term_id;
		}
	}

	// Parent → children ID map used for BFS and later for detecting parent items.
	$children_map = [];
	foreach ( $terms as $term ) {
		$children_map[ (int) $term->parent ][] = $term->term_id;
	}

	// BFS: collect all descendants of excluded roots.
	$queue = $all_excluded;
	while ( $queue ) {
		$pid = array_shift( $queue );
		foreach ( $children_map[ $pid ] ?? [] as $cid ) {
			if ( ! in_array( $cid, $all_excluded, true ) ) {
				$all_excluded[] = $cid;
				$queue[]        = $cid;
			}
		}
	}

	// ── Build fake menu items ────────────────────────────────────────────────

	/*
	 * ID offset: fake db_ids must not collide with real nav menu item IDs.
	 * WordPress nav menu items are stored as posts; post IDs rarely exceed 100k
	 * for small/medium shops. 900000 provides ample headroom.
	 */
	$id_offset  = 900000;
	$fake_items = [];
	$order      = 1;

	// Pre-load term meta for all icon IDs in a single cache-warm call.
	$term_ids = array_column( $terms, 'term_id' );
	update_termmeta_cache( $term_ids );

	foreach ( $terms as $term ) {
		if ( in_array( $term->term_id, $all_excluded, true ) ) {
			continue;
		}

		$fake_id   = $id_offset + (int) $term->term_id;
		$parent_id = ( (int) $term->parent === 0 )
			? $parent_db_id
			: $id_offset + (int) $term->parent;

		$item                        = new \stdClass();
		$item->ID                    = $fake_id;
		$item->db_id                 = $fake_id;
		$item->menu_item_parent      = $parent_id;
		$item->object_id             = (int) $term->term_id;
		$item->object                = 'product_cat';
		$item->type                  = 'taxonomy';
		$item->type_label            = 'Kategoria produktu';
		$item->post_type             = 'nav_menu_item';
		$item->post_status           = 'publish';
		$item->menu_order            = $order++;
		$item->title                 = $term->name;
		$item->url                   = get_term_link( $term );
		$item->target                = '';
		$item->attr_title            = '';
		$item->description           = '';
		$item->xfn                   = '';
		$item->current               = false;
		$item->current_item_ancestor = false;
		$item->current_item_parent   = false;
		$item->classes               = [
			'menu-item',
			'menu-item-type-taxonomy',
			'menu-item-object-product_cat',
		];

		// Pre-fetch icon URL now (term meta cache is warm) to avoid per-render queries.
		$item->grofi_icon_url = get_product_cat_icon_url( (int) $term->term_id );

		$fake_items[] = $item;
	}

	// Mark items that are parents of other items.
	$parent_ids = array_flip(
		array_map( static fn( \stdClass $i ): int => (int) $i->menu_item_parent, $fake_items )
	);

	foreach ( $fake_items as $fake_item ) {
		if ( isset( $parent_ids[ $fake_item->db_id ] ) ) {
			$fake_item->classes[] = 'menu-item-has-children';
		}
	}

	return $fake_items;
}

/**
 * Flushes the product-category nav transient.
 *
 * Hooked to: category CRUD, icon save, nav menu save, permalink changes.
 * Uses the WordPress transient API — works correctly with both DB storage
 * and persistent object caches (Redis / Memcached).
 */
function grofi_flush_product_cat_nav_cache(): void {
	delete_transient( GROFI_NAV_CAT_TRANSIENT );
}

add_action( 'edited_product_cat',        'grofi_flush_product_cat_nav_cache' );
add_action( 'created_product_cat',       'grofi_flush_product_cat_nav_cache' );
add_action( 'delete_product_cat',        'grofi_flush_product_cat_nav_cache' );
add_action( 'wp_update_nav_menu',        'grofi_flush_product_cat_nav_cache' );
add_action( 'permalink_structure_changed', 'grofi_flush_product_cat_nav_cache' );


// ── Mobile Menu Panels (flat, server-rendered) ────────────────────────────────

/**
 * Renders flat, sibling mobile menu panels for a registered nav location.
 *
 * Each panel is a <div data-id="…" data-parent="…" data-title="…">.
 * JS switches only CSS classes — zero DOM construction on the client side.
 *
 * @param string $location Registered nav menu location slug.
 */
function render_mobile_menu_panels( string $location ): void {
	$nav_locations = get_nav_menu_locations();

	if ( empty( $nav_locations[ $location ] ) ) {
		return;
	}

	$items = wp_get_nav_menu_items( (int) $nav_locations[ $location ] );
	if ( ! $items ) {
		return;
	}

	// Apply the same category injection as the desktop menu.
	$fake_args = (object) [ 'theme_location' => $location ];
	$items     = inject_product_categories_into_menu( $items, $fake_args );

	// Build parent → children map.
	$map = [];
	foreach ( $items as $item ) {
		$map[ (int) $item->menu_item_parent ][] = $item;
	}

	_grofi_mobile_render_panel( $map, 0, 'root', 'Menu', null, true );
}

/**
 * Recursively renders one panel, then its children panels as siblings (not nested).
 *
 * @internal
 *
 * @param array       $map          parent_db_id → children items.
 * @param int         $parent_id    ID of items to render in this panel.
 * @param string      $panel_id     data-id for this panel element.
 * @param string      $title        Panel heading / back-button label.
 * @param string|null $parent_panel data-id of the parent panel, or null for root.
 * @param bool        $is_root      Whether this is the initially visible panel.
 * @param int         $depth        Current nesting depth (0 = root).
 */
function _grofi_mobile_render_panel(
	array   $map,
	int     $parent_id,
	string  $panel_id,
	string  $title,
	?string $parent_panel,
	bool    $is_root = false,
	int     $depth   = 0
): void {
	$children = $map[ $parent_id ] ?? [];
	if ( ! $children ) {
		return;
	}

	$class = 'mobile-menu-panel' . ( $is_root ? ' is-active' : '' );
	?>
	<div class="<?php echo esc_attr( $class ); ?>"
		data-id="<?php echo esc_attr( $panel_id ); ?>"
		<?php if ( $parent_panel !== null ) : ?>data-parent="<?php echo esc_attr( $parent_panel ); ?>"<?php endif; ?>
		data-title="<?php echo esc_attr( $title ); ?>">

		<ul class="mobile-menu-list">
			<?php foreach ( $children as $item ) :
				$child_panel_id = 'dp-' . (int) $item->db_id;
				$has_sub        = ! empty( $map[ (int) $item->db_id ] );

				$icon_html = '';
				if ( $depth === 1 && ( $item->object ?? '' ) === 'product_cat' ) {
					// Prefer pre-fetched URL stored by the injection function.
					$icon_url = $item->grofi_icon_url
						?? get_product_cat_icon_url( (int) ( $item->object_id ?? 0 ) );

					if ( $icon_url ) {
						$icon_html = '<img src="' . esc_url( $icon_url ) . '" alt="" class="mm-cat-icon" aria-hidden="true" loading="lazy">';
					}
				}
			?>
			<li class="mobile-menu-item">
				<?php if ( $has_sub ) : ?>
					<div class="mobile-menu-item-row">
						<a href="<?php echo esc_url( $item->url ); ?>" class="mobile-menu-link">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built & escaped above
							echo $icon_html;
							echo esc_html( $item->title );
							?>
						</a>
						<button
							type="button"
							class="mobile-menu-arrow"
							data-target="<?php echo esc_attr( $child_panel_id ); ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category name */ __( 'Rozwiń %s', 'grofi' ), $item->title ) ); ?>">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded safe SVG constant
							echo GROFI_MENU_CHEVRON_SVG;
							?>
						</button>
					</div>
				<?php else : ?>
					<a href="<?php echo esc_url( $item->url ); ?>" class="mobile-menu-link">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built & escaped above
						echo $icon_html;
						echo esc_html( $item->title );
						?>
					</a>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>

	</div>
	<?php

	// Render children panels as siblings (not nested).
	foreach ( $children as $item ) {
		if ( ! empty( $map[ (int) $item->db_id ] ) ) {
			_grofi_mobile_render_panel(
				$map,
				(int) $item->db_id,
				'dp-' . (int) $item->db_id,
				$item->title,
				$panel_id,
				false,
				$depth + 1
			);
		}
	}
}
