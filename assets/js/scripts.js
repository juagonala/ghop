/**
 * Frontend scripts.
 *
 * @package Ghop/Assets/JS
 * @since   1.0.0
 */

/* global ghop_scripts_params */
( function( $, params ) {

	'use strict';

	$(function () {
		$( document.body ).on( 'click', '#ghop-open-door', function( event ) {
			event.preventDefault();

			var $button = $( this ),
				text    = $( this ).text();

			$button.text( params.button_text );
			$button.parent().find( '.ghop-open-door-notice' ).remove();

			$.post({
				url: params.ajax_url,
				dataType: 'json',
				data: {
					action: 'ghop_open_door',
					nonce: params.nonce
				},
				success: function( result ) {
					var $notice = $( '<div class="ghop-open-door-notice"></div>' )

					$notice.html( result.data.message )
						.removeClass( 'success error' )
						.addClass( result.success ? 'success' : 'error' );

					// Restore the original text.
					$button.text( text );

					$button.after( $notice );
				}
			});
		});
	});
} )( jQuery, ghop_scripts_params );
