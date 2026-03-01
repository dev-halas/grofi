<?php
defined('ABSPATH') || exit;

if (empty($crumbs) || !is_array($crumbs)) {
	return;
}
?>
<nav class="woocommerce-breadcrumb" aria-label="<?php esc_attr_e('Nawigacja okruszkowa', 'grofi'); ?>">
	<?php foreach ($crumbs as $key => $crumb) : ?>
		<?php if (!empty($crumb[1]) && count($crumbs) !== $key + 1) : ?>
			<a href="<?php echo esc_url($crumb[1]); ?>"><?php echo esc_html($crumb[0]); ?></a>
			<span class="breadcrumb-sep" aria-hidden="true">/</span>
		<?php else : ?>
			<span class="breadcrumb-current" aria-current="page"><?php echo esc_html($crumb[0]); ?></span>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
