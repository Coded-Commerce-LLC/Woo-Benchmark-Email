
// Track Last Page
var bmew_page = 1;

// On Button Click
jQuery( document ).ready( function( $ ) {

	// Hide On Page Load
	$( 'input#bmew_sync' ).hide();
	$( 'span#sync_complete' ).hide();
	$( 'span#sync_in_progress' ).hide();

	// Handle Sync Request
	$( 'a#sync_customers' ).click( function() {
		$( 'span#sync_complete' ).hide();
		$( 'span#sync_progress_bar' ).empty();
		$( 'span#sync_in_progress' ).show();
		bmew_page = 1;
		bmew_query( $ );
		return false;
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
			$( 'span#sync_in_progress' ).hide();
			$( 'span#sync_progress_bar' ).empty();
			$( 'span#sync_complete' ).show();
			return;
		}

		// Display Page Processed
		$( 'span#sync_progress_bar' ).append( ' ' + response );

		// Advance
		bmew_page ++;
		bmew_query( $ );
	} );
}
