
// Track Last Page
var bmew_page = 1;

// DOM Loaded
jQuery( document ).ready( function( $ ) {

	// Hide On Page Load
	$( 'input#bmew_sync' ).hide();
	$( 'span#sync_complete' ).hide();
	$( 'span#sync_in_progress' ).hide();

	// Handle API Key Click
	$( 'a#get_api_key' ).click( function() {

		// Prompt User And Pass
		var user = prompt(
			"Please enter your Benchmark Email username", ''
		);
		var pass = prompt(
			"Please enter your Benchmark Email password", ''
		);

		// Validate Input
		if( user === null || pass === null ) { return; }

		// Pass To AJAX Handler
		var data = {
			'action': 'bmew_action',
			'sync': 'get_api_key',
			'user': user,
			'pass': pass
		};
		$( 'input#bmew_key' ).val( 'Loading...' );
		$.post( ajaxurl, data, function( response ) {

			// Process Response
			if( response != '' ) {
				$( 'input#bmew_key' ).val( response );
			}
		} );
	} );

	// Handle Sync Click
	$( 'a#sync_customers' ).click( function() {
		$( 'span#sync_complete' ).hide();
		$( 'span#sync_progress_bar' ).empty();
		$( 'span#sync_in_progress' ).show();
		bmew_page = 1;
		bmew_sync_query( $ );
		return false;
	} );
} );

// Customer Sync AJAX
function bmew_sync_query( $ ) {
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
		bmew_sync_query( $ );
	} );
}
