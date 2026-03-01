# Agents Instructions — Custom WordPress / WooCommerce Theme

> Ten plik zawiera instrukcje dla agentów AI (Claude, Cursor, Copilot itp.) dotyczące pracy z tym projektem. Przeczytaj go w całości przed wprowadzaniem jakichkolwiek zmian.

---

## 1. Kontekst projektu

- Własny customowy szablon WordPress z pełną obsługą WooCommerce
- Bundler: **Vite** (JS + SCSS)
- JavaScript: **Vanilla JS** jako domyślne podejście — **Alpine.js** dozwolony w kontekście WordPress/WooCommerce (szablony PHP, interakcje WC, formularze, checkout, nagłówek/nawigacja)
- **jQuery jest bezwzględnie zakazany** — nie używaj go nigdzie w projekcie, nawet jeśli WordPress go dostarcza
- Sklep o dużym wolumenie (ok. 3000 produktów, ~100k wizyt miesięcznie) — priorytetem jest **wydajność**
- Szablon klasyczny (PHP templates), nie block theme / FSE

---

## 2. Struktura katalogów

```
theme-root/
├── _dev/                         # Pliki źródłowe (nie edytuj dist/ ręcznie)
│   ├── app.js                    # Jedyny entry point Vite (importuje SCSS + moduły JS)
│   ├── assets/
│   │   ├── icons/                # SVG ikony (cart-white, facebook, instagram, mail, phone, search, user)
│   │   └── logo.svg
│   ├── js/                       # Moduły JavaScript
│   │   ├── animations.js         # Animacje (GSAP itp.)
│   │   ├── header.js             # Logika nagłówka (menu, sticky, itp.)
│   │   └── swiper.js             # Konfiguracja Swiper.js
│   └── scss/
│       ├── base/                 # Reset, typografia, zmienne, mixiny, animacje
│       │   ├── _animations.scss
│       │   ├── _core.scss
│       │   ├── _mixins.scss
│       │   ├── _reset.scss
│       │   └── _values.scss      # CSS Custom Properties (zmienne globalne)
│       ├── layout/               # Header, footer, siatka
│       │   ├── _header.scss
│       │   └── _footer.scss
│       └── main.scss             # Entry point SCSS — importuje wszystkie partiale
├── dist/                         # Pliki wyjściowe Vite (nie edytuj ręcznie)
│   ├── app.js
│   ├── main.css
│   └── fonts/
├── libs/                         # Pliki PHP helpers (dołączane przez functions.php)
│   ├── nav-menu.php              # Walker menu, logika nawigacji
│   ├── post-types.php            # Rejestracja custom post types
│   └── product-cat-icon.php     # Ikony kategorii produktów WooCommerce
├── woocommerce/                  # Nadpisania szablonów WooCommerce (1:1 z WC plugin)
├── 404.php
├── archive.php
├── footer.php
├── front-page.php
├── functions.php                 # Enqueue assets, rejestracja menu, require libs/*, filtry
├── header.php
├── index.php
├── page.php
├── search.php
├── single.php
├── vite.config.js
├── package.json
└── style.css                     # Wymagany nagłówek motywu WordPress
```

> Jeśli tworzysz nowy plik JS, umieść go w `_dev/js/` i zaimportuj w `_dev/app.js`.
> Jeśli tworzysz nowy plik SCSS, umieść go w odpowiednim podkatalogu `_dev/scss/` i zaimportuj w `_dev/scss/main.scss`.
> Nowe helpery PHP umieszczaj w `libs/` i dołączaj przez `functions.php`.

---

## 3. Bundler — Vite

### Komendy

```bash
npm run start      # tryb watch — automatyczny rebuild przy zmianach (development)
npm run dev        # Vite dev server z HMR
npm run build      # produkcyjny build do dist/
```

### Konfiguracja (vite.config.js)

- Jedyny entry point: `_dev/app.js` — importuje SCSS przez `import './scss/main.scss'`
- Output: `dist/` (root motywu)
- Pliki wyjściowe: `dist/app.js`, `dist/main.css`, `dist/fonts/`
- Brak manifestu — PHP ładuje pliki bezpośrednio przez stałe ścieżki
- W PHP: `wp_enqueue_script('theme-js', .../dist/app.js)` i `wp_enqueue_style('theme-css', .../dist/main.css)`

