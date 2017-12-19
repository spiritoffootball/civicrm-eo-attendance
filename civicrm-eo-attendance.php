<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM Event Organiser Attendance
Description: Attendance functionality for CiviCRM Event Organiser plugin.
Version: 0.5.2
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: https://github.com/christianwach/civicrm-eo-attendance
Text Domain: civicrm-eo-attendance
Domain Path: /languages
--------------------------------------------------------------------------------
*/



// set our version here
define( 'CIVICRM_EO_ATTENDANCE_VERSION', '0.5.2' );

// store reference to this file
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_FILE' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_URL' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_URL', plugin_dir_url( CIVICRM_EO_ATTENDANCE_FILE ) );
}

// store PATH to this plugin's directory
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_PATH' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_PATH', plugin_dir_path( CIVICRM_EO_ATTENDANCE_FILE ) );
}



/**
 * CiviCRM Event Organiser Attendance Class.
 *
 * A class that encapsulates this plugin's functionality.
 *
 * @since 0.1
 */
class CiviCRM_Event_Organiser_Attendance {

	/**
	 * CiviCRM Event Organiser plugin reference.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $civicrm_eo The plugin reference
	 */
	public $civicrm_eo;

	/**
	 * Participant Listing object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $participant_listing The Participant Listing object
	 */
	public $participant_listing;

	/**
	 * Event Leader object.
	 *
	 * @since 0.3
	 * @access public
	 * @var object $event_leader The Event Leader object
	 */
	public $event_leader;

	/**
	 * Event Custom Data object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var object $custom_data_event The Event Custom Data object
	 */
	public $custom_data_event;

	/**
	 * Participant Custom Data object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var object $custom_data_participant The Participant Custom Data object
	 */
	public $custom_data_participant;

	/**
	 * Event Sharing object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var object $event_sharing The Event Sharing object
	 */
	public $event_sharing;

	/**
	 * Event Paid object.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var object $event_paid The Event Paid object
	 */
	public $event_paid;

	/**
	 * Rendez Vous Manager object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var object $event_paid The Rendez Vous Manager object
	 */
	public $rendez_vous;



	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// initialise
		$this->initialise();

