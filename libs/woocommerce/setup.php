<?php
/**
 * WooCommerce – konfiguracja motywu + metody wysyłki
 *
 * @package Grofi
 */

defined( 'ABSPATH' ) || exit;

// =============================================================
// WSPARCIE WOOCOMMERCE W MOTYWIE
// =============================================================

add_action( 'after_setup_theme', static function (): void {
	add_theme_support( 'woocommerce', [
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
	] );
} );

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

add_filter('woocommerce_product_get_image', function($image) {
	return str_replace('<img ', '<img loading="lazy" decoding="async" ', $image);
});

// =============================================================
// PĘTLA SKLEPU
// =============================================================

add_filter( 'loop_shop_per_page', static function (): int {
	$allowed   = [ 24, 32, 48 ];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$requested = isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : 0;
	return in_array( $requested, $allowed, true ) ? $requested : 24;
}, 20 );

add_filter( 'loop_shop_columns', static fn (): int => 3 );

/**
 * Hide the "In stock" message on product page.
 *
 * @param string $html
 * @param string $text
 * @param WC_Product $product
 * @return string
 */
function my_wc_hide_in_stock_message( $html, $text, $product ) {
	$availability = $product->get_availability();

	if ( isset( $availability['class'] ) && 'in-stock' === $availability['class'] ) {
		return '';
	}

	return $html;
}

add_filter( 'woocommerce_stock_html', 'my_wc_hide_in_stock_message', 10, 3 );

// =============================================================
// HELPERS – logika pakowania
// =============================================================

/**
 * Oblicza liczbę paczek dla listy pozycji koszyka.
 *
 *   boxes = ceil( sum( qty_i / per_box_i ) )
 *
 * @param array<int,array> $contents        WC cart items.
 * @param int              $default_per_box Fallback gdy produkt nie ma meta.
 */
function mth_calculate_fractional_boxes( array $contents, int $default_per_box ): int {
	$fill = 0.0;

	foreach ( $contents as $item ) {
		/** @var WC_Product $product */
		$product = $item['data'];
		$per_box = mth_resolve_per_box( $product, $default_per_box );
		$fill   += (int) $item['quantity'] / $per_box;
	}

	return (int) ceil( $fill );
}

/**
 * Zwraca per_box dla produktu: wariant → rodzic → domyślna.
 */
function mth_resolve_per_box( WC_Product $product, int $default_per_box ): int {
	$per_box = (int) $product->get_meta( '_items_per_box' );

	if ( $per_box <= 0 && $product->get_parent_id() ) {
		$per_box = (int) get_post_meta( $product->get_parent_id(), '_items_per_box', true );
	}

	return $per_box > 0 ? $per_box : $default_per_box;
}

/**
 * Zwraca PEŁNĄ zawartość koszyka niezależnie od podziału na shipping packages.
 * WooCommerce lub pluginy (InPost) mogą dzielić koszyk na N packages po jednym
 * na klasę wysyłkową. Używamy tej funkcji żeby zawsze liczyć paczki z całości.
 */
function mth_get_full_cart_contents(): array {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return [];
	}
	return array_values( WC()->cart->get_cart() );
}

// =============================================================
// POLE META "_items_per_box" – produkt prosty (zakładka Wysyłka)
// =============================================================

add_action( 'woocommerce_product_options_shipping', 'mth_per_box_render_product_field' );

function mth_per_box_render_product_field(): void {
	echo '<div class="options_group">';
	woocommerce_wp_text_input( [
		'id'                => '_items_per_box',
		'label'             => __( 'Maks. szt. w paczce', 'grofi' ),
		'description'       => __( 'Ile sztuk mieści się w jednej paczce. Puste = wartość domyślna z metody wysyłki.', 'grofi' ),
		'desc_tip'          => true,
		'type'              => 'number',
		'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
	] );
	echo '</div>';
}

add_action( 'woocommerce_process_product_meta', 'mth_per_box_save_product_field' );