### Zasady

- Nie edytuj plików w `dist/` — są generowane automatycycznie
- SCSS jest importowany wewnątrz `_dev/app.js` (`import './scss/main.scss'`) — to jedyna dopuszczalna forma importu SCSS w JS
- Nowe moduły JS dodawaj w `_dev/js/` i importuj w `_dev/app.js`
- Nowe partiale SCSS dodawaj w odpowiednim podkatalogu `_dev/scss/` i importuj w `_dev/scss/main.scss`

---

## 4. JavaScript — konwencje i styl kodu

### Ogólne zasady

- **Vanilla JS** jako domyślne podejście — nie dodawaj żadnych innych frameworków JS bez wcześniejszej dyskusji
- **Alpine.js** jest dozwolony i preferowany nad jQuery wszędzie tam, gdzie potrzebna jest reaktywność lub interakcje w kontekście WordPress/WooCommerce:
  - Szablony PHP / komponenty WC (np. karty produktów, filtry, tabs)
  - Interakcje nagłówka i nawigacji (np. menu mobilne, dropdowny)
  - Formularze i checkout WooCommerce
  - Wszędzie tam, gdzie wcześniej użytoby jQuery
- **jQuery jest bezwzględnie zakazany** — nie używaj `$()`, `jQuery()` ani żadnych metod jQuery, nawet jeśli WordPress ładuje jQuery globalnie. Zastąp Alpine.js lub czystym Vanilla JS
- ES Modules (`import`/`export`) — bez CommonJS (`require`)
- Używaj `const` domyślnie, `let` gdy konieczna jest ponowna przypisanie; **nigdy `var`**
- Async/await zamiast `.then()/.catch()` dla czytelności
- Jeden moduł = jeden plik = jedna odpowiedzialność

### Alpine.js — zasady użycia

```html
<!-- ✅ Poprawnie — Alpine.js zamiast jQuery dla interakcji WC -->
<div x-data="{ open: false }">
  <button @click="open = !open">Menu</button>
  <nav x-show="open" x-transition>...</nav>
</div>

<!-- ✅ Poprawnie — dane z PHP przekazane przez x-data -->
<div x-data="productCard(<?= esc_attr( wp_json_encode( $data ) ) ?>)">
  ...
</div>
```

```js
// ✅ Rejestracja komponentu Alpine.js w _dev/js/
// _dev/js/product-card.js
document.addEventListener('alpine:init', () => {
  Alpine.data('productCard', (config) => ({
    quantity: 1,
    addToCart() { /* ... */ }
  }));
});
```

- Alpine.js ładuj przez Vite (`import 'alpinejs'` w `_dev/app.js`) — nie przez CDN
- Komponenty Alpine rejestruj w dedykowanych plikach w `_dev/js/` i importuj w `_dev/app.js`
- Nie używaj `Alpine.start()` ręcznie — Alpine inicjalizuje się automatycznie po imporcie

### Manipulacja DOM

```js
// ✅ Poprawnie
const el = document.createElement('li');
el.textContent = sanitize(userInput);
el.classList.add('nav__item');
parent.appendChild(el);

// ❌ Niedozwolone — zagrożenie XSS
element.innerHTML = `<li>${userInput}</li>`;

// ❌ Niedozwolone — jQuery
$('.nav__item').addClass('active');
$('.nav__item').on('click', handler);
```

- **`innerHTML` z dynamicznymi danymi jest bezwzględnie zakazane**
- Dozwolone: `textContent`, `createElement`, `appendChild`, `insertAdjacentElement`
- Jeśli musisz użyć `insertAdjacentHTML`, dane muszą być najpierw sanityzowane przez dedykowaną funkcję `sanitizeHTML()` z `utils/sanitize.js`

### Eventy i wydajność

- Używaj **event delegation** zamiast wielu listenerów na elementach
- Funkcje wywoływane przy scroll/resize owijaj w `debounce()` lub `throttle()` z `utils/performance.js`
- Używaj `IntersectionObserver` zamiast nasłuchiwania na scroll dla lazy loadingu
- Czyść listenery (`removeEventListener`) gdy element jest usuwany z DOM

