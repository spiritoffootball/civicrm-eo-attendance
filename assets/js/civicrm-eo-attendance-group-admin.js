/**
 * CiviCRM Event Organiser Attendance "Group Admin" Javascript.
 *
 * Implements Select2 functionality on Rendez Vous group admin pages.
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

/**
 * Create CiviCRM Event Organiser Attendance Participant Listing object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.5.3
 */
var CiviCRM_EO_Attendance_Group_Admin = CiviCRM_EO_Attendance_Group_Admin || {};

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.5.3
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Select2 Handler.
	 *
	 * @since 0.5.3
	 */
	CiviCRM_EO_Attendance_Group_Admin.select2 = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5.3
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5.3
		 */
		this.dom_ready = function() {

			// Enable listeners.
			$('#_civicrm_eo_event_organizer').select2({
				width: '100%',
				dropdownParent: $('#group-settings-form')
			});

		};

	};

	// Init select2.
	CiviCRM_EO_Attendance_Group_Admin.select2.init();

} )( jQuery );

/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.5.3
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_EO_Attendance_Group_Admin.select2.dom_ready();

});