function mth_per_box_save_product_field( int $post_id ): void {
	$value = isset( $_POST['_items_per_box'] ) ? absint( $_POST['_items_per_box'] ) : 0;
	if ( $value > 0 ) {
		update_post_meta( $post_id, '_items_per_box', $value );
	} else {
		delete_post_meta( $post_id, '_items_per_box' );
	}
}

// =============================================================
// POLE META "_items_per_box" – wariant produktu
// =============================================================

add_action( 'woocommerce_product_after_variable_attributes', 'mth_per_box_render_variation_field', 10, 3 );

function mth_per_box_render_variation_field( int $loop, array $variation_data, WP_Post $variation ): void {
	woocommerce_wp_text_input( [
		'id'                => "_items_per_box_{$loop}",
		'name'              => "_items_per_box[{$loop}]",
		'label'             => __( 'Maks. szt. w paczce', 'grofi' ),
		'desc_tip'          => true,
		'description'       => __( 'Nadpisuje ustawienie produktu głównego dla tego wariantu.', 'grofi' ),
		'value'             => get_post_meta( $variation->ID, '_items_per_box', true ),
		'type'              => 'number',
		'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
		'wrapper_class'     => 'form-row form-row-full',
	] );
}

add_action( 'woocommerce_save_product_variation', 'mth_per_box_save_variation_field', 10, 2 );

function mth_per_box_save_variation_field( int $variation_id, int $loop ): void {
	$value = isset( $_POST['_items_per_box'][ $loop ] ) ? absint( $_POST['_items_per_box'][ $loop ] ) : 0;
	if ( $value > 0 ) {
		update_post_meta( $variation_id, '_items_per_box', $value );
	} else {
		delete_post_meta( $variation_id, '_items_per_box' );
	}
}

// =============================================================
// METODA WYSYŁKI "PER PACZKA" (np. GLS, kurier własny)
// =============================================================

add_action( 'woocommerce_shipping_init', 'mth_shipping_per_box_init' );

