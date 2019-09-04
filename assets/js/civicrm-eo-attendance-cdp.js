/**
 * CiviCRM Event Organiser Attendance "Custom Data (Participant)" Javascript.
 *
 * Implements Custom Data (Participant) functionality on EO Event pages.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Custom Data (Participant) object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2.2
 */
var CiviCRM_EO_Attendance_CDP = CiviCRM_EO_Attendance_CDP || {};



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.2.2
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Singleton.
	 *
	 * @since 0.2.2
	 */
	CiviCRM_EO_Attendance_CDP.settings = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.2
		 */
		this.init = function() {

			// Init localisation.
			me.init_localisation();

			// Init settings.
			me.init_settings();

		};

		// Init localisation array.
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 0.2.8
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDP_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_CDP_Settings.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.2.8
		 *
		 * @param {String} The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.2.2
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDP_Settings ) {
				me.settings = CiviCRM_EO_Attendance_CDP_Settings.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.2.2
		 *
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

		// Init clicked array.
		me.clicked = [];

		/**
		 * Getter for retrieving an item.
		 *
		 * @since 0.2.1
		 *
		 * @param {String} The identifier for the desired item.
		 * @return The value of the item.
		 */
		this.get_clicked = function( identifier ) {
			if ( $.inArray( identifier, me.clicked ) !== -1 ) {
				return true;
			} else {
				return false;
			}
		};

		/**
		 * Setter for registering a button click.
		 *
		 * @since 0.2.1
		 *
		 * @param {Integer} The value for the item.
		 */
		this.set_clicked = function( value ) {
			if ( ! me.get_clicked( value ) ) {
				me.clicked.push( value );
			}
		};

	};

	/**
	 * Create Form Handling Singleton.
	 *
	 * @since 0.2.2
	 */
	CiviCRM_EO_Attendance_CDP.form = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Form.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.2
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.2
		 */
		this.dom_ready = function() {

			// Enable listeners.
			me.listeners();

		};

		/**
		 * Set up listeners.
		 *
		 * @since 0.2.2
		 */
		this.listeners = function() {

			// Declare vars.
			var link = $('span.civicrm-eo-custom-data-participant');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object.
			 */
			link.on( 'click', function( event ) {

				// Declare vars.
				var classes, participant_id = 0;

				// Grab classes.
				classes = $(this).prop( 'class' ).split( ' ' );

				// Loop to find the one we want.
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-cdp-participant-id-' ) ) {
						participant_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// If already clicked.
				if ( CiviCRM_EO_Attendance_CDP.settings.get_clicked( participant_id ) ) {

					// Toggle form and bail.
					$('.civicrm-eo-cdp-participant-id-' + participant_id).next('ul').slideToggle();
					return;

				}

				// Show spinner.
				$(this).next( '.civicrm-eo-loading' ).show();

				// Register click.
				CiviCRM_EO_Attendance_CDP.settings.set_clicked( participant_id );

				// Request custom data form for this participant.
				me.form_get( participant_id );

			});

		};

		/**
		 * Get form for a Participant via AJAX request.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} participant_id The numeric ID of the Participant.
		 */
		this.form_get = function( participant_id ) {

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_CDP.settings.get_setting( 'ajax_url' ),

				{

					// Token received by WordPress.
					action: 'participant_custom_data_form_get',

					// Send participant ID.
					participant_id: participant_id

				},

				// Callback.
				function( data, textStatus ) {

					// If success.
					if ( textStatus == 'success' ) {

						// Update DOM.
						me.form_render( data );

					} else {

						// Show error.
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// Expected format.
				'json'

			);

		};

		/**
		 * Act on the data received from an AJAX request.
		 *
		 * @since 0.2.2
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.form_render = function( data ) {

			// Vars.
			var item, processed, list_item;

			// Are we still in progress?
			if ( data.markup != '' ) {

				// Find our item.
				item = $('.civicrm-eo-cdp-participant-id-' + data.participant_id);

				// Hide spinner.
				item.next( '.civicrm-eo-loading' ).hide();

				// Process into jQuery object.
				processed = $( $.parseHTML( data.markup ) );

				// Target enclosing item.
				list_item = item.parent();

				// Append to link and show.
				processed.appendTo( list_item ).hide().slideDown();

				// Add listener for form submit button.
				me.form_rendered( data.participant_id );

			}

		};

		/**
		 * Perform tasks once the form has been rendered.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} participant_id The numeric ID of the Participant.
		 */
		this.form_rendered = function( participant_id ) {

			// Declare vars.
			var submit_button = $('#civicrm_eo_cdp_submit_' + participant_id);

			/**
			 * Add a click event listener to the form submit button.
			 *
			 * @param {Object} event The event object.
			 */
			submit_button.on( 'click', function( event ) {

				var button = $(this);

				// Bail if disabled.
				if ( button.prop('disabled') ) {
					return;
				}

				// Prevent form submission.
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// Declare vars.
				var participant_id = 0;

				// Grab participant ID.
				participant_id = parseInt( button.prop('id').split('_')[4] );

				// Submit custom data form.
				me.form_submit( participant_id, button );

			});

		};

		/**
		 * Submit the custom data form for a Participant via AJAX.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} participant_id The numeric ID of the Participant.
		 * @param {Object} submit_button The form's submit button jQuery object.
		 */
		this.form_submit = function( participant_id, submit_button ) {

			// Grab form values.
			var hours = $('#civicrm_eo_cdp_hours_' + participant_id).val(),
				minutes = $('#civicrm_eo_cdp_minutes_' + participant_id).val();

			// Can't have empty values.
			if ( hours == '' || minutes == '' ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'empty' )
				);
				return;
			}

			// Can't have non-numeric values.
			if ( ! $.isNumeric( hours ) || ! $.isNumeric( minutes ) ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'numeric' )
				);
				return;
			}

			// Can't have non-integer values.
			if ( Math.floor( hours ) != hours || Math.floor( minutes ) != minutes ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'integer' )
				);
				return;
			}

			// Can't have negative values.
			if ( parseInt( hours ) < 0 || parseInt( minutes ) < 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'negative' )
				);
				return;
			}

			// Can't be no time at all.
			if ( parseInt( hours ) === 0 && parseInt( minutes ) === 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'zero' )
				);
				return;
			}

			// Can't have minutes more than 59.
			if ( parseInt( minutes ) > 59 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'mins' )
				);
				return;
			}

			// Change text.
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_CDP.settings.get_setting( 'ajax_url' ),

				{

					// Token received by WordPress.
					action: 'participant_custom_data_form_process',

					// Send form data.
					participant_id: parseInt( participant_id ),
					civicrm_eo_cdp_hours: parseInt( hours ),
					civicrm_eo_cdp_minutes: parseInt( minutes )

				},

				// Callback.
				function( data, textStatus ) {

					// If success.
					if ( textStatus == 'success' ) {

						// Update DOM.
						me.form_feedback( data );

					} else {

						// Show error.
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// Expected format.
				'json'

			);

		};

		/**
		 * Act on the data received from an AJAX form submission.
		 *
		 * @since 0.2.2
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.form_feedback = function( data ) {

			/*
			// Trace.
			if ( console.log ) {
				console.log( 'cdp data', data );
			}
			*/

			// Hide form's enclosing list.
			$('#civicrm_eo_cdp_' + data.participant_id).parent().parent().slideUp( 'fast', function() {

				// After slide.
				var processed, list_item;

				// Are we still in progress?
				if ( data.markup != '' ) {

					// Process into jQuery object.
					processed = $( $.parseHTML( data.markup ) );

					// Target enclosing item.
					list_item = $('.civicrm-eo-cdp-participant-id-' + data.participant_id).parent();

					// Append to link and show.
					processed.appendTo( list_item ).hide().slideDown();

				}

			});

		};

	};

	// Init settings.
	CiviCRM_EO_Attendance_CDP.settings.init();

	// Init list.
	CiviCRM_EO_Attendance_CDP.form.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_EO_Attendance_CDP.form.dom_ready();

}); // End document.ready()