### Nazewnictwo

| Element | Konwencja | Przykład |
|---|---|---|
| Pliki JS | `kebab-case` | `mega-menu.js` |
| Klasy | `PascalCase` | `class MegaMenu {}` |
| Funkcje/zmienne | `camelCase` | `initMegaMenu()` |
| Stałe | `UPPER_SNAKE_CASE` | `const MAX_ITEMS = 10` |
| Prywatne metody | prefix `_` | `_handleClick()` |
| Komponenty Alpine | `camelCase` | `Alpine.data('productCard', ...)` |

---

## 5. SCSS — konwencje i styl kodu

- Metodologia: **BEM** (`block__element--modifier`)
- Zmienne globalne w `_dev/scss/base/_values.scss` jako **CSS Custom Properties** (`--color-primary`)
- Mixiny w `_dev/scss/base/_mixins.scss`
- Unikaj `@extend` — preferuj mixiny lub powtórzenie klas
- Maksymalne zagnieżdżenie: **3 poziomy**
- Media queries: mobile-first (`min-width`)
- Nie używaj `!important` — jeśli potrzebujesz, rozwiąż problem przez specyficzność

```scss
// ✅ Poprawnie — BEM + CSS vars
.nav-menu {
  background: var(--color-bg);

  &__item {
    display: flex;

    &--active {
      color: var(--color-primary);
    }
  }
}

// ❌ Unikaj — za głębokie, zbyt specyficzne
.header .nav .menu li a span { ... }
```

### Nazewnictwo plików SCSS

- Partiale z prefixem `_` → `_variables.scss`, `_buttons.scss`
- Nazwa pliku = nazwa bloku BEM → komponent `.mega-menu` → plik `_mega-menu.scss`

---

## 6. PHP — konwencje i styl kodu

- Standard: **WordPress Coding Standards** (WPCS)
- Nazewnictwo funkcji: `prefix_nazwa_funkcji()` — prefix = skrót nazwy projektu (np. `grofi_`)
- Każda funkcja musi być poprzedzona komentarzem DocBlock
- `functions.php` zawiera enqueue assets, rejestrację menu, `require_once` dla plików z `libs/` oraz globalne filtry/akcje
- Nowe helpery PHP umieszczaj w `libs/` i dołączaj przez `require_once` w `functions.php`

```php
// ✅ Poprawnie
/**
 * Enqueue theme scripts and styles.
 */
function enqueue_vite_assets() {
    $theme_dist = get_template_directory_uri() . '/dist';

    wp_enqueue_script('theme-js', $theme_dist . '/app.js', [], null, true);
    wp_enqueue_style('theme-css', $theme_dist . '/main.css', [], null);
}
```

> **Ważne:** Nie dodawaj jQuery jako zależności (`wp_enqueue_script(..., ['jquery'])`). Alpine.js i Vanilla JS zastępują jQuery w całym projekcie.

### Bezpieczeństwo PHP

- Zawsze escapuj output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Zawsze sanityzuj input: `sanitize_text_field()`, `absint()`, `wp_unslash()`
- Nonces dla wszystkich formularzy i requestów AJAX
- Sprawdzaj uprawnienia przed operacjami: `current_user_can()`

---

## 7. WooCommerce — zasady

### Szablony

- Nadpisania szablonów WC umieszczaj w katalogu `woocommerce/` (root motywu), odwzorowując ścieżki z pluginu WC
- Przed nadpisaniem sprawdź, czy nie wystarczy hook — **preferuj hooki nad nadpisywaniem szablonów**
- Przy aktualizacji WC sprawdzaj, czy nadpisane szablony nie są przestarzałe (`WooCommerce > Status > System Status`)
- W szablonach WC używaj Alpine.js dla interakcji (np. ilość produktu, dodaj do koszyka, filtry) — **nie jQuery**

### Hooki i filtry

- Wszystkie `add_action()` i `add_filter()` związane z WC trzymaj w `libs/woocommerce-hooks.php` (utwórz jeśli nie istnieje i dołącz w `functions.php`)
- Nie mieszaj hooków WC z ogólnymi hookami WordPress w jednym pliku
- Dokumentuj każdego hooka — co robi i dlaczego jest potrzebny

