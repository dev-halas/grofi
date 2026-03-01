<?php

/**
 * Zwraca zawartość pliku SVG z cache'owaniem w pamięci procesu.
 * Plik pochodzi z dysku motywu — traktujemy go jako zaufane źródło.
 *
 * @param  string $relative_path Ścieżka względna od katalogu motywu.
 * @return string                Zawartość SVG lub pusty string gdy plik nie istnieje.
 */
function grofi_get_theme_svg( string $relative_path ): string {
	static $cache = [];

	if ( isset( $cache[ $relative_path ] ) ) {
		return $cache[ $relative_path ];
	}

	$absolute_path = get_theme_file_path( $relative_path );

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$cache[ $relative_path ] = file_exists( $absolute_path )
		? file_get_contents( $absolute_path )
		: '';

	return $cache[ $relative_path ];
}
