( function ( $ ) {
	'use strict';

	var $importBtn;
	var $spinner;
	var $resultsWrap;
	var $resultsSummary;
	var $messagesLog;
	var progressTimer = null;
	var progressStartTime = 0;

	function init() {
		$importBtn = $( '#devto-wp-importer-import-btn' );
		$spinner = $( '#devto-wp-importer-spinner' );
		$resultsWrap = $( '#devto-wp-importer-results' );
		$resultsSummary = $( '#devto-wp-importer-results-summary' );
		$messagesLog = $( '#devto-wp-importer-messages-log' );

		$importBtn.on( 'click', handleImport );
	}

	function handleImport( e ) {
		e.preventDefault();

		if ( ! window.confirm( devtoWpImporterAdmin.i18n.confirm ) ) {
			return;
		}

		setImporting( true );

		$.ajax(
			{
				url: devtoWpImporterAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'devto_wp_importer_import_articles',
					nonce: devtoWpImporterAdmin.nonce,
				},
				timeout: 600000,
				success: function ( response ) {
					setImporting( false );

					if ( response.success ) {
						displayResults( response.data );
					} else {
						displayError( response.data && response.data.message ? response.data.message : devtoWpImporterAdmin.i18n.importFailed );
					}
				},
				error: function ( xhr, status ) {
					setImporting( false );
					displayError( devtoWpImporterAdmin.i18n.importFailed + ' (' + status + ')' );
				},
			}
		);
	}

	function setImporting( isImporting ) {
		$importBtn.prop( 'disabled', isImporting );
		$spinner.toggleClass( 'is-active', isImporting );

		if ( isImporting ) {
			$importBtn.text( devtoWpImporterAdmin.i18n.importing );
			progressStartTime = Date.now();
			if ( progressTimer ) {
				window.clearInterval( progressTimer );
			}
			showInProgressMessage( 0 );
			progressTimer = window.setInterval( function () {
				var elapsed = Math.floor( ( Date.now() - progressStartTime ) / 1000 );
				showInProgressMessage( elapsed );
			}, 1000 );
			$resultsWrap.show();
		} else {
			if ( progressTimer ) {
				window.clearInterval( progressTimer );
				progressTimer = null;
			}
			$importBtn.text( $importBtn.data( 'label' ) );
		}
	}

	function showInProgressMessage( elapsed ) {
		var template = devtoWpImporterAdmin.i18n.inProgress || 'Import in progress... elapsed: %ds';
		var message = template.replace( '%d', elapsed );

		$resultsSummary.html(
			'<div class="notice notice-info inline"><p>' +
				$( '<span>' ).text( message ).html() +
			'</p></div>'
		);
		$messagesLog.empty();
	}

	function displayResults( data ) {
		var html =
			'<table class="widefat devto-wp-importer-results-table">' +
			'<thead><tr><th>' +
			devtoWpImporterAdmin.i18n.importDone +
			'</th><th>#</th></tr></thead>' +
			'<tbody>' +
			'<tr class="devto-wp-importer-created"><td>' +
			devtoWpImporterAdmin.i18n.created +
			'</td><td><strong>' +
			data.created +
			'</strong></td></tr>' +
			'<tr class="devto-wp-importer-updated"><td>' +
			devtoWpImporterAdmin.i18n.updated +
			'</td><td><strong>' +
			data.updated +
			'</strong></td></tr>' +
			'<tr class="devto-wp-importer-skipped"><td>' +
			devtoWpImporterAdmin.i18n.skipped +
			'</td><td><strong>' +
			data.skipped +
			'</strong></td></tr>' +
			'<tr class="devto-wp-importer-failed"><td>' +
			devtoWpImporterAdmin.i18n.failed +
			'</td><td><strong>' +
			data.failed +
			'</strong></td></tr>' +
			'</tbody></table>';

		$resultsSummary.html( html );

		if ( data.messages && data.messages.length > 0 ) {
			var msgHtml = '<div class="devto-wp-importer-messages"><h4>' + ( devtoWpImporterAdmin.i18n.importLog || 'Import Log' ) + '</h4><ul>';
			for ( var i = 0; i < data.messages.length; i++ ) {
				msgHtml += '<li>' + $( '<span>' ).text( data.messages[ i ] ).html() + '</li>';
			}
			msgHtml += '</ul></div>';
			$messagesLog.html( msgHtml );
		} else {
			$messagesLog.empty();
		}

		$resultsWrap.show();
	}

	function displayError( message ) {
		$resultsSummary.html(
			'<div class="notice notice-error inline"><p>' +
				$( '<span>' ).text( message ).html() +
			'</p></div>'
		);
		$messagesLog.empty();
		$resultsWrap.show();
	}

	$( document ).ready( init );
} )( jQuery );
