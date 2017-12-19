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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDE_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_CDE_Settings.localisation;
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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_CDE_Settings ) {
				me.settings = CiviCRM_EO_Attendance_CDE_Settings.settings;
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
	CiviCRM_EO_Attendance_CDE.form = new function() {

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
			var link = $('span.civicrm-eo-custom-data-event');

			/**
			 * Add a click event listener to Participant Custom Data forms.
			 *
			 * @param {Object} event The event object
			 */
			link.on( 'click', function( event ) {

				// declare vars
				var classes, civi_event_id = 0;

				// grab classes
				classes = $(this).prop( 'class' ).split( ' ' );

				// loop to find the one we want
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-cde-event-id-' ) ) {
						civi_event_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// if already clicked
				if ( CiviCRM_EO_Attendance_CDE.settings.get_clicked( civi_event_id ) ) {

					// toggle form and bail
					$('.civicrm-eo-cde-event-id-' + civi_event_id).next('ul').slideToggle();
					return;

				}

				// show spinner
				$(this).next( '.civicrm-eo-loading' ).show();

				// register click
				CiviCRM_EO_Attendance_CDE.settings.set_clicked( civi_event_id );

				// request custom data form for this event
				me.form_get( civi_event_id );

			});

		};

		/**
		 * Get form for an event via AJAX request.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent
		 */
		this.form_get = function( civi_event_id ) {

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_EO_Attendance_CDE.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: 'event_custom_data_form_get',

					// send event ID
					civi_event_id: civi_event_id

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
				item = $('.civicrm-eo-cde-event-id-' + data.civi_event_id);

				// hide spinner
				item.next( '.civicrm-eo-loading' ).hide();

				// process into jQuery object
				processed = $( $.parseHTML( data.markup ) );

				// target enclosing item
				list_item = item.parent();

				// append to link and show
				processed.appendTo( list_item ).hide().slideDown();

				// add listener for form submit button
				me.form_rendered( data.civi_event_id );

			}

		};

		/**
		 * Perform tasks once the form has been rendered.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent
		 */
		this.form_rendered = function( civi_event_id ) {

			// declare vars
			var submit_button = $('#civicrm_eo_cde_submit_' + civi_event_id);

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
				var civi_event_id = 0;

				// grab event ID
				civi_event_id = parseInt( button.prop('id').split('_')[4] );

				// submit custom data form
				me.form_submit( civi_event_id, button );

			});

		};

		/**
		 * Submit the custom data form for an event via AJAX.
		 *
		 * @since 0.2.2
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent
		 * @param {Object} submit_button The form's submit button jQuery object
		 */
		this.form_submit = function( civi_event_id, submit_button ) {

			// grab form values
			var total = $('#civicrm_eo_cde_total_' + civi_event_id).val(),
				boys = $('#civicrm_eo_cde_boys_' + civi_event_id).val(),
				girls = $('#civicrm_eo_cde_girls_' + civi_event_id).val(),
				low = $('#civicrm_eo_cde_low_' + civi_event_id).val(),
				high = $('#civicrm_eo_cde_high_' + civi_event_id).val(),
				totals_sum;

			// can't have empty values
			if ( total == '' || boys == '' || girls == '' || low == '' || high == '' ) {
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'empty' )
				);
				return;
			}

			// can't have negative values
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

			// basic validation of totals
			totals_sum = parseInt( boys ) + parseInt( girls );
			if ( parseInt( total ) !== totals_sum ) {
				// these should match
				//console.log( 'totals should match', parseInt( total ), totals_sum );
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'match' )
				);
				return;
			}

			// can't have zero values for age
			if (
				parseInt( low ) === 0 ||
				parseInt( high ) === 0
			) {
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE_Widget.settings.get_localisation( 'positive' )
				);
				return;
			}

			// check age range
			if ( parseInt( low ) > parseInt( high ) ) {
				// this would make no sense
				//console.log( 'age range is screwy', low, high );
				$('.civicrm_eo_cde_error_' + civi_event_id).html(
					CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'range' )
				);
				return;
			}

			// change text
			submit_button.attr( 'value', CiviCRM_EO_Attendance_CDE.settings.get_localisation( 'processing' ) );
			submit_button.prop( 'disabled', true );

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_EO_Attendance_CDE.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: 'event_custom_data_form_process',

					// send form data
					civi_event_id: parseInt( civi_event_id ),
					civicrm_eo_cde_total: parseInt( total ),
					civicrm_eo_cde_boys: parseInt( boys ),
					civicrm_eo_cde_girls: parseInt( girls ),
					civicrm_eo_cde_low: parseInt( low ),
					civicrm_eo_cde_high: parseInt( high )

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
				console.log( 'cde data', data );
			}
			*/

			// hide form's enclosing list
			$('#civicrm_eo_cde_' + data.civi_event_id).parent().parent().slideUp( 'fast', function() {

				// after slide
				var processed, list_item;

				// are we still in progress?
				if ( data.markup != '' ) {

					// process into jQuery object
					processed = $( $.parseHTML( data.markup ) );

					// target enclosing item
					list_item = $('.civicrm-eo-cde-event-id-' + data.civi_event_id).parent();

					// append to link and show
					processed.appendTo( list_item ).hide().slideDown();

				}

			});

		};

	};

	// init settings
	CiviCRM_EO_Attendance_CDE.settings.init();

	// init list
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

}); // end document.ready()



