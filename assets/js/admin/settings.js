( function ( $ ) {
	$(document).ready( function(){
		$('input[type="submit"]#save-cocart').click( function(e){
			// Prevent Default functionality
			e.preventDefault();

			// Empties save results from previous save.
			$('.save-results').empty();

			// Disable save button until Ajax completed.
			$(this).prop('disabled', true);

			// Salt Key validation.
			var skip_salt = false;

			var salt_key = $('input[type="text"]#salt_key').val();

			// Check if salt key is already hashed so we don't hash a hash.
			var is_salt_hashed = salt_key.match(/^[a-f0-9]{32}$/gi) ? true : false;

			if ( is_salt_hashed ) {
				console.log('Salt key is already hashed! Skipped saving field.');
				skip_salt = true;
			}

			// If salt key field is disabled then it's defined, so we don't save this field.
			if ( $('input[type="text"]#salt_key').prop('disabled') ) {
				console.log('Salt key is already hashed in wp-config.php file! Skipped saving field.');
				skip_salt = true;
			}

			// Prepare posted data.
			var formData = $('#settings-form').serializeArray().reduce((obj, field) => {
				if ( field.name !== 'cocart-settings' && field.name !== '_wpnonce' && field.name !== '_wp_http_referer' && ( field.name === 'salt_key' && skip_salt === false ) ) {
					obj[field.name] = field.value;
				}
				return obj;
			}, {});
			var settings = $('input[name="cocart-settings"]').val();

			// Save settings.
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

					// Hide notice after 5 seconds.
					setTimeout( function() {
						$('.notice').hide('slow');
					}, 5000 );
				}
			});
		});
	});
} )( jQuery, cocart_params );
