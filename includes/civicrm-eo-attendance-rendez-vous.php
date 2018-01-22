<?php

/**
 * CiviCRM Event Organiser Attendance Rendez Vous Management Class.
 *
 * A class that encapsulates Rendez Vous Management functionality.
 *
 * @since 0.4.7
 */
class CiviCRM_EO_Attendance_Rendez_Vous {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var object $plugin The plugin object
	 */
	public $plugin;

	/**
	 * CiviCRM EO Plugin Admin object reference.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var object $eo The CiviCRM EO Plugin Admin object reference
	 */
	public $db;

	/**
	 * Rendez Vous Term ID Option name.
	 *
	 * No longer used, but left for reference until I get rid of it completely.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $term_option The Rendez Vous Term ID option name
	 */
	public $term_option = 'civicrm_eo_event_rv_term_id';

	/**
	 * Rendez Vous "Month" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $month_meta_key The Rendez Vous "Month" meta key name
	 */
	public $month_meta_key = '_rendez_vous_month';

	/**
	 * Rendez Vous "Reference Array" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $reference_meta_key The Rendez Vous "Month" meta key name
	 */
	public $reference_meta_key = '_rendez_vous_reference';

	/**
	 * Group "Attendance Enabled" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $group_meta_key The group "Attendance Enabled" meta key name
	 */
	public $group_meta_key = '_civicrm_eo_event_attendance';

	/**
	 * Group "Rendez Vous Organizer" meta key name.
	 *
	 * This group meta value is set per group to determine the person in the
	 * group who is responsible for managing the attendance Rendez Vous. The UI
	 * actually allows any group admin or mod to manage registrations, but there
	 * has to be one person in charge.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $group_meta_key The group "Rendez Vous Organizer" meta key name
	 */
	public $organizer_meta_key = '_civicrm_eo_event_organizer';

	/**
	 * The number of future Rendez Vous to generate.
	 *
	 * @since 0.5
	 * @access public
	 * @var int $future_count The number of future Rendez Vous to generate
	 */
	public $future_count = 6;



	/**
	 * Initialises this object.
	 *
	 * @since 0.4.7
	 */
	public function __construct() {

		// register hooks
		$this->register_hooks();

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.4.7
	 *
	 * @param object $parent The parent object
	 */
	public function set_references( $parent ) {

		// store
		$this->plugin = $parent;
		$this->db = $parent->civicrm_eo->db;

	}



	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.4.7
	 */
	public function register_hooks() {

		// wrap table in div
		add_filter( 'rendez_vous_single_get_the_dates', array( $this, 'rv_form_wrap_table'), 10, 2 );

		// filter the column titles
		add_filter( 'rendez_vous_single_get_the_dates_header', array( $this, 'rv_form_column_header'), 10, 2 );

		// add extra row with registration links
		add_filter( 'rendez_vous_single_get_the_dates_rows_after', array( $this, 'rv_form_last_row'), 10, 3 );

		// filter rendez vous form classes
		add_filter( 'rendez_vous_single_the_form_class', array( $this, 'rv_form_class'), 10, 2 );

		// remove edit button on Attendance Rendez Vous
		add_filter( 'bp_get_button', array( $this, 'rv_form_button'), 10, 3 );

		// add refresh button on Rendez Vous group archive
		add_filter( 'bp_get_button', array( $this, 'rv_archive_button'), 10, 3 );

		// add scripts to single view Rendez Vous
		add_action( 'rendez_vous_single_content_before', array( $this, 'enqueue_scripts') );

		// add AJAX handler
		add_action( 'wp_ajax_event_attendance_form_process', array( $this, 'rv_form_process' ) );

		// hook into BPEO and remove original taxonomy metabox
		add_action( 'add_meta_boxes_event', array( $this, 'radio_tax_enforce' ), 3 );

		// allow a single Rendez Vous to be refreshed
		add_action( 'rendez_vous_single_screen', array( $this, 'refresh_rv_single' ) );

		// allow all Rendez Vous to be refreshed
		add_action( 'bp_screens', array( $this, 'refresh_rv_all' ), 3 );

		// add form element to group manage screen
		add_action( 'rendez_vous_group_edit_screen_after', array( $this, 'group_manage_form_amend' ), 10, 1 );

		// check element when group manage screen is submitted
		add_action( 'rendez_vous_group_edit_screen_save', array( $this, 'group_manage_form_submit' ), 10, 2 );

		// check registrations when a user updates their attendance
		add_action( 'rendez_vous_before_attendee_prefs', array( $this, 'registrations_check' ), 10, 1 );

		// update registrations when a user updates their attendance
		add_action( 'rendez_vous_after_attendee_prefs', array( $this, 'registrations_update' ), 9, 3 );

	}



	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.4.7
	 */
	public function activate() {

		// create data entities
		//$this->entities_create();

	}



	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

	}



	//##########################################################################



