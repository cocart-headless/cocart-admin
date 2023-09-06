( function ( $ ) {
	$( function() {
		$( 'a.generate-token' ).click( function( event ) {
			if ( ! confirm( cocart_params.i18n_regenerate_token ) ) {
				event.preventDefault();
			}

			event.preventDefault();

			$.ajax({
				method: 'POST',
				dataType: 'json',
				url: cocart_params.ajax_url,
				data: {
					action: 'cocart_generate_access_token',
					regenerate_nonce: cocart_params.generate_token_nonce
				},
				success: function( response ) {
					if ( response.success ) {
						var token = response.data;

						$( 'input[type="text"]#access_token' ).val( token );
					}
				}
			});
		});
	});
} )( jQuery, cocart_params );
