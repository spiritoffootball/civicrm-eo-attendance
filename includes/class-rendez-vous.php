<?php
/**
 * Rendez Vous Management Class.
 *
 * Handles Rendez Vous Management functionality.
 *
 * @since 0.4.7
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Rendez Vous Management Class.
 *
 * A class that encapsulates Rendez Vous Management functionality.
 *
 * @since 0.4.7
 */
class CiviCRM_EO_Attendance_Rendez_Vous {

	/**
	 * Plugin object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var CiviCRM_Event_Organiser_Attendance
	 */
	public $plugin;

	/**
	 * CiviCRM EO Plugin Admin object reference.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var CEO_Admin_DB
	 */
	public $db;

	/**
	 * Rendez Vous Term ID Option name.
	 *
	 * No longer used, but left for reference until I get rid of it completely.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var string
	 */
	public $term_option = 'civicrm_eo_event_rv_term_id';

	/**
	 * Rendez Vous "Month" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $month_meta_key = '_rendez_vous_month';

	/**
	 * Rendez Vous "Reference Array" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $reference_meta_key = '_rendez_vous_reference';

	/**
	 * Group "Attendance Enabled" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $group_meta_key = '_civicrm_eo_event_attendance';

	/**
	 * Group "Rendez Vous Organizer" meta key name.
	 *
	 * This Group meta value is set per Group to determine the people in the
	 * Group who are responsible for managing the attendance Rendez Vous. The UI
	 * actually allows any Group admin or mod to manage registrations, but there
	 * has to be at least one person in charge.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $organizer_meta_key = '_civicrm_eo_event_organizer';

	/**
	 * The number of future Rendez Vous to generate.
	 *
	 * @since 0.5
	 * @access public
	 * @var integer
	 */
	public $future_count = 6;

	/**
	 * Rendered Form markup.
	 *
	 * @since 0.5
	 * @access private
	 * @var string
	 */
	private $form;

	/**
	 * Initialises this object.
	 *
	 * @since 0.4.7
	 */
	public function __construct() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Set references to other objects.
	 *
	 * @since 0.4.7
	 *
	 * @param object $parent The parent object.
	 */
	public function set_references( $parent ) {

		// Store.
		$this->plugin = $parent;

	}

	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.4.7
	 */
	public function register_hooks() {

		// Wrap table in div.
		add_filter( 'rendez_vous_single_get_the_dates', [ $this, 'rv_form_wrap_table' ], 10, 2 );

		// Filter the column titles.
		add_filter( 'rendez_vous_single_get_the_dates_header', [ $this, 'rv_form_column_header' ], 10, 2 );

		// Add extra row with registration links.
		add_filter( 'rendez_vous_single_get_the_dates_rows_after', [ $this, 'rv_form_last_row' ], 10, 3 );

		// Filter Rendez Vous form classes.
		add_filter( 'rendez_vous_single_the_form_class', [ $this, 'rv_form_class' ], 10, 2 );

		// Remove edit button on Attendance Rendez Vous.
		add_filter( 'bp_get_button', [ $this, 'rv_form_button' ], 10, 3 );

		// Add refresh button on Rendez Vous Group archive.
		add_filter( 'bp_get_button', [ $this, 'rv_archive_button' ], 10, 3 );

		// Add scripts to single view Rendez Vous.
		add_action( 'rendez_vous_single_content_before', [ $this, 'enqueue_scripts' ] );

		// Add AJAX handler.
		add_action( 'wp_ajax_event_attendance_form_process', [ $this, 'rv_form_process' ] );

		// Hook into BPEO and remove original Taxonomy metabox.
		add_action( 'add_meta_boxes_event', [ $this, 'radio_tax_enforce' ], 3 );

		// Allow a single Rendez Vous to be refreshed.
		add_action( 'rendez_vous_single_screen', [ $this, 'refresh_rv_single' ] );

		// Allow all Rendez Vous to be refreshed.
		add_action( 'bp_screens', [ $this, 'refresh_rv_all' ], 3 );

		/*
		// Sort Rendez Vous listings by descending date.
		add_filter( 'bp_before_rendez_vouss_has_args_parse_args', [ $this, 'rv_sort' ], 20, 1 );
		*/

		// Add form element, scripts and styles to Group manage screen.
		add_action( 'rendez_vous_group_edit_screen_after', [ $this, 'group_manage_form_amend' ], 10, 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'group_manage_form_enqueue_styles' ], 20 );
		add_action( 'wp_enqueue_scripts', [ $this, 'group_manage_form_enqueue_scripts' ], 20 );

		// Check element when Group manage screen is submitted.
		add_action( 'rendez_vous_group_edit_screen_save', [ $this, 'group_manage_form_submit' ], 10, 2 );

		// Check registrations when a User updates their attendance.
		add_action( 'rendez_vous_before_attendee_prefs', [ $this, 'registrations_check' ], 10, 1 );

