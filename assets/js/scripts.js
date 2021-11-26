/**
 * Frontend scripts.
 *
 * @package Ghop/Assets/JS
 * @since   1.0.0
 */

/* global ghop_scripts_params */
( function( $, params ) {

	'use strict';

	var GhopDoorButton = function( element, options ) {
		var defaults = {
			'buttonText': 'Opening&hellip;',
			'phoneVerified': false
		};

		this.options           = $.extend( true, {}, defaults, options );
		this.$element          = $( element );
		this.verifyPhoneDialog = undefined;

		this.bindEvents();
	};

	GhopDoorButton.prototype = {

		bindEvents: function() {
			var that = this;

			this.$element.on( 'click', function( event ) {
				event.preventDefault();

				if ( that.options.phoneVerified ) {
					that.openDoor();
				} else {
					that.verifyPhone();
				}
			} );

			$( document.body ).on( 'verifyPhone.contentUpdated', this.initPhoneField );

			$( document.body ).on( 'submit', '.ghop-dialog #ghop-verify-phone-form', function( event ) {
				event.preventDefault();

				that.verifyPhoneDialog.showLoading();
				that.verifyPhone({
					step: $( this ).find( 'input[name="step"]' ).val(),
					phone: $( this ).find( 'input[name="phone"]' ).val(),
					code: $( this ).find( 'input[name="code"]' ).val()
				} );

				return false;
			} );

			$( document.body ).on( 'click', '.ghop-dialog .phone-verified-button', function( event ) {
				event.preventDefault();

				that.verifyPhoneDialog.close();
			} );
		},

		openDoor: function() {
			var that         = this,
				originalText = this.$element.text();

			this.$element.text( this.options.buttonText )
			this.$element.parent().find( '.ghop-open-door-notice' ).remove()

			$.post({
				url: params.ajax_url,
				dataType: 'json',
				data: {
					action: 'ghop_open_door',
					nonce: params.nonces.open_door
				},
				success: function( result ) {
					var $notice = $( '<div class="ghop-open-door-notice"></div>' )

					$notice.html( result.data.message )
						.removeClass( 'success error' )
						.addClass( result.success ? 'success' : 'error' );

					// Restore the original text.
					that.$element.text( originalText );

					that.$element.after( $notice );
				}
			});
		},

		verifyPhone: function( args ) {
			var that = this,
				data = $.extend( {}, args || {}, {
					action: 'ghop_verify_phone',
					nonce: params.nonces.verify_phone
				} );

			if ( ! this.verifyPhoneDialog ) {
				this.verifyPhoneDialog = $.dialog( {
					columnClass: 'ghop-dialog',
					title: '',
					width: 'auto',
					content: ''
				} );
			} else {
				this.verifyPhoneDialog.open();
				this.verifyPhoneDialog.showLoading();
			}

			$.post( {
				url: params.ajax_url,
				dataType: 'json',
				data: data
			} )
			.done( function( response ) {
				that.verifyPhoneDialog.setContent( response.data.content );
				$( document.body ).trigger( 'verifyPhone.contentUpdated' );
			} )
			.fail( function() {
				that.verifyPhoneDialog.setContent( '<p>Something went wrong.</p>' );
			} )
			.always( function() {
				that.verifyPhoneDialog.hideLoading();
			} );
		},

		initPhoneField: function() {
			var input = document.querySelector( '.ghop-dialog #ghop-verify-phone-form .wp-sms-input-mobile' );

			if ( ! input ) {
				return;
			}

			window.intlTelInput( input, {
				onlyCountries: wp_sms_intel_tel_input.only_countries,
				preferredCountries: wp_sms_intel_tel_input.preferred_countries,
				autoHideDialCode: wp_sms_intel_tel_input.auto_hide,
				nationalMode: wp_sms_intel_tel_input.national_mode,
				separateDialCode: wp_sms_intel_tel_input.separate_dial,
				utilsScript: wp_sms_intel_tel_input.util_js,
				customContainer: 'intel-otp'
			});
		}
	};

	$.fn.ghopDoorButton = function( options ) {
		options = $.extend( true, {}, options );

		this.each( function() {
			new GhopDoorButton( this, options );
		} );

		return this;
	};

	$(function () {
		$( '#ghop-open-door' ).ghopDoorButton({
			phoneVerified: ( '1' === params.phone_verified ),
			buttonText: params.button_text
		});
	});
} )( jQuery, ghop_scripts_params );
