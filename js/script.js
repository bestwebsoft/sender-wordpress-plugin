(function( $ ){
	$( document ).ready( function() {
		/**
		 * Mark checkboxes on mail editor page
		 */
		var checkboxAll  = $( '.sndr-check-all' ),
			counter      = $( '#sndr-calculate' ),
			roleCheckbox = checkboxAll.parent().parent().children().children( '.sndr-role' ),
			allCount     = checkboxAll.parent().children( '.sndr-count' ).text(),
			usersNumber;
		//click event on "All" checkbox
		checkboxAll.click( function() {
			if ( $( this ).is( ':checked' ) ) {
				roleCheckbox.attr( 'checked', true );
				counter.text( allCount );
			} else {
				roleCheckbox.attr( 'checked', false );
				counter.text( '0' );
			}
		});
		//click event on checkbox with user roles
		roleCheckbox.click( function() {
			if ( checkboxAll.is( ':checked' ) ) {
				checkboxAll.attr( 'checked', false );
				roleCheckbox.attr( 'checked', false );
				$( this ).attr( 'checked', true );
			}
			// get number of mails which would be sent
			usersNumber = 0;
			roleCheckbox.each( function() {
				if ( $( this ).is( ':checked' ) ) {
					usersNumber += parseInt( $( this ).parent().children( '.sndr-count' ).text() );
				}
			});
			counter.text( usersNumber );
		});

		/**
		 * calculte maximum number of sent mails and show confirm-window if user enter too large value
		 */
		var runTime      = $( '#sndr_mail_run_time' ),
			sendCount    = $( '#sndr_mail_send_count' ),
			number       = 0;
		runTime.on( 'input change', function() {
			if ( $( this ).val() >= 60 ) {
				number = parseInt( sendCount.val() );
			} else {
				if ( 0 == ( 60 % $( this ).val() ) ) {
					number = Math.floor( 60 / $( this ).val() ) * parseInt( sendCount.val() );
				} else {
					number = ( Math.floor( 60 / $( this ).val() ) + 1 ) * parseInt( sendCount.val() );
				}
			}
			$( '#sndr-calculate' ).text( '' ).text( number );
		} );
		runTime.change( function() {
			if ( parseInt( $( this ).val() ) < 1 || !( /^\s*(\+|-)?\d+\s*$/.test( $( this ).val() ) ) ) {
				$( this ).val( '1' ).text( '1' );
				$( this ).trigger('change');
			}
			if ( parseInt( $( this ).val() ) > 360 ) {
				if( ! confirm( sndrScriptVars['toLongMessage'] ) ) {
					$( this ).val( runTime.val() ).text( runTime.val() );
					$( this ).trigger('change');
				}
			}
		} );
		sendCount.on( 'input change', function() {
			if ( parseInt( runTime.val() ) >= 60 ) {
				number = $( this ).val();
			} else {
				if ( 0 == ( 60 % parseInt( runTime.val() ) ) ) {
					number = Math.floor( 60 / parseInt( runTime.val() ) ) * $( this ).val();
				} else {
					number = ( Math.floor( 60 / parseInt( runTime.val() ) ) + 1 ) * $( this ).val();
				}
			}
			$( '#sndr-calculate' ).text( '' ).text( number );
		} );
		sendCount.change( function() {
			if ( parseInt( $( this ).val() ) < 1 || !( /^\s*(\+|-)?\d+\s*$/.test( $( this ).val() ) ) ) {
				$( this ).val( '1' ).text( '1' );
				$( this ).trigger('change');
			}
			if ( parseInt( $( this ).val() ) > 50 ) {
				if( ! confirm( sndrScriptVars['toLongMessage'] ) ) {
					$( this ).val( sendCount.val() ).text( sendCount.val() );
					$( this ).trigger('change');
				}
			}
		} );

		/**
		 * scroll to report table
		 */
		if ( $( '.report' ).length ) {
			$( 'html, body' ).animate({
				scrollTop: $( '.report' ).offset().top - 30 + 'px'
			}, 0 );

			/* set lists per page or set page */
			$( '.report .sndr_set_list_per_page, .report .sndr_list_paged' ).focusout( function() {
				var value = $( this ).val();
				if ( $( this ).next( '.total_pages' ).find( '.hide-if-js' ).val() != value ) {
					var url = $( 'input[name="sndr_url"]' ).val();

					if ( $( this ).hasClass( 'sndr_set_list_per_page' ) ) {
						if ( $( '.report .sndr_list_paged' ).length > 0 ) {
							paged = $( '.report .sndr_list_paged' ).val();
						} else {
							paged = 1;
						}
						url = url + '&list_paged=' + paged + '&list_per_page=' + value;
					} else {
						url = url + '&list_paged=' + value + '&list_per_page=' + $( '.report .sndr_set_list_per_page' ).val();
					}
					location.href = url;
				}
			});
			$( '.report .sndr_set_list_per_page, .report .sndr_list_paged' ).on( 'keypress', function( event ) {
				if ( event.which == 13 ) {
					event.preventDefault();
					var value = $( this ).val();
					if ( $( this ).next( '.total_pages' ).find( '.hide-if-js' ).val() != value ) {
						var url = $( 'input[name="sndr_url"]' ).val();

						if ( $( this ).hasClass( 'sndr_set_list_per_page' ) ) {
							if ( $( '.report .sndr_list_paged' ).length > 0 ) {
								paged = $( '.report .sndr_list_paged' ).val();
							} else {
								paged = 1;
							}
							url = url + '&list_paged=' + paged + '&list_per_page=' + value;
						} else {
							url = url + '&list_paged=' + value + '&list_per_page=' + $( '.report .sndr_set_list_per_page' ).val();
						}
						location.href = url;
					}
				}
			});
		}
	});
})(jQuery);