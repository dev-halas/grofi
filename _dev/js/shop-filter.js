/**
 * AJAX navigation + filters for WooCommerce product archive.
 *
 * Category navigation:
 *  - Hover .cat-tree__link → silent prefetch (fetchpriority: low, 200ms debounce)
 *  - Click → instant DOM swap if prefetch already resolved
 *
 * Filters:
 *  - Brand / attribute checkboxes (filter_*) → navigate immediately on change
 *  - Price (min/max) → navigate on "Apply" submit
 *  - "Clear filters" link → navigate
 *
 * Transport:
 *  X-Shop-Ajax: fragments header → server returns JSON fragments instead of full HTML (~85% less data).
 *
 * Swapped fragments on each navigation:
 *  .shop-layout__content (replaceChildren) · .cat-tree · #shop-filters · .shop-layout__title
 *
 * Scroll behavior:
 *  - Category change or pagination → scroll to top
 *  - Filter / sort change          → scroll to .shop-layout__content
 *  - popstate                      → restore saved scroll position
 */

(function () {
	'use strict';

	const SEL = {
		content: '.shop-layout__content',
		catTree: '.cat-tree',
		filters: '#shop-filters',
		title:   '.shop-layout__title',
	};

	// Incremented on every navigate() call; stale responses are dropped by version check.
	let navVersion = 0;

	/**
	 * url → Promise<object|null>
	 * Capped at 120 entries to avoid unbounded RAM growth in long sessions.
	 */
	const prefetchCache = new Map();
	const PREFETCH_MAX  = 120;
	let   prefetchTrimScheduled = false;

	// Tracks the last URL sent to fetchPage so we don't fire duplicate requests
	// before the Promise makes it into the cache.
	let lastPrefetchUrl = '';
	let prefetchTimer   = 0;

	// ───────────────────────────────────────────────────────────────────────────
	// AbortSignal helper (Safari < 16 doesn't have AbortSignal.timeout)
	// ───────────────────────────────────────────────────────────────────────────

	function timeoutSignal(ms) {
		if (typeof AbortSignal.timeout === 'function') {
			return AbortSignal.timeout(ms);
		}
		const ctrl = new AbortController();
		setTimeout(() => ctrl.abort(), ms);
		return ctrl.signal;
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Nav type classification
	// ───────────────────────────────────────────────────────────────────────────

	/**
	 * Returns the type of navigation based on the URL change:
	 *  'category' — different pathname (different category or shop root)
	 *  'page'     — same pathname, ?paged changed
	 *  'filter'   — same pathname, only filters/sort changed
	 *
	 * Used to decide scroll behavior after the DOM swap.
	 */
	function getNavType(toUrl) {
		const from = new URL(window.location.href);
		const to   = new URL(toUrl);

		if (from.pathname !== to.pathname) {
			return 'category';
		}

		if (to.searchParams.has('paged') && from.searchParams.get('paged') !== to.searchParams.get('paged')) {
			return 'page';
		}

		return 'filter';
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Fetch with promise caching
	// ───────────────────────────────────────────────────────────────────────────

	/**
	 * Fetches JSON fragments for a given URL.
	 * Returns null on any error — navigate() falls back to a full page load.
	 */
	function fetchPage(url, priority = 'auto') {
		if (prefetchCache.has(url)) {
			return prefetchCache.get(url);
		}

		const p = fetch(url, {
			// @ts-ignore — fetchpriority (Chrome 102+)
			priority,
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'X-Shop-Ajax':      'fragments',
			},
			signal: timeoutSignal(10000),
		})
			.then(r => {
				if (!r.ok) throw new Error(`HTTP ${r.status}`);
				return r.json();
			})
			.catch(() => {
				// Remove from cache on error so the next attempt retries.
				prefetchCache.delete(url);
				return null;
			});

		prefetchCache.set(url, p);

		// Trim oldest entries when over the cap (Map preserves insertion order).
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
	// HTML parsing + DOM swap + Alpine re-init
	// ───────────────────────────────────────────────────────────────────────────

	/**
	 * Parses an HTML string via an inert <template> element, returns a DocumentFragment.
	 * Scripts don't execute, images don't load, no layout reflow during parsing.
	 * This is the standard alternative to DOMParser when inserting fragments.
	 */
	function parseHtml(html) {
		const tpl = document.createElement('template');
		tpl.innerHTML = html;
		return tpl.content;
	}

	/**
	 * Replaces an existing element (matched by selector) with a new one parsed from outerHtml.
	 *
	 * Uses querySelector(selector) inside the fragment rather than firstElementChild because
	 * some template parts render multiple siblings — e.g. category-tree.php outputs
	 * <h3> + <nav class="cat-tree"> and we need the nav, not the heading.
	 *
	 * Destroys Alpine tree before removal to avoid memory leaks, re-inits on the new node.
	 */
	function swapElFromHtml(selector, outerHtml) {
		if (!outerHtml) return;

		const oldEl = document.querySelector(selector);
		if (!oldEl) return;

		const fragment = parseHtml(outerHtml.trim());
		const newEl    = fragment.querySelector(selector) ?? fragment.firstElementChild;
		if (!newEl) return;

		if (window.Alpine) Alpine.destroyTree(oldEl);
		oldEl.replaceWith(newEl);
		if (window.Alpine) Alpine.initTree(newEl);
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Main navigation
	// ───────────────────────────────────────────────────────────────────────────

	/**
	 * @param {string}  url
	 * @param {object}  [opts]
	 * @param {boolean} [opts.pushState=true] — pass false on popstate to avoid duplicate history entries
	 */
	async function navigate(url, { pushState = true } = {}) {
		const version = ++navVersion;
		const navType = getNavType(url);

		// Clear so the navigated-to URL can be prefetched again if needed.
		lastPrefetchUrl = '';

		const contentEl = document.querySelector(SEL.content);
		const filtersEl = document.querySelector(SEL.filters);
		const catTreeEl = document.querySelector(SEL.catTree);

		contentEl?.classList.add('is-loading');
		contentEl?.setAttribute('aria-busy', 'true');
		filtersEl?.classList.add('is-loading');
		catTreeEl?.classList.add('is-loading');

		try {
			const data = await fetchPage(url, 'high');

			// A newer navigate() call started — drop this result.
			// Don't touch spinners; the newer call owns them.
			if (version !== navVersion) return;

			if (!data) {
				// Server doesn't support fragments or request failed — hard navigate.
				window.location.href = url;
				return;
			}

			// replaceChildren keeps the wrapper node reference intact,
			// so event delegation on document keeps working without rebinding.
			if (contentEl && data.content != null) {
				const fragment = parseHtml(data.content);
				if (window.Alpine) Alpine.destroyTree(contentEl);
				contentEl.replaceChildren(...Array.from(fragment.childNodes));
				if (window.Alpine) Alpine.initTree(contentEl);
			}

			swapElFromHtml(SEL.catTree, data.cat_tree);
			swapElFromHtml(SEL.filters, data.filters);
			swapElFromHtml(SEL.title,   data.title);

			const pageTitle = data.page_title ?? document.title;
			document.title  = pageTitle;

			if (pushState) {
				if (navType === 'category' || navType === 'page') {
					history.pushState({ shopUrl: url, scrollY: 0 }, pageTitle, url);
					contentEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
				} else {
					history.pushState({ shopUrl: url, scrollY: window.scrollY }, pageTitle, url);
				}
			} else {
				history.replaceState(history.state, pageTitle, url);
			}

		} finally {
			// Only clean up spinners if this is still the latest navigation.
			// Otherwise we'd remove spinners that a newer navigate() just added.
			if (version === navVersion) {
				document.querySelector(SEL.content)?.classList.remove('is-loading');
				document.querySelector(SEL.content)?.removeAttribute('aria-busy');
				document.querySelector(SEL.filters)?.classList.remove('is-loading');
				document.querySelector(SEL.catTree)?.classList.remove('is-loading');
			}
		}
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Build URL from current location + filter form state
	// ───────────────────────────────────────────────────────────────────────────

	function applyFilters(formEl) {
		const url = new URL(window.location.href);

		// Reset pagination whenever filters change.
		url.searchParams.delete('paged');

		// Brands
		const checkedBrands = [...formEl.querySelectorAll('input[name="brand"]:checked')]
			.map(cb => cb.value)
			.filter(Boolean);

		if (checkedBrands.length > 0) {
			url.searchParams.set('brand', checkedBrands.join(','));
		} else {
			url.searchParams.delete('brand');
		}

		// WooCommerce attributes (?filter_{slug}=val1,val2)
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

		// Drop stale filter_* params not present in the current form
		// (happens when navigating between categories with different attributes).
		for (const key of [...url.searchParams.keys()]) {
			if (key.startsWith('filter_') && !attrNames.includes(key)) {
				url.searchParams.delete(key);
			}
		}

		// Price
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
	// Helpers
	// ───────────────────────────────────────────────────────────────────────────

	function isSameOrigin(url) {
		try {
			return new URL(url).origin === window.location.origin;
		} catch {
			return false;
		}
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Event handlers (delegated on document)
	// ───────────────────────────────────────────────────────────────────────────

	function onDocClick(e) {
		const catLink = e.target.closest('.cat-tree a.cat-tree__link');
		if (catLink && isSameOrigin(catLink.href)) {
			e.preventDefault();
			navigate(catLink.href);
			return;
		}

		const resetLink = e.target.closest('.shop-filters__reset');
		if (resetLink && isSameOrigin(resetLink.href)) {
			e.preventDefault();
			navigate(resetLink.href);
			return;
		}

		const pageLink = e.target.closest(`${SEL.content} .woocommerce-pagination a`);
		if (pageLink && isSameOrigin(pageLink.href)) {
			e.preventDefault();
			navigate(pageLink.href);
		}
	}

	function onDocChange(e) {
		const checkbox = e.target.closest('#shop-filters input[type="checkbox"]');
		if (checkbox) {
			const form = checkbox.closest('form');
			if (form) applyFilters(form);
			return;
		}

		// Toolbar selects (sort, per-page) — value is the target URL.
		// add_query_arg() returns a relative path so resolve it first.
		const toolbarSelect = e.target.closest('select[data-ajax-nav]');
		if (toolbarSelect && toolbarSelect.value) {
			const resolvedUrl = new URL(toolbarSelect.value, window.location.href).href;
			navigate(resolvedUrl);
		}
	}

	function onDocSubmit(e) {
		const form = e.target.closest('#shop-filters form');
		if (form) {
			e.preventDefault();
			applyFilters(form);
		}
	}

	function onDocPointerover(e) {
		// Touch devices don't hover — prefetch would fire on every tap right before click.
		if (e.pointerType === 'touch') return;

		const catLink = e.target.closest('.cat-tree a.cat-tree__link');
		if (!catLink || !isSameOrigin(catLink.href)) return;

		// Capture href immediately — the node may be removed before the timer fires.
		const url = catLink.href;
		if (url === lastPrefetchUrl) return;

		clearTimeout(prefetchTimer);
		prefetchTimer = setTimeout(() => {
			if (url === lastPrefetchUrl) return;
			lastPrefetchUrl = url;
			fetchPage(url, 'low');
		}, 200);
	}

	function onDocPointerout(e) {
		if (e.pointerType === 'touch') return;

		// Cancel scheduled prefetch if the cursor leaves before the debounce fires.
		const catLink = e.target.closest('.cat-tree a.cat-tree__link');
		if (catLink) clearTimeout(prefetchTimer);
	}

	function onPopState(e) {
		const url     = e.state?.shopUrl ?? window.location.href;
		const scrollY = e.state?.scrollY ?? 0;

		navigate(url, { pushState: false }).then(() => {
			window.scrollTo({ top: scrollY, behavior: 'instant' });
		});
	}

	// ───────────────────────────────────────────────────────────────────────────
	// Init
	// ───────────────────────────────────────────────────────────────────────────

	function initCatTreeToggle() {
		document.addEventListener('click', (e) => {
			const toggle = e.target.closest('.shop-sidebar__title--toggle');
			if (!toggle) return;

			const nav = toggle.nextElementSibling?.closest('.cat-tree');
			if (!nav) return;

			const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', String(!isExpanded));
			nav.style.display = isExpanded ? 'none' : '';
		});
	}

	function initMobileSidebarToggle() {
		const toggle = document.querySelector('.mobile-sidebar__toggle');
		if (!toggle) return;

		toggle.addEventListener('click', () => {
			const content = toggle.closest('aside')?.querySelector('.sidebar-content');
			if (!content) return;

			const isOpen = content.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', String(isOpen));
		});
	}

	function init() {
		// Store scroll position for the initial page so popstate can restore it.
		history.replaceState(
			{ shopUrl: window.location.href, scrollY: window.scrollY },
			document.title,
			window.location.href,
		);

		document.addEventListener('click',       onDocClick);
		document.addEventListener('change',      onDocChange);
		document.addEventListener('submit',      onDocSubmit);
		document.addEventListener('pointerover', onDocPointerover);
		document.addEventListener('pointerout',  onDocPointerout);
		window.addEventListener('popstate',      onPopState);

		initCatTreeToggle();
		initMobileSidebarToggle();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
