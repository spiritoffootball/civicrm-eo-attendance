/**
 * CiviCRM Event Organiser Attendance "Custom Data (Participant) Widget" Javascript.
 *
 * Implements Custom Data (Participant) functionality for Participant Widget.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Custom Data (Participant) Widget object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.5.2
 */
var CiviCRM_EO_Attendance_CDP_Widget = CiviCRM_EO_Attendance_CDP_Widget || {};



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.5.2
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Singleton.
	 *
	 * @since 0.5.2
	 */
	CiviCRM_EO_Attendance_CDP_Widget.settings = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5.2
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
		 * @since 0.5.2
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDP_Widget_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_CDP_Widget_Settings.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.5.2
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
		 * @since 0.5.2
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDP_Widget_Settings ) {
				me.settings = CiviCRM_EO_Attendance_CDP_Widget_Settings.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.5.2
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
		 * @since 0.5.2
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
		 * @since 0.5.2
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
	 * @since 0.5.2
	 */
	CiviCRM_EO_Attendance_CDP_Widget.form = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Form.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5.2
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5.2
		 */
		this.dom_ready = function() {

			// enable listeners
			me.listeners();

		};

		/**
		 * Set up listeners.
		 *
		 * @since 0.5.2
		 */
		this.listeners = function() {

			// declare vars
			var toggle = $('.widget span.civicrm-eo-cdp-feedback'),
				submit_button = $('form.civicrm_eo_cdp p.submit input');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object
			 */
			toggle.on( 'click', function( event ) {

				// grab participant ID
				var participant_id = parseInt( $(this).prop('id').split('-')[4] );

				// bail if already submitted
				if ( CiviCRM_EO_Attendance_CDP_Widget.settings.get_clicked( participant_id ) ) {
					return;
				}

				// toggle form
				$(this).next( '.civicrm_eo_cdp' ).slideToggle();

			});

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
		 * @since 0.5.2
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
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'empty' )
				);
				return;
			}

			// can't have non-numeric values
			if ( ! $.isNumeric( hours ) || ! $.isNumeric( minutes ) ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'numeric' )
				);
				return;
			}

			// can't have non-integer values
			if ( Math.floor( hours ) != hours || Math.floor( minutes ) != minutes ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'integer' )
				);
				return;
			}

			// can't have negative values
			if ( parseInt( hours ) < 0 || parseInt( minutes ) < 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'negative' )
				);
				return;
			}

			// can't be no time at all
			if ( parseInt( hours ) === 0 && parseInt( minutes ) === 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'zero' )
				);
				return;
			}

			// can't have minutes more than 59
			if ( parseInt( minutes ) > 59 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'mins' )
				);
				return;
			}

			// change text
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// register submission
			CiviCRM_EO_Attendance_CDP_Widget.settings.set_clicked( participant_id );

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_EO_Attendance_CDP_Widget.settings.get_setting( 'ajax_url' ),

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
		 * @since 0.5.2
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

			// hide form
			$('#civicrm_eo_cdp_' + data.participant_id).slideUp( 'fast', function() {

				// after slide
				var processed, list_item;

				// do we have markup?
				if ( data.markup != '' ) {

					// hide toggle
					$('#civicrm-eo-cdp-feedback-' + data.participant_id).hide();

					// process into jQuery object
					processed = $( $.parseHTML( data.markup ) );

					// target parent item
					list_item = $(this).parent();

					// append to link and show
					processed.appendTo( list_item ).hide().slideDown();

					// hide the whole item
					setTimeout(function () {
						list_item.slideUp( 'fast', function() {

							// get remaining list items before removal
							var enclosing = list_item.parent(),
								remaining = list_item.parent().children(),
								feedback = '';

							// remove this one
							list_item.remove();

							// add feedback if there are none remaining
							if ( remaining.length == 1 ) {
								feedback = $( $.parseHTML(
									'<li class="civicrm-eo-cdp-widget cdp-up-to-date">' +
										CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'complete' ) +
									'</li>'
								) );
								feedback.appendTo( enclosing ).hide().slideDown();
							}

						});
					}, 2000 );

				}

			});

		};

	};

	// init settings
	CiviCRM_EO_Attendance_CDP_Widget.settings.init();

	// init list
	CiviCRM_EO_Attendance_CDP_Widget.form.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.5.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	CiviCRM_EO_Attendance_CDP_Widget.form.dom_ready();

}); // end document.ready()



