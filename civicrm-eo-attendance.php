<?php
/**
 * CiviCRM Event Organiser Attendance
 *
 * Plugin Name: CiviCRM Event Organiser Attendance
 * Description: Attendance functionality for CiviCRM Event Organiser plugin.
 * Plugin URI:  https://github.com/christianwach/civicrm-eo-attendance
 * Version:     0.7.0a
 * Author:      Christian Wach
 * Author URI:  https://haystack.co.uk
 * Text Domain: civicrm-eo-attendance
 * Domain Path: /languages
 *
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'CIVICRM_EO_ATTENDANCE_VERSION', '0.7.0a' );

// Store reference to this file.
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_FILE' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_URL' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_URL', plugin_dir_url( CIVICRM_EO_ATTENDANCE_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_PATH' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_PATH', plugin_dir_path( CIVICRM_EO_ATTENDANCE_FILE ) );
}

// Set our debugging status here.
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_DEBUG' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_DEBUG', true );
}

// Set our localhost status here.
if ( ! defined( 'CIVICRM_EO_ATTENDANCE_LOCALHOST' ) ) {
	define( 'CIVICRM_EO_ATTENDANCE_LOCALHOST', true );
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
	 * @var CiviCRM_Event_Organiser
	 */
	public $civicrm_eo;

	/**
	 * Participant Listing object.
	 *
	 * @since 0.1
	 * @access public
	 * @var CiviCRM_EO_Attendance_Participant_Listing
	 */
	public $participant_listing;

	/**
	 * Event Leader object.
	 *
	 * @since 0.3
	 * @access public
	 * @var CiviCRM_EO_Attendance_Event_Leader
	 */
	public $event_leader;

	/**
	 * Event Custom Data object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var CiviCRM_EO_Attendance_Custom_Data_Event
	 */
	public $custom_data_event;

	/**
	 * Participant Custom Data object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var CiviCRM_EO_Attendance_Custom_Data_Participant
	 */
	public $custom_data_participant;

	/**
	 * Event Sharing object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var CiviCRM_EO_Attendance_Event_Sharing
	 */
	public $event_sharing;

	/**
	 * Event Paid object.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var CiviCRM_EO_Attendance_Event_Paid
	 */
	public $event_paid;

	/**
	 * Rendez Vous Manager object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var CiviCRM_EO_Attendance_Rendez_Vous
	 */
	public $rendez_vous;

	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Initialise.
		$this->initialise();

		// Translation.
		add_action( 'plugins_loaded', [ $this, 'enable_translation' ] );

		// External references.
		add_action( 'plugins_loaded', [ $this, 'setup_objects' ], 20 );

	}

	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.3
	 */
	public function activate() {

		// Set up objects.
		$this->setup_objects();

		// Pass to classes that need activation.
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

		// Pass to classes that need deactivation.
		$this->rendez_vous->deactivate();

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Add actions and filters.
		$this->register_hooks();

	}

	/**
	 * Include files.
	 *
	 * @since 0.1
	 */
	public function include_files() {

		// Load our class files.
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-participant-listing.php';
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-event-leader.php';
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-event-paid.php';
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-custom-data-participant.php';
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-custom-data-event.php';
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-event-sharing.php';
		require CIVICRM_EO_ATTENDANCE_PATH . 'includes/class-rendez-vous.php';

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		/*
		// Test for basepage CiviEvent info requests so we can redirect to the
		// relevant Event Organiser Event.
		add_action( 'civicrm_initialized', array( $this, 'maybe_redirect_to_eo' ) );
		*/

		// Front end hooks.
		if ( ! is_admin() ) {

			// Change URLs on front end.
			add_action( 'civicrm_alterContent', [ $this, 'content_parse' ], 10, 4 );

			// Register any public styles.
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ], 20 );

		}

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.3
	 */
	public function setup_objects() {

		// Bail if CiviCRM Event Organiser plugin is not persent.
		if ( ! function_exists( 'civicrm_eo' ) ) {
			return;
		}

		// Init flag.
		static $done;

		// Only do this once.
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Store reference to CiviCRM Event Organiser.
		$this->civicrm_eo = civicrm_eo();

		// Init objects.
		$this->participant_listing     = new CiviCRM_EO_Attendance_Participant_Listing();
		$this->event_leader            = new CiviCRM_EO_Attendance_Event_Leader();
		$this->event_paid              = new CiviCRM_EO_Attendance_Event_Paid();
		$this->custom_data_participant = new CiviCRM_EO_Attendance_Custom_Data_Participant();
		$this->custom_data_event       = new CiviCRM_EO_Attendance_Custom_Data_Event();
		$this->event_sharing           = new CiviCRM_EO_Attendance_Event_Sharing();
		$this->rendez_vous             = new CiviCRM_EO_Attendance_Rendez_Vous();

		// Store references.
		$this->participant_listing->set_references( $this );
		$this->event_leader->set_references( $this );
		$this->event_paid->set_references( $this );
		$this->custom_data_participant->set_references( $this );
		$this->custom_data_event->set_references( $this );
		$this->event_sharing->set_references( $this );
		$this->rendez_vous->set_references( $this );

		// We're done.
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

		// Load translations.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'civicrm-eo-attendance', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // Relative path.
		);

	}

	/**
	 * Add our front-end stylesheets.
	 *
	 * @since 0.4.5
	 */
	public function enqueue_styles() {

		// Add front end CSS.
		wp_enqueue_style(
			'civicrm-eo-attendance',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/css/civicrm-eo-attendance.css',
			null,
			CIVICRM_EO_ATTENDANCE_VERSION,
			'all' // Media.
		);

	}

	/**
	 * Alter generated page content for a CiviEvent.
	 *
	 * @since 0.3.4
	 *
	 * @param str    $content Previously generated content.
	 * @param str    $context Context of content - page or form.
	 * @param str    $tpl_name The file name of the template.
	 * @param object $object A reference to the page or form object.
	 */
	public function content_parse( $content, $context, $tpl_name, $object ) {

		// Bail if not one of the forms we want.
		if (
			'CRM/Event/Form/Registration/ThankYou.tpl' !== $tpl_name &&
			'CRM/Event/Page/EventInfo.tpl' !== $tpl_name
		) {
			return;
		}

		// CiviEvent ID needs to be retrieved differently per template.
		if ( 'CRM/Event/Form/Registration/ThankYou.tpl' === $tpl_name ) {

			// Do we have a CiviEvent ID?
			if ( ! isset( $object->_values['event']['id'] ) ) {
				return;
			}

			// Get CiviEvent ID.
			$civi_event_id = absint( $object->_values['event']['id'] );

		}

		// CiviEvent ID needs to be retrieved differently per template.
		if ( 'CRM/Event/Page/EventInfo.tpl' === $tpl_name ) {

			// Do we have a CiviEvent ID?
			if ( ! isset( $object->_id ) ) {
				return;
			}

			// Get CiviEvent ID.
			$civi_event_id = absint( $object->_id );

		}

		// Init links.
		$links = [];

		// Use CiviCRM to construct link.
		$link = CRM_Utils_System::url(
			'civicrm/event/info',
			'reset=1&id=' . $civi_event_id,
			true,
			null,
			false,
			true
		);

		// Add this and its processed variant.
		$links[] = $link;
		$links[] = htmlentities( $link );

		// Use CiviCRM to construct alternative link.
		$link = CRM_Utils_System::url(
			'civicrm/event/info',
			'id=' . $civi_event_id . '&reset=1',
			true,
			null,
			false,
			true
		);

		// Add this and its processed variant.
		$links[] = $link;
		$links[] = htmlentities( $link );

		// Get EO Event ID.
		$event_id = $this->civicrm_eo->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// Bail if we didn't get one.
		if ( false === $event_id ) {
			return;
		}

		// Get target permalink.
		$event_url = get_permalink( $event_id );

		// Replace into content.
		$content = str_replace( $links, $event_url, $content );

	}

	/**
	 * Redirect to an Event Organiser Event when viewing a CiviEvent info page.
	 *
	 * @since 0.3.4
	 */
	public function maybe_redirect_to_eo() {

		// Only fire once.
		remove_action( 'civicrm_initialized', [ $this, 'maybe_redirect_to_eo' ] );

		// Bail if no CiviCRM.
		if ( ! function_exists( 'civi_wp' ) ) {
			return;
		}

		// Bail if not on CiviCRM's basepage.
		if ( 'basepage' !== civi_wp()->civicrm_context_get() ) {
			return;
		}

		// Get CiviCRM's arguments.
		$args = civi_wp()->get_request_args();

		// Bail if we don't have any.
		if ( is_null( $args['argString'] ) ) {
			return;
		}

		// Init path.
		$path = [];

		// Check for Event.
		if ( isset( $args['args'][0] ) && 'civicrm' === $args['args'][0] ) {
			$path[] = $args['args'][0];
		}

		// Check for Event.
		if ( isset( $args['args'][1] ) && 'event' === $args['args'][1] ) {
			$path[] = $args['args'][1];
		}

		// Check for info page.
		if ( isset( $args['args'][2] ) && 'info' === $args['args'][2] ) {
			$path[] = $args['args'][2];
		}

		// Bail if not on a CiviEvent info page.
		if ( 'civicrm/event/info' !== implode( '/', $path ) ) {
			return;
		}

		// Bail if this is itself a redirect to an info page.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['noFullMsg'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['noFullMsg'] ) ) ) {
			return;
		}

		// Bail if no ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['id'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( ! is_numeric( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) ) {
			return;
		}

		// Get the ID of the CiviEvent.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$civi_event_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );

		// Get EO Event ID.
		$event_id = $this->civicrm_eo->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// Bail if we didn't get one.
		if ( false === $event_id ) {
			return;
		}

		// Get target permalink.
		$event_url = get_permalink( $event_id );

		// Redirect now.
		wp_safe_redirect( $event_url );
		exit();

	}

	/**
	 * Write to the error log.
	 *
	 * @since 0.6.1
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging.
		if ( false === CIVICRM_EO_ATTENDANCE_DEBUG ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		if ( true === CIVICRM_EO_ATTENDANCE_LOCALHOST ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $error );
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $error, 3, WP_CONTENT_DIR . '/debug.log' );
		}

	}

}

/**
 * Utility to get a reference to this plugin.
 *
 * @since 0.1
 *
 * @return object $plugin The plugin reference.
 */
function civicrm_eo_attendance() {

	static $plugin;

	// Instantiate if not yet done.
	if ( ! isset( $plugin ) ) {
		$plugin = new CiviCRM_Event_Organiser_Attendance();
	}

	// --<
	return $plugin;

}

// Declare as global.
civicrm_eo_attendance();

// Activation.
register_activation_hook( __FILE__, [ civicrm_eo_attendance(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ civicrm_eo_attendance(), 'deactivate' ] );
