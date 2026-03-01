// ── Desktop Mega Menu (hover) ─────────────────────────────

class MegaMenu {
    static #OPEN_DELAY  = 100;
    static #CLOSE_DELAY = 200;

    constructor(selector) {
        this.nav = document.querySelector(selector);
        if (!this.nav) return;
        this.#bindEvents();
    }

    #bindEvents() {
        this.nav.querySelectorAll('.mm-item--has-children').forEach(item => {
            item.addEventListener('mouseenter', () => this.#onEnter(item));
            item.addEventListener('mouseleave', () => this.#onLeave(item));
        });
    }

    #onEnter(item) {
        this.#cancelClose(item);
        clearTimeout(item._openTimer);
        item._openTimer = setTimeout(() => this.#open(item), MegaMenu.#OPEN_DELAY);
    }

    #onLeave(item) {
        clearTimeout(item._openTimer);
        this.#scheduleClose(item);
    }

    #open(item) {
        item.parentElement
            .querySelectorAll(':scope > .mm-item--has-children.is-open')
            .forEach(s => { if (s !== item) this.#scheduleClose(s); });

        item.classList.add('is-open');
        this.#positionSubPanel(item);
    }

    // Podmenu lvl-2 i lvl-3 dostają position:fixed z wyliczonymi współrzędnymi.
    // Dzięki temu uciekają spod overflow-y:auto rodzica bez żadnego togglowania –
    // scroll w lvl-1 pozostaje stabilny, a kursor zawsze trafia w element.
    #positionSubPanel(item) {
        const dropdown = item.querySelector(':scope > .mm-dropdown');
        if (!dropdown) return;

        const parentPanel = item.closest('.mm-dropdown');
        if (!parentPanel) return; // lvl-0: lvl-1 pozycjonowany przez CSS, bez zmian

        const rect = parentPanel.getBoundingClientRect();
        dropdown.style.position  = 'fixed';
        dropdown.style.top       = rect.top    + 'px';
        dropdown.style.left      = rect.right  + 'px';
        dropdown.style.minHeight = rect.height + 'px';
        dropdown.style.maxHeight = rect.height + 'px';
    }

    #close(item) {
        item.classList.remove('is-open');
        item.querySelectorAll('.is-open').forEach(c => c.classList.remove('is-open'));

        const dropdown = item.querySelector(':scope > .mm-dropdown');
        if (dropdown) {
            dropdown.style.position  = '';
            dropdown.style.top       = '';
            dropdown.style.left      = '';
            dropdown.style.minHeight = '';
            dropdown.style.maxHeight = '';
        }
    }

    #scheduleClose(item) {
        item._closeTimer = setTimeout(() => this.#close(item), MegaMenu.#CLOSE_DELAY);
    }

    #cancelClose(item) {
        clearTimeout(item._closeTimer);
    }
}


// ── Mobile Menu (push navigation) ────────────────────────
// Panele są generowane server-side (PHP).
// JS wyłącznie przełącza klasy CSS: is-active (translateX 0) i is-prev (-100%).

class MobileMenu {
    #BREAKPOINT = 768;
    #menu       = null;
    #titleEl    = null;
    #backBtn    = null;
    #isOpen     = false;

    constructor() {
        this.#menu = document.getElementById('mobileMenu');
        if (!this.#menu) return;

        this.#titleEl = this.#menu.querySelector('.mobile-menu-title');
        this.#backBtn = this.#menu.querySelector('.mobile-menu-back');

        this.#updateHeader();

        // Event delegation — jeden listener na całe menu
        this.#menu.addEventListener('click', e => {
            const btn = e.target.closest('.mobile-menu-arrow[data-target]');
            if (btn) this.#push(btn.dataset.target);
        });

        this.#backBtn.addEventListener('click', () => this.#pop());
        this.#menu.querySelector('.mobile-menu-close').addEventListener('click', () => this.#close());
        document.getElementById('mobileMenuBackdrop')?.addEventListener('click', () => this.#close());

        document.querySelector('.hamburger')?.addEventListener('click', () => {
            if (window.innerWidth > this.#BREAKPOINT) return;
            this.#isOpen ? this.#close() : this.#open();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > this.#BREAKPOINT && this.#isOpen) this.#close();
        });
    }

    // ── Helpers ───────────────────────────────────────────

    #active() {
        return this.#menu.querySelector('.mobile-menu-panel.is-active');
    }

    #updateHeader() {
        const active = this.#active();
        this.#titleEl.textContent = active?.dataset.title ?? 'Menu';
        this.#backBtn.classList.toggle('is-visible', !!active?.dataset.parent);
    }

    // ── Navigation ────────────────────────────────────────

    #push(targetId) {
        const current = this.#active();
        const next    = this.#menu.querySelector(`[data-id="${targetId}"]`);
        if (!current || !next) return;

        current.classList.remove('is-active');
        current.classList.add('is-prev');
        next.classList.add('is-active');
        this.#updateHeader();
    }

    #pop() {
        const current  = this.#active();
        const parentId = current?.dataset.parent;
        if (!parentId) return;

        const prev = this.#menu.querySelector(`[data-id="${parentId}"]`);
        current.classList.remove('is-active');
        prev?.classList.remove('is-prev');
        prev?.classList.add('is-active');
        this.#updateHeader();
    }

    // ── Open / Close ──────────────────────────────────────

    #open() {
        this.#isOpen = true;
        this.#menu.classList.add('is-open');
        this.#menu.setAttribute('aria-hidden', 'false');
        document.getElementById('mobileMenuBackdrop')?.classList.add('is-visible');
        document.body.classList.add('mobile-menu-open');
        document.querySelector('header')?.classList.add('is-open');
    }

    #close() {
        this.#isOpen = false;
        this.#menu.classList.remove('is-open');
        this.#menu.setAttribute('aria-hidden', 'true');
        document.getElementById('mobileMenuBackdrop')?.classList.remove('is-visible');
        document.body.classList.remove('mobile-menu-open');
        document.querySelector('header')?.classList.remove('is-open');

        setTimeout(() => this.#resetToRoot(), 370);
    }

    #resetToRoot() {
        this.#menu.querySelectorAll('.mobile-menu-panel').forEach(p =>
            p.classList.remove('is-active', 'is-prev')
        );
        this.#menu.querySelector('[data-id="root"]')?.classList.add('is-active');
        this.#updateHeader();
    }
}


// ── Init ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    new MegaMenu('#megaMenu');
    new MobileMenu();
});