		// translation
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// external references
		add_action( 'plugins_loaded', array( $this, 'setup_objects' ), 20 );

	}



	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.3
	 */
	public function activate() {

		// set up objects
		$this->setup_objects();

		// pass to classes that need activation
		$this->custom_data_participant->activate();
		$this->custom_data_event->activate();
		$this->rendez_vous->activate();

	}



	/**
	 * Do stuff on plugin deactivation.
	 *
	 * @since 0.3
	 */
	public function deactivate() {

		// pass to classes that need deactivation
		$this->rendez_vous->deactivate();

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// include files
		$this->include_files();

		// add actions and filters
		$this->register_hooks();

	}



	/**
	 * Include files.
	 *
	 * @since 0.1
	 */
	public function include_files() {

		// load our Participant Listing class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-participant-listing.php' );

		// load our Event Leader class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-event-leader.php' );

		// load our Event Paid class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-event-paid.php' );

		// load our Participant Custom Data class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-custom-data-participant.php' );

		// load our Event Custom Data class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-custom-data-event.php' );

		// load our Event Sharing class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-event-sharing.php' );

		// load our Rendez Vous class
		require( CIVICRM_EO_ATTENDANCE_PATH . 'includes/civicrm-eo-attendance-rendez-vous.php' );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// test for basepage CiviEvent info requests so we can redirect to the
		// relevant Event Organiser post
		//add_action( 'civicrm_initialized', array( $this, 'maybe_redirect_to_eo' ) );

		// front end hooks
		if ( ! is_admin() ) {

			// change urls on front end
			add_action( 'civicrm_alterContent', array( $this, 'content_parse' ), 10, 4 );

			// register any public styles
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 20 );

		}

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3
	 */
	public function setup_objects() {

		// bail if CiviCRM Event Organiser plugin is not persent
		if ( ! function_exists( 'civicrm_eo' ) ) return;

		// init flag
		static $done;

		// only do this once
		if ( isset( $done ) AND $done === true ) return;

		// store reference to CiviCRM Event Organiser
		$this->civicrm_eo = civicrm_eo();

		// init objects
		$this->participant_listing = new CiviCRM_EO_Attendance_Participant_Listing;
		$this->event_leader = new CiviCRM_EO_Attendance_Event_Leader;
		$this->event_paid = new CiviCRM_EO_Attendance_Event_Paid;
		$this->custom_data_participant = new CiviCRM_EO_Attendance_Custom_Data_Participant;
		$this->custom_data_event = new CiviCRM_EO_Attendance_Custom_Data_Event;
		$this->event_sharing = new CiviCRM_EO_Attendance_Event_Sharing;
		$this->rendez_vous = new CiviCRM_EO_Attendance_Rendez_Vous;

		// store references
		$this->participant_listing->set_references( $this );
		$this->event_leader->set_references( $this );
		$this->event_paid->set_references( $this );
		$this->custom_data_participant->set_references( $this );
		$this->custom_data_event->set_references( $this );
		$this->event_sharing->set_references( $this );
		$this->rendez_vous->set_references( $this );

		// we're done
		$done = true;

	}



	/**
	 * Load translation files.
	 *
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// not used, as there are no translations as yet
		load_plugin_textdomain(
			'civicrm-eo-attendance', // unique name
			false, // deprecated argument
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // relative path
		);

	}



	/**
	 * Add our front-end stylesheets.
	 *
	 * @since 0.4.5
	 */
	public function enqueue_styles() {

		// add front end css
		wp_enqueue_style(
			'civicrm-eo-attendance',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/css/civicrm-eo-attendance.css',
			null,
			CIVICRM_EO_ATTENDANCE_VERSION,
			'all' // media
		);

	}



	/**
	 * Alter generated page content for a CiviEvent.
	 *
	 * @since 0.3.4
	 *
	 * @param $content Previously generated content.
	 * @param $context Context of content - page or form.
	 * @param $tplName The file name of the tpl.
	 * @param $object A reference to the page or form object.
	 */
	public function content_parse( &$content, $context, $tplName, &$object ) {

		// bail if not one of the forms we want
		if (
			$tplName != 'CRM/Event/Form/Registration/ThankYou.tpl' AND
			$tplName != 'CRM/Event/Page/EventInfo.tpl'
		) {
			return;
		}

		// CiviEvent ID needs to be retrieved differently per template
		if ( $tplName == 'CRM/Event/Form/Registration/ThankYou.tpl' ) {

			// do we have a CiviEvent ID?
			if ( ! isset( $object->_values['event']['id'] ) ) return;

			// get CiviEvent ID
			$civi_event_id = absint( $object->_values['event']['id'] );

		}

		// CiviEvent ID needs to be retrieved differently per template
		if ( $tplName == 'CRM/Event/Page/EventInfo.tpl' ) {

			// do we have a CiviEvent ID?
			if ( ! isset( $object->_id ) ) return;

			// get CiviEvent ID
			$civi_event_id = absint( $object->_id );

		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			//'content' => $content,
			'context' => $context,
			'tplName' => $tplName,
			'civi_event_id' => $civi_event_id,
			//'object' => $object,
			'backtrace' => $trace,
		), true ) );
		*/

		// init links
		$links = array();

		// use CiviCRM to construct link
		$link = CRM_Utils_System::url(
			'civicrm/event/info', 'reset=1&id=' . $civi_event_id,
			TRUE,
			NULL,
			FALSE,
			TRUE
		);

		// add this and its processed variant
		$links[] = $link;
		$links[] = htmlentities( $link );

		// use CiviCRM to construct alternative link
		$link = CRM_Utils_System::url(
			'civicrm/event/info', 'id=' . $civi_event_id . '&reset=1',
			TRUE,
			NULL,
			FALSE,
			TRUE
		);

		// add this and its processed variant
		$links[] = $link;
		$links[] = htmlentities( $link );

		// get EO event ID
		$event_id = $this->civicrm_eo->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// bail if we didn't get one
		if ( $event_id === false ) return;

		// get target permalink
		$event_url = get_permalink( $event_id );

		// replace into content
		$content = str_replace( $links, $event_url, $content );

	}



	/**
	 * Redirect to an Event Organiser post when viewing a CiviEvent info page.
	 *
	 * @since 0.3.4
	 *
	 * @param object $parent The parent object
	 */
	public function maybe_redirect_to_eo() {

		// only fire once
		remove_action( 'civicrm_initialized', array( $this, 'maybe_redirect_to_eo' ) );

		// bail if no CiviCRM
		if ( ! function_exists( 'civi_wp' ) ) return;

		// bail if not on CiviCRM's basepage
		if ( 'basepage' != civi_wp()->civicrm_context_get() ) return;

		// get CiviCRM's arguments
		$args = civi_wp()->get_request_args();

		// bail if we don't have any
		if ( is_null( $args['argString'] ) ) return;

		// init path
		$path = array();

		// check for event
		if ( isset( $args['args'][0] ) AND $args['args'][0] == 'civicrm' ) {
			$path[] = $args['args'][0];
		}

		// check for event
		if ( isset( $args['args'][1] ) AND $args['args'][1] == 'event' ) {
			$path[] = $args['args'][1];
		}

		// check for info page
		if ( isset( $args['args'][2] ) AND $args['args'][2] == 'info' ) {
			$path[] = $args['args'][2];
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'args' => $args,
			'path' => $path,
			'implode' => implode( '/', $path ),
			'backtrace' => $trace,
		), true ) );
		*/

		// bail if not on a CiviEvent info page
		if ( 'civicrm/event/info' !== implode( '/', $path ) ) return;

		// bail if this is itself a redirect to an info page
		if ( isset( $_GET['noFullMsg'] ) AND $_GET['noFullMsg'] == 'true' ) return;

		// bail if no ID
		if ( ! isset( $_GET['id'] ) ) return;
		if ( ! is_numeric( trim( $_GET['id'] ) ) ) return;

		// get the ID of the CiviEvent
		$civi_event_id = absint( trim( $_GET['id'] ) );

		// get EO event ID
		$event_id = $this->civicrm_eo->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// bail if we didn't get one
		if ( $event_id === false ) return;

		// get target permalink
		$event_url = get_permalink( $event_id );

		// GO!!
		wp_redirect( $event_url );
		exit();

	}



} // class ends



// declare as global
global $civicrm_eo_attendance;

// init plugin
$civicrm_eo_attendance = new CiviCRM_Event_Organiser_Attendance;

// activation
register_activation_hook( __FILE__, array( $civicrm_eo_attendance, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $civicrm_eo_attendance, 'deactivate' ) );




/**
 * Utility to get a reference to this plugin.
 *
 * @since 0.1
 *
 * @return object $civicrm_eo_attendance The plugin reference
 */
function civicrm_eo_attendance() {

	// return instance
	global $civicrm_eo_attendance;
	return $civicrm_eo_attendance;

}