```php
/**
 * Remove default WooCommerce breadcrumbs — replaced by custom component.
 */
add_action( 'init', function() {
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
} );
```

### AJAX w WooCommerce

- Własne endpointy AJAX rejestruj przez `wp_ajax_` / `wp_ajax_nopriv_` w `libs/woocommerce-ajax.php` (utwórz jeśli nie istnieje i dołącz w `functions.php`)
- Po stronie JS wywołuj AJAX przez natywne `fetch()` — **nie `$.ajax()` ani `$.post()`**
- Zawsze weryfikuj nonce i uprawnienia
- Odpowiedź zawsze przez `wp_send_json_success()` / `wp_send_json_error()` + `wp_die()`

```js
// ✅ Poprawnie — fetch zamiast $.ajax()
const response = await fetch(grofi_ajax.url, {
  method: 'POST',
  body: new URLSearchParams({
    action: 'grofi_add_to_cart',
    nonce: grofi_ajax.nonce,
    product_id: productId,
  }),
});
const data = await response.json();

// ❌ Niedozwolone — jQuery AJAX
$.post(grofi_ajax.url, { action: '...' }, function(data) { ... });
```

### Wydajność WooCommerce

- Nie uruchamiaj ciężkich zapytań w pętlach produktów — używaj `posts_clauses` lub cache
- Używaj `wc_get_product()` zamiast `get_post()` dla produktów
- Transients (`set_transient` / `get_transient`) dla kosztownych danych

---

## 8. Zasady bezpieczeństwa (podsumowanie)

| Obszar | Zasada |
|---|---|
| JS — DOM | Zakaz `innerHTML` z dynamicznymi danymi |
| JS — dane | Sanityzacja przez `sanitizeHTML()` przed wstawieniem do DOM |
| JS — biblioteki | Zakaz jQuery; Alpine.js lub Vanilla JS |
| PHP — output | Zawsze `esc_*` lub `wp_kses_*` |
| PHP — input | Zawsze `sanitize_*` + `wp_unslash()` |
| PHP — AJAX | Weryfikacja nonce + `current_user_can()` |
| PHP — SQL | Zawsze `$wpdb->prepare()` dla własnych zapytań |
| Pliki | Walidacja typów przy uploaderach, nigdy `eval()` |

---

## 9. Czego NIE robić

- Nie edytuj plików w `dist/` — są generowane przez Vite
- Nie używaj `innerHTML` z niezaufanymi danymi
- Nie używaj `var` w JavaScript
- **Nie używaj jQuery** — ani `$()`, ani `jQuery()`, ani żadnych metod jQuery; zastąp Alpine.js lub czystym Vanilla JS
- Nie dodawaj jQuery jako zależności w `wp_enqueue_script()`
- Nie instaluj innych frameworków JS bez konsultacji
- Nie nadpisuj szablonów WC jeśli wystarczy hook
- Nie pisz zapytań SQL bez `$wpdb->prepare()`
- Nie commituj `.env`, `node_modules/`, `dist/` (sprawdź `.gitignore`)

---

## 10. Przed wprowadzeniem zmian — checklist dla agenta

- [ ] Czy zmiana dotyczy JS? → sprawdź sekcję 4
- [ ] Czy zmiana dotyczy SCSS? → sprawdź sekcję 5
- [ ] Czy zmiana dotyczy PHP? → sprawdź sekcję 6
- [ ] Czy zmiana dotyczy WooCommerce? → sprawdź sekcję 7
- [ ] Czy nowy plik trafia do właściwego katalogu? → sprawdź sekcję 2
- [ ] Czy kod jest wolny od `innerHTML` z dynamicznymi danymi?
- [ ] Czy kod jest wolny od jQuery (`$`, `jQuery`)?
- [ ] Czy interakcje WP/WC używają Alpine.js zamiast jQuery?
- [ ] Czy wywołania AJAX używają `fetch()` zamiast `$.ajax()`?
- [ ] Czy output PHP jest escapowany?
- [ ] Czy nie modyfikujesz plików w `dist/`?