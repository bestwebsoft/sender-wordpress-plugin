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
			runTimeVal   = runTime.val(),
			sendCount    = $( '#sndr_mail_send_count' ),
			sendCountVal = sendCount.val(),
			number       = 0;
		runTime.change( function() {
			if ( parseInt( $( this ).val() ) < 1 || !( /^\s*(\+|-)?\d+\s*$/.test( $( this ).val() ) ) ) {
				$( this ).val( '1' ).text( '1' );
			}
			if ( parseInt( $( this ).val() ) > 360 ) {
				if( ! confirm( sndrScriptVars['toLongMessage'] ) ) {
					$( this ).val( runTimeVal ).text( runTimeVal );
				}
			}
			number = Math.floor( ( 60 / $( this ).val() )  * parseInt( sendCount.val() ) );
			$( '#sndr-calculate' ).text( '' ).text( number );
		});
		sendCount.change( function() {
			if ( parseInt( $( this ).val() ) < 1 || !( /^\s*(\+|-)?\d+\s*$/.test( $( this ).val() ) ) ) {
				$( this ).val( '1' ).text( '1' );
			}
			if ( parseInt( $( this ).val() ) > 50 ) {
				if( ! confirm( sndrScriptVars['toLongMessage'] ) ) {
					$( this ).val( sendCountVal ).text( sendCountVal );
				}
			}
			number = parseInt( ( 60 / runTime.val() ) * $( this ).val() );
			$( '#sndr-calculate' ).text( '' ).text( number );
		});

		/**
		 * Show/hide some blocks on plugin settings page
		 */
		var phpRadio    = $( '#sndr_wp_mail_radio, #sndr_php_mail_radio' ),
			smtpRadio   = $( '#sndr_smtp_mail_radio' ),
			smtpOptions = $( '.sndr_smtp_options' );
		phpRadio.click( function() {
			smtpOptions.hide();
		});
		smtpRadio.click( function() {
			smtpOptions.show();
		});
		
		/**
		 * show not necessary columns on report page
		 */
		if ( ! $( '#subject-hide' ).is( ':checked' ) ) {
			$( '.column-subject' ).hide();
		}
		if ( ! $( '#status-hide' ).is( ':checked' ) ) {
			$( '.column-status' ).hide();
		}
		if ( ! $( '#date-hide' ).is( ':checked' ) ) {
			$( '.column-date' ).hide();
		}

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