/**
 * CiviCRM Event Organiser Attendance "Custom Data (Event)" Widget Javascript.
 *
 * Implements Custom Data (Event) functionality for Event Widget.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Custom Data (Event) Widget object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.5.2
 */
var CiviCRM_EO_Attendance_CDE_Widget = CiviCRM_EO_Attendance_CDE_Widget || {};



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
	CiviCRM_EO_Attendance_CDE_Widget.settings = new function() {

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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDE_Widget_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_CDE_Widget_Settings.localisation;
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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDE_Widget_Settings ) {
				me.settings = CiviCRM_EO_Attendance_CDE_Widget_Settings.settings;
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

		// Init clicked array
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
	CiviCRM_EO_Attendance_CDE_Widget.form = new function() {

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

			// Declare vars
			var toggle = $('.widget span.civicrm-eo-cde-feedback'),
				submit_button = $('form.civicrm_eo_cde p.submit input');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object.
			 */
			toggle.on( 'click', function( event ) {

				// Grab event ID.
				civi_event_id = parseInt( $(this).prop('id').split('-')[4] );

				// Bail if already submitted.
				if ( CiviCRM_EO_Attendance_CDE_Widget.settings.get_clicked( civi_event_id ) ) {
					return;
				}

				// Toggle form.
				$(this).next( '.civicrm_eo_cde' ).slideToggle();

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
				var civi_event_id = 0;

				// Grab event ID.
				civi_event_id = parseInt( button.prop('id').split('_')[4] );

				// Submit custom data form.
				me.form_submit( civi_event_id, button );

			});

		};

		/**
		 * Submit the custom data form for an event via AJAX.
		 *
		 * @since 0.5.2
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent.
		 * @param {Object} submit_button The form's submit button jQuery object.
		 */
		this.form_submit = function( civi_event_id, submit_button ) {

			// Grab form values.
			var total = $('#civicrm_eo_cde_total_' + civi_event_id).val(),
				boys = $('#civicrm_eo_cde_boys_' + civi_event_id).val(),
				girls = $('#civicrm_eo_cde_girls_' + civi_event_id).val(),
				low = $('#civicrm_eo_cde_low_' + civi_event_id).val(),
				high = $('#civicrm_eo_cde_high_' + civi_event_id).val(),
				totals_sum;

			// Can't have empty values.
			if ( total == '' || boys == '' || girls == '' || low == '' || high == '' ) {
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'empty' )
				);
				return;
			}

			// Can't have negative values.
			if (
				parseInt( total ) < 0 ||
				parseInt( boys ) < 0 ||
				parseInt( girls ) < 0 ||
				parseInt( low ) < 0 ||
				parseInt( high ) < 0
			) {
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'negative' )
				);
				return;
			}

			// Basic validation of totals.
			totals_sum = parseInt( boys ) + parseInt( girls );
			if ( parseInt( total ) !== totals_sum ) {
				// These should match.
				//console.log( 'Totals should match', parseInt( total ), totals_sum );
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'match' )
				);
				return;
			}

			// Can't have zero values for age.
			if (
				parseInt( low ) === 0 ||
				parseInt( high ) === 0
			) {
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'positive' )
				);
				return;
			}

			// Check age range.
			if ( parseInt( low ) > parseInt( high ) ) {
				// This would make no sense.
				//console.log( 'Age range is screwy', low, high );
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'range' )
				);
				return;
			}

			// Change text.
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// Register click.
			CiviCRM_EO_Attendance_CDE_Widget.settings.set_clicked( civi_event_id );

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_CDE_Widget.settings.get_setting( 'ajax_url' ),

				{

					// Token received by WordPress.
					action: 'event_custom_data_form_process',

					// Send form data.
					civi_event_id: parseInt( civi_event_id ),
					civicrm_eo_cde_total: parseInt( total ),
					civicrm_eo_cde_boys: parseInt( boys ),
					civicrm_eo_cde_girls: parseInt( girls ),
					civicrm_eo_cde_low: parseInt( low ),
					civicrm_eo_cde_high: parseInt( high )

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
				console.log( 'cde data', data );
			}
			*/

			// Hide form.
			$('#civicrm_eo_cde_' + data.civi_event_id).slideUp( 'fast', function() {

				// After slide.
				var processed, list_item;

				// Do we have markup?
				if ( data.markup != '' ) {

					// Hide toggle.
					$('#civicrm-eo-cde-feedback-' + data.civi_event_id).hide();

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
									'<li class="civicrm-eo-cde-widget cde-up-to-date">' +
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
	CiviCRM_EO_Attendance_CDE_Widget.settings.init();

	// Init list.
	CiviCRM_EO_Attendance_CDE_Widget.form.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.5.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_EO_Attendance_CDE_Widget.form.dom_ready();

}); // End document.ready()



