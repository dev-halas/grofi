/**
 * Cart – Alpine store + MiniCart. Zero jQuery.
 *
 * WooCommerce wywołuje eventy przez $.fn.trigger() (jQuery-only).
 * Patchujemy jQuery.fn.trigger jednorazowo przy starcie, żeby bridgować
 * te eventy jako natywne CustomEventy – od tej chwili słuchamy wyłącznie
 * przez addEventListener.
 *
 * wc-cart-fragments jest odrejestrowany (libs/woocommerce.php),
 * fragmenty odświeżamy sami przez fetch (fetchFragments).
 */

import Alpine from 'alpinejs';


// ── Stałe ──────────────────────────────────────────────────

const WC_AJAX_BASE   = '?wc-ajax=';
const BUMP_CLASS     = 'cartButton--bump';
const BUMP_DURATION  = 400;
const REMOVING_CLASS = 'minicart__item--removing';

const BRIDGED_EVENTS = new Set([
	'added_to_cart',
	'wc_fragments_refreshed',
	'updated_cart_totals',
	'removed_from_cart',
	'wc_cart_emptied',
]);


// ── Alpine store ───────────────────────────────────────────

Alpine.store('cart', {
	open: false,

	toggle()    { this.open = !this.open; },
	openCart()  { this.open = true;  },
	closeCart() { this.open = false; },

	init() {
		Alpine.effect(() => {
			document.body.classList.toggle('minicart-is-open', this.open);
		});
	},
});

// Alpine.start();


// ── Bridge: jQuery.fn.trigger → natywny CustomEvent ───────
//
// WooCommerce woła $(document.body).trigger('added_to_cart', [...]).
// Patch sprawia, że każdy .trigger() dla BRIDGED_EVENTS dispatchuje też
// natywny CustomEvent – dzięki temu słuchamy ich przez addEventListener
// bez żadnego jQuery w naszym kodzie. Patch wykonywany jednorazowo.

(function bridgeJQueryTriggers() {
	function patch($) {
		const _trigger = $.fn.trigger;

		$.fn.trigger = function (type, extraArgs) {
			if (typeof type === 'string' && BRIDGED_EVENTS.has(type) && this[0]) {
				this[0].dispatchEvent(
					new CustomEvent(type, { bubbles: true, detail: extraArgs ?? [] }),
				);
			}
			return _trigger.apply(this, arguments);
		};
	}

	if (window.jQuery) {
		patch(window.jQuery);
	} else {
		document.addEventListener('DOMContentLoaded', () => {
			if (window.jQuery) patch(window.jQuery);
		}, { once: true });
	}
}());


// ── Narzędzia ──────────────────────────────────────────────

/**
 * Parsuje ciąg HTML i zwraca pierwszy element potomny (bez innerHTML).
 * DOMParser nie wykonuje skryptów zawartych w przekazanym HTML.
 *
 * @param {string} html
 * @returns {Element|null}
 */
function parseHTML(html) {
	return new DOMParser()
		.parseFromString(html, 'text/html')
		.body.firstElementChild;
}

/**
 * Podmienia węzły DOM zgodnie z mapą fragmentów WooCommerce.
 * Każdy klucz to selektor CSS, wartość – ciąg HTML zastępczego elementu.
 * Fragment parsowany raz; przy wielu trafach używany jest klon.
 *
 * @param {Record<string, string>} fragments
 */
function applyFragments(fragments) {
	if (!fragments || typeof fragments !== 'object') return;

	for (const [selector, html] of Object.entries(fragments)) {
		const replacement = parseHTML(html);
		if (!replacement) continue;

		document.querySelectorAll(selector).forEach((el, index) => {
			el.replaceWith(index === 0 ? replacement : replacement.cloneNode(true));
		});
	}
}

/**
 * Pobiera odświeżone fragmenty z WooCommerce i aplikuje je w DOM.
 * Zastępuje wc-cart-fragments (odrejestrowane w libs/woocommerce.php).
 */
async function fetchFragments() {
	try {
		const res = await fetch(`${WC_AJAX_BASE}get_refreshed_fragments`, {
			credentials: 'same-origin',
		});
		if (!res.ok) throw new Error(`HTTP ${res.status}`);

		const data = await res.json();
		if (data.success && data.fragments) applyFragments(data.fragments);
	} catch {
		// silent fail – błąd sieci nie przerywa UX
	}
}


// ── Animacja bump na przycisku koszyka ─────────────────────

function bumpCartButton() {
	const btn = document.querySelector('a.cartButton');
	if (!btn) return;

	btn.classList.remove(BUMP_CLASS);
	// Double rAF: wymuś repaint pomiędzy remove a add, żeby animacja odpalała się na nowo
	requestAnimationFrame(() => {
		requestAnimationFrame(() => {
			btn.classList.add(BUMP_CLASS);
			setTimeout(() => btn.classList.remove(BUMP_CLASS), BUMP_DURATION);
		});
	});
}


// ── Handler: produkt dodany do koszyka ─────────────────────

function onAddedToCart() {
	bumpCartButton();
	Alpine.store('cart').openCart();
	fetchFragments();
}