		// Update registrations when a User updates their attendance.
		add_action( 'rendez_vous_after_attendee_prefs', [ $this, 'registrations_update' ], 9, 3 );

	}

	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.4.7
	 */
	public function activate() {

		/*
		// Create data entities.
		$this->entities_create();
		*/

	}

	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Creates a Rendez Vous Term for the Rendez Vous used by this plugin.
	 *
	 * No longer used.
	 *
	 * @since 0.4.7
	 */
	public function entities_create() {

		// Bail if we've already done this.
		if ( 'fgffgs' !== $this->plugin->civicrm_eo->db->option_get( $this->term_option, 'fgffgs' ) ) {
			return;
		}

		// Define Term name.
		$term_name = __( 'Attendance', 'civicrm-eo-attendance' );

		// Create it.
		$new_term = $this->term_create( $term_name );

		// Log and bail on error.
		if ( is_wp_error( $new_term ) ) {

			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'new_term'  => $new_term,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return;

		}

		// Store as plugin option.
		$this->plugin->civicrm_eo->db->option_save( $this->term_option, $new_term['term_id'] );

	}

	/**
	 * Add our Javascript for registering Attendees.
	 *
	 * @since 0.4.8
	 */
	public function enqueue_scripts() {

		// Get current item.
		$item = rendez_vous()->item;

		// Bail if this Rendez Vous doesn't have our meta value.
		if ( ! $this->has_meta( $item ) ) {
			return;
		}

		// Get current User.
		$current_user = wp_get_current_user();

		// Only show if User is allowed to manage this Rendez Vous.
		if ( ! $this->rv_form_access_granted( $item ) ) {
			return;
		}

		// Add script to footer.
		wp_enqueue_script(
			'civicrm-eo-attendance-rvm',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-rvm.js',
			[ 'jquery' ],
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // In footer.
		);

		// Translations.
		$localisation = [
			'submit'     => __( 'Submit', 'civicrm-eo-attendance' ),
			'update'     => __( 'Update', 'civicrm-eo-attendance' ),
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'leader'     => __( 'You must choose someone to be the Event Leader.', 'civicrm-eo-attendance' ),
		];

		// Define settings.
		$settings = [
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'civicrm_eo_rvm_ajax_nonce' ),
		];

		// Localisation array.
		$vars = [
			'localisation' => $localisation,
			'settings'     => $settings,
		];

		// Localise the WordPress way.
		wp_localize_script(
			'civicrm-eo-attendance-rvm',
			'CiviCRM_EO_Attendance_RVM_Settings',
			$vars
		);

	}

	/**
	 * Force Radio Buttons for Taxonomies to remove the original Taxonomy metabox.
	 *
	 * @since 0.4.8
	 */
	public function radio_tax_enforce() {

		// Bail if we're in the admin area.
		if ( defined( 'WP_NETWORK_ADMIN' ) ) {
			return;
		}

		// Bail if no RBFT instance.
		if ( ! function_exists( 'Radio_Buttons_for_Taxonomies' ) ) {
			return;
		}

		// Get instance.
		$rbft = Radio_Buttons_for_Taxonomies();

		// Sanity check.
		if ( ! isset( $rbft->taxonomies ) || ! is_array( $rbft->taxonomies ) ) {
			return;
		}

		// Force removal of metaboxes.
		foreach ( $rbft->taxonomies as $tax ) {
			$tax->remove_meta_box();
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Sort the list of Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param array $args The array of params for the Rendez Vous.
	 * @return int|bool $rendez_vous_id The numeric ID of the Rendez Vous Post, or false on failure.
	 */
	public function rv_sort( $args ) {

		// Bail if not on a Group screen.
		if ( empty( $args['group_id'] ) ) {
			return $args;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args['meta_query'] = [
			'rendez_vous_month' => [
				'key'     => '_rendez_vous_month',
				'compare' => 'EXISTS',
			],
		];

		// Set order to date descending.
		$args['orderby'] = 'rendez_vous_month';
		$args['order']   = 'DESC';

		// --<
		return $args;

	}

	/**
	 * Intercept clicks on "Refresh" button for a single Rendez Vous.
	 *
	 * @since 0.5
	 */
	public function refresh_rv_single() {

		// Check link validity.
		$nonce = filter_input( INPUT_GET, 'civicrm_eo_refresh_nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'civicrm_eo_refresh_action' ) ) {
			return;
		}

		// Sanity checks.
		if ( empty( $_GET['action'] ) || empty( $_GET['rdv'] ) ) {
			return;
		}

		// Was the "refresh" button clicked?
		if ( 'refresh' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			return;
		}

		// Get redirect and Rendez Vous ID.
		$redirect       = remove_query_arg( [ 'rdv', 'action', 'n', 'civicrm_eo_refresh_nonce' ], wp_get_referer() );
		$rendez_vous_id = (int) sanitize_text_field( wp_unslash( $_GET['rdv'] ) );

		// Get Rendez Vous to rebuild.
		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );

		// Bail if this is not an attendance Rendez Vous.
		$month = $this->get_meta( $rendez_vous );
		if ( false === $month ) {
			return;
		}

		// Create DateTime object.
		$datetime = new DateTime( $month, eo_get_blog_timezone() );

		// Do the update.
		$updated_id = $this->rv_update( $datetime, $rendez_vous_id );

		// Appropriate error messages.
		if ( false === $updated_id ) {
			bp_core_add_message( __( 'Refreshing this Rendez-vous failed.', 'civicrm-eo-attendance' ), 'error' );
		} else {
			bp_core_add_message( __( 'Rendez-vous successfully refreshed.', 'civicrm-eo-attendance' ) );
			$redirect = add_query_arg( 'rdv', $updated_id, $redirect );
		}

		// Finally redirect.
		bp_core_redirect( $redirect );

	}

	/**
	 * Intercept clicks on "Refresh" button on Group Rendez Vous listing page.
	 *
	 * @since 0.5
	 */
	public function refresh_rv_all() {

		// Check link validity.
		$nonce = filter_input( INPUT_GET, 'civicrm_eo_refresh_all_nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'civicrm_eo_refresh_all_action' ) ) {
			return;
		}

		// Was the "refresh" button clicked?
		if ( empty( $_GET['action'] ) || 'refresh_all' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			return;
		}

		// Is this the Rendez Vous component Group archive?
		if ( bp_is_group() && bp_is_current_action( rendez_vous()->get_component_slug() ) && empty( $_REQUEST['rdv'] ) ) {

			// Get redirect.
			$redirect = remove_query_arg( [ 'action', 'rdv', 'n', 'civicrm_eo_refresh_all_nonce' ], wp_get_referer() );

			// Get Group ID.
			$group_id = bp_get_current_group_id();

			// Get the current and future Rendez Vous.
			$args = [
				'per_page' => ( $this->future_count + 1 ),
				'group_id' => $group_id,
				'orderby'  => 'rendez_vous_date',
				'order'    => 'DESC',
				'no_cache' => true,
			];

			// Add our meta query via a filter.
			add_filter( 'rendez_vous_query_args', [ $this, 'refresh_filter_query' ], 10, 1 );

			// Do the query.
			$has_rendez_vous = rendez_vous_has_rendez_vouss( $args );

			// Remove our filter.
			remove_filter( 'rendez_vous_query_args', [ $this, 'refresh_filter_query' ], 10 );

			$rv_meta = [];
			$rv_ids  = [];

			// Do the loop to find existing IDs.
			if ( $has_rendez_vous ) {
				while ( rendez_vous_the_rendez_vouss() ) {
					rendez_vous_the_rendez_vous();
					$id             = rendez_vous_get_the_rendez_vous_id();
					$rv_ids[]       = $id;
					$rv_meta[ $id ] = get_post_meta( $id, $this->month_meta_key, true );
				}
			}

			// Init error flag.
			$has_error = false;

			// Get months to check.
			$checks = $this->refresh_get_months();
			foreach ( $checks as $month ) {

				// Create DateTime object.
				$datetime = new DateTime( $month, eo_get_blog_timezone() );

				// Create it if it doesn't exist, update otherwise.
				if ( ! in_array( $month, $rv_meta, true ) ) {
					$returned_id = $this->rv_create( $datetime, $group_id );
				} else {
					$flipped        = array_flip( $rv_meta );
					$rendez_vous_id = $flipped[ $month ];
					$returned_id    = $this->rv_update( $datetime, $rendez_vous_id );
				}

				// Error check.
				if ( false === $returned_id ) {
					$has_error = true;
				}

			}

			// Appropriate error messages.
			if ( true === $has_error ) {
				bp_core_add_message( __( 'Refreshing all Rendez-vous failed.', 'civicrm-eo-attendance' ), 'error' );
			} else {
				bp_core_add_message( __( 'All Rendez-vous successfully refreshed.', 'civicrm-eo-attendance' ) );
			}

			// Finally redirect.
			bp_core_redirect( $redirect );

		}

	}

	/**
	 * Filter the query for refresh action only.
	 *
	 * @since 0.5
	 *
	 * @param array $query_args The existing query args.
	 * @return array $query_args The modified query args.
	 */
	public function refresh_filter_query( $query_args = [] ) {

		// Get months to check.
		$checks = $this->refresh_get_months();

		// Find all Rendez Vous with these meta values.
		$month_query = [
			[
				'key'     => $this->month_meta_key,
				'value'   => $checks,
				'compare' => 'IN',
			],
		];

		if ( empty( $query_args['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query_args['meta_query'] = [ $month_query ];
		} else {
			$query_args['meta_query'][] = $month_query;
		}

		// --<
		return $query_args;

	}

	/**
	 * Get an array of months in the future.
	 *
	 * @since 0.5
	 *
	 * @return array $checks The array of months to check.
	 */
	public function refresh_get_months() {

		// Get now.
		$now = new DateTime( 'now', eo_get_blog_timezone() );

		// Get first day of this month.
		$month_start = $now->format( 'Y-m-01' );
		$start       = new DateTime( $month_start, eo_get_blog_timezone() );

		// Check this month plus N months ahead.
		$checks = [ $start->format( 'Y-m-d' ) ];
		for ( $i = 1; $i < ( $this->future_count + 1 ); $i++ ) {
			$start->add( new DateInterval( 'P1M' ) );
			$checks[] = $start->format( 'Y-m-d' );
		}

		// --<
		return $checks;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create a Rendez Vous for use by this plugin.
	 *
	 * @since 0.4.7
	 *
	 * @param DateTime $datetime The DateTime object for the month to create an RV for.
	 * @param int      $group_id The numeric ID of the Group.
	 * @return int|bool $updated_id The ID of the created Rendez Vous - false on failure or true if skipped.
	 */
	public function rv_create( $datetime, $group_id = 0 ) {

		// Members Group ID.
		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		// The current User must be an Organiser.
		$organizer_id = $this->is_organiser( $group_id );
		if ( empty( $organizer_id ) ) {
			return true;
		}

		// Get members of Group.
		$attendee_ids = $this->rv_attendees_get( $group_id );

		// For the title, we use the 3rd day of the month to avoid daylight-saving
		// and timezone oddities related to the 1st.
		$title_date     = $datetime->format( 'Y-m-03' );
		$title_datetime = new DateTime( $title_date, eo_get_blog_timezone() );

		// Define title.
		$title = sprintf(
			/* translators: %s: The formatted Event date. */
			__( 'Availability for %s', 'civicrm-eo-attendance' ),
			date_i18n( 'F Y', $title_datetime->getTimestamp() )
		);

		// Events on this month.
		$month_start = $datetime->format( 'Y-m-01' );
		$month_end   = $datetime->format( 'Y-m-t' );

		// Default event query args.
		$event_query_args = [
			'event_start_after'  => $month_start,
			'event_start_before' => $month_end,
		];

		/**
		 * Filter Event query arguments.
		 *
		 * Used internally to include only paid Events.
		 *
		 * @since 0.4.7
		 *
		 * @param array $event_query_args The array of query args.
		 */
		$event_args = apply_filters( 'civicrm_event_organiser_rendez_vous_event_args', $event_query_args );

		// Get Event data.
		$events = eo_get_events( $event_args );

		// Init Event data.
		$days = [];

		// Init reference array.
		$references = [];

		// Init used.
		$used = [];

		// Format days array for these Events.
		if ( count( $events ) > 0 ) {
			foreach ( $events as $event ) {

				// Get CiviEvent ID.
				$civi_event_id = $this->plugin->civicrm_eo->mapping->get_civi_event_id_by_eo_occurrence_id( $event->ID, $event->occurrence_id );

				// Skip unless we have a CiviEvent.
				if ( false === $civi_event_id ) {
					continue;
				}

				// Get start.
				$event_start_time = eo_get_the_start( DATETIMEOBJ, $event->ID, $event->occurrence_id );
				$timestamp        = $event_start_time->getTimestamp();

				// Ensure no duplicates by adding a trivial amount.
				while ( in_array( $timestamp, $used, true ) ) {
					$timestamp++;
				}

				// Add to RV days.
				$days[ $timestamp ] = [];

				// Add CiviEvent ID to reference array.
				$references[ $timestamp ] = $civi_event_id;

				// Add to used array.
				$used[] = $timestamp;

			}
		}

		// No need to create if there are no dates.
		if ( count( $days ) === 0 ) {
			return true;
		}

		// Construct create array.
		$rendez_vous = [
			'title'     => $title,
			'organizer' => $organizer_id,
			'duration'  => '01:00',
			'venue'     => __( 'Erfurt', 'civicrm-eo-attendance' ),
			'status'    => 'draft',
			'group_id'  => (int) $group_id,
			'attendees' => $attendee_ids,
			'days'      => $days,
		];

		// Create it.
		$rendez_vous_id = $this->rv_save( $rendez_vous );

		// Log and bail on error.
		if ( ! is_int( $rendez_vous_id ) ) {

			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'           => __METHOD__,
				'new_rv_id'        => $rendez_vous_id,
				'rendez_vous_args' => $rendez_vous,
				'backtrace'        => $trace,
			];
			$this->plugin->log_error( $log );
			return false;

		}

		// Store the Event IDs for the Rendez Vous item.
		add_post_meta( $rendez_vous_id, $this->reference_meta_key, $references, true );

		// Store the month for the Rendez Vous.
		add_post_meta( $rendez_vous_id, $this->month_meta_key, $month_start, true );

		// Update args.
		$rendez_vous['id']     = $rendez_vous_id;
		$rendez_vous['status'] = 'publish';

		// Publish the Rendez Vous.
		$rendez_vous_id = $this->rv_save( $rendez_vous );

		// --<
		return $rendez_vous_id;

	}

	/**
	 * Update a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param DateTime $datetime The DateTime object for the month of the Rendez Vous.
	 * @param int      $rendez_vous_id The numeric ID of the Rendez Vous to update.
	 * @return int|bool $updated_id The ID of the updated Rendez Vous, or false on failure.
	 */
	public function rv_update( $datetime, $rendez_vous_id ) {

		// Get Rendez Vous to rebuild.
		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );

		// Bail if this fails for some reason.
		if ( ! ( $rendez_vous instanceof Rendez_Vous_Item ) ) {
			return false;
		}

		// Bail if this Rendez Vous doesn't have our meta value.
		if ( ! $this->has_meta( $rendez_vous ) ) {
			return false;
		}

		// Get members of Group.
		$attendee_ids = $this->rv_attendees_get( $rendez_vous->group_id );

		// Get reference meta and sanity check.
		$references = get_post_meta( $rendez_vous->id, $this->reference_meta_key, true );
		if ( ! is_array( $references ) ) {
			$references = [];
		}

		// Events on this month.
		$month_start = $datetime->format( 'Y-m-01' );
		$month_end   = $datetime->format( 'Y-m-t' );

		// Default event query args.
		$event_query_args = [
			'event_start_after'  => $month_start,
			'event_start_before' => $month_end,
		];

		/**
		 * Filter Event query arguments.
		 *
		 * Used internally to include only paid Events.
		 *
		 * @since 0.4.7
		 *
		 * @param array $event_query_args The array of query args.
		 */
		$event_args = apply_filters( 'civicrm_event_organiser_rendez_vous_event_args', $event_query_args );

		// Get Event data.
		$events = eo_get_events( $event_args );

		// Init comparison arrays.
		$correspondences = [];
		$new_items       = [];
		$missing_items   = [];

		// Parse Events.
		if ( count( $events ) > 0 ) {
			foreach ( $events as $event ) {

				// Get CiviEvent ID.
				$civi_event_id = $this->plugin->civicrm_eo->mapping->get_civi_event_id_by_eo_occurrence_id( $event->ID, $event->occurrence_id );

				// Skip unless we have a CiviEvent.
				if ( false === $civi_event_id ) {
					continue;
				}

				// Make sure it's an integer.
				$civi_event_id = (int) $civi_event_id;

				// Always add to correspondences.
				$correspondences[ $event->occurrence_id ] = $civi_event_id;

				// Add to array if new.
				if ( ! in_array( $civi_event_id, $references, true ) ) {
					$new_items[ $civi_event_id ] = [ $event->ID, $event->occurrence_id ];
				}

			}
		}

		// Parse existing Event references.
		if ( count( $references ) > 0 ) {
			foreach ( $references as $timestamp => $civi_event_id ) {

				// Add to array if missing.
				if ( ! in_array( $civi_event_id, $correspondences, true ) ) {
					$missing_items[ $timestamp ] = $civi_event_id;
				}

			}
		}

		// Are there any new items to add?
		if ( count( $new_items ) > 0 ) {

			// Get existing timestamps.
			$used = array_keys( $rendez_vous->days );

			// Parse each new item.
			foreach ( $new_items as $civi_event_id => $event_data ) {

				// Get timestamp.
				$event_start_time = eo_get_the_start( DATETIMEOBJ, $event_data[0], $event_data[1] );
				$timestamp        = $event_start_time->getTimestamp();

				// Ensure no duplicates by adding a trivial amount.
				while ( in_array( $timestamp, $used, true ) ) {
					$timestamp++;
				}

				// Add the timestamp to the Rendez Vous.
				$rendez_vous->days[ $timestamp ] = [];

				// Add CiviEvent ID to reference array.
				$references[ $timestamp ] = $civi_event_id;

			}

		}

		// Are there any missing items?
		if ( count( $missing_items ) > 0 ) {
			foreach ( $missing_items as $timestamp => $civi_event_id ) {

				// Remove from the Rendez Vous it if it's there.
				if ( isset( $rendez_vous->days[ $timestamp ] ) ) {
					unset( $rendez_vous->days[ $timestamp ] );
				}

				// Remove from the reference array it if it's there.
				if ( isset( $references[ $timestamp ] ) ) {
					unset( $references[ $timestamp ] );
				}

			}
		}

		// If there are no changes to dates.
		if ( count( $missing_items ) === 0 && count( $new_items ) === 0 ) {

			// Compare like-with-like.
			sort( $rendez_vous->attendees );
			sort( $attendee_ids );

			// Bail if there is no change of members.
			if ( $rendez_vous->attendees === $attendee_ids ) {
				return $rendez_vous_id;
			}

		}

		// Construct update array.
		$rv_data = [
			'id'        => $rendez_vous->id,
			'title'     => $rendez_vous->title,
			'organizer' => $rendez_vous->organizer,
			'duration'  => '01:00',
			'venue'     => $rendez_vous->venue,
			'status'    => 'publish',
			'group_id'  => $rendez_vous->group_id,
			'attendees' => $attendee_ids,
			'days'      => $rendez_vous->days,
		];

		// Update the Rendez Vous.
		$updated_id = $this->rv_save( $rv_data );

		// Bail if this fails.
		if ( empty( $updated_id ) ) {
			return false;
		}

		// Update days.
		$this->rv_days_update( $updated_id, $rendez_vous->days );

		// Store the Event IDs for the Rendez Vous.
		update_post_meta( $updated_id, $this->reference_meta_key, $references );

		// --<
		return $updated_id;

	}

	/**
	 * Create a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param array $args The array of params for the Rendez Vous.
	 * @return int|bool $rendez_vous_id The numeric ID of the Rendez Vous Post, or false on failure.
	 */
	public function rv_save( $args ) {

		// Create a Rendez Vous.
		$rendez_vous_id = rendez_vous_save( $args );

		// --<
		return $rendez_vous_id;

	}

	/**
	 * Delete a Rendez Vous.
	 *
	 * @since 0.4.8
	 *
	 * @param int $id The numeric ID of the Rendez Vous to delete.
	 * @return bool $success True if successfully deleted, false on failure.
	 */
	public function rv_delete( $id ) {

		// Delete a Rendez Vous.
		$success = rendez_vous_delete_item( $id );

		// --<
		return $success;

	}

	/**
	 * Update the days in a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param int   $rendez_vous_id The numeric ID of the Rendez Vous Post.
	 * @param array $days The array of dates for the Rendez Vous.
	 */
	public function rv_days_update( $rendez_vous_id, $days ) {

		// Make sure we have the "none" option.
		if ( ! in_array( 'none', array_keys( $days ), true ) ) {
			$days['none'] = [];
		}

		// Update the days in the Rendez Vous item.
		update_post_meta( $rendez_vous_id, '_rendez_vous_days', $days );

	}

	/**
	 * Get the attendees for a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param int $group_id The numeric ID of the Group.
	 * @return array $attendee_ids An array of numeric IDs of the Rendez Vous Users.
	 */
	public function rv_attendees_get( $group_id ) {

		// Bail if no BuddyPress.
		if ( ! function_exists( 'buddypress' ) ) {
			return [];
		}

		// Build query.
		$query = [
			'group_id'   => $group_id,
			'type'       => 'alphabetical',
			'per_page'   => 10000,
			'page'       => 1,
			'group_role' => [ 'member', 'mod', 'admin' ],
		];

		// Perform the Group member query.
		$members = new BP_Group_Member_Query( $query );

		// Bail if no results.
		if ( count( $members->results ) === 0 ) {
			return [];
		}

		// Structure the return.
		$attendee_ids = array_keys( $members->results );

		// --<
		return $attendee_ids;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filters the "New Rendez-vous" button output.
	 *
	 * This is a bit of a hack, in that we're appending our button to the output
	 * of an existing button. Preferable to have a hook to add just this button
	 * but there isn't one. So, light touch hack instead.
	 *
	 * @since 0.5
	 *
	 * @param string    $contents Button context to be used.
	 * @param array     $args Array of args for the button.
	 * @param BP_Button $button BP_Button object.
	 * @return string $contents The modified Button context to be used.
	 */
	public function rv_archive_button( $contents, $args, $button ) {

		// Bail if not a Rendez Vous button.
		if ( ! isset( $args['component'] ) || 'rendez_vous' !== $args['component'] ) {
			return $contents;
		}

		// Bail if not a New Rendez Vous button.
		if ( ! isset( $args['wrapper_id'] ) || 'new-rendez-vous' !== $args['wrapper_id'] ) {
			return $contents;
		}

		// Bail if not the Rendez Vous component.
		if ( ! bp_is_group() || ! bp_is_current_action( rendez_vous()->get_component_slug() ) ) {
			return $contents;
		}

		// Get the currently displayed Group ID.
		$group_id = bp_get_current_group_id();

		// Bail if attendance is not enabled for this Group.
		if ( ! $this->group_get_option( $group_id, $this->group_meta_key ) ) {
			return $contents;
		}

		// The current User must be an Organiser.
		$organizer_id = $this->is_organiser( $group_id );
		if ( false !== $organizer_id ) {

			// Build the URL we want.
			$current_url = home_url( add_query_arg( [] ) );
			$refresh_url = add_query_arg( 'action', 'refresh_all', $current_url );
			$url         = wp_nonce_url( $refresh_url, 'civicrm_eo_refresh_all_action', 'civicrm_eo_refresh_all_nonce' );

			// Construct link.
			$link = '<a href="' . $url . '">' .
						__( 'Refresh All', 'civicrm-eo-attendance' ) .
					'</a>';

			// Add in our button.
			$contents = str_replace(
				'</div>',
				'</div><div class="generic-button civicrm-eo-generic-button">' . $link . '</div>',
				$contents
			);

		}

		// --<
		return $contents;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filters the single Rendez Vous "Edit" button output.
	 *
	 * @since 0.4.8
	 *
	 * @param string    $contents Button context to be used.
	 * @param array     $args Array of args for the button.
	 * @param BP_Button $button BP_Button object.
	 * @return string $contents The modified Button context to be used.
	 */
	public function rv_form_button( $contents, $args, $button ) {

		// Is this a Rendez Vous button?
		if ( isset( $args['component'] ) && 'rendez_vous' === $args['component'] ) {

			// Is this a Rendez Vous edit button?
			if ( isset( $args['wrapper_id'] ) && 'rendez-vous-edit-btn' === $args['wrapper_id'] ) {

				// Get current item.
				$item = rendez_vous()->item;

				// Does this Rendez Vous have our meta value?
				if ( $this->has_meta( $item ) ) {

					// Change action, class and title.
					$contents = str_replace(
						[
							'action=edit',
							'class="edit-rendez-vous"',
							__( 'Edit', 'civicrm-eo-attendance' ),
						],
						[
							'action=refresh&#038;civicrm_eo_refresh_nonce=' . wp_create_nonce( 'civicrm_eo_refresh_action' ),
							'class="refresh-rendez-vous"',
							__( 'Refresh', 'civicrm-eo-attendance' ),
						],
						$contents
					);

					// Override button.
					return $contents;

				}

			}

		}

		// --<
		return $contents;

	}

	/**
	 * Filter the Rendez Vous form classes.
	 *
	 * @since 0.5
	 *
	 * @param str $class The existing space-delimited classes.
	 * @param obj $item The Rendez Vous object.
	 * @return str $class The existing space-delimited classes.
	 */
	public function rv_form_class( $class, $item ) {

		// Bail if we don't have the meta value.
		if ( ! $this->has_meta( $item ) ) {
			return $class;
		}

		// Add identifier.
		$class .= ' rendez-vous-civicrm-eo-attendance';

		// --<
		return $class;

	}

	/**
	 * Wrap a Rendez Vous table in a div so it can be scrolled sideways.
	 *
	 * @since 0.4.7
	 *
	 * @param str $output The existing table HTML.
	 * @param str $view The view (either 'edit' or 'view').
	 * @return str $output The modified table HTML.
	 */
	public function rv_form_wrap_table( $output, $view ) {

		// Wrap in div.
		$output = '<div class="civicrm-eo-attendance-table">' . $output . '</div>';

		// --<
		return $output;

	}

	/**
	 * Modify the column headers for a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param str $header The existing column header.
	 * @param int $date The UNIX timestamp.
	 * @return str $header The modified column header.
	 */
	public function rv_form_column_header( $header, $date ) {

		// Do we have the meta?
		if ( ! isset( $this->item_meta ) ) {

			// Get current item.
			$item = rendez_vous()->item;

			// Get reference meta.
			$this->item_meta = get_post_meta( $item->id, $this->reference_meta_key, true );

		}

		// Start from scratch.
		$col_header = '';

		// Modify header to include Event title.
		if ( is_long( $date ) ) {

			// Get Event ID.
			$civi_event_id = isset( $this->item_meta[ $date ] ) ? $this->item_meta[ $date ] : 0;

			// Get Event ID.
			$event_id = $this->plugin->civicrm_eo->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );

			// Get occurrence ID.
			$occurrence_id = $this->plugin->civicrm_eo->mapping->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );

			// Get Event title.
			$event_title = get_the_title( $event_id );

			// Get Event permalink.
			$event_link = get_permalink( $event_id );

			// Construct title.
			$linked_title = '<a href="' . esc_url( $event_link ) . '">' . esc_html( $event_title ) . '</a>';

			$col_header .= '<div class="title">' . $linked_title . '</div>';
			$col_header .= '<div class="date">' . eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ) . '</div>';
			$col_header .= '<div class="time">' . eo_get_the_start( 'g:ia', $event_id, $occurrence_id ) . '</div>';

		} else {

			// Show useful message.
			$col_header .= '<div class="none">' . esc_html__( 'I am not available this month', 'civicrm-eo-attendance' ) . '</div>';

		}

		// --<
		return $col_header;

	}

	/**
	 * Append row to the view table of a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param str   $output The existing output.
	 * @param array $header The array of dates.
	 * @param str   $view The output mode ('view' or 'edit').
	 * @return str $output The modified output.
	 */
	public function rv_form_last_row( $output, $header, $view ) {

		// Get current item.
		$item = rendez_vous()->item;

		// Bail if this Rendez Vous doesn't have our meta value.
		if ( ! $this->has_meta( $item ) ) {
			return $output;
		}

		// Open row.
		$output .= '<tr><td class="rendez-vous-date-blank">&nbsp;</td>';

		// Add register link.
		foreach ( $header as $date ) {

			$output .= '<td class="rendez-vous-date">';
			$output .= $this->rv_form_column_header( $header, $date );
			$output .= '</td>';

		}

		// Close row.
		$output .= '</tr>';

		// Bail if this User cannot access the tools.
		if ( ! $this->rv_form_access_granted( $item ) ) {
			return $output;
		}

		// Init form markup.
		$this->form = '';

		// Open row.
		$output .= '<tr>';

		// Init with pseudo-th.
		$output .= '<td>' . esc_html__( 'Registration', 'civicrm-eo-attendance' ) . '</td>';

		// Add register link.
		foreach ( $header as $date ) {

			// Handle "none" item.
			if ( 'none' === $date ) {
				$output .= '<td>&nbsp;</td>';
				continue;
			}

			// Add registration link.
			if ( is_long( $date ) ) {

				// Init class array with base class.
				$classes = [ 'civicrm-eo-rendezvous-register-all' ];

				// Get Event ID.
				$civi_event_id = isset( $this->item_meta[ $date ] ) ? $this->item_meta[ $date ] : 0;

				// Add Event ID class.
				$classes[] = 'civicrm-eo-rv-event-id-' . $civi_event_id;

				// Get IDs of attendees.
				$attendee_ids = $item->days[ $date ];

				// Add attendee IDs class.
				$classes[] = 'civicrm-eo-rv-ids-' . implode( '-', $attendee_ids );

				// Open table row.
				$output .= '<td>';

				// Add span.
				$output .= '<span class="' . implode( ' ', $classes ) . '">' . __( 'Register', 'civicrm-eo-attendance' ) . '</span>';

				// Create dummy Event object.
				$post     = new stdClass();
				$post->ID = $this->plugin->civicrm_eo->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );

				// Add form.
				$this->form .= $this->rv_form_registration_render( $civi_event_id, $attendee_ids, $post );

				// Close table row.
				$output .= '</td>';

			}

		}

		// Close row.
		$output .= '</tr>';

		// Trigger rendering of forms in footer.
		add_action( 'wp_footer', [ $this, 'rv_form_footer' ] );

		// --<
		return $output;

	}

	/**
	 * Add Registration Form for an Event in a Rendez Vous.
	 *
	 * @since 0.4.8
	 *
	 * @param int    $civi_event_id The numeric ID of the CiviEvent.
	 * @param array  $attendee_ids The numeric IDs of the attendees.
	 * @param object $post A bare-bones Post object with the EO Event ID.
	 * @return str $markup The rendered registration form.
	 */
	public function rv_form_registration_render( $civi_event_id, $attendee_ids, $post ) {

		// Init markup.
		$markup = '';

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return $markup;
		}

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $markup;
		}

		// Get Event Leader Role for error checking.
		$event_leader_role = $this->plugin->event_leader->role_default_get( $post );

		// Init data arrays.
		$checked = [];
		$select  = [];

		// Init "event has Participants" flag.
		$participants_exist = false;

		// Get current Participant statuses.
		if ( count( $attendee_ids ) > 0 ) {
			foreach ( $attendee_ids as $attendee_id ) {

				// Get the CiviCRM Contact ID.
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// Skip if no Contact ID found.
				if ( empty( $contact_id ) ) {
					continue;
				}

				// Get current Participant data.
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// If currently registered.
				if ( false !== $participant ) {

					// Populate data arrays.
					$checked[ $attendee_id ] = true;
					$select[ $attendee_id ]  = $this->rv_form_registration_roles( $post, $participant['participant_role_id'] );

					// Set flag.
					$participants_exist = true;

				} else {

					// Add default entries to data arrays.
					$checked[ $attendee_id ] = false;
					$select[ $attendee_id ]  = $this->rv_form_registration_roles( $post );

				}

			}
		}

		// Set label for submit button.
		if ( $participants_exist ) {
			$submit_label = __( 'Update', 'civicrm-eo-attendance' );
		} else {
			$submit_label = __( 'Submit', 'civicrm-eo-attendance' );
		}

		// Start buffering.
		ob_start();

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-attendance/attendance-form.php';

		// Save the output and flush the buffer.
		$markup = ob_get_clean();

		// --<
		return $markup;

	}

	/**
	 * Builds the Participant Roles select element for an EO Event.
	 *
	 * @since 0.4.8
	 *
	 * @param object $post An EO Event object.
	 * @param int    $participant_role_id The numeric ID of the Participant Role.
	 * @return str $html Markup to display in the form.
	 */
	public function rv_form_registration_roles( $post, $participant_role_id = null ) {

		// Init html.
		$html = '';

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return $html;
		}

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $html;
		}

		// First, get all Participant Roles.
		if ( ! isset( $this->all_roles ) ) {
			$this->all_roles = $this->plugin->civicrm_eo->civi->registration->get_participant_roles();
		}

		// Did we get any?
		if ( 0 === (int) $this->all_roles['is_error'] && count( $this->all_roles['values'] ) > 0 ) {

			// Get the values array.
			$roles = $this->all_roles['values'];

			// Init options.
			$options = [];

			// Get default role ID.
			$default_id = $this->plugin->civicrm_eo->civi->registration->get_participant_role( $post );

			// Loop.
			foreach ( $roles as $key => $role ) {

				// Get role.
				$role_id = absint( $role['value'] );

				// Init selected.
				$selected = '';

				// Override selected when this value is the same as the given Participant Role ID.
				if ( ! empty( $participant_role_id ) && (int) $participant_role_id === (int) $role_id ) {
					$selected = ' selected="selected"';
				}

				// Override selected when this value is the same as the default.
				if ( empty( $participant_role_id ) && (int) $default_id === (int) $role_id ) {
					$selected = ' selected="selected"';
				}

				// Construct option.
				$options[] = '<option value="' . $role_id . '"' . $selected . '>' . esc_html( $role['label'] ) . '</option>';

			}

			// Create html.
			$html = implode( "\n", $options );

		}

		// Return.
		return $html;

	}

	/**
	 * Append forms to page footer.
	 *
	 * @since 0.4.8
	 */
	public function rv_form_footer() {

		// Render forms.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->form;

	}

	/**
	 * Process a form submitted via AJAX.
	 *
	 * @since 0.4.8
	 */
	public function rv_form_process() {

		// Init data.
		$data = [
			'error'  => '1',
			'markup' => __( 'Oops! Something went wrong.', 'civicrm-eo-attendance' ),
		];

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( 'civicrm_eo_rvm_ajax_nonce', false, false );
		if ( false === $result ) {
			wp_send_json( $json );
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			wp_send_json( $data );
		}

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			wp_send_json( $data );
		}

		// Get form data.
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['civi_event_id'] ) ) : '0';
		$civi_event_id = (int) $civi_event_id;

		// Check and sanitise Register data.
		$register_data = filter_input( INPUT_POST, 'register', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $register_data ) ) {

			// Sanitise array contents.
			array_walk(
				$register_data,
				function( &$item ) {
					$item = (int) trim( $item );
				}
			);

		} else {
			$register_data = [];
		}

		// Check and sanitise Unregister data.
		$unregister_data = filter_input( INPUT_POST, 'unregister', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $unregister_data ) ) {

			// Sanitise array contents.
			array_walk(
				$unregister_data,
				function( &$item ) {
					$item = (int) trim( $item );
				}
			);

		} else {
			$unregister_data = [];
		}

		// Get now in appropriate format.
		$now_obj = new DateTime( 'now', eo_get_blog_timezone() );
		$now     = $now_obj->format( 'Y-m-d H:i:s' );

		// Get Event details.
		$event_id      = $this->plugin->civicrm_eo->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );
		$occurrence_id = $this->plugin->civicrm_eo->mapping->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );
		$event_title   = get_the_title( $event_id );
		$event_link    = get_permalink( $event_id );

		// Construct title.
		$linked_title = '<a href="' . esc_url( $event_link ) . '">' . esc_html( $event_title ) . '</a>';

		// Handle registrations.
		if ( ! empty( $register_data ) ) {

			// Register each attendee.
			foreach ( $register_data as $attendee_id => $role_id ) {

				// Get the CiviCRM Contact ID.
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// Skip if no Contact ID found.
				if ( empty( $contact_id ) ) {
					continue;
				}

				// Build params to create Participant.
				$params = [
					'version'       => 3,
					'contact_id'    => $contact_id,
					'event_id'      => $civi_event_id,
					'status_id'     => 1, // Registered.
					'role_id'       => $role_id,
					'register_date' => $now, // As in: '2007-07-21 00:00:00'.
					'source'        => __( 'Registration via Rendez Vous', 'civicrm-eo-attendance' ),
				];

				// Get current Participant data.
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// Trigger update if Participant is found.
				if ( false !== $participant ) {
					$params['id'] = $participant['participant_id'];
				}

				// Use CiviCRM API to create Participant.
				$result = civicrm_api( 'Participant', 'create', $params );

				// Error check.
				if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

					// Log error.
					$e     = new Exception();
					$trace = $e->getTraceAsString();
					$log   = [
						'method'    => __METHOD__,
						'message'   => $result['error_message'],
						'params'    => $params,
						'result'    => $result,
						'backtrace' => $trace,
					];
					$this->plugin->log_error( $log );

				} else {

					// Set up message.
					$subject = sprintf(
						/* translators: 1: The Event title. */
						__( 'You have been registered for %s', 'civicrm-eo-attendance' ),
						esc_html( $event_title )
					);

					// Construct message body.
					$message = sprintf(
						/* translators: 1: The Event title, 2: The start date, 3: The start time. */
						__( 'This is to let you know that you have been registered as attending the Event "%1$s" on %2$s at %3$s. If you think this has been done in error, or you have anything else you want to say to the organiser, please follow the link below and reply to this conversation on the Spirit of Football website.', 'civicrm-eo-attendance' ),
						$linked_title,
						eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ),
						eo_get_the_start( 'g:ia', $event_id, $occurrence_id )
					);

					// Send message.
					$this->rv_form_notify_user( $attendee_id, $subject, $message );

				}

			}

		}

		// Handle de-registrations.
		if ( ! empty( $unregister_data ) ) {

			// De-register each attendee.
			foreach ( $unregister_data as $attendee_id => $role_id ) {

				// Get the CiviCRM Contact ID.
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// Skip if no Contact ID found.
				if ( empty( $contact_id ) ) {
					continue;
				}

				// Get Participant data.
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// Skip if no Participant found.
				if ( false === $participant ) {
					continue;
				}

				// Build params to delete Participant.
				$params = [
					'version' => 3,
					'id'      => $participant['participant_id'],
				];

				// Use CiviCRM API to delete Participant.
				$result = civicrm_api( 'Participant', 'delete', $params );

				// Error check.
				if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

					// Log error.
					$e     = new Exception();
					$trace = $e->getTraceAsString();
					$log   = [
						'method'    => __METHOD__,
						'message'   => $result['error_message'],
						'params'    => $params,
						'result'    => $result,
						'backtrace' => $trace,
					];
					$this->plugin->log_error( $log );

				} else {

					// Set up message.
					$subject = sprintf(
						/* translators: 1: The Event title. */
						__( 'You have been unregistered from %s', 'civicrm-eo-attendance' ),
						esc_html( $event_title )
					);

					// Construct message body.
					$message = sprintf(
						/* translators: 1: The Event title, 2: The start date, 3: The start time. */
						__( 'This is to let you know that your registration for the Event "%1$s" on %2$s at %3$s has been cancelled and that you will no longer be attending. If you think this has been done in error, please follow the link below and reply to this conversation on the Spirit of Football website.', 'civicrm-eo-attendance' ),
						$linked_title,
						eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ),
						eo_get_the_start( 'g:ia', $event_id, $occurrence_id )
					);

					// Send message.
					$this->rv_form_notify_user( $attendee_id, $subject, $message );

				}

			}

		}

		// Build return data.
		$data = [
			'civi_event_id' => $civi_event_id,
			'error'         => '0',
			'markup'        => __( 'Thanks!', 'civicrm-eo-attendance' ),
		];

		// Send data to browser.
		wp_send_json( $data );

	}

	/**
	 * Notify a User when they have been registered for an Event.
	 *
	 * @since 0.5.3
	 *
	 * @param int $recipient_id The numeric Id of the User the notify.
	 * @param str $subject The subject of the message.
	 * @param str $content The content of the message.
	 */
	public function rv_form_notify_user( $recipient_id, $subject, $content ) {

		// Bail if anything is amiss.
		if ( empty( $recipient_id ) ) {
			return;
		}
		if ( empty( $subject ) ) {
			return;
		}
		if ( empty( $content ) ) {
			return;
		}

		// Get current User.
		$current_user = wp_get_current_user();

		// Don't notify the recipient if they are the current User.
		if ( (int) $recipient_id === $current_user->ID ) {
			return;
		}

		// Set up message.
		$msg_args = [
			'sender_id'  => $current_user->ID,
			'thread_id'  => false,
			'recipients' => [ $recipient_id ], // Can be an array of usernames, user_ids or mixed.
			'subject'    => $subject,
			'content'    => $content,
		];

		// Send message.
		messages_new_message( $msg_args );

	}

	/**
	 * Check if a User can access Participant management functionality.
	 *
	 * @since 0.5
	 *
	 * @param obj $rendez_vous The Rendez Vous to check.
	 * @return bool $allowed True if allowed, false otherwise.
	 */
	public function rv_form_access_granted( $rendez_vous ) {

		// Get current User.
		$current_user = wp_get_current_user();

		// Allow if User is the Rendez Vous 'organizer'.
		if ( (int) $rendez_vous->organizer === $current_user->ID ) {
			return true;
		}

		// Allow if User is a Group admin.
		if ( groups_is_user_admin( $current_user->ID, $rendez_vous->group_id ) ) {
			return true;
		}

		// Allow if User is a Group mod.
		if ( groups_is_user_mod( $current_user->ID, $rendez_vous->group_id ) ) {
			return true;
		}

		// Disallow.
		return false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check if a Rendez Vous has a Term.
	 *
	 * @since 0.4.7
	 *
	 * @param string $item The Rendez Vous to check.
	 * @return bool $term True if the Rendez Cous has the Term, false otherwise.
	 */
	public function has_term( $item ) {

		// Does this Rendez Vous have a Term?
		if ( isset( $item->type ) && is_array( $item->type ) ) {
			foreach ( $item->type as $type ) {

				// Is this our Term?
				if ( (int) $type->term_id === (int) $this->plugin->civicrm_eo->db->option_get( $this->term_option ) ) {
					return true;
				}

			}
		}

		// Fallback.
		return false;

	}

	/**
	 * Create a Rendez Vous Term.
	 *
	 * @since 0.4.7
	 *
	 * @param string $term_name The Term to add.
	 * @return array|WP_Error $term An array containing the `term_id` and `term_taxonomy_id`.
	 */
	public function term_create( $term_name ) {

		// Create a Rendez Vous Term.
		$term = rendez_vous_insert_term( $term_name );

		// --<
		return $term;

	}

	/**
	 * Delete a Rendez Vous Term.
	 *
	 * @since 0.4.7
	 *
	 * @param int $term_id The ID of the Term.
	 * @return bool|WP_Error $retval Returns false if not Term; true if Term deleted.
	 */
	public function term_delete( $term_id ) {

		// Delete a Rendez Vous Term.
		$retval = rendez_vous_delete_term( $term_id );

		// --<
		return $retval;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * For a given Rendez Vous, get our meta value.
	 *
	 * @since 0.5.1
	 *
	 * @param string $item The Rendez Vous to check.
	 * @return bool True if the Rendez Cous has the meta value, false otherwise.
	 */
	public function get_meta( $item ) {

		// Bail if this isn't a Rendez Vous object.
		if ( ! ( $item instanceof Rendez_Vous_Item ) ) {
			return false;
		}

		// Get meta for this Rendez Vous.
		$rv_meta = get_post_meta( $item->id, $this->month_meta_key, true );

		// There's no meta if we get the default empty string returned.
		if ( '' === $rv_meta ) {
			return false;
		}

		// We're good.
		return $rv_meta;

	}

	/**
	 * Check if a Rendez Vous has our meta value.
	 *
	 * @since 0.5
	 *
	 * @param string $item The Rendez Vous to check.
	 * @return bool True if the Rendez Cous has the meta value, false otherwise.
	 */
	public function has_meta( $item ) {

		// Bail if this isn't a Rendez Vous object.
		if ( ! ( $item instanceof Rendez_Vous_Item ) ) {
			return false;
		}

		// Get meta for this Rendez Vous.
		$rv_meta = get_post_meta( $item->id, $this->month_meta_key, true );

		// There's no meta if we get the default empty string returned.
		if ( '' === $rv_meta ) {
			return false;
		}

		// We're good.
		return true;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Inject a checkbox into Group manage screen.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The numeric ID of the Group.
	 */
	public function group_manage_form_amend( $group_id ) {

		// Get the attendance enabled Group meta.
		$attendance_enabled = groups_get_groupmeta( $group_id, $this->group_meta_key );

		// Set checkbox checked attribute.
		$checked = '';
		if ( ! empty( $attendance_enabled ) ) {
			$checked = ' checked="checked"';
		}

		// Construct checkbox.
		$checkbox = '<input type="checkbox" id="' . esc_attr( $this->group_meta_key ) . '" name="' . esc_attr( $this->group_meta_key ) . '" value="1"' . $checked . '> ';

		// Construct label.
		$label = '<label for="' . esc_attr( $this->group_meta_key ) . '">' .
			$checkbox . esc_html__( 'Enable Attendance', 'civicrm-eo-attendance' ) .
		'</label>';

		// Wrap in divs.
		$enabled_div = '<div class="field-group civicrm-eo-group-enabled"><div class="checkbox">' .
			$label .
		'</div></div>';

		// Get the Organiser IDs from Group meta.
		$organizer_ids = groups_get_groupmeta( $group_id, $this->organizer_meta_key );

		// Init options.
		$options = [];

		// Get Group admins.
		$admins = groups_get_group_admins( $group_id );

		// Get Group mods.
		$mods = groups_get_group_mods( $group_id );

		// Merge arrays.
		$organizers = array_merge( $admins, $mods );

		// Loop.
		foreach ( $organizers as $user ) {

			// Assign selected if this User is an Organizer.
			$selected = '';
			if ( in_array( $user->user_id, (array) $organizer_ids, true ) ) {
				$selected = ' selected="selected"';
			}

			// Construct option.
			$options[] = '<option value="' . esc_attr( $user->user_id ) . '"' . $selected . '>' .
				bp_core_get_user_displayname( $user->user_id ) .
			'</option>';

		}

		// Create options markup.
		$organizer_options = implode( "\n", $options );

		// Wrap in multi-select.
		$organizer_options = '<select id="' . esc_attr( $this->organizer_meta_key ) . '" name="' . esc_attr( $this->organizer_meta_key ) . '[]" multiple="multiple">' .
			$organizer_options .
		'</select>';

		// Construct label.
		$organizer_label = '<label for="' . $this->organizer_meta_key . '">' .
			esc_html__( 'Attendance Organizers', 'civicrm-eo-attendance' ) .
		'</label>';

		// Wrap in divs.
		$organizer_div = '<div class="field-group civicrm-eo-group-organizer"><div class="select">' .
			$organizer_label .
			$organizer_options .
			'<p class="description">' . esc_html__( 'Choose the people responsible for Attendance.', 'civicrm-eo-attendance' ) . '</p>' .
		'</div></div>';

		// Construct markup.
		$markup = $enabled_div . $organizer_div;

		// Show markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $markup;

	}

	/**
	 * Check the value of the checkbox on the Group manage screen.
	 *
	 * @since 0.5.1
	 *
	 * @param int   $group_id The numeric ID of the Group.
	 * @param array $settings The Rendez Vous settings array.
	 */
	public function group_manage_form_submit( $group_id, $settings ) {

		// Sanity checks.
		if ( empty( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}
		if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
			return false;
		}

		// Init defaults.
		$defaults = [
			$this->group_meta_key     => 0,
			$this->organizer_meta_key => [],
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$args = wp_parse_args( $_POST, $defaults );

		// Cast "enabled" value as integer.
		if ( ! empty( $args[ $this->group_meta_key ] ) ) {
			$args[ $this->group_meta_key ] = (int) $args[ $this->group_meta_key ];
		}

		// Cast Organiser IDs as integers.
		if ( ! empty( $args[ $this->organizer_meta_key ] ) ) {
			$args[ $this->organizer_meta_key ] = array_map( 'absint', $args[ $this->organizer_meta_key ] );
		}

		// Go ahead and save the meta values.
		groups_update_groupmeta( $group_id, $this->group_meta_key, $args[ $this->group_meta_key ] );
		groups_update_groupmeta( $group_id, $this->organizer_meta_key, $args[ $this->organizer_meta_key ] );

	}

	/**
	 * Add our CSS to the Group manage screen.
	 *
	 * @since 0.5.3
	 */
	public function group_manage_form_enqueue_styles() {

		// Bail if not on group admin screen.
		if ( ! bp_is_group_admin_screen( 'rendez-vous' ) ) {
			return;
		}

		// Register Select2 styles.
		wp_register_style(
			'ceo_attendance_select2_css',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
			null,
			'4.0.13'
		);

		// Enqueue styles.
		wp_enqueue_style( 'ceo_attendance_select2_css' );

	}

	/**
	 * Add our Javascripts to the Group manage screen.
	 *
	 * @since 0.5.3
	 */
	public function group_manage_form_enqueue_scripts() {

		// Bail if not on group admin screen.
		if ( ! bp_is_group_admin_screen( 'rendez-vous' ) ) {
			return;
		}

		// Register Select2.
		wp_register_script(
			'ceo_attendance_select2_js',
			set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
			[ 'jquery' ],
			'4.0.13',
			true
		);

		// Enqueue script.
		wp_enqueue_script( 'ceo_attendance_select2_js' );

		// Enqueue group admin js.
		wp_enqueue_script(
			'ceo_attendance_select2_custom_js',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-group-admin.js',
			[ 'ceo_attendance_select2_js' ],
			CIVICRM_EO_ATTENDANCE_VERSION,
			true
		);

	}

	/**
	 * Get Group meta value, use default if meta value is not set.
	 *
	 * @since 0.5.1
	 *
	 * @param int   $group_id The Group ID.
	 * @param str   $meta_key The meta key.
	 * @param mixed $default The default value to fallback to.
	 * @return mixed The meta value, or false on failure.
	 */
	public function group_get_option( $group_id = 0, $meta_key = '', $default = '' ) {

		// Sanity check.
		if ( empty( $group_id ) || empty( $meta_key ) ) {
			return false;
		}

		// Get the value.
		$meta_value = groups_get_groupmeta( $group_id, $meta_key );

		// Maybe set default.
		if ( '' === $meta_value ) {
			$meta_value = $default;
		}

		// --<
		return $meta_value;

	}

	/**
	 * Update registrations when a User updates their attendance.
	 *
	 * @since 0.5.1
	 *
	 * @param array $args The arguments.
	 */
	public function registrations_check( $args = [] ) {

		// Bail if something's not right with the array.
		if ( ! isset( $args['id'] ) ) {
			return;
		}
		if ( ! is_int( absint( $args['id'] ) ) ) {
			return;
		}

		// Get reference meta and sanity check.
		$references = get_post_meta( $args['id'], $this->reference_meta_key, true );
		if ( ! is_array( $references ) ) {
			return;
		}

		// Get Rendez Vous.
		$rendez_vous = rendez_vous_get_item( $args['id'] );

		// Bail if this is not an attendance Rendez Vous.
		$rv_meta = $this->get_meta( $rendez_vous );
		if ( false === $rv_meta ) {
			return;
		}

		// Bail if this fails for some reason.
		if ( ! ( $rendez_vous instanceof Rendez_Vous_Item ) ) {
			return;
		}

		// Get current User.
		$current_user = wp_get_current_user();

		// Get the days that this attendee used to have.
		$this->attending = [];
		foreach ( $rendez_vous->days as $timestamp => $attendee_ids ) {
			if ( in_array( $current_user->ID, $attendee_ids, true ) ) {
				$this->attending[ $timestamp ] = $references[ $timestamp ];
			}
		}

	}

	/**
	 * Update registrations when a User updates their attendance.
	 *
	 * This only removes registrations where the User was previously registered
	 * and has now indicated that they are no longer available. It should maybe
	 * inform the organizer that the person has been de-registered.
	 *
	 * @since 0.5.1
	 *
	 * @param array  $args The arguments.
	 * @param int    $attendee_id The User ID of the attendee.
	 * @param object $rendez_vous The Rendez Vous.
	 */
	public function registrations_update( $args = [], $attendee_id = 0, $rendez_vous = null ) {

		// Bail if we don't have our attending array.
		if ( ! isset( $this->attending ) ) {
			return;
		}
		if ( ! is_array( $this->attending ) ) {
			return;
		}
		if ( count( $this->attending ) === 0 ) {
			return;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return;
		}

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return;
		}

		// Get the CiviCRM Contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

		// Look for missing items.
		$missing_items = [];
		foreach ( $this->attending as $timestamp => $civi_event_id ) {
			if ( ! in_array( $timestamp, $args['days'], true ) ) {
				$missing_items[ $timestamp ] = $civi_event_id;
			}
		}

		// Bail if there are no missing items.
		if ( count( $missing_items ) === 0 ) {
			return;
		}

		// Loop through the rest.
		foreach ( $missing_items as $timestamp => $civi_event_id ) {

			// Skip if not a timestamp.
			if ( ! is_long( $timestamp ) ) {
				continue;
			}

			// Get date of Event.
			$date = gmdate( 'Y-m-d H:i:s', $timestamp );

			// Skip to next if it is a past Event.
			if ( $this->is_past( $date ) ) {
				continue;
			}

			// Build params to get Participant.
			$params = [
				'version'    => 3,
				'contact_id' => $contact_id,
				'event_id'   => $civi_event_id,
			];

			// Get Participant instances.
			$participants = civicrm_api( 'Participant', 'get', $params );

			// Error check.
			if ( isset( $participants['is_error'] ) && 1 === (int) $participants['is_error'] ) {

				// Log and skip.
				$e     = new Exception();
				$trace = $e->getTraceAsString();
				$log   = [
					'method'       => __METHOD__,
					'message'      => $participants['error_message'],
					'params'       => $params,
					'participants' => $participants,
					'backtrace'    => $trace,
				];
				$this->plugin->log_error( $log );
				continue;

			}

			// Skip if not registered.
			if ( count( $participants['values'] ) === 0 ) {
				continue;
			}

			// We should only have one registration.
			$registration = array_pop( $participants['values'] );

			// Build params to delete Participant.
			$params = [
				'version' => 3,
				'id'      => $registration['id'],
			];

			// Use CiviCRM API to delete Participant.
			$result = civicrm_api( 'Participant', 'delete', $params );

			// Error check.
			if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

				// Log error and skip.
				$e     = new Exception();
				$trace = $e->getTraceAsString();
				$log   = [
					'method'    => __METHOD__,
					'message'   => $result['error_message'],
					'params'    => $params,
					'result'    => $result,
					'backtrace' => $trace,
				];
				$this->plugin->log_error( $log );
				continue;

			}

			// Send message if component is available.
			if ( bp_is_active( 'messages' ) ) {

				// Construct subject.
				$subject = sprintf(
					/* translators: 1: The Contact name, 2: The Event title. */
					__( '%1$s cannot attend "%2$s"', 'civicrm-eo-attendance' ),
					$registration['display_name'],
					$registration['event_title']
				);

				// Construct link to Rendez Vous.
				$link             = rendez_vous_get_single_link( $rendez_vous->id, $rendez_vous->organizer );
				$rendez_vous_link = '<a href="' . esc_url( $link ) . '">' . esc_html( $rendez_vous->title ) . '</a>';

				// Construct content..
				$content = sprintf(
					/* translators: 1: The Contact name, 2: The Event title, 3: The Event date, 4: The Event time, 5: The link. */
					__( 'Unfortunately %1$s can no longer attend "%2$s" on %3$s at %4$s and their registration for this Event has been cancelled. Please visit the "%5$s" to review registrations for this Event.', 'civicrm-eo-attendance' ),
					$registration['display_name'],
					$registration['event_title'],
					date_i18n( get_option( 'date_format' ), $timestamp ),
					date_i18n( get_option( 'time_format' ), $timestamp ),
					$rendez_vous_link
				);

				// Set up message.
				$msg_args = [
					'sender_id'  => $attendee_id,
					'thread_id'  => false,
					'recipients' => $rendez_vous->organizer,
					'subject'    => $subject,
					'content'    => $content,
				];

				// Send message.
				messages_new_message( $msg_args );

			}

		}

	}

	/**
	 * Check if an Event is a past Event.
	 *
	 * @since 0.4.6
	 *
	 * @param str $end_date The end date string for a CiviEvent.
	 * @return bool $past_event True if the Event is past, false otherwise.
	 */
	public function is_past( $end_date ) {

		// Init past Event flag.
		$past_event = false;

		// Override if it's a past Event.
		$now = new DateTime( 'now', eo_get_blog_timezone() );
		$end = new DateTime( $end_date, eo_get_blog_timezone() );
		if ( $end < $now ) {
			$past_event = true;
		}

		// --<
		return $past_event;

	}

	/**
	 * Check if the current User is an Organiser.
	 *
	 * @since 0.5.3
	 *
	 * @param int $group_id The numeric ID of the Group.
	 * @return int|bool $is_organiser False if the current User is not an organiser, User ID otherwise.
	 */
	public function is_organiser( $group_id ) {

		// Assume not Organiser.
		$is_organiser = false;

		// Bail if the current User is not logged in.
		if ( ! is_user_logged_in() ) {
			return $is_organiser;
		}

		// Bail if we don't have any organizers.
		$organizer_ids = groups_get_groupmeta( $group_id, $this->organizer_meta_key );
		if ( empty( $organizer_ids ) ) {
			return $is_organiser;
		}

		// Always allow super admins.
		if ( is_super_admin() ) {
			return array_pop( $organizer_ids );
		}

		// Bail if the current User is not an organizer.
		$current_user = wp_get_current_user();
		if ( ! in_array( $current_user->ID, (array) $organizer_ids, true ) ) {
			return $is_organiser;
		}

		// Okay this is an Organiser.
		$is_organiser = $current_user->ID;

		// --<
		return $is_organiser;

	}

}
