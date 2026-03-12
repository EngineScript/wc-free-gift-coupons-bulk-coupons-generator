/**
 * Free Gift Coupons Bulk Generator — Admin JavaScript.
 *
 * Modern ESNext code. WordPress 6.5+ targets browsers with full ES6+ support.
 * All user-facing strings are sourced from `fgcbg_i18n` (wp_localize_script).
 *
 * @since   1.6.0
 * @package FreeGiftCouponsBulkGenerator
 */

if ( typeof window !== 'undefined' && typeof window.jQuery !== 'undefined' ) {
	window.jQuery( document ).ready( ( $ ) => {
		'use strict';

		/** Localized strings injected by wp_localize_script(). */
		const i18n = window.fgcbg_i18n ?? {};

		/**
		 * Main admin controller for the coupon generator form.
		 */
		const FGCBG_Admin = {

			/**
			 * Clean a string to uppercase alphanumeric only.
			 *
			 * @param {string} value     Raw input value.
			 * @param {number} maxLength Maximum output length.
			 * @return {string} Cleaned value.
			 */
			cleanAlphanumeric( value, maxLength = 10 ) {
				return value
					.replace( /[^a-zA-Z0-9]/g, '' )
					.toUpperCase()
					.slice( 0, maxLength );
			},

			/** Bootstrap all event bindings. */
			init() {
				this.bindEvents();
				this.initFormValidation();
			},

			/** Attach DOM event handlers. */
			bindEvents() {
				$( '.scg-form' ).on( 'submit', this.handleFormSubmission );
				$( '#coupon_prefix' ).on( 'input', this.formatPrefix );
				$( '#number_of_coupons' ).on( 'input', this.validateNumberInput );
			},

			/**
			 * Handle form submission — validate, confirm large batches, run AJAX generation.
			 *
			 * @param {Event} e Submit event.
			 */
			handleFormSubmission( e ) {
				e.preventDefault();

				if ( ! FGCBG_Admin.validateForm() ) {
					return;
				}

				const total = parseInt( $( '#number_of_coupons' ).val(), 10 );

				if ( total > 25 ) {
					const message = ( i18n.confirm_large_batch ?? '' ).replace( '%d', total );

					if ( ! confirm( message ) ) { // eslint-disable-line no-alert
						return;
					}
				}

				FGCBG_Admin.runBatchGeneration( total );
			},

			/**
			 * Run AJAX batch coupon generation with progress feedback.
			 *
			 * @param {number} total Total coupons to generate.
			 */
			async runBatchGeneration( total ) {
				const BATCH_SIZE = 10;
				const $form      = $( '.scg-form' );
				const $submitBtn = $form.find( '.button-primary' );
				const $progress  = $( '#fgcbg-progress' );
				const $bar       = $( '#fgcbg-progress-bar' );
				const $text      = $( '#fgcbg-progress-text' );

				$form.addClass( 'loading' );
				$submitBtn.prop( 'disabled', true );
				$progress.show();

				let generated = 0;
				let remaining = total;

				while ( remaining > 0 ) {
					const batchSize = Math.min( BATCH_SIZE, remaining );

					try {
						const response = await $.ajax( {
							url: i18n.ajax_url,
							type: 'POST',
							data: {
								action: 'fgcbg_generate_batch',
								nonce: i18n.nonce,
								product_ids: $( '#product_id' ).val(),
								batch_size: batchSize,
								coupon_prefix: $( '#coupon_prefix' ).val(),
								discount_type: $( '#discount_type' ).val(),
							},
						} );

						if ( response?.success ) {
							generated += response.data?.generated ?? 0;
						} else {
							const msg = response?.data?.message ?? ( i18n.generation_failed ?? '' );
							FGCBG_Admin.showErrorMessage( msg );
							break;
						}
					} catch {
						FGCBG_Admin.showErrorMessage( i18n.generation_failed ?? '' );
						break;
					}

					remaining -= batchSize;

					const pct = Math.round( ( generated / total ) * 100 );
					$bar.css( 'width', pct + '%' );
					$text.text(
						( i18n.generating_progress ?? '' )
							.replace( '%1$d', generated )
							.replace( '%2$d', total )
					);
				}

				$form.removeClass( 'loading' );
				$submitBtn.prop( 'disabled', false );

				if ( generated > 0 ) {
					$bar.css( 'width', '100%' );
					const msg = ( i18n.generation_complete ?? '' ).replace( '%d', generated );
					FGCBG_Admin.showSuccessMessage( msg );
				}

				if ( generated === 0 ) {
					$progress.hide();
				}
			},

			/** Sanitize the coupon prefix input on keystroke. */
			formatPrefix() {
				const $input = $( this );
				$input.val( FGCBG_Admin.cleanAlphanumeric( $input.val(), 10 ) );
			},

			/** Validate and clamp the coupon-count input field. */
			validateNumberInput() {
				const $input = $( this );
				const raw = $input.val().replace( /\D/g, '' );
				let num = parseInt( raw, 10 );

				$( '#coupon-count-warning' ).remove();

				if ( isNaN( num ) || num < 1 ) {
					$input.val( '1' );
					return;
				}

				if ( num > 100 ) {
					num = 100;
					$input.val( num );
					$input.after(
						`<span id="coupon-count-warning" class="scg-coupon-count-warning scg-coupon-count-warning--error">${ i18n.max_coupons_warning ?? '' }</span>`
					);
				} else if ( num > 50 ) {
					$input.val( num );
					$input.after(
						`<span id="coupon-count-warning" class="scg-coupon-count-warning scg-coupon-count-warning--caution">${ i18n.many_coupons_warning ?? '' }</span>`
					);
				} else {
					$input.val( num );
				}
			},

			/**
			 * Run all field validations.
			 *
			 * @return {boolean} True when valid.
			 */
			validateForm() {
				const errors = [];
				let firstInvalid = null;

				if ( ! this.validateProductSelection( errors ) && ! firstInvalid ) {
					firstInvalid = $( '#product_id' );
				}
				if ( ! this.validateCouponCount( errors ) && ! firstInvalid ) {
					firstInvalid = $( '#number_of_coupons' );
				}
				if ( ! this.validateCouponPrefix( errors ) && ! firstInvalid ) {
					firstInvalid = $( '#coupon_prefix' );
				}

				if ( errors.length > 0 ) {
					this.showErrorMessage( errors.join( '\n' ) );
					firstInvalid?.addClass( 'error' ).trigger( 'focus' );
				}

				return errors.length === 0;
			},

			/**
			 * Validate product selection.
			 *
			 * @param {string[]} errors Collector array.
			 * @return {boolean}
			 */
			validateProductSelection( errors ) {
				const ids = $( '#product_id' ).val();
				if ( ! ids || ( Array.isArray( ids ) && ids.length === 0 ) ) {
					errors.push( i18n.select_product ?? 'Please select at least one product.' );
					return false;
				}
				return true;
			},

			/**
			 * Validate coupon count field.
			 *
			 * @param {string[]} errors Collector array.
			 * @return {boolean}
			 */
			validateCouponCount( errors ) {
				const raw = $( '#number_of_coupons' ).val();
				const count = parseInt( raw, 10 );

				if ( ! raw || isNaN( count ) || count < 1 ) {
					errors.push( i18n.invalid_coupon_count ?? 'Please enter a valid number of coupons (minimum 1).' );
					return false;
				}
				if ( count > 100 ) {
					errors.push( i18n.max_coupon_count ?? 'Maximum number of coupons is 100.' );
					return false;
				}
				return true;
			},

			/**
			 * Validate coupon prefix field.
			 *
			 * @param {string[]} errors Collector array.
			 * @return {boolean}
			 */
			validateCouponPrefix( errors ) {
				const prefix = $( '#coupon_prefix' ).val();
				if ( prefix && prefix.length > 10 ) {
					errors.push( i18n.prefix_too_long ?? 'Coupon prefix must be 10 characters or less.' );
					return false;
				}
				return true;
			},

			/** Wire up real-time error-class removal on focus/input. */
			initFormValidation() {
				$( '#product_id, #number_of_coupons, #coupon_prefix' ).on(
					'focus input change',
					function () {
						$( this ).removeClass( 'error' );
					}
				);
			},

			/**
			 * Display an error notice above the form.
			 *
			 * @param {string} message Error text.
			 */
			showErrorMessage( message ) {
				$( '.scg-error-message' ).remove();

				message = String( message ).slice( 0, 500 );

				const $el = $( '<div class="notice notice-error scg-error-message"><p></p></div>' );
				$el.find( 'p' ).text( message );
				$( '.scg-form' ).before( $el );

				const offset = $el.offset();
				if ( offset?.top ) {
					$( 'html, body' ).animate( { scrollTop: Math.max( 0, offset.top - 50 ) }, 300 );
				}

				setTimeout( () => $el.fadeOut( 400, () => $el.remove() ), 5000 );
			},

			/**
			 * Display a success notice above the form.
			 *
			 * @param {string} message Success text.
			 */
			showSuccessMessage( message ) {
				message = String( message ).slice( 0, 500 );

				const $el = $( '<div class="notice notice-success is-dismissible"><p></p></div>' );
				$el.find( 'p' ).text( message );
				$( '.scg-form' ).before( $el );

				const offset = $el.offset();
				if ( offset?.top ) {
					$( 'html, body' ).animate( { scrollTop: Math.max( 0, offset.top - 50 ) }, 300 );
				}
			},
		};

		// Boot.
		FGCBG_Admin.init();

		// Warn on navigating away during generation.
		$( window ).on( 'beforeunload', () => {
			if ( $( '.scg-form' ).hasClass( 'loading' ) ) {
				return i18n.generation_in_progress ?? '';
			}
		} );

		// Reset loading state on fresh page load (back-button / refresh edge case).
		$( '.scg-form' ).removeClass( 'loading' );
		$( '.button-primary' ).prop( 'disabled', false );
	} );
}