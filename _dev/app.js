import '@fontsource/inter/latin.css';
import '@fancyapps/ui/dist/fancybox/fancybox.css';

import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';

import './scss/main.scss';
import './js/header';
import './js/animations';
import './js/swiper';
import './js/cart';
// checkout, product-gallery i shop-filter ładowane warunkowo przez WordPress

// Guard: don't start twice if a plugin already loaded Alpine
if ( ! window.Alpine ) {
	Alpine.plugin(Collapse);
	window.Alpine = Alpine;
	Alpine.start();
}
