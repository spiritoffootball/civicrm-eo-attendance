/**
 * CiviCRM Event Organiser Attendance "Participant Listing" Javascript.
 *
 * Implements Participant Listing functionality on EO Event pages.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Participant Listing object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2.1
 */
var CiviCRM_EO_Attendance_PL = CiviCRM_EO_Attendance_PL || {};

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.2.1
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Singleton.
	 *
	 * @since 0.2.1
	 */
	CiviCRM_EO_Attendance_PL.settings = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.1
		 */
		this.init = function() {

			// Init settings.
			me.init_settings();

		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.2.1
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CiviCRM_EO_Attendance_PL_Settings ) {
				me.settings = CiviCRM_EO_Attendance_PL_Settings.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.2.1
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
	 * Create List Handling Singleton.
	 *
	 * @since 0.2.1
	 */
	CiviCRM_EO_Attendance_PL.list = new function() {

		// Prevent reference collisions
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.1
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.1
		 */
		this.dom_ready = function() {

			// Enable listeners
			me.listeners();

		};

		/**
		 * Set up listeners.
		 *
		 * @since 0.2.1
		 */
		this.listeners = function() {

			// Declare vars.
			var link = $('a.civicrm-eo-participant-link');

			/**
			 * Add a click event listener to Participant Listing Page links.
			 *
			 * @param {Object} event The event object.
			 */
			link.on( 'click', function( event ) {

				// Declare vars.
				var classes, civi_event_id = 0;

				// Prevent form submission.
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// Grab classes.
				classes = $(this).prop( 'class' ).split( ' ' );

				// Loop to find the one we want.
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-pl-event-id-' ) ) {
						civi_event_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// Bail if already clicked.
				if ( CiviCRM_EO_Attendance_PL.settings.get_clicked( civi_event_id ) ) {

					// Toggle list and bail.
					$('.civicrm-eo-pl-event-id-' + civi_event_id).next('ul').slideToggle();
					return;

				}

				// Show spinner.
				$(this).next( '.civicrm-eo-loading' ).show();

				// Register click.
				CiviCRM_EO_Attendance_PL.settings.set_clicked( civi_event_id );

				// Request Participants.
				me.send( civi_event_id );

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.1
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent.
		 */
		this.send = function( civi_event_id ) {

			// Use jQuery post.
			$.post(

				// URL to post to.
				CiviCRM_EO_Attendance_PL.settings.get_setting( 'ajax_url' ),

				{

					// Token received by WordPress.
					action: 'participants_list_get',

					// Send Event ID.
					civi_event_id: civi_event_id

				},

				// Callback.
				function( data, textStatus ) {

					// If success.
					if ( textStatus == 'success' ) {

						// Update progress bar.
						me.update( data );

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
		 * @since 0.2.1
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.update = function( data ) {

			// Vars.
			var item, processed, list_item;

			// Are we still in progress?
			if ( data.markup != '' ) {

				// Find our item.
				item = $('.civicrm-eo-pl-event-id-' + data.civi_event_id);

				// Hide spinner.
				item.next( '.civicrm-eo-loading' ).hide();

				// Process into jQuery object.
				processed = $( $.parseHTML( data.markup ) );

				// Target enclosing list item.
				list_item = item.parent();

				// Append to link and show.
				processed.appendTo( list_item ).hide().slideDown();

			}

		};

	};

	// Init settings.
	CiviCRM_EO_Attendance_PL.settings.init();

	// Init list.
	CiviCRM_EO_Attendance_PL.list.init();

} )( jQuery );

/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.1
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_EO_Attendance_PL.list.dom_ready();

}); // End document.ready()
