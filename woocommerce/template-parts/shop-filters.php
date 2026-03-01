<?php
/**
 * Template Part: Filtry sklepu
 *
 * Renderuje automatycznie:
 *  • Markę (product_brand — WooCommerce natywny)  → ?brand=slug1,slug2
 *  • Wszystkie publiczne atrybuty WooCommerce      → ?filter_{slug}=val1,val2
 *  • Filtr cenowy                                  → ?min_price=X&max_price=Y
 *
 * OPTYMALIZACJA:
 *  Dane filtrów pobiera grofi_get_filter_data() (libs/woocommerce.php).
 *  Używa jednego zapytania JOIN-SQL na taxonomię (bez get_posts() dla ID)
 *  i cache'uje wynik w transiencie przez 6h.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended

// ── Aktywne wartości z URL ──────────────────────────────────────────────────
$selected_brands = [];
if ( ! empty( $_GET['brand'] ) ) {
	$selected_brands = array_values( array_filter(
		array_map( 'sanitize_title', explode( ',', sanitize_text_field( $_GET['brand'] ) ) )
	) );
}

$min_price = isset( $_GET['min_price'] ) ? (int) $_GET['min_price'] : '';
$max_price = isset( $_GET['max_price'] ) ? (int) $_GET['max_price'] : '';

// Aktywne filtry atrybutów: ['filter_color' => ['red', 'blue'], ...]
$active_attr_filters = [];
foreach ( $_GET as $key => $val ) {
	if ( str_starts_with( $key, 'filter_' ) && is_string( $val ) && $val !== '' ) {
		$active_attr_filters[ $key ] = array_map(
			'sanitize_title',
			explode( ',', sanitize_text_field( $val ) )
		);
	}
}

// phpcs:enable

// ── Dane filtrów (cache 6h, invalidacja przy save_post_product) ─────────────
$cat_term   = is_tax( 'product_cat' ) ? get_queried_object() : null;
$cat_term   = ( $cat_term instanceof WP_Term ) ? $cat_term : null;
$filter_data = grofi_get_filter_data( $cat_term );

$brands             = $filter_data['brands']     ?? [];
$attribute_sections = array_map( function ( array $section ) use ( $active_attr_filters ): array {
	$section['selected'] = $active_attr_filters[ $section['param'] ] ?? [];
	return $section;
}, $filter_data['attributes'] ?? [] );

// ── Reset URL — usuwa wszystkie aktywne parametry filtrów ────────────────────
$filter_params_to_clear = array_merge(
	[ 'brand', 'min_price', 'max_price', 'paged' ],
	array_keys( $active_attr_filters )
);
$reset_url = remove_query_arg( $filter_params_to_clear );

// ── Czy jakikolwiek filtr jest aktywny? ─────────────────────────────────────
$has_active_filters = ! empty( $selected_brands )
	|| $min_price !== ''
	|| $max_price !== ''
	|| ! empty( $active_attr_filters );

$has_any_section = ! empty( $brands ) || ! empty( $attribute_sections );

?>

<div class="shop-filters shop-sidebar__widget" id="shop-filters">
	<form class="shop-filters__form" novalidate>

		<?php
		// ── Marka ───────────────────────────────────────────────────────────
		if ( ! empty( $brands ) ) :
		?>
		<div class="shop-filters__section" x-data="{ open: true }">

			<button type="button" class="shop-filters__section-head" @click="open = !open">
				<span class="shop-filters__section-title">
					<?php esc_html_e( 'Marka', 'grofi' ); ?>
					<?php if ( ! empty( $selected_brands ) ) : ?>
					<span class="shop-filters__badge"><?php echo count( $selected_brands ); ?></span>
					<?php endif; ?>
				</span>
				<?php echo theme_shop_filter_chevron(); ?>
			</button>

			<ul class="shop-filters__list" x-show="open" x-collapse>
				<?php foreach ( $brands as $brand ) : ?>
				<li class="shop-filters__item">
					<label class="shop-filters__label">
						<input type="checkbox"
						       class="shop-filters__checkbox"
						       name="brand"
						       value="<?php echo esc_attr( $brand->slug ); ?>"
						       <?php checked( in_array( $brand->slug, $selected_brands, true ) ); ?>>
						<span class="shop-filters__label-text"><?php echo esc_html( $brand->name ); ?></span>
						<span class="shop-filters__count"><?php echo (int) $brand->count; ?></span>
					</label>
				</li>
				<?php endforeach; ?>
			</ul>

		</div>
		<?php endif; ?>

		<?php
		// ── Atrybuty WooCommerce ─────────────────────────────────────────────
		foreach ( $attribute_sections as $section ) :
			$active_count = count( $section['selected'] );
		?>
		<div class="shop-filters__section" x-data="{ open: true }">

			<button type="button" class="shop-filters__section-head" @click="open = !open">
				<span class="shop-filters__section-title">
					<?php echo esc_html( $section['label'] ); ?>
					<?php if ( $active_count > 0 ) : ?>
					<span class="shop-filters__badge"><?php echo $active_count; ?></span>
					<?php endif; ?>
				</span>
				<?php echo theme_shop_filter_chevron(); ?>
			</button>

			<ul class="shop-filters__list" x-show="open" x-collapse>
				<?php foreach ( $section['terms'] as $term ) :
					$is_checked = in_array( $term->slug, $section['selected'], true );
				?>
				<li class="shop-filters__item">
					<label class="shop-filters__label">
						<input type="checkbox"
						       class="shop-filters__checkbox"
						       name="<?php echo esc_attr( $section['param'] ); ?>"
						       value="<?php echo esc_attr( $term->slug ); ?>"
						       <?php checked( $is_checked ); ?>>
						<span class="shop-filters__label-text"><?php echo esc_html( $term->name ); ?></span>
						<span class="shop-filters__count"><?php echo (int) $term->count; ?></span>
					</label>
				</li>
				<?php endforeach; ?>
			</ul>

		</div>
		<?php endforeach; ?>

		<?php
		// ── Filtr ceny ────────────────────────────────────────────────────────
		?>
		<div class="shop-filters__section<?php echo ( ! $has_any_section ) ? ' shop-filters__section--first' : ''; ?>"
		     x-data="{ open: true }">

			<button type="button" class="shop-filters__section-head" @click="open = !open">
				<span class="shop-filters__section-title">
					<?php esc_html_e( 'Cena', 'grofi' ); ?>
					<?php if ( $min_price !== '' || $max_price !== '' ) : ?>
					<span class="shop-filters__badge">1</span>
					<?php endif; ?>
				</span>
				<?php echo theme_shop_filter_chevron(); ?>
			</button>

			<div class="shop-filters__price-wrap" x-show="open" x-collapse>
				<div class="shop-filters__price-row">

					<div class="shop-filters__price-field">
						<label class="shop-filters__price-label"
						       for="filter-min-price"><?php esc_html_e( 'Od', 'grofi' ); ?></label>
						<div class="shop-filters__price-input-wrap">
							<span class="shop-filters__currency" aria-hidden="true">zł</span>
							<input type="number" id="filter-min-price"
							       class="shop-filters__price-input" name="min_price"
							       value="<?php echo esc_attr( $min_price ); ?>"
							       min="0" step="1" placeholder="0">
						</div>
					</div>

					<span class="shop-filters__price-dash" aria-hidden="true">–</span>

					<div class="shop-filters__price-field">
						<label class="shop-filters__price-label"
						       for="filter-max-price"><?php esc_html_e( 'Do', 'grofi' ); ?></label>
						<div class="shop-filters__price-input-wrap">
							<span class="shop-filters__currency" aria-hidden="true">zł</span>
							<input type="number" id="filter-max-price"
							       class="shop-filters__price-input" name="max_price"
							       value="<?php echo esc_attr( $max_price ); ?>"
							       min="0" step="1" placeholder="∞">
						</div>
					</div>

				</div>
				<button type="submit" class="shop-filters__price-btn">
					<?php esc_html_e( 'Zastosuj', 'grofi' ); ?>
				</button>
			</div>

		</div>

		<?php if ( $has_active_filters ) : ?>
		<a href="<?php echo esc_url( $reset_url ); ?>" class="shop-filters__reset">
			<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
			     fill="none" stroke="currentColor" stroke-width="2.5"
			     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
			<?php esc_html_e( 'Wyczyść filtry', 'grofi' ); ?>
		</a>
		<?php endif; ?>

	</form>
</div>
