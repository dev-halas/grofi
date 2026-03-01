<!DOCTYPE html>
<html <?php language_attributes();?>>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php if(is_search()):?>
			<meta name="robots" content="noindex, nofollow" />
		<?php endif;?>
		
		<?php wp_head() ?>
		<title><?php echo bloginfo('name'); echo ' - '; bloginfo('description');?></title> 
		<meta charset="<?php bloginfo('charset')?>" />
	</head>
	

	<body <?php body_class()?>>
		<header class="header">
			
      <div class="headerInner container">
				<div class="headerTop">
          <div class="headerLogo">
            <a href="<?php echo esc_url(home_url('/')) ?>">
              <img src="<?php echo THEME_URL; ?>/_dev/assets/logo.svg" alt="Logo">
            </a>
          </div>
          <div class="headerSearch">
            <div class="headerSearch-item">
              <?php echo do_shortcode('[fibosearch]'); ?>
            </div>
            
            <button class="hamburger" aria-label="Menu" aria-expanded="false">
              <svg viewBox="0 0 32 32">
                <path
                  class="line line-top-bottom"
                  d="M27 10 13 10C10.8 10 9 8.2 9 6 9 3.5 10.8 2 13 2 15.2 2 17 3.8 17 6L17 26C17 28.2 18.8 30 21 30 23.2 30 25 28.2 25 26 25 23.8 23.2 22 21 22L7 22"
                ></path>
                <path class="line" d="M7 16 27 16"></path>
              </svg>
            </button>

          </div>
          <div class="headerContact">
            <a href="tel:">
              <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/phone.svg" alt="Phone">
              +48 666 666 666
            </a>
            <a href="mailto:">
              <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/mail.svg" alt="Email">
              kontakt@grofi.pl
            </a>
          </div>
          <div class="headerShop">
            <a href="<?php echo function_exists('wc_get_account_endpoint_url') ? esc_url( wc_get_account_endpoint_url('dashboard') ) : '#'; ?>"
               class="userAccount"
               aria-label="<?php esc_attr_e('Moje konto', 'grofi'); ?>">
              <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/user.svg"
                   alt="<?php esc_attr_e('Konto użytkownika', 'grofi'); ?>">
            </a>

            <?php if ( function_exists('grofi_cart_button') ) : ?>
              <?php grofi_cart_button(); ?>
            <?php else : ?>
              <a href="#" class="cartButton" aria-label="<?php esc_attr_e('Koszyk', 'grofi'); ?>">
                <div class="cartIcon">
                  <span class="cartCount"></span>
                  <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="currentColor" aria-hidden="true">
                    <path d="M8.81543 19.6338C10.4723 19.634 11.8203 20.9818 11.8203 22.6387C11.8203 24.2956 10.4723 25.6434 8.81543 25.6436C7.15839 25.6436 5.80957 24.2957 5.80957 22.6387C5.80963 20.9817 7.15843 19.6338 8.81543 19.6338ZM20.8359 19.6338C22.4928 19.6339 23.8408 20.9818 23.8408 22.6387C23.8408 24.2956 22.4929 25.6434 20.8359 25.6436C19.1789 25.6436 17.8311 24.2957 17.8311 22.6387C17.8311 20.9817 19.1789 19.6338 20.8359 19.6338ZM8.81543 21.6367C8.26312 21.6367 7.81354 22.0864 7.81348 22.6387C7.81348 23.191 8.26308 23.6406 8.81543 23.6406C9.36763 23.6405 9.81641 23.1909 9.81641 22.6387C9.81635 22.0865 9.3676 21.6369 8.81543 21.6367ZM20.8359 21.6367C20.2836 21.6367 19.834 22.0864 19.834 22.6387C19.834 23.191 20.2836 23.6406 20.8359 23.6406C21.3882 23.6405 21.8379 23.1909 21.8379 22.6387C21.8378 22.0864 21.3881 21.6368 20.8359 21.6367ZM2.3877 0C3.79517 0.000214296 5.03027 0.999714 5.3252 2.37598C5.32698 2.38433 5.32946 2.39297 5.33105 2.40137L5.59473 3.80664H24.6426C25.259 3.80684 25.7317 4.36045 25.6299 4.97266L23.9062 15.3193C23.6638 16.7744 22.4165 17.8311 20.9414 17.8311H8.67969C7.23039 17.8311 5.98775 16.7963 5.72461 15.3711L3.36426 2.78516C3.26197 2.33188 2.85316 2.00412 2.3877 2.00391H1.00195C0.448715 2.00391 1.74075e-05 1.55519 0 1.00195C0 0.448705 0.448705 0 1.00195 0H2.3877ZM7.69434 15.0049C7.78272 15.4826 8.19667 15.8271 8.67969 15.8271H20.9414C21.4331 15.8271 21.8488 15.4752 21.9297 14.9902L23.46 5.81055H5.96973L7.69434 15.0049Z"/>
                  </svg>
                </div>
                <span class="cartValue">0,00 zł</span>
              </a>
            <?php endif; ?>
          </div>
				</div>
        <nav class="headerNav">
          <button class="hamburger" aria-label="Menu" aria-expanded="false">
            <svg viewBox="0 0 32 32">
              <path
                class="line line-top-bottom"
                d="M27 10 13 10C10.8 10 9 8.2 9 6 9 3.5 10.8 2 13 2 15.2 2 17 3.8 17 6L17 26C17 28.2 18.8 30 21 30 23.2 30 25 28.2 25 26 25 23.8 23.2 22 21 22L7 22"
              ></path>
              <path class="line" d="M7 16 27 16"></path>
            </svg>
          </button>
          <div class="megaMenu" id="megaMenu">
            <?php wp_nav_menu([
              'theme_location' => 'main_nav',
              'container'      => false,
              'menu_class'     => 'mm-topbar',
              'walker'         => new Mega_Menu_Walker(),
            ]); ?>
          </div>
          <a href="#" class="ctaPipes">Rurydowody.pl</a>
        </nav>
			</div>

		</header>

    <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
      <div class="mobile-menu-header">
        <button class="mobile-menu-back" aria-label="<?php esc_attr_e('Wróć', 'grofi'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <span class="mobile-menu-title">Menu</span>
        <button class="mobile-menu-close" aria-label="<?php esc_attr_e('Zamknij menu', 'grofi'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="mobile-menu-panels" id="mobileMenuPanels">
        <?php render_mobile_menu_panels( 'main_nav' ); ?>
      </div>
      <div class="mobile-menu-footer">
        <a href="#" class="mobile-menu-footer-link">
          <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/phone.svg" alt="Phone">
          +48 666 666 666
        </a>
        <a href="#" class="mobile-menu-footer-link">
          <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/mail.svg" alt="Email">
          kontakt@grofi.pl
        </a>
      </div>
    </div>

    <div class="mobile-menu-backdrop" id="mobileMenuBackdrop"></div>

    <?php if (function_exists('grofi_minicart_content')) : ?>
    <div class="minicart" id="minicart"
         x-data
         x-bind:class="{ 'is-open': $store.cart?.open }"
         x-bind:aria-hidden="$store.cart ? !$store.cart.open : 'true'"
         role="dialog"
         aria-label="<?php esc_attr_e('Koszyk', 'grofi'); ?>">
      <div class="minicart__header">
        <h3 class="minicart__title"><?php esc_html_e('Koszyk', 'grofi'); ?></h3>
        <button class="minicart__close"
                x-on:click="$store.cart?.closeCart()"
                aria-label="<?php esc_attr_e('Zamknij koszyk', 'grofi'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <?php grofi_minicart_content(); ?>
    </div>
    <div class="minicart__backdrop" id="minicartBackdrop"
         x-data
         x-bind:class="{ 'is-visible': $store.cart?.open }"
         x-on:click="$store.cart?.closeCart()"></div>
    <?php endif; ?>


