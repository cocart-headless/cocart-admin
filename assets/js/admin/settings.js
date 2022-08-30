( function ( $ ) {
	$(document).ready( function(){
		$('input[type="submit"]').click( function(e){
			// Prevent Default functionality
			e.preventDefault();

			var formData = $('#settings-form').serializeArray().reduce((obj, field) => {
				if ( field.name !== 'cocart-settings' && field.name !== '_wpnonce' && field.name !== '_wp_http_referer' ) {
					obj[field.name] = field.value;
				}
				return obj;
			}, {});
			var settings = $('input[name="cocart-settings"]').val();

			$.ajax({
				method: 'POST',
				url: cocart_params.root + 'cocart/settings/save?settings=' + settings + '&_wpnonce=' + cocart_params.nonce,
				data: JSON.stringify( formData, null, ' ' ),
				dataType: 'json',
				contentType: 'application/json; charset=utf-8',
				success: function ( data ) {
					console.log( data );
				},
			});
		});
	});
} )( jQuery, cocart_params );
