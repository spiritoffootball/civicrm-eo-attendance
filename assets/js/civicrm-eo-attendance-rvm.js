/**
 * CiviCRM Event Organiser Attendance "Rendez Vous Management" Javascript.
 *
 * Implements Rendez Vous management functionality.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Rendez Vous Management object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2.2
 */
var CiviCRM_EO_Attendance_RVM = CiviCRM_EO_Attendance_RVM || {};

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
	CiviCRM_EO_Attendance_RVM.settings = new function() {

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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_RVM_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_RVM_Settings.localisation;
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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_RVM_Settings ) {
				me.settings = CiviCRM_EO_Attendance_RVM_Settings.settings;
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

	};

	/**
	 * Create Form Handling Singleton.
	 *
	 * @since 0.2.2
	 */
	CiviCRM_EO_Attendance_RVM.form = new function() {

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
			var link = $('span.civicrm-eo-rendezvous-register-all'),
				cancel = $('.civicrm_eo_rvm_cancel'),
				submit = $('.civicrm_eo_rvm_submit');

			/**
			 * Add a click event listener to Register links.
			 *
			 * @param {Object} event The event object.
			 */
			link.on( 'click', function( event ) {

				// Declare vars.
				var classes,
					civi_event_id = 0,
					attendee_ids_raw = '',
					attendee_ids;

				// Grab classes.
				classes = $(this).prop( 'class' ).split( ' ' );

				// Get Event ID.
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-rv-event-id-' ) ) {
						civi_event_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// Get attendee IDs.
				for (var j = 0, item; item = classes[j++];) {
					if ( item.match( 'civicrm-eo-rv-ids-' ) ) {
						attendee_ids_raw = item.split('civicrm-eo-rv-ids-')[1];
						attendee_ids = attendee_ids_raw.split('-');
						break;
					}
				}

				// Show div.
				$('#civicrm_eo_rvm_' + civi_event_id).show();

			});

			/**
			 * Add a click event listener to Cancel buttons.
			 *
			 * @param {Object} event The event object.
			 */
			cancel.on( 'click', function( event ) {

				// Declare vars.
				var id = $(this).prop( 'id' ),
					civi_event_id = 0;

				// Get Event ID.
				if ( id.match( 'civicrm_eo_rvm_cancel_' ) ) {
					civi_event_id = parseInt( id.split('_')[4] );
				}

				// Hide div.
				$('#civicrm_eo_rvm_' + civi_event_id).hide();

			});

			/**
			 * Add a click event listener to the submit button.
			 *
			 * @param {Object} event The event object.
			 */
			submit.on( 'click', function( event ) {

				// Declare vars.
				var button = $(this),
					id = $(this).prop( 'id' ),
					civi_event_id = 0,
					event_leader = 0,
					event_leader_exists = 0,
					register_data = {},
					unregister_data = {};

				// Bail if disabled.
				if ( button.prop('disabled') ) {
					return;
				}

				// Get Event ID.
				if ( id.match( 'civicrm_eo_rvm_submit_' ) ) {
					civi_event_id = parseInt( id.split('_')[4] );
				}

				// Get Event Leader ID.
				event_leader = parseInt( $( '#civicrm_eo_rvm_' + civi_event_id + '_leader' ).val() );

				/**
				 * Loop through options and check for at least one Event Leader.
				 *
				 * @since 1.4.8
				 */
				$('#civicrm_eo_rvm_' + civi_event_id + ' select').each( function(i) {

					// Grab value.
					var role = parseInt( $(this).val() );

					// Do we have one?
					if ( role === event_leader ) {
						event_leader_exists = 1;
					}

				});

				// Bail if we have no Event Leader.
				if ( event_leader_exists === 0 ) {
					$('#civicrm_eo_rvm_error_' + civi_event_id).html(
						CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'leader' )
					);
					return;
				}

				// Clear error.
				$('#civicrm_eo_rvm_error_' + civi_event_id).html( '' );

				// Change text.
				button.html( CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'processing' ) );
				button.prop( 'disabled', true );

				/**
				 * Loop through attendees and grab data.
				 *
				 * @since 1.4.8
				 */
				$('#civicrm_eo_rvm_' + civi_event_id + ' li').each( function(i) {

					// Get class.
					var attendee_id = 0,
						attendee_role = 0;

					// Get attendee ID.
					attendee_id = parseInt( $(this).prop( 'class' ).split('_')[4] );

					// Get checked/unchecked.
					checked = $('#civicrm_eo_rvm_event_' + civi_event_id + '_attendee_' + attendee_id).prop( 'checked' );

					// Construct data arrays based on checked status.
					if ( checked ) {

						// Get role.
						attendee_role = parseInt(
							$('#civicrm_eo_rvm_role_event_' + civi_event_id + '_attendee_' + attendee_id).val()
						);

						// Register.
						register_data[attendee_id] = attendee_role;

					// Otherwise unregister.
					} else {
						unregister_data[attendee_id] = 0;
					}

				});

				//console.log( 'civi_event_id', civi_event_id );
				//console.log( 'register', register_data );
				//console.log( 'unregister', unregister_data );

				// Use jQuery post
				$.post(

					// URL to post to.
					CiviCRM_EO_Attendance_RVM.settings.get_setting( 'ajax_url' ),

					{

						// Token received by WordPress.
						action: 'event_attendance_form_process',

						// Send form data.
						civi_event_id: parseInt( civi_event_id ),
						register: register_data,
						unregister: unregister_data

					},

					// Callback.
					function( data, textStatus ) {

						// If success.
						if ( textStatus == 'success' ) {

							// Update DOM.
							me.form_feedback( data, button );

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

			});

		};

		/**
		 * Act on the data received from an AJAX form submission.
		 *
		 * @since 0.2.2
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.form_feedback = function( data, button ) {

			/*
			// Trace.
			if ( console.log ) {
				console.log( 'rvm data', data );
			}
			*/

			// Did we get an error?
			if ( data.error != '0' ) {

				// Keep as submit.
				button.html( CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'submit' ) );

				// Enable button.
				button.prop( 'disabled', false );

				// Show error.
				$('#civicrm_eo_rvm_error_' + data.civi_event_id).html(
					data.markup
				);

				return;
			}

			// Change to update button.
			button.html( CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'update' ) );

			// Show markup.
			$('#civicrm_eo_rvm_success_' + data.civi_event_id).html(
				data.markup
			);

			// Pause for a bit.
			setTimeout( function() {

				// Hide div.
				$('#civicrm_eo_rvm_' + data.civi_event_id).hide();

				// Enable button.
				button.prop( 'disabled', false );

				// Clear markup.
				$('#civicrm_eo_rvm_success_' + data.civi_event_id).html( '' );

			}, 1500 );

		};

	};

	// Init settings.
	CiviCRM_EO_Attendance_RVM.settings.init();

	// Init list.
	CiviCRM_EO_Attendance_RVM.form.init();

} )( jQuery );

/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_EO_Attendance_RVM.form.dom_ready();

}); // End document.ready()
