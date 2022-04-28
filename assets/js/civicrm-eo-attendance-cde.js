/**
 * CiviCRM Event Organiser Attendance "Custom Data (Event)" Javascript.
 *
 * Implements Custom Data (Event) functionality on EO Event pages.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Custom Data (Event) object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2.2
 */
var CiviCRM_EO_Attendance_CDE = CiviCRM_EO_Attendance_CDE || {};

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
	CiviCRM_EO_Attendance_CDE.settings = new function() {

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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDE_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_CDE_Settings.localisation;
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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDE_Settings ) {
				me.settings = CiviCRM_EO_Attendance_CDE_Settings.settings;
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
	CiviCRM_EO_Attendance_CDE.form = new function() {

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
			var link = $('span.civicrm-eo-custom-data-event');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object.
			 */
			link.on( 'click', function( event ) {

				// Declare vars.
				var classes, civi_event_id = 0;

				// Grab classes.
				classes = $(this).prop( 'class' ).split( ' ' );

				// Loop to find the one we want.
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-cde-event-id-' ) ) {
						civi_event_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// If already clicked.
				if ( CiviCRM_EO_Attendance_CDE.settings.get_clicked( civi_event_id ) ) {

					// Toggle form and bail.
					$('.civicrm-eo-cde-event-id-' + civi_event_id).next('ul').slideToggle();
					return;

				}

				// Show spinner.
				$(this).next( '.civicrm-eo-loading' ).show();

				// Register click.
				CiviCRM_EO_Attendance_CDE.settings.set_clicked( civi_event_id );

				// Request form for this Event.
				me.form_get( civi_event_id );

			});

		};

		/**
		 * Get form for an Event via AJAX request.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent.
		 */
		this.form_get = function( civi_event_id ) {

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_CDE.settings.get_setting( 'ajax_url' ),

				{

					// Token received by WordPress.
					action: 'event_custom_data_form_get',

					// Send Event ID.
					civi_event_id: civi_event_id

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
				item = $('.civicrm-eo-cde-event-id-' + data.civi_event_id);

				// Hide spinner.
				item.next( '.civicrm-eo-loading' ).hide();

				// Process into jQuery object.
				processed = $( $.parseHTML( data.markup ) );

				// Target enclosing item.
				list_item = item.parent();

				// Append to link and show.
				processed.appendTo( list_item ).hide().slideDown();

				// Add listener for form submit button.
				me.form_rendered( data.civi_event_id );

			}

		};

		/**
		 * Perform tasks once the form has been rendered.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent.
		 */
		this.form_rendered = function( civi_event_id ) {

			// Declare vars.
			var submit_button = $('#civicrm_eo_cde_submit_' + civi_event_id);

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

				// Grab Event ID.
				civi_event_id = parseInt( button.prop('id').split('_')[4] );

				// Submit form.
				me.form_submit( civi_event_id, button );

			});

		};

		/**
		 * Submit the form for an Event via AJAX.
		 *
		 * @since 0.2.2
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
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'empty' )
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
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'negative' )
				);
				return;
			}

			// Basic validation of totals.
			totals_sum = parseInt( boys ) + parseInt( girls );
			if ( parseInt( total ) !== totals_sum ) {
				// These should match.
				//console.log( 'Totals should match', parseInt( total ), totals_sum );
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'match' )
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
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'range' )
				);
				return;
			}

			// Change text.
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_CDE.settings.get_setting( 'ajax_url' ),

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
		 * @since 0.2.2
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

			// Hide form's enclosing list.
			$('#civicrm_eo_cde_' + data.civi_event_id).parent().parent().slideUp( 'fast', function() {

				// After slide.
				var processed, list_item;

				// Are we still in progress?
				if ( data.markup != '' ) {

					// Process into jQuery object.
					processed = $( $.parseHTML( data.markup ) );

					// Target enclosing item.
					list_item = $('.civicrm-eo-cde-event-id-' + data.civi_event_id).parent();

					// Append to link and show.
					processed.appendTo( list_item ).hide().slideDown();

				}

			});

		};

	};

	// Init settings.
	CiviCRM_EO_Attendance_CDE.settings.init();

	// Init list.
	CiviCRM_EO_Attendance_CDE.form.init();

} )( jQuery );

/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	CiviCRM_EO_Attendance_CDE.form.dom_ready();

}); // End document.ready()
