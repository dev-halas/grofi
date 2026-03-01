<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Stałe ───────────────────────────────────────────────────────────────────

const OMNIBUS_META_HISTORY  = '_omnibus_price_history';
const OMNIBUS_META_PRICE    = '_price';
const OMNIBUS_HISTORY_DAYS  = 30;
const OMNIBUS_CACHE_GROUP   = 'omnibus';
const OMNIBUS_CACHE_SECONDS = 3 * HOUR_IN_SECONDS;

// ─── Hook ────────────────────────────────────────────────────────────────────

add_action( 'woocommerce_before_product_object_save', 'omnibus_save_price_history' );

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Zwraca timestamp punktu granicznego (30 dni wstecz).
 */
function omnibus_cutoff(): int {
    return strtotime( '-' . OMNIBUS_HISTORY_DAYS . ' days' );
}

/**
 * Sprawdza, czy wpis historii jest poprawny i nie wygasł.
 */
function omnibus_is_valid_entry( mixed $entry, int $cutoff ): bool {
    return is_array( $entry )
        && isset( $entry['price'], $entry['timestamp'] )
        && is_numeric( $entry['price'] )
        && is_int( $entry['timestamp'] )
        && $entry['timestamp'] >= $cutoff;
}

/**
 * Pobiera i oczyszcza historię cen z post meta.
 *
 * @return array<int, array{price: float, timestamp: int}>
 */
function omnibus_get_clean_history( int $product_id ): array {
    $raw = get_post_meta( $product_id, OMNIBUS_META_HISTORY, true );
    if ( ! is_array( $raw ) ) {
        return [];
    }

    $cutoff = omnibus_cutoff();

    return array_values(
        array_filter( $raw, static fn( $entry ) => omnibus_is_valid_entry( $entry, $cutoff ) )
    );
}

// ─── Zapis historii ──────────────────────────────────────────────────────────

/**
 * Zapisuje cenę produktu do historii tuż PRZED nadpisaniem jej w bazie.
 * Dzięki temu historia zawiera poprzednie ceny, a nie aktualną.
 *
 * @param WC_Product $product
 */
function omnibus_save_price_history( WC_Product $product ): void {
    $product_id = $product->get_id();
    if ( ! $product_id ) {
        return;
    }

    // Produkty zmienne nie mają własnej ceny – obsługujemy tylko proste i wariacje
    if ( $product->is_type( 'variable' ) ) {
        return;
    }

    // Czytamy STARĄ cenę bezpośrednio z bazy (post meta), zanim WooCommerce ją nadpisze
    $price = (float) get_post_meta( $product_id, OMNIBUS_META_PRICE, true );
    if ( $price <= 0 ) {
        return;
    }

    $history    = omnibus_get_clean_history( $product_id );
    $last_entry = ! empty( $history ) ? $history[ array_key_last( $history ) ] : null;
    $last_price = $last_entry !== null ? (float) $last_entry['price'] : null;

    if ( $last_price === null || abs( $last_price - $price ) >= 0.01 ) {
        $history[] = [
            'price'     => $price,
            'timestamp' => time(),
        ];
        update_post_meta( $product_id, OMNIBUS_META_HISTORY, $history );

        // Unieważnij cache po aktualizacji historii
        wp_cache_delete( $product_id, OMNIBUS_CACHE_GROUP );
    }
}

// ─── Odczyt najniższej ceny ──────────────────────────────────────────────────

/**
 * Zwraca najniższą cenę produktu z ostatnich 30 dni.
 * Dla produktów zmiennych sprawdza wariacje i zwraca globalne minimum.
 *
 * @return float|null Minimalna cena lub null gdy brak historii.
 */
function omnibus_get_lowest_price( int $product_id ): ?float {
    $cache_key = 'lowest_' . $product_id;
    $cached    = wp_cache_get( $cache_key, OMNIBUS_CACHE_GROUP );

    if ( $cached !== false ) {
        // wp_cache_get zwraca false przy braku klucza, a null to poprawna wartość
        return $cached === 'null' ? null : (float) $cached;
    }

    $result = omnibus_calculate_lowest_price( $product_id );

    wp_cache_set( $cache_key, $result === null ? 'null' : $result, OMNIBUS_CACHE_GROUP, OMNIBUS_CACHE_SECONDS );

    return $result;
}

/**
 * Właściwa logika obliczania najniższej ceny (bez cachowania).
 *
 * @return float|null
 */
function omnibus_calculate_lowest_price( int $product_id ): ?float {
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return null;
    }

    if ( $product->is_type( 'variable' ) ) {
        return omnibus_lowest_price_for_variable( $product );
    }

    return omnibus_lowest_price_from_history( $product_id );
}

/**
 * Dla produktów zmiennych pobiera meta wszystkich wariacji jednym zapytaniem
 * zamiast ładować każdą wariację jako obiekt WC_Product (unika N+1).
 *
 * @param WC_Product_Variable $product
 * @return float|null
 */
function omnibus_lowest_price_for_variable( WC_Product $product ): ?float {
    $variation_ids = $product->get_children();
    if ( empty( $variation_ids ) ) {
        return null;
    }

    // Jedno zapytanie do bazy dla wszystkich wariacji naraz
    global $wpdb;

    $placeholders = implode( ',', array_fill( 0, count( $variation_ids ), '%d' ) );

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_id, meta_value
             FROM {$wpdb->postmeta}
             WHERE post_id IN ($placeholders)
               AND meta_key = %s",
            array_merge( $variation_ids, [ OMNIBUS_META_HISTORY ] )
        )
    );

    if ( empty( $rows ) ) {
        return null;
    }

    $cutoff = omnibus_cutoff();
    $prices = [];

    foreach ( $rows as $row ) {
        $history = maybe_unserialize( $row->meta_value );
        if ( ! is_array( $history ) ) {
            continue;
        }

        foreach ( $history as $entry ) {
            if ( omnibus_is_valid_entry( $entry, $cutoff ) ) {
                $prices[] = (float) $entry['price'];
            }
        }
    }

    return ! empty( $prices ) ? min( $prices ) : null;
}

/**
 * Zwraca najniższą cenę z historii dla pojedynczego produktu/wariacji.
 *
 * @return float|null
 */
function omnibus_lowest_price_from_history( int $product_id ): ?float {
    $history = omnibus_get_clean_history( $product_id );
    if ( empty( $history ) ) {
        return null;
    }

    $prices = array_column( $history, 'price' );

    return ! empty( $prices ) ? (float) min( $prices ) : null;
}