<?php
/**
 * Single Product Meta – marka + SKU.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="product_meta">
    
    <?php
	$brands = get_the_term_list( $product->get_id(), 'product_brand', '', ', ' );
	if ( $brands && ! is_wp_error( $brands ) ) : ?>
		<span class="posted_brand">
			<?php esc_html_e( 'Marka:', 'woocommerce' ); ?>
			<?php echo $brands; ?>
		</span>
	<?php endif; ?>

	<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( ProductType::VARIABLE ) ) ) : ?>
		<span class="sku_wrapper">
			<?php esc_html_e( 'SKU:', 'woocommerce' ); ?>
			<span class="sku"><?php echo ( $sku = $product->get_sku() ) ? esc_html( $sku ) : esc_html__( 'N/A', 'woocommerce' ); ?></span>
		</span>
	<?php endif; ?>

</div>