	/**
	 * Create our custom data entities.
	 *
	 * Creates a Rendez Vous term for our custom Rendez Vous.
	 *
	 * No longer used.
	 *
	 * @since 0.4.7
	 */
	public function entities_create() {

		// bail if we've already done this
		if ( 'fgffgs' !== $this->db->option_get( $this->term_option, 'fgffgs' ) ) return;

		// define term name
		$term_name = __( 'Attendance', 'civicrm-eo-attendance' );

		// create it
		$new_term = $this->term_create( $term_name );

		// log and bail on error
		if ( is_wp_error( $new_term ) ) {

			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'new_term' => $new_term,
				'backtrace' => $trace,
			), true ) );
			return;

		}

		// store as plugin option
		$this->db->option_save( $this->term_option, $new_term['term_id'] );

	}



	/**
	 * Add our Javascript for registering Attendees.
	 *
	 * @since 0.4.8
	 */
	public function enqueue_scripts() {

		// get current item
		$item = rendez_vous()->item;

		// bail if this Rendez Vous doesn't have our custom meta value
		if ( ! $this->has_meta( $item ) ) return;

		// get current user
		$current_user = wp_get_current_user();

		// only show if user is allowed to manage this rendez vous
		if ( ! $this->rv_form_access_granted( $item ) ) return;

		// add script to footer
		wp_enqueue_script(
			'civicrm-eo-attendance-rvm',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-rvm.js',
			array( 'jquery' ),
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // in footer
		);

		// translations
		$localisation = array(
			'submit' => __( 'Submit', 'civicrm-eo-attendance' ),
			'update' => __( 'Update', 'civicrm-eo-attendance' ),
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'leader' => __( 'You must choose someone to be the event leader.', 'civicrm-eo-attendance' ),
		);

		// define settings
		$settings = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);

		// localisation array
		$vars = array(
			'localisation' => $localisation,
			'settings' => $settings,
		);

		// localise the WordPress way
		wp_localize_script(
			'civicrm-eo-attendance-rvm',
			'CiviCRM_EO_Attendance_RVM_Settings',
			$vars
		);

	}



	/**
	 * Force Radio Buttons for Taxonomies to remove the original taxonomy metabox.
	 *
	 * @since 0.4.8
	 */
	public function radio_tax_enforce() {

		// bail if we're in the admin area
	    if ( defined( 'WP_NETWORK_ADMIN' ) ) return;

		// bail if no RBFT instance
		if ( ! function_exists( 'Radio_Buttons_for_Taxonomies' ) ) return;

		// get instance
		$rbft = Radio_Buttons_for_Taxonomies();

		// sanity check
		if ( ! isset( $rbft->taxonomies ) OR ! is_array( $rbft->taxonomies ) ) return;

		// force removal of metaboxes
		foreach( $rbft->taxonomies AS $tax ) {
			$tax->remove_meta_box();
		}

	}



	//##########################################################################



	/**
	 * Intercept clicks on "Refresh" button for a single Rendez Vous.
	 *
	 * @since 0.5
	 */
	public function refresh_rv_single() {

		// was the "refresh" button clicked?
		if ( ! empty( $_GET['action'] ) && 'refresh' == $_GET['action'] && ! empty( $_GET['rdv'] ) ) {

			// get redirect and Rendez Vous ID
			$redirect = remove_query_arg( array( 'rdv', 'action', 'n' ), wp_get_referer() );
			$rendez_vous_id = absint( $_GET['rdv'] );

			// do the update
			$updated_id = $this->rv_update( $rendez_vous_id );

			// appropriate error messages
			if ( $updated_id === false ) {
				bp_core_add_message( __( 'Refreshing this Rendez-vous failed.', 'civicrm-eo-attendance' ), 'error' );
			} else {
				bp_core_add_message( __( 'Rendez-vous successfully refreshed.', 'civicrm-eo-attendance' ) );
				$redirect = add_query_arg( 'rdv', $updated_id, $redirect );
			}

			// finally redirect
			bp_core_redirect( $redirect );

		}

	}



	/**
	 * Intercept clicks on "Refresh" button on Group Rendez Vous listing page.
	 *
	 * @since 0.5
	 */
	public function refresh_rv_all() {

		// was the "refresh" button clicked?
		if ( empty( $_GET['action'] ) ) return;
		if ( 'refresh_all' != trim( $_GET['action'] ) ) return;

		// is this the Rendez Vous component group archive?
		if ( bp_is_group() AND bp_is_current_action( rendez_vous()->get_component_slug() ) AND empty( $_REQUEST['rdv'] ) ) {

			// get redirect
			$redirect = remove_query_arg( array( 'action', 'rdv', 'n' ), wp_get_referer() );

			// get group ID
			$group_id = bp_get_current_group_id();

			// get the current and future Rendez Vous
			$args = array(
				'per_page' => ( $this->future_count + 1 ),
				'group_id' => $group_id,
				'orderby' => 'rendez_vous_date',
				'order'   => 'DESC',
				'no_cache' => true,
			);

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'redirect' => $redirect,
				'group_id' => $group_id,
				'args' => $args,
				'backtrace' => $trace,
			), true ) );
			*/

			// add our meta query via a filter
			add_filter( 'rendez_vous_query_args', array( $this, 'refresh_filter_query' ), 10, 1 );

			// do the query
			$has_rendez_vous = rendez_vous_has_rendez_vouss( $args );

			// remove our filter
			remove_filter( 'rendez_vous_query_args', array( $this, 'refresh_filter_query' ), 10 );

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'args' => $args,
				'has_rendez_vous' => $has_rendez_vous,
				'backtrace' => $trace,
			), true ) );
			*/

			$rv_meta = array();
			$rv_ids = array();

			// do the loop to find existing IDs
			if ( $has_rendez_vous ) {
				while ( rendez_vous_the_rendez_vouss() ) {
					rendez_vous_the_rendez_vous();
					$rv_ids[] = $id = rendez_vous_get_the_rendez_vous_id();
					$rv_meta[$id] = get_post_meta( $id, $this->month_meta_key, true );
				}
			}

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'has_rendez_vous' => $has_rendez_vous,
				'rv_ids' => $rv_ids,
				'rv_meta' => $rv_meta,
				'backtrace' => $trace,
			), true ) );
			*/

			/*
			// ============================== delete ===============================
			foreach( $rv_ids AS $rv_id ) {
				$this->rv_delete( $rv_id );
			}
			return;
			// ============================== delete ===============================
			*/

			// init error flag
			$has_error = false;

			// get months to check
			$checks = $this->refresh_get_months();
			foreach( $checks AS $month ) {

				// create DateTime object
				$datetime = new DateTime( $month, eo_get_blog_timezone() );

				// create it if it doesn't exist, update otherwise
				if ( ! in_array( $month, $rv_meta ) ) {
					$returned_id = $this->rv_create( $datetime, $group_id );
				} else {
					$flipped = array_flip( $rv_meta );
					$returned_id = $this->rv_update( $flipped[$month] );
				}

				// error check
				if ( $returned_id === false ) {
					$has_error = true;
				}

			}

			// appropriate error messages
			if ( $has_error === true ) {
				bp_core_add_message( __( 'Refreshing all Rendez-vous failed.', 'civicrm-eo-attendance' ), 'error' );
			} else {
				bp_core_add_message( __( 'All Rendez-vous successfully refreshed.', 'civicrm-eo-attendance' ) );
			}

			// finally redirect
			bp_core_redirect( $redirect );

		}

	}



	/**
	 * Filter the query for refresh action only.
	 *
	 * @since 0.5
	 *
	 * @param array $query_args The existing query args
	 * @return array $query_args The modified query args
	 */
	public function refresh_filter_query( $query_args = array() ) {

		// get months to check
		$checks = $this->refresh_get_months();

		// find all Rendez Vous with these meta values
		$month_query = array(
			array(
				'key'     => $this->month_meta_key,
				'value'   => $checks,
				'compare' => 'IN',
			)
		);

		if ( empty( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array( $month_query );
		} else {
			$query_args['meta_query'][] = $month_query;
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'checks' => $checks,
			'backtrace' => $trace,
		), true ) );
		*/

		// --<
		return $query_args;

	}



	/**
	 * Get an array of months in the future.
	 *
	 * @since 0.5
	 *
	 * @return array $checks The array of months to check
	 */
	public function refresh_get_months() {

		// get now
		$now = new DateTime( 'now', eo_get_blog_timezone() );

		// get first day of this month
		$month_start = $now->format( 'Y-m-01' );
		$start = new DateTime( $month_start, eo_get_blog_timezone() );

		// check this month plus N months ahead
		$checks = array( $start->format('Y-m-d') );
		for ( $i = 1; $i < ( $this->future_count + 1 ) ; $i = $i +1 ) {
			$start->add( new DateInterval( 'P1M' ) );
			$checks[] = $start->format('Y-m-d');
		}

		// --<
		return $checks;

	}



	//##########################################################################



	/**
	 * Create a custom Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param DateTime $datetime The DateTime object for the month to create an RV for
	 * @param int $group_id The numeric ID of the group
	 * @return int|bool $updated_id The ID of the created Rendez Vous (false on failure or true if skipped)
	 */
	public function rv_create( $datetime, $group_id = 0 ) {

		// members group ID
		if ( $group_id === 0 ) {
			$group_id = bp_get_current_group_id();
		}

		// get members of group
		$attendee_ids = $this->rv_attendees_get( $group_id );

		// define title
		$title = sprintf(
			__( 'Availability for %s', 'civicrm-eo-attendance' ),
			date_i18n( 'F Y', $datetime->getTimestamp() )
		);

		// events on this month
		$month_start = $datetime->format( 'Y-m-01' );
		$month_end = $datetime->format( 'Y-m-t' );

		// construct args
		$event_args = apply_filters( 'civicrm_event_organiser_rendez_vous_event_args', array(
			'event_start_after'  => $month_start,
			'event_start_before' => $month_end,
		) );

		// get event data
		$events = eo_get_events( $event_args );

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'events' => $events,
			'backtrace' => $trace,
		), true ) );
		*/

		// init event data
		$days = array();

		// init reference array
		$references = array();

		// init used
		$used = array();

		// format days array for these events
		if ( count( $events ) > 0 ) {
			foreach( $events AS $event ) {

				// get CiviEvent ID
				$civi_event_id = $this->db->get_civi_event_id_by_eo_occurrence_id( $event->ID, $event->occurrence_id );

				// skip unless we have a CiviEvent
				if ( $civi_event_id === false ) continue;

				// get start
				$event_start_time = eo_get_the_start( DATETIMEOBJ, $event->ID, $event->occurrence_id );
				$timestamp = $event_start_time->getTimestamp();

				// ensure no duplicates by adding a trivial amount
				while( in_array( $timestamp, $used ) ) {
					$timestamp++;
				}

				// add to RV days
				$days[$timestamp] = array();

				// add CiviEvent ID to reference array
				$references[$timestamp] = $civi_event_id;

				// add to used array
				$used[] = $timestamp;

			}
		}

		// no need to create if there are no dates
		if ( count( $days ) === 0 ) return true;

		// get organizer
		$organizer_id = groups_get_groupmeta( $group_id, $this->organizer_meta_key );

		// bail if we don't have one
		if ( empty( $organizer_id ) ) return true;

		// construct create array
		$rendez_vous = array(
			'title' => $title,
			'organizer' => $organizer_id,
			'duration' => '01:00',
			'venue' => __( 'Erfurt', 'civicrm-eo-attendance' ),
			'status' => 'draft',
			'group_id' => $group_id,
			'attendees' => $attendee_ids,
			'days' => $days,
		);

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'rendez_vous' => $rendez_vous,
			'backtrace' => $trace,
		), true ) );
		*/

		// create it
		$rendez_vous_id = $this->rv_save( $rendez_vous );

		// log and bail on error
		if ( ! is_int( $rendez_vous_id ) ) {

			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'new_rv' => $rendez_vous_id,
				'backtrace' => $trace,
			), true ) );
			return false;

		}

		// store the event IDs for the rendez vous item
		add_post_meta( $rendez_vous_id, $this->reference_meta_key, $references, true );

		// store the month for the rendez vous
		add_post_meta( $rendez_vous_id, $this->month_meta_key, $month_start, true );

		// update args
		$rendez_vous['id'] = $rendez_vous_id;
		$rendez_vous['status'] = 'publish';

		// publish the Rendez Vous
		$rendez_vous_id = $this->rv_save( $rendez_vous );

		// --<
		return $rendez_vous_id;

	}



	/**
	 * Update a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param int|bool $rendez_vous_id The numeric ID of the Rendez Vous to update
	 * @return int|bool $updated_id The ID of the updated Rendez Vous, or false on failure
	 */
	public function rv_update( $rendez_vous_id ) {

		// get Rendez Vous to rebuild
		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );

		// bail if this fails for some reason
		if ( ! ( $rendez_vous instanceof Rendez_Vous_Item ) ) return false;

		// bail if this Rendez Vous doesn't have our custom meta value
		if ( ! $this->has_meta( $rendez_vous ) ) return false;

		// get members of group
		$attendee_ids = $this->rv_attendees_get( $rendez_vous->group_id );

		// get reference meta and sanity check
		$references = get_post_meta( $rendez_vous->id, $this->reference_meta_key, true );
		if ( ! is_array( $references ) ) $references = array();

		// get final date
		$final = new DateTime( $rendez_vous->older_date, eo_get_blog_timezone() );

		// events on this month
		$month_start = $final->format( 'Y-m-01' );
		$month_end = $final->format( 'Y-m-t' );

		// construct args
		$event_args = apply_filters( 'civicrm_event_organiser_rendez_vous_event_args', array(
			'event_start_after'  => $month_start,
			'event_start_before' => $month_end,
		) );

		// get event data
		$events = eo_get_events( $event_args );

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'events' => $events,
			'backtrace' => $trace,
		), true ) );
		*/

		// init comparison arrays
		$correspondences = array();
		$new_items = array();
		$missing_items = array();

		// parse events
		if ( count( $events ) > 0 ) {
			foreach( $events AS $event ) {

				// get CiviEvent ID
				$civi_event_id = $this->db->get_civi_event_id_by_eo_occurrence_id( $event->ID, $event->occurrence_id );

				// skip unless we have a CiviEvent
				if ( $civi_event_id === false ) continue;

				// always add to correspondences
				$correspondences[$event->occurrence_id] = $civi_event_id;

				// add to array if new
				if ( ! in_array( $civi_event_id, $references ) ) {
					$new_items[$civi_event_id] = array( $event->ID, $event->occurrence_id );
				}

			}
		}

		// parse existing event references
		if ( count( $references ) > 0 ) {
			foreach( $references AS $timestamp => $civi_event_id ) {

				// add to array if missing
				if ( ! in_array( $civi_event_id, $correspondences ) ) {
					$missing_items[$timestamp] = $civi_event_id;
				}

			}
		}

		// are there any new items to add?
		if ( count( $new_items ) > 0 ) {

			// get existing timestamps
			$used = array_keys( $rendez_vous->days );

			// parse each new item
			foreach( $new_items AS $civi_event_id => $event_data ) {

				// get timestamp
				$event_start_time = eo_get_the_start( DATETIMEOBJ, $event_data[0], $event_data[1] );
				$timestamp = $event_start_time->getTimestamp();

				// ensure no duplicates by adding a trivial amount
				while( in_array( $timestamp, $used ) ) {
					$timestamp++;
				}

				// add the timestamp to the Rendez Vous
				$rendez_vous->days[$timestamp] = array();

				// add CiviEvent ID to reference array
				$references[$timestamp] = $civi_event_id;

			}

		}

		// are there any missing items?
		if ( count( $missing_items ) > 0 ) {
			foreach( $missing_items AS $timestamp => $civi_event_id ) {

				// remove from the Rendez Vous it if it's there
				if ( isset( $rendez_vous->days[$timestamp] ) ) {
					unset( $rendez_vous->days[$timestamp] );
				}

				// remove from the reference array it if it's there
				if ( isset( $references[$timestamp] ) ) {
					unset( $references[$timestamp] );
				}

			}
		}

		// if there are no changes to dates
		if ( count( $missing_items ) == 0 AND count( $new_items ) == 0 ) {

			// compare like-with-like
			sort( $rendez_vous->attendees );
			sort( $attendee_ids );

			// bail if there is no change of members
			if ( $rendez_vous->attendees == $attendee_ids ) return $rendez_vous_id;

		}

		// define title
		$title = sprintf(
			__( 'Availability for %s', 'civicrm-eo-attendance' ),
			date_i18n( 'F Y', $final->getTimestamp() )
		);

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'rendez_vous' => $rendez_vous,
			'references' => $references,
			'title' => $title,
			'locale' => get_locale(),
			'locale-php' => setlocale( LC_ALL, 0 ),
			'locale-wp' => eo_get_blog_timezone(),
			'getTimestamp' => $final->getTimestamp(),
			'date_i18n-wp' => date_i18n( 'F Y', $final->getTimestamp() ),
			//'backtrace' => $trace,
		), true ) );
		*/

		// construct update array
		$rv_data = array(
			'id' => $rendez_vous->id,
			'title' => $title,
			'duration' => '01:00',
			'venue' => $rendez_vous->venue,
			'status' => 'publish',
			'group_id' => $rendez_vous->group_id,
			'attendees' => $attendee_ids,
			'days' => $rendez_vous->days,
		);

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'rendez_vous' => $rendez_vous,
			'references' => $references,
			'month_start' => $month_start,
			'month_end' => $month_end,
			'events' => $events,
			'new_items' => $new_items,
			'missing_items' => $missing_items,
			'rv_data' => $rv_data,
			'backtrace' => $trace,
		), true ) );
		*/

		// update the Rendez Vous
		$updated_id = $this->rv_save( $rv_data );

		// bail if this fails
		if ( empty( $updated_id ) ) return false;

		// update days
		$this->rv_days_update( $updated_id, $rendez_vous->days );

		// store the event IDs for the rendez vous
		update_post_meta( $updated_id, $this->reference_meta_key, $references );

		// store the month for the rendez vous
		update_post_meta( $updated_id, $this->month_meta_key, $month_start );

		// --<
		return $updated_id;

	}



	/**
	 * Create a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param array $args The array of params for the Rendez Vous
	 * @return int|bool $rendez_vous_id The numeric ID of the Rendez Vous post, or false on failure
	 */
	public function rv_save( $args ) {

		// create a rendez vous
		$rendez_vous_id = rendez_vous_save( $args );

		// --<
		return $rendez_vous_id;

	}



	/**
	 * Delete a Rendez Vous.
	 *
	 * @since 0.4.8
	 *
	 * @param int $id The numeric ID of the Rendez Vous to delete
	 * @return bool $success True if successfully deleted, false on failure
	 */
	public function rv_delete( $id ) {

		// delete a rendez vous
		$success = rendez_vous_delete_item( $id );

		// --<
		return $success;

	}



	/**
	 * Update the days in a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param int $rendez_vous_id The numeric ID of the Rendez Vous post
	 * @param array $days The array of dates for the Rendez Vous
	 */
	public function rv_days_update( $rendez_vous_id, $days ) {

		// make sure we have the "none" option
		if ( ! in_array( 'none', array_keys( $days ) ) ) {
			$days['none'] = array();
		}

		// update the days in the rendez vous item
		update_post_meta( $rendez_vous_id, '_rendez_vous_days', $days );

	}



	/**
	 * Get the attendees for a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param array $args The array of params for the Rendez Vous
	 * @return array $attendee_ids An array of numeric IDs of the Rendez Vous users
	 */
	public function rv_attendees_get( $group_id ) {

		// bail if no BuddyPress
		if ( ! function_exists( 'buddypress' ) ) return array();

		// perform the group member query
		$members = new BP_Group_Member_Query( array(
			'group_id' => $group_id,
			'type' => 'alphabetical',
			'per_page' => 10000,
			'page' => 1,
			'group_role' => array( 'member', 'mod', 'admin' ),
		) );

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'group_id' => $group_id,
			'members' => $members,
			'backtrace' => $trace,
		), true ) );
		*/

		// bail if no results
		if ( count( $members->results ) == 0 ) return array();

		// structure the return
		$attendee_ids = array_keys( $members->results );

		// --<
		return $attendee_ids;

	}



	//##########################################################################



	/**
	 * Filters the "New Rendez-vous" button output.
	 *
	 * This is a bit of a hack, in that we're appending our button to the output
	 * of an existing button. Preferable to have a hook to add just this button
	 * but there isn't one. So, light touch hack instead.
	 *
	 * @since 0.5
	 *
	 * @param string $contents Button context to be used.
	 * @param array $args Array of args for the button.
	 * @param BP_Button $button BP_Button object.
	 * @return string $contents The modified Button context to be used.
	 */
	public function rv_archive_button( $contents, $args, $button ) {

		// is this a Rendez Vous button?
		if ( isset( $args['component'] ) AND $args['component'] == 'rendez_vous' ) {

			// is this a New Rendez Vous button?
			if ( isset( $args['wrapper_id'] ) AND $args['wrapper_id'] == 'new-rendez-vous' ) {

				// is this the Rendez Vous component?
				if ( bp_is_group() AND bp_is_current_action( rendez_vous()->get_component_slug() ) ) {

					// get the currently displayed group ID
					$group_id = bp_get_current_group_id();

					// is attendance enabled for this group?
					if ( $this->group_get_option( $group_id, $this->group_meta_key ) ) {

						// build the URL we want
						$current_url = home_url( add_query_arg( array() ) );
						$url = add_query_arg( 'action', 'refresh_all', $current_url );

						// construct link
						$link = '<a href="' . esc_url( $url ) . '">' .
									__( 'Refresh All', 'civicrm-eo-attendance' ) .
								'</a>';

						// add in our button
						$contents = str_replace(
							'</div>',
							'</div><div class="generic-button civicrm-eo-generic-button">' . $link . '</div>',
							$contents
						);

					}

				}

			}

		}

		// --<
		return $contents;

	}



	//##########################################################################



	/**
	 * Filters the single Rendez Vous "Edit" button output.
	 *
	 * @since 0.4.8
	 *
	 * @param string $contents Button context to be used.
	 * @param array $args Array of args for the button.
	 * @param BP_Button $button BP_Button object.
	 * @return string $contents The modified Button context to be used.
	 */
	public function rv_form_button( $contents, $args, $button ) {

		// is this a Rendez Vous button?
		if ( isset( $args['component'] ) AND $args['component'] == 'rendez_vous' ) {

			// is this a Rendez Vous edit button?
			if ( isset( $args['wrapper_id'] ) AND $args['wrapper_id'] == 'rendez-vous-edit-btn' ) {

				// get current item
				$item = rendez_vous()->item;

				// does this Rendez Vous have our custom meta value?
				if ( $this->has_meta( $item ) ) {

					// change action, class and title
					$contents = str_replace(
						array(
							'action=edit',
							'class="edit-rendez-vous"',
							__( 'Edit', 'rendez-vous' )
						),
						array(
							'action=refresh',
							'class="refresh-rendez-vous"',
							__( 'Refresh', 'civicrm-eo-attendance' )
						),
						$contents
					);

					// override button
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
	 * @param str $class The existing space-delimited classes
	 * @param obj $item The Rendez Vous object
	 * @return str $class The existing space-delimited classes
	 */
	public function rv_form_class( $class, $item ) {

		// bail if we don't have the custom meta
		if ( ! $this->has_meta( $item ) ) return $class;

		// add identifier
		$class .= ' rendez-vous-civicrm-eo-attendance';

		// --<
		return $class;

	}



	/**
	 * Wrap a Rendez Vous table in a div so it can be scrolled sideways.
	 *
	 * @since 0.4.7
	 *
	 * @param str $output The existing table HTML
	 * @param str $view The view (either 'edit' or 'view')
	 * @return str $output The modified table HTML
	 */
	public function rv_form_wrap_table( $output, $view ) {

		// wrap in div
		$output = '<div class="civicrm-eo-attendance-table">' . $output . '</div>';

		// --<
		return $output;

	}



	/**
	 * Modify the column headers for a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param str $header The existing column header
	 * @param int $date The UNIX timestamp
	 * @return str $header The modified column header
	 */
	public function rv_form_column_header( $header, $date ) {

		// do we have the meta?
		if ( ! isset( $this->item_meta ) ) {

			// get current item
			$item = rendez_vous()->item;

			// get reference meta
			$this->item_meta = get_post_meta( $item->id, $this->reference_meta_key, true );

		}

		// start from scratch
		$col_header = '';

		// modify header to include event title
		if ( is_long( $date ) ) {

			// get event ID
			$civi_event_id = isset( $this->item_meta[$date] ) ? $this->item_meta[$date] : 0;

			// get event ID
			$event_id = $this->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

			// get occurrence ID
			$occurrence_id = $this->db->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );

			// get event title
			$event_title = get_the_title( $event_id );

			// get event permalink
			$event_link = get_permalink( $event_id );

			// construct title
			$linked_title = '<a href="' . esc_url( $event_link ) . '">' . esc_html( $event_title ) . '</a>';

			$col_header .= '<div class="title">' . $linked_title . '</div>';
			$col_header .= '<div class="date">' . eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ) . '</div>';
			$col_header .= '<div class="time">' . eo_get_the_start( 'g:ia', $event_id, $occurrence_id ) . '</div>';

		} else {

			// show useful message
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
	 * @param str $header The existing output
	 * @param array $header The array of dates.
	 * @param str $view The output mode ('view' or 'edit').
	 * @return str $header The modified output
	 */
	public function rv_form_last_row( $output, $header, $view ) {

		// get current item
		$item = rendez_vous()->item;

		// bail if this Rendez Vous doesn't have our custom meta value
		if ( ! $this->has_meta( $item ) ) return $output;

		// open row
		$output .= '<tr><td class="rendez-vous-date-blank">&nbsp;</td>';

		// add register link
		foreach ( $header AS $date ) {

			$output .= '<td class="rendez-vous-date">';
			$output .= $this->rv_form_column_header( $header, $date );
			$output .= '</td>';

		}

		// close row
		$output .= '</tr>';

		// bail if this user cannot access the tools
		if ( ! $this->rv_form_access_granted( $item ) ) return $output;

		// init form markup
		$this->form = '';

		// open row
		$output .= '<tr>';

		// init with pseudo-th
		$output .= '<td>' . esc_html__( 'Registration', 'civicrm-eo-attendance') . '</td>';

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'header' => $header,
			//'backtrace' => $trace,
		), true ) );
		*/

		// add register link
		foreach ( $header AS $date ) {

			// handle "none" item
			if ( 'none' == $date ) {
				$output .= '<td>&nbsp;</td>';
				continue;
			}

			// add registration link
			if ( is_long( $date ) ) {

				// init class array with base class
				$classes = array( 'civicrm-eo-rendezvous-register-all' );

				// get event ID
				$civi_event_id = isset( $this->item_meta[$date] ) ? $this->item_meta[$date] : 0;

				// add event ID class
				$classes[] = 'civicrm-eo-rv-event-id-' . $civi_event_id;

				// get IDs of attendees
				$attendee_ids = $item->days[$date];

				// add attendee IDs class
				$classes[] = 'civicrm-eo-rv-ids-' . implode( '-', $attendee_ids );

				// open table row
				$output .= '<td>';

				// add span
				$output .= '<span class="' . implode( ' ', $classes ) . '">' . __( 'Register', 'civicrm-eo-attendance' ) . '</span>';

				// create dummy event object
				$post = new stdClass;
				$post->ID = $this->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

				// add form
				$this->form .= $this->rv_form_registration_render( $civi_event_id, $attendee_ids, $post );

				// close table row
				$output .= '</td>';

			}

		}

		// close row
		$output .= '</tr>';

		// trigger rendering of forms in footer
		add_action( 'wp_footer', array( $this, 'rv_form_footer') );

		// --<
		return $output;

	}



	/**
	 * Add Registration Form for an event in a Rendez Vous.
	 *
	 * @since 0.4.8
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent
	 * @param array $attendee_ids The numeric IDs of the attendees
	 * @param str $select The markup for the participant roles dropdown
	 * @param object $post A bare-bones post object with the EO event ID
	 * @return str $markup The rendered registration form
	 */
	public function rv_form_registration_render( $civi_event_id, $attendee_ids, $post ) {

		// init markup
		$markup = '';

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return $markup;

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) return $markup;

		// get event leader role for error checking
		$event_leader_role = $this->plugin->event_leader->role_default_get( $post );

		// init data arrays
		$checked = array();
		$select = array();

		// init "event has participants" flag
		$participants_exist = false;

		// get current participant statuses
		if ( count( $attendee_ids ) > 0 ) {
			foreach( $attendee_ids AS $attendee_id ) {

				// get the CiviCRM contact ID
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// skip if no contact ID found
				if( empty( $contact_id ) ) continue;

				// get current participant data
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// if currently registered
				if( $participant !== false ) {

					// populate data arrays
					$checked[$attendee_id] = ' checked="checked"';
					$select[$attendee_id] = $this->rv_form_registration_roles( $post, $participant['participant_role_id'] );

					// set flag
					$participants_exist = true;

				} else {

					// add default entries to data arrays
					$checked[$attendee_id] = '';
					$select[$attendee_id] = $this->rv_form_registration_roles( $post );

				}

			}
		}

		// set label for submit button
		if ( $participants_exist ) {
			$submit_label = __( 'Update', 'civicrm-eo-attendance' );
		} else {
			$submit_label = __( 'Submit', 'civicrm-eo-attendance' );
		}

		// start buffering
		ob_start();

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-attendance/attendance-form.php' );

		// save the output and flush the buffer
		$markup = ob_get_clean();

		// --<
		return $markup;

	}



	/**
	 * Builds the Participant Roles select element for an EO event.
	 *
	 * @since 0.4.8
	 *
	 * @param object $post An EO event object
	 * @param int $participant_role_id The numeric ID of the participant role
	 * @return str $html Markup to display in the form
	 */
	public function rv_form_registration_roles( $post, $participant_role_id = null ) {

		// init html
		$html = '';

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return $html;

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) return $html;

		// first, get all participant_roles
		if ( ! isset( $this->all_roles ) ) {
			$this->all_roles = $this->plugin->civicrm_eo->civi->get_participant_roles();
		}

		// did we get any?
		if ( $this->all_roles['is_error'] == '0' AND count( $this->all_roles['values'] ) > 0 ) {

			// get the values array
			$roles = $this->all_roles['values'];

			// init options
			$options = array();

			// get default role ID
			$default_id = $this->plugin->civicrm_eo->civi->get_participant_role( $post );

			// loop
			foreach( $roles AS $key => $role ) {

				// get role
				$role_id = absint( $role['value'] );

				// init selected
				$selected = '';

				// override selected when this value is the same as the given participant_role_id
				if ( ! is_null( $participant_role_id ) AND $participant_role_id == $role_id ) {
					$selected = ' selected="selected"';
				}

				// override selected when this value is the same as the default
				if ( is_null( $participant_role_id ) AND $default_id == $role_id ) {
					$selected = ' selected="selected"';
				}

				/*
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'participant_role_id' => $participant_role_id,
					'role_id' => $role_id,
					'selected' => $selected,
					'backtrace' => $trace,
				), true ) );
				*/

				// construct option
				$options[] = '<option value="' . $role_id . '"' . $selected . '>' . esc_html( $role['label'] ) . '</option>';

			}

			// create html
			$html = implode( "\n", $options );

		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'html' => $html,
			'backtrace' => $trace,
		), true ) );
		*/

		// return
		return $html;

	}



	/**
	 * Append forms to page footer.
	 *
	 * @since 0.4.8
	 */
	public function rv_form_footer() {

		// render forms
		echo $this->form;

	}



	/**
	 * Process a form submitted via AJAX.
	 *
	 * @since 0.4.8
	 */
	public function rv_form_process() {

		// show something when there's an error
		$error_markup = __( 'Oops! Something went wrong.', 'civicrm-eo-attendance' );

		// get form data
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? $_POST['civi_event_id'] : '0';
		$civi_event_id = absint( trim( $civi_event_id ) );
		$register_data = isset( $_POST['register'] ) ? $_POST['register'] : array();
		$unregister_data = isset( $_POST['unregister'] ) ? $_POST['unregister'] : array();

		// init data
		$data = array(
			'civi_event_id' => $civi_event_id,
			'error' => '0',
			'markup' => '',
		);

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) {
			$data['error'] = '1';
			$data['markup'] = $error_markup;
			$this->send_data( $data );
		}

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) {
			$data['error'] = '1';
			$data['markup'] = $error_markup;
			$this->send_data( $data );
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'POST' => $_POST,
			'civi_event_id' => $civi_event_id,
			'register_data' => $register_data,
			'unregister_data' => $unregister_data,
			'backtrace' => $trace,
		), true ) );
		*/

		// sanity check
		if ( ! is_array( $register_data ) OR ! is_array( $unregister_data ) ) {
			$data['error'] = '1';
			$data['markup'] = $error_markup;
			$this->send_data( $data );
		}

		// get now in appropriate format
		$now_obj = new DateTime( 'now', eo_get_blog_timezone() );
		$now = $now_obj->format( 'Y-m-d H:i:s' );

		// get event details
		$event_id = $this->db->get_eo_event_id_by_civi_event_id( $civi_event_id );
		$occurrence_id = $this->db->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );
		$event_title = get_the_title( $event_id );
		$event_link = get_permalink( $event_id );

		// construct title
		$linked_title = '<a href="' . esc_url( $event_link ) . '">' . esc_html( $event_title ) . '</a>';

		// handle registrations
		if ( count( $register_data ) > 0 ) {

			// register each attendee
			foreach( $register_data AS $attendee_id => $role_id ) {

				// get the CiviCRM contact ID
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// skip if no contact ID found
				if( empty( $contact_id ) ) continue;

				// build params to create participant
				$params = array(
					'version' => 3,
					'contact_id' => $contact_id,
					'event_id' => $civi_event_id,
					'status_id' => 1, // Registered
					'role_id' => $role_id,
					'register_date' => $now, // as in: '2007-07-21 00:00:00'
					'source' => __( 'Registration via Rendez Vous', 'civicrm-eo-attendance' ),
				);

				// get current participant data
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// trigger update if participant is found
				if( $participant !== false ) {
					$params['id'] = $participant['participant_id'];
				}

				/*
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'params' => $params,
					'backtrace' => $trace,
				), true ) );
				*/

				// use CiviCRM API to create participant
				$result = civicrm_api( 'participant', 'create', $params );

				// error check
				if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

					// log error
					$e = new Exception;
					$trace = $e->getTraceAsString();
					error_log( print_r( array(
						'method' => __METHOD__,
						'message' => $result['error_message'],
						'params' => $params,
						'result' => $result,
						'backtrace' => $trace,
					), true ) );

				} else {

					// set up message
					$subject = sprintf(
						__( 'You have been registered for %s', 'civicrm-eo-attendance' ),
						esc_html( $event_title )
					);

					// construct message body
					$message = sprintf(
						__( 'This is to let you know that you have been registered as attending the event "%1$s" on %2$s at %3$s. If you think this has been done in error, or you have anything else you want to say to the organiser, please follow the link below and reply to this conversation on the Spirit of Football website.', 'civicrm-eo-attendance' ),
						$linked_title,
						eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ),
						eo_get_the_start( 'g:ia', $event_id, $occurrence_id )
					);

					// send message
					$this->rv_form_notify_user( $attendee_id, $subject, $message );


				}

			}

		}

		// handle de-registrations
		if ( count( $unregister_data ) > 0 ) {

			// de-register each attendee
			foreach( $unregister_data AS $attendee_id => $role_id ) {

				// get the CiviCRM contact ID
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// skip if no contact ID found
				if( empty( $contact_id ) ) continue;

				// get participant data
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				/*
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'participant' => $participant === false ? 'no data' : $participant,
					'backtrace' => $trace,
				), true ) );
				*/

				// skip if no participant found
				if( $participant === false ) continue;

				// build params to delete participant
				$params = array(
					'version' => 3,
					'id' => $participant['participant_id'],
				);

				/*
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'params' => $params,
					'backtrace' => $trace,
				), true ) );
				*/

				// use CiviCRM API to delete participant
				$result = civicrm_api( 'participant', 'delete', $params );

				// error check
				if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

					// log error
					$e = new Exception;
					$trace = $e->getTraceAsString();
					error_log( print_r( array(
						'method' => __METHOD__,
						'message' => $result['error_message'],
						'params' => $params,
						'result' => $result,
						'backtrace' => $trace,
					), true ) );

				} else {

					// set up message
					$subject = sprintf(
						__( 'You have been unregistered from %s', 'civicrm-eo-attendance' ),
						esc_html( $event_title )
					);

					// construct message body
					$message = sprintf(
						__( 'This is to let you know that your registration for the event "%1$s" on %2$s at %3$s has been cancelled and that you will no longer be attending. If you think this has been done in error, please follow the link below and reply to this conversation on the Spirit of Football website.', 'civicrm-eo-attendance' ),
						$linked_title,
						eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ),
						eo_get_the_start( 'g:ia', $event_id, $occurrence_id )
					);

					// send message
					$this->rv_form_notify_user( $attendee_id, $subject, $message );

				}

			}

		}

		// what to return?
		$markup = __( 'Thanks!', 'civicrm-eo-attendance' );

		// amend data
		$data['markup'] = $markup;

		// send data to browser
		$this->send_data( $data );

	}



	/**
	 * Notify a user when they have been registered for an event.
	 *
	 * @since 0.5.3
	 *
	 * @param int $recipient_id The numeric Id of the user the notify.
	 * @param str $subject The subject of the message.
	 * @param str $content The content of the message.
	 */
	public function rv_form_notify_user( $recipient_id, $subject, $content ) {

		// bail if anything is amiss
		if ( empty( $recipient_id ) ) return;
		if ( empty( $subject ) ) return;
		if ( empty( $content ) ) return;

		// get current user
		$current_user = wp_get_current_user();

		// don't notify the recipient if they are the current user
		if ( $recipient_id == $current_user->ID ) return;

		// set up message
		$msg_args = array(
			'sender_id'  => $current_user->ID,
			'thread_id'  => false,
			'recipients' => array( $recipient_id ), // Can be an array of usernames, user_ids or mixed.
			'subject'    => $subject,
			'content'    => $content,
		);

		// send message
		messages_new_message( $msg_args );

	}



	/**
	 * Check if a user can access participant management functionality.
	 *
	 * @since 0.5
	 *
	 * @param obj $rendez_vous The Rendez Vous to check
	 * @return bool $allowed True if allowed, false otherwise
	 */
	public function rv_form_access_granted( $rendez_vous ) {

		// get current user
		$current_user = wp_get_current_user();

		// allow if user is the rendez vous 'organizer'
		if ( $rendez_vous->organizer == $current_user->ID ) return true;

		// allow if user is a group admin
		if ( groups_is_user_admin( $current_user->ID, $rendez_vous->group_id ) ) return true;

		// allow if user is a group mod
		if ( groups_is_user_mod( $current_user->ID, $rendez_vous->group_id ) ) return true;

		// disallow
		return false;

	}



	//##########################################################################



	/**
	 * Check if a Rendez Vous has a term.
	 *
	 * @since 0.4.7
	 *
	 * @param string $item The Rendez Vous to check
	 * @return bool $term True if the Rendez Cous has the term, false otherwise
	 */
	public function has_term( $item ) {

		// does this Rendez Vous have a term?
		if ( isset( $item->type ) AND is_array( $item->type ) ) {
			foreach( $item->type AS $type ) {

				// is this our term?
				if ( $type->term_id == $this->db->option_get( $this->term_option ) ) {
					return true;
				}

			}
		}

		// fallback
		return false;

	}



	/**
	 * Create a Rendez Vous term.
	 *
	 * @since 0.4.7
	 *
	 * @param string $term_name The term to add
	 * @return array|WP_Error $term An array containing the `term_id` and `term_taxonomy_id`
	 */
	public function term_create( $term_name ) {

		// create a rendez vous term
		$term = rendez_vous_insert_term( $term_name );

		// --<
		return $term;

	}



	/**
	 * Delete a Rendez Vous term.
	 *
	 * @since 0.4.7
	 *
	 * @param int $term_id The ID of the term
	 * @return bool|WP_Error $retval Returns false if not term; true if term deleted.
	 */
	public function term_delete( $term_id ) {

		// delete a rendez vous term
		$retval = rendez_vous_delete_term( $term_id );

		// --<
		return $retval;

	}



	//##########################################################################



	/**
	 * For a given Rendez Vous, get our custom meta value.
	 *
	 * @since 0.5.1
	 *
	 * @param string $item The Rendez Vous to check
	 * @return bool True if the Rendez Cous has the meta value, false otherwise
	 */
	public function get_meta( $item ) {

		// bail if this isn't a Rendez Vous object
		if ( ! ( $item instanceof Rendez_Vous_Item ) ) return false;

		// get meta for this Rendez Vous
		$rv_meta = get_post_meta( $item->id, $this->month_meta_key, true );

		// there's no meta if we get the default empty string returned
		if ( $rv_meta === '' ) return false;

		// we're good
		return $rv_meta;

	}



	/**
	 * Check if a Rendez Vous has our custom meta value.
	 *
	 * @since 0.5
	 *
	 * @param string $item The Rendez Vous to check
	 * @return bool True if the Rendez Cous has the meta value, false otherwise
	 */
	public function has_meta( $item ) {

		// bail if this isn't a Rendez Vous object
		if ( ! ( $item instanceof Rendez_Vous_Item ) ) return false;

		// get meta for this Rendez Vous
		$rv_meta = get_post_meta( $item->id, $this->month_meta_key, true );

		// there's no meta if we get the default empty string returned
		if ( $rv_meta === '' ) return false;

		// we're good
		return true;

	}



	//##########################################################################



	/**
	 * Inject a checkbox into group manage screen.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The numeric ID of the group
	 */
	public function group_manage_form_amend( $group_id ) {

		// get the attendance enabled group meta
		$attendance_enabled = groups_get_groupmeta( $group_id, $this->group_meta_key );

		// set checkbox checked attribute
		$checked = '';
		if ( ! empty( $attendance_enabled ) ) {
			$checked = ' checked="checked"';
		}

		// construct checkbox
		$checkbox = '<input type="checkbox" id="' . $this->group_meta_key . '" name="' . $this->group_meta_key . '" value="1"' . $checked . '> ';

		// construct label
		$label = '<label for="' . $this->group_meta_key . '">' .
					$checkbox .
					__( 'Enable Attendance', 'civicrm-eo-attendance' ) .
				 '</label>';

		// wrap in divs
		$enabled_div = '<div class="field-group civicrm-eo-group-enabled"><div class="checkbox">' .
							$label .
						'</div></div>';

		// get the organizer ID from group meta
		$organizer_id = groups_get_groupmeta( $group_id, $this->organizer_meta_key );

		// init options
		$options = array();

		// add empty value if none set
		if ( empty( $organizer_id ) ) {
			$options[] = '<option value="0" selected="selected">' .
							__( 'Select an Organiser', 'civicrm-eo-attendance' ) .
						 '</option>';
		}

		// get group admins
		$admins = groups_get_group_admins( $group_id );

		// get group mods
		$mods = groups_get_group_mods( $group_id );

		// merge arrays
		$organizers = array_merge( $admins, $mods );

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'organizer_id' => $organizer_id,
			'organizers' => $organizers,
			'backtrace' => $trace,
		), true ) );
		*/

		// loop
		foreach( $organizers AS $user ) {

			// init selected
			$selected = '';

			// override selected if this is the organizer
			if ( $organizer_id == $user->user_id ) {
				$selected = ' selected="selected"';
			}

			// construct option
			$options[] = '<option value="' . $user->user_id . '"' . $selected . '>' .
							bp_core_get_user_displayname( $user->user_id ) .
						 '</option>';

		}

		// create options markup
		$organizer_options = implode( "\n", $options );

		// wrap in select
		$organizer_options = '<select id="' . $this->organizer_meta_key . '" name="' . $this->organizer_meta_key . '">' .
								 $organizer_options .
							 '</select>';

		// costruct label
		$organizer_label = '<label for="' . $this->organizer_meta_key . '">' .
							__( 'Choose who is responsible for Attendance', 'civicrm-eo-attendance' ) .
							'</label>';

		// wrap in divs
		$organizer_div = '<div class="field-group civicrm-eo-group-organizer"><div class="select">' .
							$organizer_label .
							$organizer_options .
						 '</div></div>';

		// construct markup
		$markup = $enabled_div . $organizer_div;

		// show markup
		echo $markup;

	}



	/**
	 * Check the value of the checkbox on the group manage screen.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The numeric ID of the group
	 * @param array $settings The Rendez Vous settings array
	 */
	public function group_manage_form_submit( $group_id, $settings ) {

		// sanity check
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		// init options
		$options = array(
			$this->group_meta_key => 0,
			$this->organizer_meta_key => 0,
		);

		// sanitise input
		if ( ! empty( $_POST[$this->group_meta_key] ) ) {
			$s = wp_parse_args( $_POST, $options );
			$options = array_intersect_key(
				array_map( 'absint', $s ),
				$options
			);
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'group_id' => $group_id,
			'settings' => $settings,
			'options' => $options,
			'backtrace' => $trace,
		), true ) );
		*/

		// go ahead and save the meta values
		groups_update_groupmeta( $group_id, $this->group_meta_key, $options[$this->group_meta_key] );
		groups_update_groupmeta( $group_id, $this->organizer_meta_key, $options[$this->organizer_meta_key] );

	}



	/**
	 * Get group meta value, use default if meta value is not set.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The group ID
	 * @param str $meta_key The meta key
	 * @param mixed $default The default value to fallback to
	 * @return mixed The meta value, or false on failure
	 */
	public function group_get_option( $group_id = 0, $meta_key = '', $default = '' ) {

		// sanity check
		if ( empty( $group_id ) OR empty( $meta_key ) ) {
			return false;
		}

		// get the value
		$meta_value = groups_get_groupmeta( $group_id, $meta_key );

		// maybe set default
		if ( '' === $meta_value ) {
			$meta_value = $default;
		}

		// --<
		return $meta_value;

	}



	/**
	 * Update registrations when a user updates their attendance.
	 *
	 * @since 0.5.1
	 *
	 * @param array $args The arguments
	 * @param int $attendee_id The user ID of the attendee
	 * @param object $rendez_vous The Rendez Vous
	 */
	public function registrations_check( $args = array() ) {

		// bail if something's not right with the array
		if ( ! isset( $args['id'] ) ) return;
		if ( ! is_int( absint( $args['id'] ) ) ) return;

		// get reference meta and sanity check
		$references = get_post_meta( $args['id'], $this->reference_meta_key, true );
		if ( ! is_array( $references ) ) return;

		// get Rendez Vous
		$rendez_vous = rendez_vous_get_item( $args['id'] );

		// bail if this is not an attendance Rendez Vous
		$rv_meta = $this->get_meta( $rendez_vous );
		if ( $rv_meta === false ) return;

		// bail if this fails for some reason
		if ( ! ( $rendez_vous instanceof Rendez_Vous_Item ) ) return;

		// get current user
		$current_user = wp_get_current_user();

		// get the days that this attendee used to have
		$this->attending = array();
		foreach( $rendez_vous->days AS $timestamp => $attendee_ids ) {
			if ( in_array( $current_user->ID, $attendee_ids ) ) {
				$this->attending[$timestamp] = $references[$timestamp];
			}
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			//'args' => $args,
			//'rendez_vous' => $rendez_vous,
			//'references' => $references,
			'attending' => $this->attending,
			'backtrace' => $trace,
		), true ) );
		*/

	}



	/**
	 * Update registrations when a user updates their attendance.
	 *
	 * This only removes registrations where the user was previously registered
	 * and has now indicated that they are no longer available. It should maybe
	 * inform the organizer that the person has been de-registered.
	 *
	 * @since 0.5.1
	 *
	 * @param array $args The arguments
	 * @param int $attendee_id The user ID of the attendee
	 * @param object $rendez_vous The Rendez Vous
	 */
	public function registrations_update( $args = array(), $attendee_id = 0, $rendez_vous = null ) {

		// bail if we don't have our attending array
		if ( ! isset( $this->attending ) ) return;
		if ( ! is_array( $this->attending ) ) return;
		if ( count( $this->attending ) === 0 ) return;

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return;

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// get user matching file
		require_once 'CRM/Core/BAO/UFMatch.php';

		// get the CiviCRM contact ID
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

		// look for missing items
		$missing_items = array();
		foreach( $this->attending AS $timestamp => $civi_event_id ) {
			if ( ! in_array( $timestamp, $args['days'] ) ) {
				$missing_items[$timestamp] = $civi_event_id;
			}
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'args' => $args,
			'attendee_id' => $attendee_id,
			'rendez_vous' => $rendez_vous,
			'attending' => $this->attending,
			'missing_items' => $missing_items,
			'backtrace' => $trace,
		), true ) );
		*/

		// bail if there are no missing items
		if ( count( $missing_items ) === 0 ) return;

		// loop through the rest
		foreach( $missing_items AS $timestamp => $civi_event_id ) {

			// skip if not a timestamp
			if ( ! is_long( $timestamp ) ) continue;

			// get date of event
			$date = date( 'Y-m-d H:i:s', $timestamp );

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'date' => $date,
				'is-past' => $this->is_past( $date ) ? 'yes' : 'no',
				'backtrace' => $trace,
			), true ) );
			*/

			// skip to next if it is a past event
			if ( $this->is_past( $date ) ) continue;

			// build params to get participant
			$params = array(
				'version' => 3,
				'contact_id' => $contact_id,
				'event_id' => $civi_event_id,
			);

			// get participant instances
			$participants = civicrm_api( 'participant', 'get', $params );

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'participants' => $participants,
				'backtrace' => $trace,
			), true ) );
			*/

			// error check
			if ( isset( $participants['is_error'] ) AND $participants['is_error'] == '1' ) {

				// log and skip
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'message' => $participants['error_message'],
					'params' => $params,
					'participants' => $participants,
					'backtrace' => $trace,
				), true ) );
				continue;

			}

			// skip if not registered
			if ( count( $participants['values'] ) === 0 ) continue;

			// we should only have one registration
			$registration = array_pop( $participants['values'] );

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'registration' => $registration,
				'backtrace' => $trace,
			), true ) );
			*/

			// build params to delete participant
			$params = array(
				'version' => 3,
				'id' => $registration['id'],
			);

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'params' => $params,
				'backtrace' => $trace,
			), true ) );
			*/

			// use CiviCRM API to delete participant
			$result = civicrm_api( 'participant', 'delete', $params );

			// error check
			if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

				// log error and skip
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'message' => $result['error_message'],
					'params' => $params,
					'result' => $result,
					'backtrace' => $trace,
				), true ) );
				continue;

			}

			// send message if component is available
			if ( bp_is_active( 'messages' ) ) {

				// construct subject
				$subject = sprintf(
					__( '%1$s cannot attend "%2$s"', 'civicrm-eo-attendance' ),
					$registration['display_name'],
					$registration['event_title']
				);

				// construct link to Rendez Vous
				$link = rendez_vous_get_single_link( $rendez_vous->id, $rendez_vous->organizer );
				$rendez_vous_link = '<a href="'. esc_url( $link ) .'">' . esc_html( $rendez_vous->title ) . '</a>';

				// construct content
				$content = sprintf(
					__( 'Unfortunately %1$s can no longer attend "%2$s" on %3$s at %4$s and their registration for this event has been cancelled. Please visit the "%5$s" to review registrations for this event.', 'civicrm-eo-attendance' ),
					$registration['display_name'],
					$registration['event_title'],
					date_i18n( get_option('date_format'), $timestamp ),
					date_i18n( get_option('time_format'), $timestamp ),
					$rendez_vous_link
				);

				// set up message
				$msg_args = array(
					'sender_id'  => $attendee_id,
					'thread_id'  => false,
					'recipients' => $rendez_vous->organizer,
					'subject'    => $subject,
					'content'    => $content,
				);

				// send message
				messages_new_message( $msg_args );

			}

		}

	}



	/**
	 * Check if an event is a past event.
	 *
	 * @since 0.4.6
	 *
	 * @param str $end_date The end date string for a CiviEvent
	 * @return bool $past_event True if the event is past, false otherwise
	 */
	public function is_past( $end_date ) {

		// init past event flag
		$past_event = false;

		// override if it's a past event
		$now = new DateTime( 'now', eo_get_blog_timezone() );
		$end = new DateTime( $end_date, eo_get_blog_timezone() );
		if ( $end < $now ) {
			$past_event = true;
		}

		// --<
		return $past_event;

	}



	//##########################################################################



	/**
	 * Send JSON data to the browser.
	 *
	 * @since 0.2.2
	 *
	 * @param array $data The data to send.
	 */
	private function send_data( $data ) {

		// is this an AJAX request?
		if ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) {

			// set reasonable headers
			header('Content-type: text/plain');
			header("Cache-Control: no-cache");
			header("Expires: -1");

			// echo
			echo json_encode( $data );

			// die
			exit();

		}

	}

} // class ends