function mth_shipping_per_box_init(): void {

	if ( class_exists( 'MTH_Shipping_Per_Box' ) ) {
		return;
	}

	class MTH_Shipping_Per_Box extends WC_Shipping_Method {

		public function __construct( int $instance_id = 0 ) {
			$this->id                 = 'per_box';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'Wysyłka per paczka', 'grofi' );
			$this->method_description = __( 'Koszt wysyłki liczony per paczka. Liczba szt./paczkę ustawiana na stronie produktu.', 'grofi' );
			$this->supports           = [ 'shipping-zones', 'instance-settings' ];

			$this->init_form_fields();
			$this->init_settings();

			$this->title   = $this->get_option( 'title', $this->method_title );
			$this->enabled = $this->get_option( 'enabled', 'yes' );

			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				[ $this, 'process_admin_options' ]
			);
		}

		public function init_form_fields(): void {
			$fields = [
				'title'                 => [
					'title'   => __( 'Nazwa metody', 'grofi' ),
					'type'    => 'text',
					'default' => $this->method_title,
				],
				'logo_url'              => [
					'title'       => __( 'URL logo dostawcy', 'grofi' ),
					'type'        => 'text',
					'default'     => '',
					'description' => __( 'Pełny URL obrazka wyświetlanego przy metodzie na kasie. Puste = brak logo.', 'grofi' ),
					'desc_tip'    => true,
					'placeholder' => 'https://example.com/logo.png',
				],
				'default_items_per_box' => [
					'title'             => __( 'Domyślna liczba szt. w paczce', 'grofi' ),
					'type'              => 'number',
					'default'           => 1,
					'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
					'description'       => __( 'Używana gdy produkt nie ma ustawionej własnej wartości.', 'grofi' ),
					'desc_tip'          => true,
				],
				'price_per_box'         => [
					'title'       => __( 'Cena za paczkę – domyślna (netto)', 'grofi' ),
					'type'        => 'price',
					'default'     => '',
					'description' => __( 'Fallback gdy klasa wysyłkowa nie ma własnej ceny. Puste = metoda nieaktywna.', 'grofi' ),
					'desc_tip'    => true,
				],
				'free_above'            => [
					'title'       => __( 'Darmowa wysyłka powyżej (opcjonalnie)', 'grofi' ),
					'type'        => 'price',
					'default'     => '',
					'description' => __( 'Wartość koszyka brutto. Puste = wyłączone.', 'grofi' ),
					'desc_tip'    => true,
				],
			];

			foreach ( WC()->shipping()->get_shipping_classes() as $class ) {
				$fields[ 'price_per_box_class_' . $class->slug ] = [
					'title'       => sprintf( __( 'Cena za paczkę – %s (netto)', 'grofi' ), esc_html( $class->name ) ),
					'type'        => 'price',
					'default'     => '',
					'description' => __( 'Puste = użyj ceny domyślnej.', 'grofi' ),
					'desc_tip'    => true,
				];
			}

			$this->instance_form_fields = $fields;
		}

		public function calculate_shipping( $package = [] ): void {
			/**
			 * Liczymy wyłącznie z bieżącego $package['contents'], nie z pełnego koszyka.
			 * Dzięki temu:
			 *  - gdy WC dzieli koszyk na N packages, każdy liczy swoje paczki osobno
			 *    i WC sumuje je poprawnie,
			 *  - wielokrotne wywołania calculate_totals() w jednym requeście nie psują
			 *    wyniku (brak static, który zeruje się dopiero przy nowym request).
			 */
			$default_per_box   = max( 1, (int) $this->get_option( 'default_items_per_box', 1 ) );
			$default_price_raw = $this->get_option( 'price_per_box', '' );
			$default_price     = '' !== $default_price_raw ? (float) $default_price_raw : 0.0;
			$free_above        = $this->get_option( 'free_above', '' );

			$logo_url = esc_url_raw( $this->get_option( 'logo_url', '' ) );

			// Próg darmowej wysyłki.
			if ( '' !== $free_above && (float) $free_above > 0 ) {
				if ( WC()->cart->get_subtotal() >= (float) $free_above ) {
					$this->add_rate( [
						'id'        => $this->get_rate_id(),
						'label'     => $this->title,
						'cost'      => 0,
						'meta_data' => [ 'logo_url' => $logo_url ],
					] );
					return;
				}
			}

			$pkg_items = array_values( $package['contents'] ?? [] );

			if ( empty( $pkg_items ) ) {
				return;
			}

			// Odfiltruj klasy z jawnym kosztem 0.00 (produkty wirtualne/cyfrowe).
			$billable = array_filter( $pkg_items, function ( $item ) {
				$slug  = $item['data']->get_shipping_class() ?: '__none__';
				$price = $this->get_option( 'price_per_box_class_' . $slug, '' );
				return ! ( '' !== $price && 0.0 === (float) $price );
			} );

			if ( empty( $billable ) ) {
				return;
			}

			$boxes = mth_calculate_fractional_boxes( array_values( $billable ), $default_per_box );

			if ( $boxes <= 0 ) {
				return;
			}

			// Cena: najwyższa cena klasy wśród pozycji, fallback na domyślną.
			$price_per_box = 0.0;
			foreach ( $billable as $item ) {
				$slug      = $item['data']->get_shipping_class() ?: '__none__';
				$class_raw = $this->get_option( 'price_per_box_class_' . $slug, '' );
				if ( '' !== $class_raw && (float) $class_raw > 0 ) {
					$price_per_box = max( $price_per_box, (float) $class_raw );
				}
			}

			if ( $price_per_box <= 0.0 ) {
				$price_per_box = $default_price;
			}

			if ( $price_per_box <= 0.0 ) {
				return;
			}

			$this->add_rate( [
				'id'        => $this->get_rate_id(),
				'label'     => $this->title,
				'cost'      => $boxes * $price_per_box,
				'meta_data' => [
					'boxes_total' => $boxes,
					'logo_url'    => $logo_url,
				],
			] );
		}
	}
}

