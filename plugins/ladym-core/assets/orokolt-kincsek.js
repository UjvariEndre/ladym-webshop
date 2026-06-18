/* global LadymOK, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof LadymOK === 'undefined' ) {
		return;
	}
	var $root = $( '#ladym-ok' );
	if ( ! $root.length ) {
		return;
	}

	var i18n      = LadymOK.i18n || {};
	var $form     = $root.find( '.ladym-ok__form' );
	var $steps    = $form.find( '.ladym-ok__step' );
	var $bar      = $form.find( '.ladym-ok__progress-bar' );
	var $back     = $form.find( '.ladym-ok__back' );
	var $next     = $form.find( '.ladym-ok__next' );
	var $submit   = $form.find( '.ladym-ok__submit' );
	var $error    = $form.find( '.ladym-ok__error' );
	var $thumbs   = $form.find( '.ladym-ok__thumbs' );
	var $file     = $form.find( '.ladym-ok__file' );
	var $drop     = $form.find( '.ladym-ok__drop' );
	var $done     = $root.find( '.ladym-ok__done' );
	var submitTxt = $submit.text();

	var total = $steps.length;
	var step  = 1;
	var files = [];

	function showError( msg ) { $error.text( msg || i18n.genericError ).removeAttr( 'hidden' ); }
	function hideError() { $error.attr( 'hidden', true ).text( '' ); }

	function showStep( n ) {
		step = Math.max( 1, Math.min( total, n ) );
		$steps.attr( 'hidden', true ).removeClass( 'is-active' );
		$steps.filter( '[data-step="' + step + '"]' ).removeAttr( 'hidden' ).addClass( 'is-active' );
		$bar.css( 'width', ( step / total * 100 ) + '%' );
		$back.prop( 'hidden', step === 1 );
		$next.prop( 'hidden', step === total );
		$submit.prop( 'hidden', step !== total );
		hideError();
	}

	function isEmail( v ) { return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( v ); }

	function validateStep( n ) {
		if ( n === 1 && ! $form.find( 'input[name=item]:checked' ).length ) { showError( i18n.needChoice ); return false; }
		if ( n === 2 && ! $form.find( 'input[name=goal]:checked' ).length ) { showError( i18n.needChoice ); return false; }
		if ( n === 4 ) {
			var name  = $.trim( $form.find( 'input[name=name]' ).val() );
			var email = $.trim( $form.find( 'input[name=email]' ).val() );
			if ( ! name || ! isEmail( email ) ) { showError( i18n.needContact ); return false; }
		}
		return true;
	}

	$next.on( 'click', function () { if ( validateStep( step ) ) { showStep( step + 1 ); } } );
	$back.on( 'click', function () { showStep( step - 1 ); } );

	// Egy kártya kiválasztása után automatikusan tovább (gyorsabb élmény).
	$form.on( 'change', 'input[name=item], input[name=goal]', function () {
		hideError();
		window.setTimeout( function () { if ( step < total ) { showStep( step + 1 ); } }, 220 );
	} );

	// Fotók.
	function renderThumbs() {
		$thumbs.empty();
		files.forEach( function ( file, idx ) {
			var $li = $( '<li class="ladym-ok__thumb"><img alt=""/><button type="button" class="ladym-ok__thumb-x" aria-label="Törlés">&times;</button></li>' );
			$li.find( 'img' ).attr( 'src', URL.createObjectURL( file ) );
			$li.find( '.ladym-ok__thumb-x' ).on( 'click', function () { files.splice( idx, 1 ); renderThumbs(); } );
			$thumbs.append( $li );
		} );
	}
	function addFiles( list ) {
		hideError();
		Array.prototype.slice.call( list ).forEach( function ( file ) {
			if ( files.length >= LadymOK.maxFiles ) { showError( i18n.tooManyFiles ); return; }
			var okType = /^image\//.test( file.type ) || /\.(jpe?g|png|webp|heic|heif|gif)$/i.test( file.name );
			if ( ! okType ) { showError( i18n.badType ); return; }
			if ( file.size > LadymOK.maxSizeMb * 1024 * 1024 ) { showError( i18n.tooBig ); return; }
			files.push( file );
		} );
		renderThumbs();
	}
	$file.on( 'change', function () { addFiles( this.files ); this.value = ''; } );
	$drop.on( 'dragover', function ( e ) { e.preventDefault(); $drop.addClass( 'is-over' ); } );
	$drop.on( 'dragleave drop', function () { $drop.removeClass( 'is-over' ); } );
	$drop.on( 'drop', function ( e ) {
		e.preventDefault();
		if ( e.originalEvent && e.originalEvent.dataTransfer ) { addFiles( e.originalEvent.dataTransfer.files ); }
	} );

	// Beküldés.
	$form.on( 'submit', function ( e ) {
		e.preventDefault();
		if ( ! validateStep( 4 ) ) { return; }
		var fd = new FormData();
		fd.append( 'action', 'ladym_ok_submit' );
		fd.append( 'nonce', LadymOK.nonce );
		fd.append( 'item', $form.find( 'input[name=item]:checked' ).val() || '' );
		fd.append( 'goal', $form.find( 'input[name=goal]:checked' ).val() || '' );
		fd.append( 'story', $form.find( 'textarea[name=story]' ).val() || '' );
		fd.append( 'name', $form.find( 'input[name=name]' ).val() || '' );
		fd.append( 'email', $form.find( 'input[name=email]' ).val() || '' );
		fd.append( 'phone', $form.find( 'input[name=phone]' ).val() || '' );
		files.forEach( function ( file ) { fd.append( 'photos[]', file, file.name ); } );

		$submit.prop( 'disabled', true ).text( i18n.sending );
		$.ajax( { url: LadymOK.ajaxUrl, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$form.attr( 'hidden', true );
					$done.removeAttr( 'hidden' );
					if ( $root.offset() ) { $( 'html, body' ).animate( { scrollTop: $root.offset().top - 60 }, 300 ); }
				} else {
					showError( res && res.data && res.data.message );
					$submit.prop( 'disabled', false ).text( submitTxt );
				}
			} )
			.fail( function () {
				showError();
				$submit.prop( 'disabled', false ).text( submitTxt );
			} );
	} );

	showStep( 1 );
} )( jQuery );
