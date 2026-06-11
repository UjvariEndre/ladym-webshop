/* global LadymSM, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof LadymSM === 'undefined' ) {
		return;
	}

	var $root = $( '#ladym-sm' );
	if ( ! $root.length ) {
		return;
	}

	var state = { textileId: null, size: null, fazon: null, variation: null };

	var $previewImg   = $( '#ladym-sm-preview' );
	var $previewEmpty = $( '#ladym-sm-preview-empty' );
	var $previewCap   = $( '#ladym-sm-preview-cap' );
	var $summary      = $( '#ladym-sm-summary' );
	var $order        = $( '#ladym-sm-order' );
	var $msg          = $( '#ladym-sm-msg' );

	function textileById( id ) {
		for ( var i = 0; i < LadymSM.textiles.length; i++ ) {
			if ( String( LadymSM.textiles[ i ].id ) === String( id ) ) {
				return LadymSM.textiles[ i ];
			}
		}
		return null;
	}

	function fazonBySlug( slug ) {
		for ( var i = 0; i < LadymSM.fazons.length; i++ ) {
			if ( LadymSM.fazons[ i ].slug === slug ) {
				return LadymSM.fazons[ i ];
			}
		}
		return null;
	}

	// A nagy előnézeti kép: a kiválasztott textil × fazon placeholderre vált.
	function updatePreview() {
		var textile = state.textileId ? textileById( state.textileId ) : null;
		var src = '';
		if ( textile && state.fazon && textile.previews ) {
			src = textile.previews[ state.fazon ] || '';
		}
		if ( src ) {
			$previewImg.attr( 'src', src ).attr( 'alt', textile.name );
			$previewImg.show();
			$previewEmpty.hide();
			var fazon = fazonBySlug( state.fazon );
			$previewCap.text( textile.name + ( fazon ? ' · ' + fazon.label : '' ) );
		} else {
			$previewImg.attr( 'src', '' ).hide();
			$previewEmpty.show();
			$previewCap.text( '' );
		}
	}

	function updateSummary() {
		var parts = [];
		if ( state.textileId ) {
			var t = textileById( state.textileId );
			if ( t ) {
				parts.push( LadymSM.i18n.textile + ': ' + t.name );
			}
		}
		if ( state.size ) {
			parts.push( LadymSM.i18n.size + ': ' + state.size );
		}
		if ( state.fazon ) {
			var f = fazonBySlug( state.fazon );
			if ( f ) {
				parts.push( LadymSM.i18n.fazon + ': ' + f.label );
			}
		}
		$summary.text( parts.join( '  ·  ' ) );
	}

	function refresh() {
		updatePreview();
		updateSummary();
		var ready = state.textileId && state.size && state.fazon;
		$order.prop( 'disabled', ! ready );
	}

	// Rádiócsoport-választás (egy aktív gomb csoportonként).
	function selectInGroup( $btn, selector ) {
		$root.find( selector ).removeClass( 'is-selected' ).attr( 'aria-checked', 'false' );
		$btn.addClass( 'is-selected' ).attr( 'aria-checked', 'true' );
	}

	$root.on( 'click', '.ladym-sm__swatch', function () {
		state.textileId = $( this ).data( 'textileId' );
		selectInGroup( $( this ), '.ladym-sm__swatch' );
		$msg.text( '' );
		refresh();
	} );

	$root.on( 'click', '.ladym-sm__size', function () {
		state.size = String( $( this ).data( 'size' ) );
		selectInGroup( $( this ), '.ladym-sm__size' );
		refresh();
	} );

	$root.on( 'click', '.ladym-sm__fazon', function () {
		state.fazon = String( $( this ).data( 'fazon' ) );
		state.variation = $( this ).data( 'variation' );
		selectInGroup( $( this ), '.ladym-sm__fazon' );
		refresh();
	} );

	$( '#ladym-sm-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		if ( ! ( state.textileId && state.size && state.fazon ) ) {
			return;
		}
		$order.prop( 'disabled', true ).addClass( 'is-loading' );
		$msg.removeClass( 'is-error' ).text( LadymSM.i18n.adding );

		$.post( LadymSM.ajaxUrl, {
			action: 'ladym_sm_add',
			nonce: LadymSM.nonce,
			textile_id: state.textileId,
			size: state.size,
			fazon: state.fazon
		} ).done( function ( res ) {
			if ( res && res.success ) {
				window.location.href = ( res.data && res.data.cart_url ) ? res.data.cart_url : LadymSM.cartUrl;
				return;
			}
			var m = ( res && res.data && res.data.message ) ? res.data.message : LadymSM.i18n.genericError;
			$msg.addClass( 'is-error' ).text( m );
			$order.prop( 'disabled', false ).removeClass( 'is-loading' );
		} ).fail( function () {
			$msg.addClass( 'is-error' ).text( LadymSM.i18n.genericError );
			$order.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	} );

	refresh();
} )( jQuery );
