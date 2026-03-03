<?php

// -------------------------------------------------------
// Deklaracja wsparcia WooCommerce w motywie
// -------------------------------------------------------
add_action( 'after_setup_theme', function () {
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


// -------------------------------------------------------
// Wyłącz domyślne style WooCommerce – używamy własnych
// -------------------------------------------------------
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );


// -------------------------------------------------------
// Liczba produktów i kolumn w pętli sklepu
// -------------------------------------------------------
add_filter( 'loop_shop_per_page', function (): int {
	$allowed = [ 16, 32, 48 ];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['per_page'] ) ) {
		$requested = (int) $_GET['per_page'];
		if ( in_array( $requested, $allowed, true ) ) {
			return $requested;
		}
	}
	return 16;
}, 20 );

add_filter( 'loop_shop_columns', fn () => 3 );



defined( 'ABSPATH' ) || exit;

// =============================================================
// 1. POLE NA STRONIE PRODUKTU (zakładka Wysyłka)
// =============================================================

add_action( 'woocommerce_product_options_shipping', 'wc_per_box_add_product_field' );

function wc_per_box_add_product_field(): void {
	echo '<div class="options_group">';

	woocommerce_wp_text_input( [
		'id'                => '_items_per_box',
		'label'             => __( 'Maks. szt. w paczce', 'wc-shipping-per-box' ),
		'description'       => __( 'Ile sztuk tego produktu mieści się w jednej paczce. Puste = wartość domyślna z metody wysyłki.', 'wc-shipping-per-box' ),
		'desc_tip'          => true,
		'type'              => 'number',
		'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
	] );

	echo '</div>';
}

add_action( 'woocommerce_process_product_meta', 'wc_per_box_save_product_field' );

function wc_per_box_save_product_field( int $post_id ): void {
	$value = isset( $_POST['_items_per_box'] ) ? absint( $_POST['_items_per_box'] ) : 0;

	if ( $value > 0 ) {
		update_post_meta( $post_id, '_items_per_box', $value );
	} else {
		delete_post_meta( $post_id, '_items_per_box' );
	}
}

// Warianty produktu
add_action( 'woocommerce_product_after_variable_attributes', 'wc_per_box_add_variation_field', 10, 3 );

function wc_per_box_add_variation_field( int $loop, array $variation_data, WP_Post $variation ): void {
	woocommerce_wp_text_input( [
		'id'                => "_items_per_box_{$loop}",
		'name'              => "_items_per_box[{$loop}]",
		'label'             => __( 'Maks. szt. w paczce', 'wc-shipping-per-box' ),
		'desc_tip'          => true,
		'description'       => __( 'Nadpisuje ustawienie produktu głównego dla tego wariantu.', 'wc-shipping-per-box' ),
		'value'             => get_post_meta( $variation->ID, '_items_per_box', true ),
		'type'              => 'number',
		'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
		'wrapper_class'     => 'form-row form-row-full',
	] );
}

add_action( 'woocommerce_save_product_variation', 'wc_per_box_save_variation_field', 10, 2 );

function wc_per_box_save_variation_field( int $variation_id, int $loop ): void {
	$value = isset( $_POST['_items_per_box'][ $loop ] ) ? absint( $_POST['_items_per_box'][ $loop ] ) : 0;

	if ( $value > 0 ) {
		update_post_meta( $variation_id, '_items_per_box', $value );
	} else {
		delete_post_meta( $variation_id, '_items_per_box' );
	}
}

// =============================================================
// 2. METODA WYSYŁKI
// =============================================================

add_action( 'woocommerce_shipping_init', 'wc_shipping_per_box_init' );

