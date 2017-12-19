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

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2.1
		 */
		this.init = function() {

			// init settings
			me.init_settings();

		};

		// init settings array
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
	 * Create List Handling Singleton.
	 *
	 * @since 0.2.1
	 */
	CiviCRM_EO_Attendance_PL.list = new function() {

		// prevent reference collisions
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

			// enable listeners
			me.listeners();

		};

		/**
		 * Set up listeners.
		 *
		 * @since 0.2.1
		 */
		this.listeners = function() {

			// declare vars
			var link = $('a.civicrm-eo-participant-link');

			/**
			 * Add a click event listener to Participant Listing Page links.
			 *
			 * @param {Object} event The event object
			 */
			link.on( 'click', function( event ) {

				// declare vars
				var classes, civi_event_id = 0;

				// prevent form submission
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// grab classes
				classes = $(this).prop( 'class' ).split( ' ' );

				// loop to find the one we want
				for (var i = 0, item; item = classes[i++];) {
					if ( item.match( 'civicrm-eo-pl-event-id-' ) ) {
						civi_event_id = parseInt( item.split('-')[5] );
						break;
					}
				}

				// bail if already clicked
				if ( CiviCRM_EO_Attendance_PL.settings.get_clicked( civi_event_id ) ) {

					// toggle list and bail
					$('.civicrm-eo-pl-event-id-' + civi_event_id).next('ul').slideToggle();
					return;

				}

				// show spinner
				$(this).next( '.civicrm-eo-loading' ).show();

				// register click
				CiviCRM_EO_Attendance_PL.settings.set_clicked( civi_event_id );

				// request participants
				me.send( civi_event_id );

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.1
		 *
		 * @param {Integer} civi_event_id The numeric ID of the CiviEvent
		 */
		this.send = function( civi_event_id ) {

			// use jQuery post
			$.post(

				// URL to post to
				CiviCRM_EO_Attendance_PL.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: 'participants_list_get',

					// send event ID
					civi_event_id: civi_event_id

				},

				// callback
				function( data, textStatus ) {

					// if success
					if ( textStatus == 'success' ) {

						// update progress bar
						me.update( data );

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
		 * @since 0.2.1
		 *
		 * @param {Array} data The data received from the server
		 */
		this.update = function( data ) {

			// vars
			var item, processed, list_item;

			// are we still in progress?
			if ( data.markup != '' ) {

				// find our item
				item = $('.civicrm-eo-pl-event-id-' + data.civi_event_id);

				// hide spinner
				item.next( '.civicrm-eo-loading' ).hide();

				// process into jQuery object
				processed = $( $.parseHTML( data.markup ) );

				// target enclosing list item
				list_item = item.parent();

				// append to link and show
				processed.appendTo( list_item ).hide().slideDown();

			}

		};

	};

	// init settings
	CiviCRM_EO_Attendance_PL.settings.init();

	// init list
	CiviCRM_EO_Attendance_PL.list.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2.1
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	CiviCRM_EO_Attendance_PL.list.dom_ready();

}); // end document.ready()



