<?php
/**
 * Template Part: Pasek narzędzi sklepu
 * Sortowanie i liczba produktów na stronę.
 *
 * Użycie: get_template_part( 'woocommerce/template-parts/shop-toolbar' );
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$current_orderby = isset( $_GET['orderby'] )
	? sanitize_text_field( wp_unslash( $_GET['orderby'] ) )
	: apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'popularity' ) );

$current_per_page = isset( $_GET['per_page'] )
	? (int) $_GET['per_page']
	: (int) apply_filters( 'loop_shop_per_page', 24 );
// phpcs:enable

$orderby_options = apply_filters( 'woocommerce_catalog_orderby', [
	'menu_order' => __( 'Domyślne sortowanie', 'woocommerce' ),
	'popularity' => __( 'Sortuj wg popularności', 'woocommerce' ),
	'rating'     => __( 'Sortuj wg oceny', 'woocommerce' ),
	'date'       => __( 'Sortuj wg nowości', 'woocommerce' ),
	'price'      => __( 'Sortuj wg ceny: rosnąco', 'woocommerce' ),
	'price-desc' => __( 'Sortuj wg ceny: malejąco', 'woocommerce' ),
] );

$per_page_options = [ 24, 32, 48 ];
?>

<div class="shop-toolbar">

	<div class="shop-toolbar__select-wrap">
		<select class="shop-toolbar__select"
		        data-ajax-nav
		        aria-label="<?php esc_attr_e( 'Sortowanie produktów', 'grofi' ); ?>">
			<?php foreach ( $orderby_options as $val => $label ) :
				$url = esc_url( add_query_arg( [ 'orderby' => $val, 'paged' => false ] ) );
			?>
			<option value="<?php echo $url; ?>" <?php selected( $current_orderby, $val ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="shop-toolbar__select-wrap">
		<select class="shop-toolbar__select"
		        data-ajax-nav
		        aria-label="<?php esc_attr_e( 'Liczba produktów na stronę', 'grofi' ); ?>">
			<?php foreach ( $per_page_options as $num ) :
				$url = esc_url( add_query_arg( [ 'per_page' => $num, 'paged' => false ] ) );
			?>
			<option value="<?php echo $url; ?>" <?php selected( $current_per_page, $num ); ?>>
				<?php echo esc_html( $num . ' ' . __( 'produktów', 'grofi' ) ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>

</div>
