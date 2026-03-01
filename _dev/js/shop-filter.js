/**
 * AJAX navigation + filtry dla archiwum produktów WooCommerce.
 *
 * Nawigacja po kategoriach:
 *  - Hover na .cat-tree__link → cichy prefetch (fetchpriority: low)
 *  - Klik → natychmiastowa zamiana DOM jeśli prefetch gotowy
 *
 * Filtry:
 *  - Checkbox marki + atrybuty WC (filter_*) → natychmiastowe przeładowanie AJAX
 *  - Cena (min/max) → po kliknięciu "Zastosuj"
 *  - Link "Wyczyść filtry" → AJAX
 *
 * Podmieniane elementy przy każdej nawigacji:
 *  .shop-layout__content · .cat-tree · #shop-filters · .woocommerce-breadcrumb
 */

(function () {
	'use strict';

	const SEL = {
		content:    '.shop-layout__content',
		catTree:    '.cat-tree',
		filters:    '#shop-filters',
		breadcrumb: '.woocommerce-breadcrumb',
	};

	/** Wersja nawigacji — odrzuca odpowiedzi zdezaktualizowanych requestów */
	let navVersion = 0;

	/**
	 * Cache: url → Promise<string|null>
	 * Maks. 120 wpisów — chroni przed nieograniczonym wzrostem RAM przy długich sesjach.
	 */
	const prefetchCache = new Map();
	const PREFETCH_MAX  = 120;
	let   prefetchTrimScheduled = false;

	/**
	 * Ostatnio zlecony prefetch URL.
	 * Deduplikuje wywołania zanim Promise zdąży trafić do cache
	 * (prefetchCache.has() jeszcze nie zwróci true gdy fetch jest w toku).
	 */
	let lastPrefetchUrl = '';

	const domParser = new DOMParser();

	// ───────────────────────────────────────────────────────────────────────────
	// Fetch z cache'owaniem promisów
	// ───────────────────────────────────────────────────────────────────────────

	function fetchPage(url, priority = 'auto') {
		if (prefetchCache.has(url)) {
			return prefetchCache.get(url);
		}

		const p = fetch(url, {
			// @ts-ignore — fetchpriority (Chrome 102+)
			priority,
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		})
			.then(r => {
				if (!r.ok) throw new Error(`HTTP ${r.status}`);
				return r.text();
			})
			.catch(() => {
				// Usuń z cache przy błędzie — kolejna próba może się udać
				prefetchCache.delete(url);
				return null;
			});

		prefetchCache.set(url, p);

		// Przytnij cache gdy przekroczono limit — usuwa najstarsze wpisy (insertion order)
		if (prefetchCache.size > PREFETCH_MAX && !prefetchTrimScheduled) {
			prefetchTrimScheduled = true;
			queueMicrotask(() => {
				for (const key of prefetchCache.keys()) {
					if (prefetchCache.size <= PREFETCH_MAX) break;
					prefetchCache.delete(key);
				}
				prefetchTrimScheduled = false;
			});
		}

		return p;
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Zamiana elementów DOM + reinicjalizacja Alpine
	// ───────────────────────────────────────────────────────────────────────────

	/**
	 * Zamienia stary element nowym z pobranego dokumentu.
	 * Niszczy drzewo Alpine przed usunięciem węzła z DOM (memory leaks),
	 * a następnie inicjalizuje je na nowym węźle.
	 */
	function swapEl(selector, doc) {
		const oldEl = document.querySelector(selector);
		const newEl = doc.querySelector(selector);

		if (!oldEl || !newEl) return;

		if (window.Alpine) Alpine.destroyTree(oldEl);
		oldEl.replaceWith(newEl);
		if (window.Alpine) Alpine.initTree(newEl);
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Główna nawigacja
	// ───────────────────────────────────────────────────────────────────────────

	async function navigate(url) {
		const version   = ++navVersion;
		const contentEl = document.querySelector(SEL.content);

		if (contentEl) contentEl.classList.add('is-loading');

		const html = await fetchPage(url);

		// Inna nawigacja zdążyła się rozpocząć — porzuć wynik i wyczyść spinner
		if (version !== navVersion) {
			if (contentEl) contentEl.classList.remove('is-loading');
			return;
		}

		if (!html) {
			// Fallback: pełne przeładowanie gdy AJAX zawiódł
			window.location.href = url;
			return;
		}

		const doc = domParser.parseFromString(html, 'text/html');

		// Treść produktów: innerHTML zamiast replaceWith — zachowuje referencję węzła
		const newContent = doc.querySelector(SEL.content);
		if (contentEl && newContent) {
			// destroyTree na starym innerHTML przed jego nadpisaniem
			if (window.Alpine) Alpine.destroyTree(contentEl);
			contentEl.innerHTML = newContent.innerHTML;
			contentEl.classList.remove('is-loading');
			// initTree na nowym innerHTML
			if (window.Alpine) Alpine.initTree(contentEl);
		}

		swapEl(SEL.catTree,    doc);
		swapEl(SEL.filters,    doc);
		swapEl(SEL.breadcrumb, doc);

		document.title = doc.title;
		history.pushState({ shopUrl: url }, doc.title, url);
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Buduje nowy URL na podstawie aktualnego + stanu formularza filtrów
	// ───────────────────────────────────────────────────────────────────────────

	function applyFilters(formEl) {
		const url = new URL(window.location.href);

		// Reset paginacji przy każdej zmianie filtrów
		url.searchParams.delete('paged');

		// ── Marki ──────────────────────────────────────────────────────────────
		const checkedBrands = [...formEl.querySelectorAll('input[name="brand"]:checked')]
			.map(cb => cb.value)
			.filter(Boolean);

		if (checkedBrands.length > 0) {
			url.searchParams.set('brand', checkedBrands.join(','));
		} else {
			url.searchParams.delete('brand');
		}

		// ── Atrybuty WooCommerce (?filter_{slug}=val1,val2) ───────────────────
		const attrNames = [
			...new Set(
				[...formEl.querySelectorAll('input[name^="filter_"]')].map(cb => cb.name)
			),
		];

		for (const name of attrNames) {
			const vals = [...formEl.querySelectorAll(`input[name="${name}"]:checked`)]
				.map(cb => cb.value)
				.filter(Boolean);

			if (vals.length > 0) {
				url.searchParams.set(name, vals.join(','));
			} else {
				url.searchParams.delete(name);
			}
		}

		// Usuń stare filter_* params nieobecne w bieżącym formularzu
		// (może wystąpić po przejściu do kategorii z innymi atrybutami)
		for (const key of [...url.searchParams.keys()]) {
			if (key.startsWith('filter_') && !attrNames.includes(key)) {
				url.searchParams.delete(key);
			}
		}

		// ── Cena ───────────────────────────────────────────────────────────────
		const minVal = formEl.querySelector('input[name="min_price"]')?.value.trim() ?? '';
		const maxVal = formEl.querySelector('input[name="max_price"]')?.value.trim() ?? '';

		if (minVal) {
			url.searchParams.set('min_price', minVal);
		} else {
			url.searchParams.delete('min_price');
		}

		if (maxVal) {
			url.searchParams.set('max_price', maxVal);
		} else {
			url.searchParams.delete('max_price');
		}

		navigate(url.toString());
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Pomocnik: sprawdzenie origin
	// ───────────────────────────────────────────────────────────────────────────

	function isSameOrigin(url) {
		try {
			return new URL(url).origin === window.location.origin;
		} catch {
			return false;
		}
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Event handlers (delegation na document)
	// ───────────────────────────────────────────────────────────────────────────

	function onDocClick(e) {
		// Linki drzewa kategorii
		const catLink = e.target.closest('.cat-tree a.cat-tree__link');
		if (catLink && isSameOrigin(catLink.href)) {
			e.preventDefault();
			navigate(catLink.href);
			return;
		}

		// Link "Wyczyść filtry"
		const resetLink = e.target.closest('.shop-filters__reset');
		if (resetLink && isSameOrigin(resetLink.href)) {
			e.preventDefault();
			navigate(resetLink.href);
			return;
		}

		// Paginacja w treści produktów
		const pageLink = e.target.closest(`${SEL.content} .woocommerce-pagination a`);
		if (pageLink && isSameOrigin(pageLink.href)) {
			e.preventDefault();
			navigate(pageLink.href);
		}
	}

	function onDocChange(e) {
		// Checkboxy marki i atrybutów WC (filter_*) → natychmiastowa nawigacja
		const checkbox = e.target.closest('#shop-filters input[type="checkbox"]');
		if (checkbox) {
			const form = checkbox.closest('form');
			if (form) applyFilters(form);
		}
	}

	function onDocSubmit(e) {
		// Formularz filtrów — przycisk "Zastosuj" (cena)
		const form = e.target.closest('#shop-filters form');
		if (form) {
			e.preventDefault();
			applyFilters(form);
		}
	}

	function onDocPointerover(e) {
		// pointerover zamiast mouseover — nowocześniejszy odpowiednik, obsługuje też touch
		const catLink = e.target.closest('.cat-tree a.cat-tree__link');
		if (!catLink || !isSameOrigin(catLink.href)) return;

		// Deduplikacja: zignoruj ten sam URL zanim Promise zdąży trafić do cache
		if (catLink.href === lastPrefetchUrl) return;

		lastPrefetchUrl = catLink.href;
		fetchPage(catLink.href, 'low');
	}

	function onPopState(e) {
		navigate(e.state?.shopUrl ?? window.location.href);
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Init
	// ───────────────────────────────────────────────────────────────────────────

	function init() {
		history.replaceState(
			{ shopUrl: window.location.href },
			document.title,
			window.location.href,
		);

		document.addEventListener('click',       onDocClick);
		document.addEventListener('change',      onDocChange);
		document.addEventListener('submit',      onDocSubmit);
		document.addEventListener('pointerover', onDocPointerover);
		window.addEventListener('popstate',      onPopState);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();