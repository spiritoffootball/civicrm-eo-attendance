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

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.2
		 */
		this.init = function() {

			// init localisation
			me.init_localisation();

			// init settings
			me.init_settings();

		};

		// init localisation array
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
		 * @param {String} The identifier for the desired localisation string
		 * @return {String} The localised string
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// init settings array
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
		 * @param {String} The identifier for the desired setting
		 * @return The value of the setting
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

		// init clicked array
		me.clicked = [];

		/**
		 * Getter for retrieving an item.
		 *
		 * @since 0.2.1
		 *
		 * @param {String} The identifier for the desired item
		 * @return The value of the item
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
		 * @param {Integer} The value for the item
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

		// prevent reference collisions
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

			// enable listeners
			me.listeners();

		};

		/**
		 * Set up listeners.
		 *
		 * @since 0.2.2
		 */
		this.listeners = function() {

			// declare vars
			var link = $('span.civicrm-eo-custom-data-participant');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object
			 */
			link.on( 'click', function( event ) {

				// declare vars
				var classes, participant_id = 0;

				// grab classes
				classes = $(this).prop( 'class' ).split( ' ' );

				// loop to find the one we want
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-cdp-participant-id-' ) ) {
						participant_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// if already clicked
				if ( CiviCRM_EO_Attendance_CDP.settings.get_clicked( participant_id ) ) {

					// toggle form and bail
					$('.civicrm-eo-cdp-participant-id-' + participant_id).next('ul').slideToggle();
					return;

				}

				// show spinner
				$(this).next( '.civicrm-eo-loading' ).show();

				// register click
				CiviCRM_EO_Attendance_CDP.settings.set_clicked( participant_id );

				// request custom data form for this participant
				me.form_get( participant_id );

			});

		};

		/**
		 * Get form for a Participant via AJAX request.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} participant_id The numeric ID of the Participant
		 */
		this.form_get = function( participant_id ) {

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_EO_Attendance_CDP.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: 'participant_custom_data_form_get',

					// send participant ID
					participant_id: participant_id

				},

				// callback
				function( data, textStatus ) {

					// if success
					if ( textStatus == 'success' ) {

						// update DOM
						me.form_render( data );

					} else {

						// show error
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// expected format
				'json'

			);

		};

		/**
		 * Act on the data received from an AJAX request.
		 *
		 * @since 0.2.2
		 *
		 * @param {Array} data The data received from the server
		 */
		this.form_render = function( data ) {

			// vars
			var item, processed, list_item;

			// are we still in progress?
			if ( data.markup != '' ) {

				// find our item
				item = $('.civicrm-eo-cdp-participant-id-' + data.participant_id);

				// hide spinner
				item.next( '.civicrm-eo-loading' ).hide();

				// process into jQuery object
				processed = $( $.parseHTML( data.markup ) );

				// target enclosing item
				list_item = item.parent();

				// append to link and show
				processed.appendTo( list_item ).hide().slideDown();

				// add listener for form submit button
				me.form_rendered( data.participant_id );

			}

		};

		/**
		 * Perform tasks once the form has been rendered.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} participant_id The numeric ID of the Participant
		 */
		this.form_rendered = function( participant_id ) {

			// declare vars
			var submit_button = $('#civicrm_eo_cdp_submit_' + participant_id);

			/**
			 * Add a click event listener to the form submit button.
			 *
			 * @param {Object} event The event object
			 */
			submit_button.on( 'click', function( event ) {

				var button = $(this);

				// bail if disabled
				if ( button.prop('disabled') ) {
					return;
				}

				// prevent form submission
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// declare vars
				var participant_id = 0;

				// grab participant ID
				participant_id = parseInt( button.prop('id').split('_')[4] );

				// submit custom data form
				me.form_submit( participant_id, button );

			});

		};

		/**
		 * Submit the custom data form for a Participant via AJAX.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} participant_id The numeric ID of the Participant
		 * @param {Object} submit_button The form's submit button jQuery object
		 */
		this.form_submit = function( participant_id, submit_button ) {

			// grab form values
			var hours = $('#civicrm_eo_cdp_hours_' + participant_id).val(),
				minutes = $('#civicrm_eo_cdp_minutes_' + participant_id).val();

			// can't have empty values
			if ( hours == '' || minutes == '' ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'empty' )
				);
				return;
			}

			// can't have non-numeric values
			if ( ! $.isNumeric( hours ) || ! $.isNumeric( minutes ) ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'numeric' )
				);
				return;
			}

			// can't have non-integer values
			if ( Math.floor( hours ) != hours || Math.floor( minutes ) != minutes ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'integer' )
				);
				return;
			}

			// can't have negative values
			if ( parseInt( hours ) < 0 || parseInt( minutes ) < 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'negative' )
				);
				return;
			}

			// can't be no time at all
			if ( parseInt( hours ) === 0 && parseInt( minutes ) === 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'zero' )
				);
				return;
			}

			// can't have minutes more than 59
			if ( parseInt( minutes ) > 59 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'mins' )
				);
				return;
			}

			// change text
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDP.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_EO_Attendance_CDP.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: 'participant_custom_data_form_process',

					// send form data
					participant_id: parseInt( participant_id ),
					civicrm_eo_cdp_hours: parseInt( hours ),
					civicrm_eo_cdp_minutes: parseInt( minutes )

				},

				// callback
				function( data, textStatus ) {

					// if success
					if ( textStatus == 'success' ) {

						// update DOM
						me.form_feedback( data );

					} else {

						// show error
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// expected format
				'json'

			);

		};

		/**
		 * Act on the data received from an AJAX form submission.
		 *
		 * @since 0.2.2
		 *
		 * @param {Array} data The data received from the server
		 */
		this.form_feedback = function( data ) {

			/*
			// trace
			if ( console.log ) {
				console.log( 'cdp data', data );
			}
			*/

			// hide form's enclosing list
			$('#civicrm_eo_cdp_' + data.participant_id).parent().parent().slideUp( 'fast', function() {

				// after slide
				var processed, list_item;

				// are we still in progress?
				if ( data.markup != '' ) {

					// process into jQuery object
					processed = $( $.parseHTML( data.markup ) );

					// target enclosing item
					list_item = $('.civicrm-eo-cdp-participant-id-' + data.participant_id).parent();

					// append to link and show
					processed.appendTo( list_item ).hide().slideDown();

				}

			});

		};

	};

	// init settings
	CiviCRM_EO_Attendance_CDP.settings.init();

	// init list
	CiviCRM_EO_Attendance_CDP.form.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	CiviCRM_EO_Attendance_CDP.form.dom_ready();

}); // end document.ready()



