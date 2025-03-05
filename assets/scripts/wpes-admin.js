jQuery( document ).ready( function ($) {
  if ($( '.wpes-settings' ).length > 0) {
    /**
     * Settings panel
     */
    let keys = 'enable_history,smtp-enabled,enable-smime,enable-dkim,smtp-is_html'.split( ',' );
    keys.forEach( (selector) => {
      $( '#' + selector ).on( 'change', function (e) { // we need 'function' here for 'this'.
        let target_id = e.target.id;
        $( '.on-' + target_id ).toggle( $( this ).is( ':checked' ) );
        $( '.not-' + target_id ).toggle( !$( this ).is( ':checked' ) );
      } ).trigger( 'change' );
    } );

    let preventInfinite = false;
    $( '.on-regexp-test' ).each( function () { // we need 'function' here for 'this'.
      (
        (field, regexp, label) => {
          $( '#' + field ).on( 'change keyup blur paste', function () {
            let value = $( this ).val() || null;
            if ($( this ).is( '[type=checkbox],[type=radio]' )) {
              if (!preventInfinite) {
                preventInfinite = true;
                let name = $( this ).attr( 'name' );
                let siblings = $( this ).closest( '.postbox' ).find( '[name="' + name + '"]' ).not( this );
                siblings.trigger( 'change' );
                preventInfinite = false;
              }
              if (!$( this ).is( ':checked' )) {
                value = null;
              }
            }
            label.toggle( null !== (
              value || ''
            ).match( new RegExp( regexp, 'i' ) ) );
          } ).trigger( 'change' );
        }
      )( $( this ).attr( 'data-field' ), $( this ).attr( 'data-regexp' ), $( this ) );
    } );
  }

  if ($( '.wpes-emails' ).length > 0) {
    /**
     * Emails panel
     */
    $( '.email-item' ).on( 'click', function (e) { // we need 'function' here for 'this'.
      if ($( e.target ).is( 'a.dashicons-download' )) {
        e.stopPropagation();
        return true;
      }

      $( this ).addClass( 'active' ).siblings().removeClass( 'active' ).removeClass( (index, className) => (
        className.match( /(^|\s)show-\S+/g ) || []
      ).join( ' ' ) );

      let id = '#' + $( '.email-item.active' ).attr( 'id' ).replace( 'email-', 'email-data-' );
      let that = $( id );
      $( '#mail-data-viewer .email-data' ).removeClass( (index, className) => (
        className.match( /(^|\s)show-\S+/g ) || []
      ).join( ' ' ) );

      // Click to cycle through the views.
      let this_and_that = $( this ).add( that );
      if ($( this ).is( '.show-body' )) {
        this_and_that.removeClass( 'show-body' ).addClass( 'show-headers' );
      } else if ($( this ).is( '.show-headers' )) {
        this_and_that.removeClass( 'show-headers' ).addClass( 'show-alt-body' );
      } else if ($( this ).is( '.show-alt-body' )) {
        this_and_that.removeClass( 'show-alt-body' ).addClass( 'show-debug' );
      } else {
        this_and_that.addClass( 'show-body' );
      }
      $( window ).trigger( 'resize' );
    } );

    $( window ).bind( 'resize', function () { // we need 'function' here for 'this'.
      $( '.autofit' ).each( function () {
        $( this ).css( 'width', $( this ).parent().innerWidth() );
        $( this ).css( 'height', $( this ).parent().innerHeight() );
      } );
    } ).trigger( 'resize' );
  }

  if ($( '.wpes-admins' ).length > 0) {
    /**
     * Admins panel
     */
    let t = function () { // we need 'function' here for 'this'.
      if (/^\/[\s\S]+\/[i]?$/.test( (
        $( this ).val() || ''
      ) )) {
        let that = this;
        let re = $( that ).val();

        re = re.split( re.substr( 0, 1 ) );
        re = new RegExp( re[1], re[2] );

        $( '.a-fail' ).each( function () {
          $( this ).toggleClass( 'match', re.test( (
            $( this ).text() || ''
          ) ) );
        } );
      } else {
        $( '.a-fail' ).removeClass( 'match' );
      }
    };
    $( '.a-regexp' ).bind( 'blur', function () { // we need 'function' here for 'this'.
      let val = (
        $( this ).val() || ''
      );
      if ('' === val) {
        return $( this ).removeClass( 'error match' );
      }
      $( this ).toggleClass( 'error', !/^\/[\s\S]+\/[i]?$/.test( val ) ).not( '.error' ).addClass( 'match' );
    } ).bind( 'focus', function (e) { // we need 'function' here for 'this'.
      $( '.a-fail,.a-regexp' ).removeClass( 'match' );
      $( this ).removeClass( 'error match' );
      t.apply( this, [e] );
    } ).bind( 'keyup', t );
  }
} );
