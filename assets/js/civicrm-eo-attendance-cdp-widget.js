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

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5.2
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
		 * @since 0.5.2
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
		 * @since 0.5.2
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
	 * @since 0.5.2
	 */
	CiviCRM_EO_Attendance_CDP_Widget.form = new function() {

		// Prevent reference collisions.
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

			// Enable listeners.
			me.listeners();

		};

		/**
		 * Set up listeners.
		 *
		 * @since 0.5.2
		 */
		this.listeners = function() {

			// Declare vars.
			var toggle = $('.widget span.civicrm-eo-cdp-feedback'),
				submit_button = $('form.civicrm_eo_cdp p.submit input');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object.
			 */
			toggle.on( 'click', function( event ) {

				// Grab participant ID.
				var participant_id = parseInt( $(this).prop('id').split('-')[4] );

				// Bail if already submitted.
				if ( CiviCRM_EO_Attendance_CDP_Widget.settings.get_clicked( participant_id ) ) {
					return;
				}

				// Toggle form.
				$(this).next( '.civicrm_eo_cdp' ).slideToggle();

			});

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
		 * @since 0.5.2
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
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'empty' )
				);
				return;
			}

			// Can't have non-numeric values.
			if ( ! $.isNumeric( hours ) || ! $.isNumeric( minutes ) ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'numeric' )
				);
				return;
			}

			// Can't have non-integer values.
			if ( Math.floor( hours ) != hours || Math.floor( minutes ) != minutes ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'integer' )
				);
				return;
			}

			// Can't have negative values.
			if ( parseInt( hours ) < 0 || parseInt( minutes ) < 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'negative' )
				);
				return;
			}

			// Can't be no time at all.
			if ( parseInt( hours ) === 0 && parseInt( minutes ) === 0 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'zero' )
				);
				return;
			}

			// Can't have minutes more than 59.
			if ( parseInt( minutes ) > 59 ) {
				$('.civicrm_eo_cdp_error_' + participant_id).html(
					CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'mins' )
				);
				return;
			}

			// Change text.
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDP_Widget.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// Register submission.
			CiviCRM_EO_Attendance_CDP_Widget.settings.set_clicked( participant_id );

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_CDP_Widget.settings.get_setting( 'ajax_url' ),

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
		 * @since 0.5.2
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

			// Hide form.
			$('#civicrm_eo_cdp_' + data.participant_id).slideUp( 'fast', function() {

				// After slide.
				var processed, list_item;

				// Do we have markup?
				if ( data.markup != '' ) {

					// Hide toggle.
					$('#civicrm-eo-cdp-feedback-' + data.participant_id).hide();

					// Process into jQuery object.
					processed = $( $.parseHTML( data.markup ) );

					// Target parent item.
					list_item = $(this).parent();

					// Append to link and show.
					processed.appendTo( list_item ).hide().slideDown();

					// Hide the whole item.
					setTimeout(function () {
						list_item.slideUp( 'fast', function() {

							// Get remaining list items before removal.
							var enclosing = list_item.parent(),
								remaining = list_item.parent().children(),
								feedback = '';

							// Remove this one.
							list_item.remove();

							// Add feedback if there are none remaining.
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

	// Init settings.
	CiviCRM_EO_Attendance_CDP_Widget.settings.init();

	// Init list.
	CiviCRM_EO_Attendance_CDP_Widget.form.init();

} )( jQuery );

/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.5.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_EO_Attendance_CDP_Widget.form.dom_ready();

}); // End document.ready()
