<?php
/**
 * Product category icon: admin fields, save handler, and template helper.
 *
 * @package Grofi
 */

// ── Constants ──────────────────────────────────────────────────────────────────

/** Term meta key used to store the per-category icon attachment ID. */
const GROFI_CAT_ICON_META_KEY = '_grofi_cat_icon_id';

/** Nonce action used to protect the per-category save handler. */
const GROFI_CAT_ICON_NONCE = 'grofi_save_cat_icon';

/**
 * Fallback icon URL displayed when a category has no icon assigned.
 *
 * Edit the URL below — any valid image path works (theme asset, CDN, absolute URL).
 * Set to '' to show nothing for categories without an icon.
 *
 * Example using a theme asset:
 *   define( 'GROFI_DEFAULT_CAT_ICON_URL', get_theme_file_uri( 'dist/icons/default-cat.svg' ) );
 *
 * Can be overridden before this file is loaded (e.g. in functions.php or child theme).
 */
if ( ! defined( 'GROFI_DEFAULT_CAT_ICON_URL' ) ) {
	define( 'GROFI_DEFAULT_CAT_ICON_URL', 'http://localhost:8888/grofi/wp-content/uploads/2026/02/Union.svg' );
}


// ── Per-category admin form fields ─────────────────────────────────────────────

/**
 * Renders the "Ikona" field on the Add New Category form.
 */
function grofi_cat_icon_field_add(): void {
	wp_nonce_field( GROFI_CAT_ICON_NONCE, 'grofi_cat_icon_nonce' );
	?>
	<div class="form-field">
		<label for="product-cat-icon-id"><?php esc_html_e( 'Ikona kategorii', 'grofi' ); ?></label>
		<?php grofi_cat_icon_widget( 0, '' ); ?>
		<p class="description"><?php esc_html_e( 'Mała ikona/obrazek wyświetlany przy nazwie kategorii.', 'grofi' ); ?></p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'grofi_cat_icon_field_add' );

/**
 * Renders the "Ikona" field on the Edit Category form.
 *
 * @param WP_Term $term The term being edited.
 */
function grofi_cat_icon_field_edit( WP_Term $term ): void {
	$icon_id  = (int) get_term_meta( $term->term_id, GROFI_CAT_ICON_META_KEY, true );
	$icon_url = $icon_id ? (string) ( wp_get_attachment_image_url( $icon_id, 'thumbnail' ) ?: '' ) : '';

	wp_nonce_field( GROFI_CAT_ICON_NONCE, 'grofi_cat_icon_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="product-cat-icon-id"><?php esc_html_e( 'Ikona kategorii', 'grofi' ); ?></label></th>
		<td>
			<?php grofi_cat_icon_widget( $icon_id, $icon_url ); ?>
			<p class="description"><?php esc_html_e( 'Mała ikona/obrazek wyświetlany przy nazwie kategorii.', 'grofi' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'grofi_cat_icon_field_edit' );

/**
 * Shared upload widget used in both add and edit forms.
 *
 * @param int    $icon_id   Current attachment ID (0 when empty).
 * @param string $icon_url  Current thumbnail URL ('' when empty).
 */
function grofi_cat_icon_widget( int $icon_id, string $icon_url ): void {
	?>
	<div id="product-cat-icon-wrap">
		<img
			id="product-cat-icon-preview"
			src="<?php echo esc_url( $icon_url ); ?>"
			alt=""
			width="80"
			style="display:<?php echo $icon_url ? 'block' : 'none'; ?>; height:auto; margin-bottom:8px; border-radius:4px;">
		<input
			type="hidden"
			name="product_cat_icon_id"
			id="product-cat-icon-id"
			value="<?php echo esc_attr( $icon_id ?: '' ); ?>">
		<br>
		<button type="button" class="button" id="product-cat-icon-upload">
			<?php esc_html_e( 'Wybierz ikonę', 'grofi' ); ?>
		</button>
		<button
			type="button"
			class="button"
			id="product-cat-icon-remove"
			style="display:<?php echo $icon_id ? 'inline-block' : 'none'; ?>">
			<?php esc_html_e( 'Usuń', 'grofi' ); ?>
		</button>
	</div>
	<?php
}


// ── Save handler ───────────────────────────────────────────────────────────────

/**
 * Persists the icon attachment ID when a product category is created or edited.
 *
 * @param int $term_id Term ID being saved.
 */
function grofi_save_cat_icon( int $term_id ): void {
	if (
		empty( $_POST['grofi_cat_icon_nonce'] ) ||
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['grofi_cat_icon_nonce'] ) ),
			GROFI_CAT_ICON_NONCE
		)
	) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	if ( ! isset( $_POST['product_cat_icon_id'] ) ) {
		return;
	}

	$icon_id = absint( $_POST['product_cat_icon_id'] );

	if ( $icon_id ) {
		update_term_meta( $term_id, GROFI_CAT_ICON_META_KEY, $icon_id );
	} else {
		delete_term_meta( $term_id, GROFI_CAT_ICON_META_KEY );
	}
}
add_action( 'created_product_cat', 'grofi_save_cat_icon' );
add_action( 'edited_product_cat',  'grofi_save_cat_icon' );


