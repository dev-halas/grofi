<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$product_id    = $args['product_id'] ?? get_the_ID();
$label         = $args['label'] ?? __( 'Najniższa cena z ostatnich 30 dni:', 'grofi' );
$product       = wc_get_product( (int) $product_id );
$lowest        = omnibus_get_lowest_price( (int) $product_id );

if ( ! $lowest || ! $product ) return;

// Nie pokazuj gdy najniższa cena jest identyczna z aktualną (brak historii zmian)
/*
$current_price = (float) $product->get_price();
if ( abs( $lowest - $current_price ) < 0.01 ) return;
*/
?>
<div class="last30-days-price">
    <?php echo esc_html( $label ); ?>
    <span class="omnibus-price__value"><?php echo wc_price( $lowest ); ?></span>
</div>