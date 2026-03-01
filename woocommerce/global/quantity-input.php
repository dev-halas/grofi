<?php
/**
 * Quantity input – custom stepper (− / input / +).
 *
 * Overrides WooCommerce default quantity input template.
 * Works for single product page, cart, mini-cart, etc.
 *
 * @var string $classes        Extra classes for the wrapper.
 * @var string $input_id       Input element ID.
 * @var string $input_name     Input name attribute.
 * @var mixed  $input_value    Current quantity value.
 * @var mixed  $max_value      Max quantity ('' = no limit).
 * @var mixed  $min_value      Min quantity.
 * @var mixed  $step           Step value.
 * @var string $placeholder    Placeholder text.
 * @var string $autocomplete   Autocomplete attribute value.
 * @var bool   $readonly       Whether the input is readonly.
 * @var string $product_name   Product name for aria-label.
 */
defined('ABSPATH') || exit;

if ($max_value && $min_value === $max_value) : ?>
	<div class="quantity hidden">
		<input
			type="hidden"
			id="<?php echo esc_attr($input_id); ?>"
			class="input-text qty text"
			name="<?php echo esc_attr($input_name); ?>"
			value="<?php echo esc_attr($min_value); ?>"
		/>
	</div>
<?php else :
	$labelledby = !empty($product_name)
		? sprintf(__('%s quantity', 'woocommerce'), wp_strip_all_tags($product_name))
		: '';
?>
	<div class="quantity">
		<label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>">
			<?php echo esc_html($labelledby); ?>
		</label>
		<div class="qty-stepper">
			<button
				type="button"
				class="qty-stepper__btn qty-stepper__btn--minus"
				aria-label="<?php esc_attr_e('Zmniejsz ilość', 'grofi'); ?>"
			>
				<img src="<?php echo get_template_directory_uri(); ?>/_dev/assets/icons/minus.svg" alt="Minus">
			</button>
			<input
				type="number"
				id="<?php echo esc_attr($input_id); ?>"
				class="input-text qty text"
				name="<?php echo esc_attr($input_name); ?>"
				value="<?php echo esc_attr($input_value); ?>"
				aria-label="<?php echo esc_attr($labelledby); ?>"
				size="4"
				min="<?php echo esc_attr($min_value); ?>"
				max="<?php echo esc_attr($max_value); ?>"
				step="<?php echo esc_attr($step); ?>"
				placeholder="<?php echo esc_attr($placeholder); ?>"
				inputmode="numeric"
				autocomplete="<?php echo esc_attr($autocomplete); ?>"
				<?php echo $readonly ? 'readonly' : ''; ?>
			/>
			<button
				type="button"
				class="qty-stepper__btn qty-stepper__btn--plus"
				aria-label="<?php esc_attr_e('Zwiększ ilość', 'grofi'); ?>"
			>
				<img src="<?php echo get_template_directory_uri(); ?>/_dev/assets/icons/plus.svg" alt="Plus">
			</button>
		</div>
	</div>
<?php endif; ?>