// ── MiniCart – toggle i klawiatura ─────────────────────────
//
// Delegacja na document – działa po każdej wymianie a.cartButton
// przez fetchFragments. Resztę (backdrop, aria, is-open) obsługuje
// Alpine przez x-on:click / x-bind w header.php.

document.addEventListener('click', (e) => {
	if (!e.target.closest('a.cartButton')) return;
	e.preventDefault();
	Alpine.store('cart').toggle();
});

document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape') Alpine.store('cart').closeCart();
});


// ── Usuwanie produktu z minicart ───────────────────────────
//
// Jeden POST → ?wc-ajax=grofi_remove_cart_item zwraca success + fragmenty.
// Optimistyczny UI: dodajemy klasę REMOVING_CLASS przed requestem.
// Błąd = rollback (usunięcie klasy), zero przeładowania strony.

document.addEventListener('click', async (e) => {
	const btn = e.target.closest('.minicart__item-remove');
	if (!btn) return;
	e.preventDefault();

	const item = btn.closest('.minicart__item');
	item?.classList.add(REMOVING_CLASS);

	const hrefUrl     = new URL(btn.href, location.origin);
	const nonce       = hrefUrl.searchParams.get('_wpnonce') ?? '';
	const cartItemKey = btn.dataset.itemKey
		?? hrefUrl.searchParams.get('remove_item')
		?? '';

	try {
		const res = await fetch(`${WC_AJAX_BASE}grofi_remove_cart_item`, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        new URLSearchParams({ cart_item_key: cartItemKey, nonce }),
		});

		if (!res.ok) throw new Error(`HTTP ${res.status}`);

		const data = await res.json();
		if (!data.success) throw new Error(data.data?.message ?? 'Remove failed');

		applyFragments(data.data.fragments);

	} catch {
		item?.classList.remove(REMOVING_CLASS);
	}
});


// ── Stepper ilości (− / +) ────────────────────────────────
//
// HTML steppera generuje PHP (woocommerce/global/quantity-input.php).
// Obsługujemy wyłącznie logikę zmiany wartości inputa.

document.addEventListener('click', (e) => {
	const btn = e.target.closest('.qty-stepper__btn');
	if (!btn) return;

	const input = btn.closest('.qty-stepper')?.querySelector('input.qty');
	if (!input) return;

	const step = parseFloat(input.step) || 1;
	const min  = input.min !== '' ? parseFloat(input.min) : 1;
	const max  = input.max !== '' ? parseFloat(input.max) : Infinity;
	const curr = parseFloat(input.value) || 1;

	const next = btn.classList.contains('qty-stepper__btn--minus')
		? Math.max(min, curr - step)
		: Math.min(max, curr + step);

	input.value = next;
	input.dispatchEvent(new Event('change', { bubbles: true }));
});


// ── Dodawanie do koszyka (single-product) ──────────────────
//
// Przechwytujemy submit form.cart, wysyłamy FormData przez fetch
// do ?wc-ajax=add_to_cart. Obsługuje:
//   • produkty proste   – [name="add-to-cart"] jako product_id
//   • produkty zmienne  – [name="variation_id"] jako product_id
//   • fallback          – normalne submitowanie gdy fetch się nie powiedzie

document.addEventListener('submit', async (e) => {
	const form = e.target.closest('form.cart');
	if (!form || !document.body.classList.contains('single-product')) return;

	e.preventDefault();

	const submitBtn = form.querySelector('[type="submit"]');
	submitBtn?.classList.add('loading');

	const formData    = new FormData(form);
	const variationId = formData.get('variation_id');
	const productId   = variationId && variationId !== '0'
		? variationId
		: (form.querySelector('[name="add-to-cart"]')?.value ?? formData.get('add-to-cart'));

	formData.set('product_id', productId);

	try {
		const res = await fetch(`${WC_AJAX_BASE}add_to_cart`, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		});

		if (!res.ok) throw new Error(`HTTP ${res.status}`);

		const data = await res.json();

		if (data.error) {
			window.location.href = data.product_url ?? window.location.href;
			return;
		}

		if (data.fragments) applyFragments(data.fragments);

		bumpCartButton();
		// Alpine.store('cart').openCart(); // odkomentuj, by otwierać minicart po dodaniu

	} catch {
		form.submit();
	} finally {
		submitBtn?.classList.remove('loading');
	}
});


// ── Eventy WooCommerce ─────────────────────────────────────
//
// added_to_cart          – klasyczny Ajax add-to-cart (bridged z jQuery)
// wc-blocks_added_to_cart – natywny CustomEvent z WC Blocks
// Pozostałe – aktualizacja koszyka na stronie koszyka (bridge z jQuery)

document.body.addEventListener('added_to_cart',          onAddedToCart);
document.body.addEventListener('wc-blocks_added_to_cart', onAddedToCart);

document.body.addEventListener('updated_cart_totals', fetchFragments);
document.body.addEventListener('removed_from_cart',   fetchFragments);
document.body.addEventListener('wc_cart_emptied',     fetchFragments);
