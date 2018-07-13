
// jQuery Ready State
jQuery( document ).ready( function( $ ) {

	// Check On Prepopulated Form
	bmew_billing_email_checker( $ );

	// Check On Data Entry
	$( '#billing_email' ).keyup( function() {
		bmew_input_delay( function() {
			bmew_billing_email_checker( $ );
		}, 2000 );
	} );
} );

var bmew_input_delay = ( function() {
	var timer = 0;
	return function( callback, ms ) {
		clearTimeout ( timer );
		timer = setTimeout( callback, ms );
	};
} )();

// Checks Email Form
function bmew_billing_email_checker( $ ) {

	// Check If Complete Field
	if( bmew_is_email( $( '#billing_email' ).val() ) ) {

		// Save For Abandoned Cart Automations
		var data = {
			'action': 'bmew_action',
			'sync': 'abandoned_cart',
			'email': $( '#billing_email' ).val(),
			'first': $( '#billing_first_name' ).val(),
			'last': $( '#billing_last_name' ).val()
		};
		$.post( bmew_ajax_object.ajax_url, data, function( response ) {
			alert( 'Abandoned cart for ' + response );
		} );
	}
}

// Validate Email Address String
function bmew_is_email( email ) {
	var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test( email );
}
