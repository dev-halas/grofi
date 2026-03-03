import '@fontsource/inter/latin.css';

import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';

import './scss/main.scss';
import './js/header';
import './js/animations';
import './js/swiper';
import './js/cart';
import './js/product-gallery';
import './js/shop-filter';
import './js/checkout';

// Guard: don't start twice if a plugin already loaded Alpine
if ( ! window.Alpine ) {
	Alpine.plugin(Collapse);
	window.Alpine = Alpine;
	Alpine.start();
}
