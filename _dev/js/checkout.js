import Alpine from 'alpinejs';

Alpine.data( 'checkoutCoupon', () => ( {
	open: false,
	code: '',
	loading: false,
	message: '',
	isError: false,

	async apply() {
		const code = this.code.trim();
		if ( ! code ) return;

		this.loading = true;
		this.message = '';

		const url = window.wc_checkout_params
			? wc_checkout_params.wc_ajax_url.replace( '%%endpoint%%', 'apply_coupon' )
			: '?wc-ajax=apply_coupon';

		const body = new URLSearchParams( {
			coupon_code: code,
			security:    window.grofi_checkout_data?.apply_coupon_nonce ?? '',
		} );

		try {
			const res      = await fetch( url, {
				method:  'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body,
			} );
			const response = await res.text();

			this.message = response ?? '';
			this.isError = this.message.includes( 'woocommerce-error' );

			window.jQuery?.( document.body ).trigger( 'update_checkout' );

			if ( ! this.isError ) this.code = '';
		} catch {
			this.message = '<ul class="woocommerce-error"><li>Błąd połączenia. Spróbuj ponownie.</li></ul>';
			this.isError = true;
		} finally {
			this.loading = false;
		}
	},
} ) );