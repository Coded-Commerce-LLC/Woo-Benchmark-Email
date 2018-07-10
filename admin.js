
// Track Last Page
var bmew_page = 1;

// On Button Click
jQuery( document ).ready( function( $ ) {
	$( '#sync_customers' ).click( function() {
		$( '#sync_progress' ).text( 'Syncing at 10 orders per page, completed:' );
		bmew_page = 1;
		bmew_query();
	} );
} );

// AJAX Caller
function bmew_query() {
	var data = {
		'action': 'bmew_action',
		'sync': 'sync_customers',
		'page': bmew_page
	};
	jQuery.post( ajaxurl, data, function( response ) {

		// Continue To Next Page
		if( response > 0 ) {

			// Display Page Processed
			jQuery( '#sync_progress' ).append( ' ' + response );

			// Advance
			bmew_page ++;
			bmew_query();
		}
	} );
}
