( function ( $ ) {
	$(document).ready( function(){
		$('input[type="submit"]#save-cocart').click( function(e){
			// Prevent Default functionality
			e.preventDefault();

			// Empties save results from previous save.
			$('.save-results').empty();

			// Disable save button until Ajax completed.
			$(this).prop('disabled', true);

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
				success: function() {
					$('.save-results').html('<div class="notice notice-success"></div>');
					$('.notice-success').append("<p><strong>" + cocart_params.saved_message + "</strong></p>").show();
				},
				error: function( xhr ) {
					var errorMessage = xhr.status + ': ' + xhr.statusText;

					$('.save-results').html('<div class="notice notice-error"></div>');
					$('.notice-error').append("<p><strong>" + errorMessage + "</strong></p>").show();
				},
				complete: function() {
					// Re-enable save button now Ajax is complete.
					$('input[type="submit"]#save-cocart').prop('disabled', false);

					setTimeout( function() {
						$('.notice').hide('slow');
					}, 5000 );
				}
				//timeout: 5000
			});
		});
	});
} )( jQuery, cocart_params );
