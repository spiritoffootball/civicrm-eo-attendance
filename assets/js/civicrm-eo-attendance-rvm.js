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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_RVM_Settings ) {
				me.localisation = CiviCRM_EO_Attendance_RVM_Settings.localisation;
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
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_RVM_Settings ) {
				me.settings = CiviCRM_EO_Attendance_RVM_Settings.settings;
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

	};

	/**
	 * Create Form Handling Singleton.
	 *
	 * @since 0.2.2
	 */
	CiviCRM_EO_Attendance_RVM.form = new function() {

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
			var link = $('span.civicrm-eo-rendezvous-register-all'),
				cancel = $('.civicrm_eo_rvm_cancel'),
				submit = $('.civicrm_eo_rvm_submit');

			/**
			 * Add a click event listener to Register links.
			 *
			 * @param {Object} event The event object
			 */
			link.on( 'click', function( event ) {

				// declare vars
				var classes,
					civi_event_id = 0,
					attendee_ids_raw = '',
					attendee_ids;

				// grab classes
				classes = $(this).prop( 'class' ).split( ' ' );

				// get event ID
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-rv-event-id-' ) ) {
						civi_event_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// get attendee IDs
				for (var j = 0, item; item = classes[j++];) {
					if ( item.match( 'civicrm-eo-rv-ids-' ) ) {
						attendee_ids_raw = item.split('civicrm-eo-rv-ids-')[1];
						attendee_ids = attendee_ids_raw.split('-');
						break;
					}
				}

				// show div
				$('#civicrm_eo_rvm_' + civi_event_id).show();

			});

			/**
			 * Add a click event listener to Cancel buttons.
			 *
			 * @param {Object} event The event object
			 */
			cancel.on( 'click', function( event ) {

				// declare vars
				var id = $(this).prop( 'id' ),
					civi_event_id = 0;

				// get event ID
				if ( id.match( 'civicrm_eo_rvm_cancel_' ) ) {
					civi_event_id = parseInt( id.split('_')[4] );
				}

				// hide div
				$('#civicrm_eo_rvm_' + civi_event_id).hide();

			});

			/**
			 * Add a click event listener to the submit button.
			 *
			 * @param {Object} event The event object
			 */
			submit.on( 'click', function( event ) {

				// declare vars
				var button = $(this),
					id = $(this).prop( 'id' ),
					civi_event_id = 0,
					event_leader = 0,
					event_leader_exists = 0,
					register_data = {},
					unregister_data = {};

				// bail if disabled
				if ( button.prop('disabled') ) {
					return;
				}

				// get event ID
				if ( id.match( 'civicrm_eo_rvm_submit_' ) ) {
					civi_event_id = parseInt( id.split('_')[4] );
				}

				// get event leader ID
				event_leader = parseInt( $( '#civicrm_eo_rvm_' + civi_event_id + '_leader' ).val() );

				/**
				 * Loop through options and check for at least one event leader.
				 *
				 * @since 1.4.8
				 */
				$('#civicrm_eo_rvm_' + civi_event_id + ' select').each( function(i) {

					// grab value
					var role = parseInt( $(this).val() );

					// do we have one?
					if ( role === event_leader ) {
						event_leader_exists = 1;
					}

				});

				// bail if we have no event leader
				if ( event_leader_exists === 0 ) {
					$('#civicrm_eo_rvm_error_' + civi_event_id).html(
						CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'leader' )
					);
					return;
				}

				// clear error
				$('#civicrm_eo_rvm_error_' + civi_event_id).html( '' );

				// change text
				button.html( CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'processing' ) );
				button.prop( 'disabled', true );

				/**
				 * Loop through attendees and grab data.
				 *
				 * @since 1.4.8
				 */
				$('#civicrm_eo_rvm_' + civi_event_id + ' li').each( function(i) {

					// get class
					var attendee_id = 0,
						attendee_role = 0;

					// get attendee ID
					attendee_id = parseInt( $(this).prop( 'class' ).split('_')[4] );

					// get checked/unchecked
					checked = $('#civicrm_eo_rvm_event_' + civi_event_id + '_attendee_' + attendee_id).prop( 'checked' );

					// construct data arrays based on checked status
					if ( checked ) {

						// get role
						attendee_role = parseInt(
							$('#civicrm_eo_rvm_role_event_' + civi_event_id + '_attendee_' + attendee_id).val()
						);

						// register
						register_data[attendee_id] = attendee_role;

					// otherwise unregister
					} else {
						unregister_data[attendee_id] = 0;
					}

				});

				console.log( 'civi_event_id', civi_event_id );
				console.log( 'register', register_data );
				console.log( 'unregister', unregister_data );

				// use jQuery post
				$.post(

					// URL to post to
					CiviCRM_EO_Attendance_RVM.settings.get_setting( 'ajax_url' ),

					{

						// token received by WordPress
						action: 'event_attendance_form_process',

						// send form data
						civi_event_id: parseInt( civi_event_id ),
						register: register_data,
						unregister: unregister_data

					},

					// callback
					function( data, textStatus ) {

						// if success
						if ( textStatus == 'success' ) {

							// update DOM
							me.form_feedback( data, button );

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

			});

		};

		/**
		 * Act on the data received from an AJAX form submission.
		 *
		 * @since 0.2.2
		 *
		 * @param {Array} data The data received from the server
		 */
		this.form_feedback = function( data, button ) {

			/*
			// trace
			if ( console.log ) {
				console.log( 'rvm data', data );
			}
			*/

			// did we get an error?
			if ( data.error != '0' ) {

				// keep as submit
				button.html( CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'submit' ) );

				// enable button
				button.prop( 'disabled', false );

				// show error
				$('#civicrm_eo_rvm_error_' + data.civi_event_id).html(
					data.markup
				);

				return;
			}

			// change to update button
			button.html( CiviCRM_EO_Attendance_RVM.settings.get_localisation( 'update' ) );

			// show markup
			$('#civicrm_eo_rvm_success_' + data.civi_event_id).html(
				data.markup
			);

			// pause for a bit
			setTimeout( function() {

				// hide div
				$('#civicrm_eo_rvm_' + data.civi_event_id).hide();

				// enable button
				button.prop( 'disabled', false );

				// clear markup
				$('#civicrm_eo_rvm_success_' + data.civi_event_id).html( '' );

			}, 1500 );

		};

	};

	// init settings
	CiviCRM_EO_Attendance_RVM.settings.init();

	// init list
	CiviCRM_EO_Attendance_RVM.form.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	CiviCRM_EO_Attendance_RVM.form.dom_ready();

}); // end document.ready()