function wc_shipping_per_box_init(): void {

	if ( class_exists( 'WC_Shipping_Per_Box' ) ) {
		return;
	}

	class WC_Shipping_Per_Box extends WC_Shipping_Method {

		public function __construct( int $instance_id = 0 ) {
			$this->id                 = 'per_box';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'Wysyłka per paczka', 'wc-shipping-per-box' );
			$this->method_description = __( 'Koszt wysyłki liczony per paczka. Liczba szt./paczkę ustawiana na stronie produktu.', 'wc-shipping-per-box' );
			$this->supports           = [ 'shipping-zones', 'instance-settings' ];

			$this->init();
		}

		private function init(): void {
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
			$shipping_classes = WC()->shipping()->get_shipping_classes();

			$fields = [
				'title'                 => [
					'title'   => __( 'Nazwa metody', 'wc-shipping-per-box' ),
					'type'    => 'text',
					'default' => $this->method_title,
				],
				'default_items_per_box' => [
					'title'             => __( 'Domyślna liczba szt. w paczce', 'wc-shipping-per-box' ),
					'type'              => 'number',
					'default'           => 1,
					'custom_attributes' => [ 'min' => 1, 'step' => 1 ],
					'description'       => __( 'Używana gdy produkt nie ma ustawionej własnej wartości.', 'wc-shipping-per-box' ),
				],
				'price_per_box'         => [
					'title'       => __( 'Cena za paczkę – domyślna (netto)', 'wc-shipping-per-box' ),
					'type'        => 'price',
					'default'     => '15.00',
					'description' => __( 'Używana gdy produkt nie należy do żadnej klasy wysyłkowej lub klasa nie ma własnej ceny.', 'wc-shipping-per-box' ),
				],
				'free_above'            => [
					'title'       => __( 'Darmowa wysyłka powyżej (opcjonalnie)', 'wc-shipping-per-box' ),
					'type'        => 'price',
					'default'     => '',
					'description' => __( 'Wartość koszyka brutto. Puste = wyłączone.', 'wc-shipping-per-box' ),
				],
			];

			foreach ( $shipping_classes as $class ) {
				$fields[ 'price_per_box_class_' . $class->slug ] = [
					'title'       => sprintf( __( 'Cena za paczkę – %s (netto)', 'wc-shipping-per-box' ), $class->name ),
					'type'        => 'price',
					'default'     => '',
					'description' => __( 'Puste = użyj ceny domyślnej.', 'wc-shipping-per-box' ),
				];
			}

			$this->instance_form_fields = $fields;
		}

		public function calculate_shipping( $package = [] ): void {
			$default_per_box  = max( 1, (int) $this->get_option( 'default_items_per_box', 1 ) );
			$default_price    = (float) $this->get_option( 'price_per_box', 15.00 );
			$free_above       = $this->get_option( 'free_above', '' );

			// Próg darmowej wysyłki.
			if ( '' !== $free_above && (float) $free_above > 0 ) {
				if ( WC()->cart->get_subtotal() >= (float) $free_above ) {
					$this->add_rate( [
						'id'    => $this->get_rate_id(),
						'label' => $this->title,
						'cost'  => 0,
					] );
					return;
				}
			}

			// Grupuj produkty wg klasy wysyłkowej.
			$class_groups = [];
			foreach ( $package['contents'] as $item ) {
				$slug = $item['data']->get_shipping_class() ?: '__none__';
				$class_groups[ $slug ][] = $item;
			}

			$total_cost  = 0.0;
			$boxes_total = 0;

			foreach ( $class_groups as $slug => $items ) {
				$option_key      = 'price_per_box_class_' . $slug;
				$class_price_raw = $this->get_option( $option_key, '' );

				// Jawne 0 = pomiń tę klasę (produkty bez kosztu wysyłki).
				if ( '' !== $class_price_raw && (float) $class_price_raw === 0.0 ) {
					continue;
				}

				$price = ( '' !== $class_price_raw && (float) $class_price_raw > 0 )
					? (float) $class_price_raw
					: $default_price;

				$boxes        = $this->calculate_boxes( $items, $default_per_box );
				$boxes_total += $boxes;
				$total_cost  += $boxes * $price;
			}

			if ( $boxes_total <= 0 ) {
				return;
			}

			$this->add_rate( [
				'id'        => $this->get_rate_id(),
				'label'     => $this->title,
				'cost'      => $total_cost,
				'meta_data' => [ 'boxes_total' => $boxes_total ],
			] );
		}

		/**
		 * Dla każdego produktu pobiera _items_per_box z meta (lub domyślną),
		 * wylicza ceil(qty / per_box) i sumuje.
		 *
		 * Przykład:
		 *   Produkt A: qty=7, per_box=5 → ceil(7/5) = 2 paczki
		 *   Produkt B: qty=3, per_box=2 → ceil(3/2) = 2 paczki
		 *   Razem: 4 paczki
		 */
		private function calculate_boxes( array $contents, int $default_per_box ): int {
			$boxes = 0;

			foreach ( $contents as $item ) {
				/** @var WC_Product $product */
				$product = $item['data'];

				// Pobierz per_box z wariantu → rodzic → domyślna.
				$per_box = (int) $product->get_meta( '_items_per_box' );

				if ( $per_box <= 0 && $product->get_parent_id() ) {
					$per_box = (int) get_post_meta( $product->get_parent_id(), '_items_per_box', true );
				}

				if ( $per_box <= 0 ) {
					$per_box = $default_per_box;
				}

				$boxes += (int) ceil( (int) $item['quantity'] / $per_box );
			}

			return $boxes;
		}
	}
}

add_filter( 'woocommerce_shipping_methods', 'wc_register_per_box_method' );

function wc_register_per_box_method( array $methods ): array {
	$methods['per_box'] = 'WC_Shipping_Per_Box';
	return $methods;
}