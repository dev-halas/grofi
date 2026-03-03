<?php
defined('ABSPATH') || exit;
?>
<div class="woocommerce-no-products-found">
  <img src="<?php echo get_template_directory_uri(); ?>/_dev/assets/logo.svg" alt="Logo Grofi">
	<h2><?php esc_html_e('przepraszamy ale...', 'grofi'); ?></h2>
	<p><?php esc_html_e('niestety nie znaleźliśmy produktów pasujących do Twojego wyszukiwania.', 'grofi'); ?></p>
  <a href="<?php echo home_url(); ?>/sklep/" class="button button--orange"><?php esc_html_e('Wróć do sklepu', 'grofi'); ?></a>
</div>