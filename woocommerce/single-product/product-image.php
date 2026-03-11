<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.0
 *
 * Single Product Image – custom Alpine.js gallery.
 */
defined( 'ABSPATH' ) || exit;

global $product;

$featured_id = $product->get_image_id();
$gallery_ids = $product->get_gallery_image_ids();
$all_ids     = $featured_id ? array_merge( [ $featured_id ], $gallery_ids ) : $gallery_ids;

$images = [];
foreach ( $all_ids as $id ) {
	$src   = wp_get_attachment_image_url( $id, 'woocommerce_single' );
	$thumb = wp_get_attachment_image_url( $id, 'woocommerce_gallery_thumbnail' );
	$alt   = trim( wp_strip_all_tags( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) );

	if ( ! $alt ) {
		$alt = trim( wp_strip_all_tags( get_the_title( $id ) ) );
	}
	if ( ! $alt ) {
		$alt = get_the_title();
	}
	if ( $src ) {
		$images[] = [
			'id'    => (int) $id,
			'src'   => $src,
			'thumb' => $thumb ?: $src,
			'alt'   => $alt,
		];
	}
}

if ( empty( $images ) ) {
	echo wc_placeholder_img( 'woocommerce_single' );
	return;
}
?>

<?php 
	$alpine_images = wp_json_encode( $images, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
?>
<div class="product-gallery" x-data="productGallery(<?php echo esc_attr( $alpine_images ); ?>)">

	<div class="product-gallery__main" @click="openLightbox(activeIndex)" role="button" tabindex="0" @keydown.enter="openLightbox(activeIndex)" :aria-label="images[activeIndex]?.alt || 'Powiększ zdjęcie'">
		<?php foreach ( $images as $i => $img ) : ?>
		<img
			src="<?php echo esc_url( $img['src'] ); ?>"
			alt="<?php echo esc_attr( $img['alt'] ); ?>"
			class="product-gallery__main-img<?php echo $i === 0 ? ' product-gallery__main-img--active' : ''; ?>"
			:class="{ 'product-gallery__main-img--active': activeIndex === <?php echo (int) $i; ?> }"
			width="600"
			height="600"
			<?php echo $i > 0 ? 'loading="lazy"' : ''; ?>
		/>
		<?php endforeach; ?>
	</div>

	<?php if ( count( $images ) > 1 ) : ?>
	<div class="product-gallery__thumbs">
		<?php foreach ( $images as $i => $img ) : ?>
		<button
			class="product-gallery__thumb"
			:class="{ 'product-gallery__thumb--active': activeIndex === <?php echo (int) $i; ?> }"
			@click="setActive(<?php echo (int) $i; ?>)"
			type="button"
			aria-label="<?php echo esc_attr( sprintf( __( 'Zdjęcie %d', 'grofi' ), $i + 1 ) ); ?>"
		>
			<img
				src="<?php echo esc_url( $img['thumb'] ); ?>"
				alt="<?php echo esc_attr( $img['alt'] ); ?>"
				loading="lazy"
			/>
		</button>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

</div>
