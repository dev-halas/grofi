import { Fancybox } from '@fancyapps/ui';

// ─── Stałe modułu ─────────────────────────────────────────────────────────────
const THUMB_MIN          = 50; // px – minimalna szerokość miniatury na desktopie
const THUMB_GAP_DEFAULT  = 12;  // px – fallback gdy gap nie jest zadeklarowany w CSS
const GAL_GAP_DEFAULT    = 12; // px – fallback gdy gap nie jest zadeklarowany w CSS
const THUMB_COUNT_MIN    = 5;  // minimalny count używany do obliczania rozmiaru miniatur

/** @param {number} val */
const px = ( val ) => `${ +val.toFixed( 2 ) }px`;

// ──────────────────────────────────────────────────────────────────────────────

document.addEventListener( 'alpine:init', () => {

window.Alpine.data( 'productGallery', ( images ) => ( {
	images,
	activeIndex: 0,
	_observer:   null,
	_rafId:      null,

	init() {
		this._observer = new ResizeObserver( () => {
			cancelAnimationFrame( this._rafId );
			this._rafId = requestAnimationFrame( () => this._resize() );
		} );

		this._observer.observe( this.$el );
	},

	destroy() {
		this._observer?.disconnect();
		cancelAnimationFrame( this._rafId );
		this._clearThumbStyles();
	},

	setActive( index ) {
		this.activeIndex = index;
	},

	openLightbox( startIndex ) {
		const items = this.images.map( ( { src, alt, thumb } ) => ( {
			src,
			alt,
			type:     'image',
			thumbSrc: thumb,
		} ) );

		Fancybox.show( items, {
			startIndex,
			animated:    true,
			dragToClose: true,
			Thumbs: { type: 'classic' },
			Images: { zoom: true },
		} );
	},


	_clearThumbStyles() {
		this.$el
			.querySelector( '.product-gallery__thumbs' )
			?.style.removeProperty( 'width' );

		this.$el
			.querySelectorAll( '.product-gallery__thumb' )
			.forEach( ( item ) => {
				item.style.removeProperty( 'width' );
				item.style.removeProperty( 'height' );
			} );
	},

	_resize() {
		const thumbsEl = this.$el.querySelector( '.product-gallery__thumbs' );
		const items    = [ ...this.$el.querySelectorAll( '.product-gallery__thumb' ) ];

		if ( !thumbsEl || !items.length ) return;

		const galleryW = this.$el.getBoundingClientRect().width;
		if ( !galleryW ) return;

		const thumbsStyle    = getComputedStyle( thumbsEl );
		const rootStyle      = getComputedStyle( this.$el );

		const thumbGap       = parseFloat( thumbsStyle.gap ) || THUMB_GAP_DEFAULT;
		const galGap         = parseFloat( rootStyle.gap )   || GAL_GAP_DEFAULT;

		const isRow          = thumbsStyle.flexDirection.startsWith( 'row' );

		// Jeśli miniatur jest mniej niż THUMB_COUNT_MIN, liczymy jakby było ich tyle
		const effectiveCount = Math.max( items.length, THUMB_COUNT_MIN );

		// ── Obliczenia ───────────────────────────────────────────────────────
		let size;

		if ( isRow ) {
			// Mobile: miniatury w poziomie – wypełniają pełną szerokość galerii
			size = ( galleryW - ( effectiveCount - 1 ) * thumbGap ) / effectiveCount;
		} else {
			size = ( galleryW - galGap - ( effectiveCount - 1 ) * thumbGap ) / ( effectiveCount + 1 );
			size = Math.max( THUMB_MIN, size );
		}

		// ── Faza zapisu (wszystkie layout writes razem) ──────────────────────
		if ( isRow ) {
			thumbsEl.style.removeProperty( 'width' );
		} else {
			thumbsEl.style.width = px( size );
		}

		items.forEach( ( item ) => {
			item.style.width  = px( size );
			item.style.height = px( size );
		} );
	},
} ) );

} );