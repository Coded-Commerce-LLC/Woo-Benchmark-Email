
// Track Last Page
var bmew_page = 1;

// On Button Click
jQuery( document ).ready( function( $ ) {
	$( '#sync_customers' ).click( function() {
		$( '#sync_complete' ).hide();
		$( '#sync_progress_bar' ).empty();
		$( '#sync_in_progress' ).show();
		bmew_page = 1;
		bmew_query( $ );
	} );
} );

// AJAX Caller
function bmew_query( $ ) {
	var data = {
		'action': 'bmew_action',
		'sync': 'sync_customers',
		'page': bmew_page
	};
	$.post( ajaxurl, data, function( response ) {

		// Handle Completion
		if( response == 0 ) {
			$( '#sync_in_progress' ).hide();
			$( '#sync_progress_bar' ).empty();
			$( '#sync_complete' ).show();
			return;
		}

		// Display Page Processed
		$( '#sync_progress_bar' ).append( ' ' + response );

		// Advance
		bmew_page ++;
		bmew_query( $ );
	} );
}