add_filter( 'woocommerce_shipping_methods', 'mth_register_per_box_method' );

function mth_register_per_box_method( array $methods ): array {
	$methods['per_box'] = 'MTH_Shipping_Per_Box';
	return $methods;
}

// =============================================================
// INPOST – ustawienia per paczka
// =============================================================

add_filter( 'woocommerce_get_sections_shipping', 'mth_inpost_add_settings_section' );

function mth_inpost_add_settings_section( array $sections ): array {
	$sections['mth_inpost_per_box'] = __( 'InPost per paczka', 'grofi' );
	return $sections;
}

add_filter( 'woocommerce_get_settings_shipping', 'mth_inpost_get_settings', 10, 2 );

function mth_inpost_get_settings( array $settings, string $current_section ): array {
	if ( 'mth_inpost_per_box' !== $current_section ) {
		return $settings;
	}

	return [
		[
			'title' => __( 'InPost – koszt per paczka', 'grofi' ),
			'type'  => 'title',
			'desc'  => __( 'Nadpisuje własny kalkulator InPost: koszt = liczba paczek × cena za paczkę. Liczba paczek liczona z _items_per_box.', 'grofi' ),
			'id'    => 'mth_inpost_per_box_section',
		],
		[
			'title'    => __( 'ID metody InPost', 'grofi' ),
			'id'       => 'mth_inpost_rate_id',
			'type'     => 'text',
			'default'  => 'easypack_parcel_machines:7',
			'desc_tip' => __( 'Rate key widoczny w woocommerce_package_rates. Format: method_id:instance_id', 'grofi' ),
		],
		[
			'title'             => __( 'Cena za paczkę – ręczny fallback (netto)', 'grofi' ),
			'id'                => 'mth_inpost_price_per_box',
			'type'              => 'number',
			'default'           => '',
			'desc_tip'          => __( 'Używane tylko gdy plugin InPost nie ma ustawionej ceny klasy wysyłkowej. Puste = override wyłączony gdy InPost też nie ma ceny.', 'grofi' ),
			'custom_attributes' => [ 'min' => 0, 'step' => '0.01' ],
		],
		[
			'title'             => __( 'Domyślna liczba szt. w paczce', 'grofi' ),
			'id'                => 'mth_inpost_default_per_box',
			'type'              => 'number',
			'default'           => '1',
			'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
			'desc_tip'          => __( 'Fallback gdy produkt nie ma ustawionego _items_per_box.', 'grofi' ),
		],
		[
			'title'    => __( 'Darmowa wysyłka powyżej (opcjonalnie)', 'grofi' ),
			'id'       => 'mth_inpost_free_above',
			'type'     => 'number',
			'default'  => '',
			'desc_tip' => __( 'Wartość koszyka brutto. Puste = wyłączone.', 'grofi' ),
		],
		[
			'type' => 'sectionend',
			'id'   => 'mth_inpost_per_box_section',
		],
	];
}

// =============================================================
// INPOST – odczyt ceny z ustawień instancji pluginu InPost
// =============================================================

/**
 * Odczytuje najwyższy koszt paczki dla podanych produktów
 * na podstawie ustawień klasy wysyłkowej w konfiguracji InPost.
 *
 * InPost zapisuje koszty pod kluczami:
 *   class_cost_{term_id}  – koszt per klasa wysyłkowa WooCommerce
 *   no_class_cost         – fallback dla produktów bez klasy
 *
 * @param string $rate_id  Format "method_id:instance_id".
 * @param array  $items    Produkty do sprawdzenia (domyślnie: pełny koszyk).
 * @return float  0.0 gdy nie znaleziono.
 */
