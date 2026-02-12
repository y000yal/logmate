/**
 * Public JavaScript for LogMate - Captures JavaScript errors.
 *
 * @package LogMate
 */

(function () {
	'use strict';

	// Wait for LogMateData to be available (WordPress localizes scripts).
	function initErrorLogging() {
		// Check if LogMateData is available and JS error logging is enabled.
		if ( typeof LogMateData === 'undefined' ) {
			console.log( '[LogMate] LogMateData not available yet, retrying...' );
			setTimeout( initErrorLogging, 100 );
			return;
		}

		if ( ! LogMateData.jsErrorLogging || LogMateData.jsErrorLogging.status !== 'enabled' ) {
			console.log( '[LogMate] JS error logging not enabled', LogMateData );
			return;
		}

		console.log( '[LogMate] JS error logging initialized', LogMateData.jsErrorLogging );

		// Log javascript errors in the front end via XHR.
		// Code source: https://plugins.svn.wordpress.org/javascript-error-reporting-client/tags/1.0.3/public/js/jerc.js.

		window.onerror = function (msg, url, lineNo, columnNo, error) {
			console.log( '[LogMate] Error caught:', msg, url, lineNo, columnNo );
			var data = {
				nonce: LogMateData.nonce,
				message: msg,
				script: url,
				lineNo: lineNo,
				columnNo: columnNo,
				pageUrl: window.location.pathname + window.location.search,
				type: 'front end'
			};

			var xhr = new XMLHttpRequest();
			xhr.open( "POST", LogMateData.jsErrorLogging.url );
			xhr.setRequestHeader( 'Content-type', 'application/json' );
			xhr.setRequestHeader( 'X-WP-Nonce', LogMateData.nonce );

			xhr.onload = function () {
				console.log( '[LogMate] Error logged successfully:', xhr.status, xhr.responseText );
			};

			xhr.onerror = function () {
				console.error( '[LogMate] Failed to log error:', xhr.status, xhr.statusText );
			};

			xhr.send( JSON.stringify( data ) );
			return false;
		};

		// Also catch unhandled promise rejections.
		window.addEventListener(
			'unhandledrejection',
			function (event) {
				var data = {
					nonce: LogMateData.nonce,
					message: event.reason ? (event.reason.message || event.reason.toString()) : 'Unhandled Promise Rejection',
					script: window.location.href,
					lineNo: 0,
					columnNo: 0,
					pageUrl: window.location.pathname + window.location.search,
					type: 'promise rejection'
				};

				var xhr = new XMLHttpRequest();
				xhr.open( "POST", LogMateData.jsErrorLogging.url );
				xhr.setRequestHeader( 'Content-type', 'application/json' );
				xhr.setRequestHeader( 'X-WP-Nonce', LogMateData.nonce );
				xhr.onload = function () {
					console.log( '[LogMate] Promise rejection logged successfully:', xhr.status, xhr.responseText );
				};

				xhr.onerror = function () {
					console.error( '[LogMate] Failed to log promise rejection:', xhr.status, xhr.statusText );
				};

				xhr.send( JSON.stringify( data ) );
			}
		);

		// Expose a manual test function to window for debugging.
		window.logMateTestError = function () {
			console.log( '[LogMate] Manual test error triggered' );
			throw new Error( 'LogMate Manual Test Error' );
		};

		// Expose a manual test function to log an object.
		window.logMateTestObject = function () {
			var testObject = {
				name: 'Manual Test Object',
				timestamp: new Date().toISOString(),
				message: 'This is a manually triggered object log test'
			};

			var data = {
				nonce: LogMateData.nonce,
				message: 'Manual Object Test: ' + JSON.stringify( testObject, null, 2 ),
				script: window.location.href,
				lineNo: 0,
				columnNo: 0,
				pageUrl: window.location.pathname + window.location.search,
				type: 'manual object test'
			};

			var xhr = new XMLHttpRequest();
			xhr.open( "POST", LogMateData.jsErrorLogging.url );
			xhr.setRequestHeader( 'Content-type', 'application/json' );
			xhr.setRequestHeader( 'X-WP-Nonce', LogMateData.nonce );
			xhr.onload = function () {
				console.log( '[LogMate] Manual object test logged successfully:', xhr.status, xhr.responseText );
			};
			xhr.onerror = function () {
				console.error( '[LogMate] Failed to log manual object test:', xhr.status, xhr.statusText );
			};
			xhr.send( JSON.stringify( data ) );
		};

		console.log( '[LogMate] Manual tests available: Run debugMasterTestError() or debugMasterTestObject() in console' );
	}

	// Start initialization when DOM is ready or immediately if already ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initErrorLogging );
	} else {
		initErrorLogging();
	}

})();