// ── Admin scripts ──────────────────────────────────────────────────────────────

/**
 * Enqueues the Media Library and icon-picker script on product_cat admin screens.
 */
function grofi_enqueue_cat_icon_scripts(): void {
	$screen = get_current_screen();
	if ( ! $screen || $screen->taxonomy !== 'product_cat' ) {
		return;
	}

	wp_enqueue_media();

	$i18n = wp_json_encode( [
		'title'  => __( 'Wybierz ikonę kategorii', 'grofi' ),
		'button' => __( 'Użyj jako ikony', 'grofi' ),
	] );

	wp_add_inline_script(
		'jquery',
		sprintf(
			'(function ($) {
				"use strict";
				const i18n = %s;
				let frame;

				$(function () {
					$("#product-cat-icon-upload").on("click", function (e) {
						e.preventDefault();

						if (frame) {
							frame.open();
							return;
						}

						frame = wp.media({
							title:    i18n.title,
							button:   { text: i18n.button },
							multiple: false,
							library:  { type: "image" },
						});

						frame.on("select", function () {
							const att = frame.state().get("selection").first().toJSON();
							const src = att.sizes?.thumbnail?.url ?? att.url;
							$("#product-cat-icon-id").val(att.id);
							$("#product-cat-icon-preview").attr("src", src).show();
							$("#product-cat-icon-remove").show();
						});

						frame.open();
					});

					$("#product-cat-icon-remove").on("click", function (e) {
						e.preventDefault();
						$("#product-cat-icon-id").val("");
						$("#product-cat-icon-preview").hide().attr("src", "");
						$(this).hide();
					});
				});
			})(jQuery);',
			$i18n
		)
	);
}
add_action( 'admin_enqueue_scripts', 'grofi_enqueue_cat_icon_scripts' );


// ── Template helper ────────────────────────────────────────────────────────────

/**
 * Returns the icon URL for a product category.
 *
 * Lookup order:
 *   1. Per-category icon (term meta set in the admin)
 *   2. GROFI_DEFAULT_CAT_ICON_URL constant (configured in code)
 *   3. '' — no icon available
 *
 * @param  int    $term_id Term ID of the product category.
 * @param  string $size    WordPress image size slug (default: 'thumbnail').
 * @return string          Icon URL, or '' when no icon is available.
 */
function get_product_cat_icon_url( int $term_id, string $size = 'thumbnail' ): string {
	$icon_id = (int) get_term_meta( $term_id, GROFI_CAT_ICON_META_KEY, true );

	if ( $icon_id ) {
		return (string) ( wp_get_attachment_image_url( $icon_id, $size ) ?: '' );
	}

	return GROFI_DEFAULT_CAT_ICON_URL;
}