function mth_get_inpost_instance_cost( string $rate_id, array $items = [] ): float {
	$parts = explode( ':', $rate_id, 2 );

	if ( 2 !== count( $parts ) || ! is_numeric( $parts[1] ) ) {
		return 0.0;
	}

	[ $method_id, $instance_id ] = $parts;

	$settings = get_option( sprintf( 'woocommerce_%s_%d_settings', $method_id, (int) $instance_id ) );

	if ( ! is_array( $settings ) ) {
		return 0.0;
	}

	if ( empty( $items ) ) {
		$items = mth_get_full_cart_contents();
	}

	$price         = 0.0;
	$has_any_class = false;

	foreach ( $items as $item ) {
		/** @var WC_Product $product */
		$product  = $item['data'];
		$class_id = $product->get_shipping_class_id();

		if ( $class_id > 0 ) {
			$has_any_class = true;
			$key           = 'class_cost_' . $class_id;

			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				$price = max( $price, (float) $settings[ $key ] );
			}
		}
	}

	// Fallback: no_class_cost gdy brak klasy lub klasa nie ma ustawionej ceny.
	if ( $price <= 0.0 ) {
		foreach ( [ 'no_class_cost', 'additional_cost', 'cost' ] as $key ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] && (float) $settings[ $key ] > 0 ) {
				$price = (float) $settings[ $key ];
				break;
			}
		}
	}

	return $price;
}

// =============================================================
// INPOST – nadpisanie kosztu algorytmem per paczka
// =============================================================

add_filter( 'woocommerce_package_rates', 'mth_override_inpost_per_box_rate', 20, 2 );

/**
 * Nadpisuje koszt raty InPost kalkulacją per paczka.
 *
 * Działa per-package – bez static. Każdy package liczy swoje pozycje osobno,
 * WooCommerce sumuje koszty ze wszystkich packages automatycznie.
 * Dzięki temu wielokrotne wywołania calculate_totals() w jednym requeście
 * (np. AJAX update koszyka) zawsze dają poprawny wynik.
 *
 * Kolejność źródeł ceny za paczkę:
 *   1. class_cost_{term_id} / no_class_cost z ustawień instancji InPost
 *   2. Ręczne pole „Cena za paczkę" w WC → Wysyłka → InPost per paczka
 */
function mth_override_inpost_per_box_rate( array $rates, array $package ): array {
	$rate_id = get_option( 'mth_inpost_rate_id', 'easypack_parcel_machines:7' );

	if ( ! isset( $rates[ $rate_id ] ) ) {
		return $rates;
	}

	$free_above = get_option( 'mth_inpost_free_above', '' );

	if ( '' !== $free_above && (float) $free_above > 0 ) {
		if ( WC()->cart->get_subtotal() >= (float) $free_above ) {
			$rates[ $rate_id ]->cost  = 0;
			$rates[ $rate_id ]->taxes = [];
			return $rates;
		}
	}

	$pkg_items = array_values( $package['contents'] ?? [] );

	if ( empty( $pkg_items ) ) {
		return $rates;
	}

	// 1. Czytamy cenę z ustawień klasy wysyłkowej InPost (dla produktów w tym package).
	$price_per_box = mth_get_inpost_instance_cost( $rate_id, $pkg_items );

	// 2. Fallback: ręczne pole z naszych ustawień.
	if ( $price_per_box <= 0.0 ) {
		$manual        = get_option( 'mth_inpost_price_per_box', '' );
		$price_per_box = '' !== $manual ? (float) $manual : 0.0;
	}

	if ( $price_per_box <= 0.0 ) {
		return $rates;
	}

	$default_per_box = max( 1, (int) get_option( 'mth_inpost_default_per_box', 1 ) );
	$boxes           = mth_calculate_fractional_boxes( $pkg_items, $default_per_box );
	$cost            = $boxes * $price_per_box;

	$rates[ $rate_id ]->cost  = $cost;
	$rates[ $rate_id ]->taxes = WC_Tax::calc_shipping_tax(
		$cost,
		WC_Tax::get_shipping_tax_rates()
	);

	return $rates;
}