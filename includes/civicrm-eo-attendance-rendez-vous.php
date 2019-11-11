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
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * CiviCRM EO Plugin Admin object reference.
	 *
	 * @since 0.4.7
	 * @access public
	 * @var object $eo The CiviCRM EO Plugin Admin object reference.
	 */
	public $db;

	/**
	 * Rendez Vous Term ID Option name.
	 *
	 * No longer used, but left for reference until I get rid of it completely.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $term_option The Rendez Vous Term ID option name.
	 */
	public $term_option = 'civicrm_eo_event_rv_term_id';

	/**
	 * Rendez Vous "Month" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $month_meta_key The Rendez Vous "Month" meta key name.
	 */
	public $month_meta_key = '_rendez_vous_month';

	/**
	 * Rendez Vous "Reference Array" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $reference_meta_key The Rendez Vous "Reference Array" meta key name.
	 */
	public $reference_meta_key = '_rendez_vous_reference';

	/**
	 * Group "Attendance Enabled" meta key name.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $group_meta_key The group "Attendance Enabled" meta key name.
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
	 * @var str $group_meta_key The group "Rendez Vous Organizer" meta key name.
	 */
	public $organizer_meta_key = '_civicrm_eo_event_organizer';

	/**
	 * The number of future Rendez Vous to generate.
	 *
	 * @since 0.5
	 * @access public
	 * @var int $future_count The number of future Rendez Vous to generate.
	 */
	public $future_count = 6;



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
		$this->db = $parent->civicrm_eo->db;

	}



	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.4.7
	 */
	public function register_hooks() {

		// Wrap table in div.
		add_filter( 'rendez_vous_single_get_the_dates', array( $this, 'rv_form_wrap_table'), 10, 2 );

		// Filter the column titles.
		add_filter( 'rendez_vous_single_get_the_dates_header', array( $this, 'rv_form_column_header'), 10, 2 );

		// Add extra row with registration links.
		add_filter( 'rendez_vous_single_get_the_dates_rows_after', array( $this, 'rv_form_last_row'), 10, 3 );

		// Filter rendez vous form classes.
		add_filter( 'rendez_vous_single_the_form_class', array( $this, 'rv_form_class'), 10, 2 );

		// Remove edit button on Attendance Rendez Vous.
		add_filter( 'bp_get_button', array( $this, 'rv_form_button'), 10, 3 );

		// Add refresh button on Rendez Vous group archive.
		add_filter( 'bp_get_button', array( $this, 'rv_archive_button'), 10, 3 );

		// Add scripts to single view Rendez Vous.
		add_action( 'rendez_vous_single_content_before', array( $this, 'enqueue_scripts') );

		// Add AJAX handler.
		add_action( 'wp_ajax_event_attendance_form_process', array( $this, 'rv_form_process' ) );

		// Hook into BPEO and remove original taxonomy metabox.
		add_action( 'add_meta_boxes_event', array( $this, 'radio_tax_enforce' ), 3 );

		// Allow a single Rendez Vous to be refreshed.
		add_action( 'rendez_vous_single_screen', array( $this, 'refresh_rv_single' ) );

		// Allow all Rendez Vous to be refreshed.
		add_action( 'bp_screens', array( $this, 'refresh_rv_all' ), 3 );

		// Add form element to group manage screen.
		add_action( 'rendez_vous_group_edit_screen_after', array( $this, 'group_manage_form_amend' ), 10, 1 );

		// Check element when group manage screen is submitted.
		add_action( 'rendez_vous_group_edit_screen_save', array( $this, 'group_manage_form_submit' ), 10, 2 );

		// Check registrations when a user updates their attendance.
		add_action( 'rendez_vous_before_attendee_prefs', array( $this, 'registrations_check' ), 10, 1 );

		// Update registrations when a user updates their attendance.
		add_action( 'rendez_vous_after_attendee_prefs', array( $this, 'registrations_update' ), 9, 3 );

	}



	/**
	 * Do stuff on plugin activation.
	 *
	 * @since 0.4.7
	 */
	public function activate() {

		// Create data entities.
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

		// Bail if we've already done this.
		if ( 'fgffgs' !== $this->db->option_get( $this->term_option, 'fgffgs' ) ) return;

		// Define term name.
		$term_name = __( 'Attendance', 'civicrm-eo-attendance' );

		// Create it.
		$new_term = $this->term_create( $term_name );

		// Log and bail on error.
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

		// Store as plugin option.
		$this->db->option_save( $this->term_option, $new_term['term_id'] );

	}



	/**
	 * Add our Javascript for registering Attendees.
	 *
	 * @since 0.4.8
	 */
	public function enqueue_scripts() {

		// Get current item.
		$item = rendez_vous()->item;

		// Bail if this Rendez Vous doesn't have our custom meta value.
		if ( ! $this->has_meta( $item ) ) return;

		// Get current user.
		$current_user = wp_get_current_user();

		// Only show if user is allowed to manage this rendez vous.
		if ( ! $this->rv_form_access_granted( $item ) ) return;

		// Add script to footer.
		wp_enqueue_script(
			'civicrm-eo-attendance-rvm',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-rvm.js',
			array( 'jquery' ),
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // In footer.
		);

		// Translations.
		$localisation = array(
			'submit' => __( 'Submit', 'civicrm-eo-attendance' ),
			'update' => __( 'Update', 'civicrm-eo-attendance' ),
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'leader' => __( 'You must choose someone to be the event leader.', 'civicrm-eo-attendance' ),
		);

		// Define settings.
		$settings = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);

		// Localisation array.
		$vars = array(
			'localisation' => $localisation,
			'settings' => $settings,
		);

		// Localise the WordPress way.
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

		// Bail if we're in the admin area.
	    if ( defined( 'WP_NETWORK_ADMIN' ) ) return;

		// Bail if no RBFT instance.
		if ( ! function_exists( 'Radio_Buttons_for_Taxonomies' ) ) return;

		// Get instance.
		$rbft = Radio_Buttons_for_Taxonomies();

		// Sanity check.
		if ( ! isset( $rbft->taxonomies ) OR ! is_array( $rbft->taxonomies ) ) return;

		// Force removal of metaboxes.
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

		// Was the "refresh" button clicked?
		if ( ! empty( $_GET['action'] ) && 'refresh' == $_GET['action'] && ! empty( $_GET['rdv'] ) ) {

			// Get redirect and Rendez Vous ID.
			$redirect = remove_query_arg( array( 'rdv', 'action', 'n' ), wp_get_referer() );
			$rendez_vous_id = absint( $_GET['rdv'] );

			// Do the update.
			$updated_id = $this->rv_update( $rendez_vous_id );

			// Appropriate error messages.
			if ( $updated_id === false ) {
				bp_core_add_message( __( 'Refreshing this Rendez-vous failed.', 'civicrm-eo-attendance' ), 'error' );
			} else {
				bp_core_add_message( __( 'Rendez-vous successfully refreshed.', 'civicrm-eo-attendance' ) );
				$redirect = add_query_arg( 'rdv', $updated_id, $redirect );
			}

			// Finally redirect.
			bp_core_redirect( $redirect );

		}

	}



	/**
	 * Intercept clicks on "Refresh" button on Group Rendez Vous listing page.
	 *
	 * @since 0.5
	 */
	public function refresh_rv_all() {

		// Was the "refresh" button clicked?
		if ( empty( $_GET['action'] ) ) return;
		if ( 'refresh_all' != trim( $_GET['action'] ) ) return;

		// Is this the Rendez Vous component group archive?
		if ( bp_is_group() AND bp_is_current_action( rendez_vous()->get_component_slug() ) AND empty( $_REQUEST['rdv'] ) ) {

			// Get redirect.
			$redirect = remove_query_arg( array( 'action', 'rdv', 'n' ), wp_get_referer() );

			// Get group ID.
			$group_id = bp_get_current_group_id();

			// Get the current and future Rendez Vous.
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

			// Add our meta query via a filter.
			add_filter( 'rendez_vous_query_args', array( $this, 'refresh_filter_query' ), 10, 1 );

			// Do the query.
			$has_rendez_vous = rendez_vous_has_rendez_vouss( $args );

			// Remove our filter.
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

			// Do the loop to find existing IDs.
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

			// Init error flag.
			$has_error = false;

			// Get months to check.
			$checks = $this->refresh_get_months();
			foreach( $checks AS $month ) {

				// Create DateTime object.
				$datetime = new DateTime( $month, eo_get_blog_timezone() );

				// Create it if it doesn't exist, update otherwise.
				if ( ! in_array( $month, $rv_meta ) ) {
					$returned_id = $this->rv_create( $datetime, $group_id );
				} else {
					$flipped = array_flip( $rv_meta );
					$returned_id = $this->rv_update( $flipped[$month] );
				}

				// Error check.
				if ( $returned_id === false ) {
					$has_error = true;
				}

			}

			// Appropriate error messages.
			if ( $has_error === true ) {
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
	public function refresh_filter_query( $query_args = array() ) {

		// Get months to check.
		$checks = $this->refresh_get_months();

		// Find all Rendez Vous with these meta values.
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
	 * @return array $checks The array of months to check.
	 */
	public function refresh_get_months() {

		// Get now.
		$now = new DateTime( 'now', eo_get_blog_timezone() );

		// Get first day of this month.
		$month_start = $now->format( 'Y-m-01' );
		$start = new DateTime( $month_start, eo_get_blog_timezone() );

		// Check this month plus N months ahead.
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
	 * @param DateTime $datetime The DateTime object for the month to create an RV for.
	 * @param int $group_id The numeric ID of the group.
	 * @return int|bool $updated_id The ID of the created Rendez Vous (false on failure or true if skipped).
	 */
	public function rv_create( $datetime, $group_id = 0 ) {

		// Members group ID.
		if ( $group_id === 0 ) {
			$group_id = bp_get_current_group_id();
		}

		// Get members of group.
		$attendee_ids = $this->rv_attendees_get( $group_id );

		// For the title, we use the 3rd day of the month to avoid daylight-saving
		// and timezone oddities related to the 1st.
		$title_date = $datetime->format( 'Y-m-03' );
		$title_datetime = new DateTime( $title_date, eo_get_blog_timezone() );

		// Define title.
		$title = sprintf(
			__( 'Availability for %s', 'civicrm-eo-attendance' ),
			date_i18n( 'F Y', $title_datetime->getTimestamp() )
		);

		// Events on this month.
		$month_start = $datetime->format( 'Y-m-01' );
		$month_end = $datetime->format( 'Y-m-t' );

		// Construct args.
		$event_args = apply_filters( 'civicrm_event_organiser_rendez_vous_event_args', array(
			'event_start_after'  => $month_start,
			'event_start_before' => $month_end,
		) );

		// Get event data.
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

		// Init event data.
		$days = array();

		// Init reference array.
		$references = array();

		// Init used.
		$used = array();

		// Format days array for these events.
		if ( count( $events ) > 0 ) {
			foreach( $events AS $event ) {

				// Get CiviEvent ID.
				$civi_event_id = $this->db->get_civi_event_id_by_eo_occurrence_id( $event->ID, $event->occurrence_id );

				// Skip unless we have a CiviEvent.
				if ( $civi_event_id === false ) continue;

				// Get start.
				$event_start_time = eo_get_the_start( DATETIMEOBJ, $event->ID, $event->occurrence_id );
				$timestamp = $event_start_time->getTimestamp();

				// Ensure no duplicates by adding a trivial amount.
				while( in_array( $timestamp, $used ) ) {
					$timestamp++;
				}

				// Add to RV days.
				$days[$timestamp] = array();

				// Add CiviEvent ID to reference array.
				$references[$timestamp] = $civi_event_id;

				// Add to used array.
				$used[] = $timestamp;

			}
		}

		// No need to create if there are no dates.
		if ( count( $days ) === 0 ) return true;

		// Get organizer.
		$organizer_id = groups_get_groupmeta( $group_id, $this->organizer_meta_key );

		// Bail if we don't have one.
		if ( empty( $organizer_id ) ) return true;

		// Construct create array.
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

		// Create it.
		$rendez_vous_id = $this->rv_save( $rendez_vous );

		// Log and bail on error.
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

		// Store the event IDs for the rendez vous item.
		add_post_meta( $rendez_vous_id, $this->reference_meta_key, $references, true );

		// Store the month for the rendez vous.
		add_post_meta( $rendez_vous_id, $this->month_meta_key, $month_start, true );

		// Update args.
		$rendez_vous['id'] = $rendez_vous_id;
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
	 * @param int|bool $rendez_vous_id The numeric ID of the Rendez Vous to update.
	 * @return int|bool $updated_id The ID of the updated Rendez Vous, or false on failure.
	 */
	public function rv_update( $rendez_vous_id ) {

		// Get Rendez Vous to rebuild.
		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );

		// Bail if this fails for some reason.
		if ( ! ( $rendez_vous instanceof Rendez_Vous_Item ) ) return false;

		// Bail if this Rendez Vous doesn't have our custom meta value.
		if ( ! $this->has_meta( $rendez_vous ) ) return false;

		// Get members of group.
		$attendee_ids = $this->rv_attendees_get( $rendez_vous->group_id );

		// Get reference meta and sanity check.
		$references = get_post_meta( $rendez_vous->id, $this->reference_meta_key, true );
		if ( ! is_array( $references ) ) $references = array();

		// Get final date.
		$final = new DateTime( $rendez_vous->older_date, eo_get_blog_timezone() );

		// Events on this month.
		$month_start = $final->format( 'Y-m-01' );
		$month_end = $final->format( 'Y-m-t' );

		// Construct args.
		$event_args = apply_filters( 'civicrm_event_organiser_rendez_vous_event_args', array(
			'event_start_after'  => $month_start,
			'event_start_before' => $month_end,
		) );

		// Get event data.
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

		// Init comparison arrays.
		$correspondences = array();
		$new_items = array();
		$missing_items = array();

		// Parse events.
		if ( count( $events ) > 0 ) {
			foreach( $events AS $event ) {

				// Get CiviEvent ID.
				$civi_event_id = $this->db->get_civi_event_id_by_eo_occurrence_id( $event->ID, $event->occurrence_id );

				// Skip unless we have a CiviEvent.
				if ( $civi_event_id === false ) continue;

				// Always add to correspondences.
				$correspondences[$event->occurrence_id] = $civi_event_id;

				// Add to array if new.
				if ( ! in_array( $civi_event_id, $references ) ) {
					$new_items[$civi_event_id] = array( $event->ID, $event->occurrence_id );
				}

			}
		}

		// Parse existing event references.
		if ( count( $references ) > 0 ) {
			foreach( $references AS $timestamp => $civi_event_id ) {

				// Add to array if missing.
				if ( ! in_array( $civi_event_id, $correspondences ) ) {
					$missing_items[$timestamp] = $civi_event_id;
				}

			}
		}

		// Are there any new items to add?
		if ( count( $new_items ) > 0 ) {

			// Get existing timestamps.
			$used = array_keys( $rendez_vous->days );

			// Parse each new item.
			foreach( $new_items AS $civi_event_id => $event_data ) {

				// Get timestamp.
				$event_start_time = eo_get_the_start( DATETIMEOBJ, $event_data[0], $event_data[1] );
				$timestamp = $event_start_time->getTimestamp();

				// Ensure no duplicates by adding a trivial amount.
				while( in_array( $timestamp, $used ) ) {
					$timestamp++;
				}

				// Add the timestamp to the Rendez Vous.
				$rendez_vous->days[$timestamp] = array();

				// Add CiviEvent ID to reference array.
				$references[$timestamp] = $civi_event_id;

			}

		}

		// Are there any missing items?
		if ( count( $missing_items ) > 0 ) {
			foreach( $missing_items AS $timestamp => $civi_event_id ) {

				// Remove from the Rendez Vous it if it's there.
				if ( isset( $rendez_vous->days[$timestamp] ) ) {
					unset( $rendez_vous->days[$timestamp] );
				}

				// Remove from the reference array it if it's there.
				if ( isset( $references[$timestamp] ) ) {
					unset( $references[$timestamp] );
				}

			}
		}

		// If there are no changes to dates.
		if ( count( $missing_items ) == 0 AND count( $new_items ) == 0 ) {

			// Compare like-with-like.
			sort( $rendez_vous->attendees );
			sort( $attendee_ids );

			// Bail if there is no change of members.
			if ( $rendez_vous->attendees == $attendee_ids ) return $rendez_vous_id;

		}

		// Define title.
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

		// Construct update array.
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

		// Update the Rendez Vous.
		$updated_id = $this->rv_save( $rv_data );

		// Bail if this fails.
		if ( empty( $updated_id ) ) return false;

		// Update days.
		$this->rv_days_update( $updated_id, $rendez_vous->days );

		// Store the event IDs for the rendez vous.
		update_post_meta( $updated_id, $this->reference_meta_key, $references );

		// Store the month for the rendez vous.
		update_post_meta( $updated_id, $this->month_meta_key, $month_start );

		// --<
		return $updated_id;

	}



	/**
	 * Create a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param array $args The array of params for the Rendez Vous.
	 * @return int|bool $rendez_vous_id The numeric ID of the Rendez Vous post, or false on failure.
	 */
	public function rv_save( $args ) {

		// Create a rendez vous.
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

		// Delete a rendez vous.
		$success = rendez_vous_delete_item( $id );

		// --<
		return $success;

	}



	/**
	 * Update the days in a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param int $rendez_vous_id The numeric ID of the Rendez Vous post.
	 * @param array $days The array of dates for the Rendez Vous.
	 */
	public function rv_days_update( $rendez_vous_id, $days ) {

		// Make sure we have the "none" option.
		if ( ! in_array( 'none', array_keys( $days ) ) ) {
			$days['none'] = array();
		}

		// Update the days in the rendez vous item.
		update_post_meta( $rendez_vous_id, '_rendez_vous_days', $days );

	}



	/**
	 * Get the attendees for a Rendez Vous.
	 *
	 * @since 0.4.7
	 *
	 * @param array $args The array of params for the Rendez Vous.
	 * @return array $attendee_ids An array of numeric IDs of the Rendez Vous users.
	 */
	public function rv_attendees_get( $group_id ) {

		// Bail if no BuddyPress.
		if ( ! function_exists( 'buddypress' ) ) return array();

		// Perform the group member query.
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

		// Bail if no results.
		if ( count( $members->results ) == 0 ) return array();

		// Structure the return.
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

		// Is this a Rendez Vous button?
		if ( isset( $args['component'] ) AND $args['component'] == 'rendez_vous' ) {

			// Is this a New Rendez Vous button?
			if ( isset( $args['wrapper_id'] ) AND $args['wrapper_id'] == 'new-rendez-vous' ) {

				// Is this the Rendez Vous component?
				if ( bp_is_group() AND bp_is_current_action( rendez_vous()->get_component_slug() ) ) {

					// Get the currently displayed group ID.
					$group_id = bp_get_current_group_id();

					// Is attendance enabled for this group?
					if ( $this->group_get_option( $group_id, $this->group_meta_key ) ) {

						// Get the organizer ID from group meta.
						$organizer_id = groups_get_groupmeta( $group_id, $this->organizer_meta_key );

						// Get current user.
						$current_user = wp_get_current_user();

						// Is this the organizer?
						if ( $organizer_id == $current_user->ID OR is_super_admin() ) {

							// Build the URL we want.
							$current_url = home_url( add_query_arg( array() ) );
							$url = add_query_arg( 'action', 'refresh_all', $current_url );

							// Construct link.
							$link = '<a href="' . esc_url( $url ) . '">' .
										__( 'Refresh All', 'civicrm-eo-attendance' ) .
									'</a>';

							// Add in our button.
							$contents = str_replace(
								'</div>',
								'</div><div class="generic-button civicrm-eo-generic-button">' . $link . '</div>',
								$contents
							);

						}

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

		// Is this a Rendez Vous button?
		if ( isset( $args['component'] ) AND $args['component'] == 'rendez_vous' ) {

			// Is this a Rendez Vous edit button?
			if ( isset( $args['wrapper_id'] ) AND $args['wrapper_id'] == 'rendez-vous-edit-btn' ) {

				// Get current item.
				$item = rendez_vous()->item;

				// Does this Rendez Vous have our custom meta value?
				if ( $this->has_meta( $item ) ) {

					// Change action, class and title.
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

		// Bail if we don't have the custom meta.
		if ( ! $this->has_meta( $item ) ) return $class;

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

		// Modify header to include event title.
		if ( is_long( $date ) ) {

			// Get event ID.
			$civi_event_id = isset( $this->item_meta[$date] ) ? $this->item_meta[$date] : 0;

			// Get event ID.
			$event_id = $this->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

			// Get occurrence ID.
			$occurrence_id = $this->db->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );

			// Get event title.
			$event_title = get_the_title( $event_id );

			// Get event permalink.
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
	 * @param str $header The existing output.
	 * @param array $header The array of dates.
	 * @param str $view The output mode ('view' or 'edit').
	 * @return str $header The modified output.
	 */
	public function rv_form_last_row( $output, $header, $view ) {

		// Get current item.
		$item = rendez_vous()->item;

		// Bail if this Rendez Vous doesn't have our custom meta value.
		if ( ! $this->has_meta( $item ) ) return $output;

		// Open row.
		$output .= '<tr><td class="rendez-vous-date-blank">&nbsp;</td>';

		// Add register link.
		foreach ( $header AS $date ) {

			$output .= '<td class="rendez-vous-date">';
			$output .= $this->rv_form_column_header( $header, $date );
			$output .= '</td>';

		}

		// Close row.
		$output .= '</tr>';

		// Bail if this user cannot access the tools.
		if ( ! $this->rv_form_access_granted( $item ) ) return $output;

		// Init form markup.
		$this->form = '';

		// Open row.
		$output .= '<tr>';

		// Init with pseudo-th.
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

		// Add register link.
		foreach ( $header AS $date ) {

			// Handle "none" item.
			if ( 'none' == $date ) {
				$output .= '<td>&nbsp;</td>';
				continue;
			}

			// Add registration link.
			if ( is_long( $date ) ) {

				// Init class array with base class.
				$classes = array( 'civicrm-eo-rendezvous-register-all' );

				// Get event ID.
				$civi_event_id = isset( $this->item_meta[$date] ) ? $this->item_meta[$date] : 0;

				// Add event ID class.
				$classes[] = 'civicrm-eo-rv-event-id-' . $civi_event_id;

				// Get IDs of attendees.
				$attendee_ids = $item->days[$date];

				// Add attendee IDs class.
				$classes[] = 'civicrm-eo-rv-ids-' . implode( '-', $attendee_ids );

				// Open table row.
				$output .= '<td>';

				// Add span.
				$output .= '<span class="' . implode( ' ', $classes ) . '">' . __( 'Register', 'civicrm-eo-attendance' ) . '</span>';

				// Create dummy event object.
				$post = new stdClass;
				$post->ID = $this->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

				// Add form.
				$this->form .= $this->rv_form_registration_render( $civi_event_id, $attendee_ids, $post );

				// Close table row.
				$output .= '</td>';

			}

		}

		// Close row.
		$output .= '</tr>';

		// Trigger rendering of forms in footer.
		add_action( 'wp_footer', array( $this, 'rv_form_footer') );

		// --<
		return $output;

	}



	/**
	 * Add Registration Form for an event in a Rendez Vous.
	 *
	 * @since 0.4.8
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @param array $attendee_ids The numeric IDs of the attendees.
	 * @param str $select The markup for the participant roles dropdown.
	 * @param object $post A bare-bones post object with the EO event ID.
	 * @return str $markup The rendered registration form.
	 */
	public function rv_form_registration_render( $civi_event_id, $attendee_ids, $post ) {

		// Init markup.
		$markup = '';

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) return $markup;

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) return $markup;

		// Get event leader role for error checking.
		$event_leader_role = $this->plugin->event_leader->role_default_get( $post );

		// Init data arrays.
		$checked = array();
		$select = array();

		// Init "event has participants" flag.
		$participants_exist = false;

		// Get current participant statuses.
		if ( count( $attendee_ids ) > 0 ) {
			foreach( $attendee_ids AS $attendee_id ) {

				// Get the CiviCRM contact ID.
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// Skip if no contact ID found.
				if( empty( $contact_id ) ) continue;

				// Get current participant data.
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// If currently registered.
				if( $participant !== false ) {

					// Populate data arrays.
					$checked[$attendee_id] = ' checked="checked"';
					$select[$attendee_id] = $this->rv_form_registration_roles( $post, $participant['participant_role_id'] );

					// Set flag.
					$participants_exist = true;

				} else {

					// Add default entries to data arrays.
					$checked[$attendee_id] = '';
					$select[$attendee_id] = $this->rv_form_registration_roles( $post );

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
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-attendance/attendance-form.php' );

		// Save the output and flush the buffer.
		$markup = ob_get_clean();

		// --<
		return $markup;

	}



	/**
	 * Builds the Participant Roles select element for an EO event.
	 *
	 * @since 0.4.8
	 *
	 * @param object $post An EO event object.
	 * @param int $participant_role_id The numeric ID of the participant role.
	 * @return str $html Markup to display in the form.
	 */
	public function rv_form_registration_roles( $post, $participant_role_id = null ) {

		// Init html.
		$html = '';

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) return $html;

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) return $html;

		// First, get all participant_roles.
		if ( ! isset( $this->all_roles ) ) {
			$this->all_roles = $this->plugin->civicrm_eo->civi->get_participant_roles();
		}

		// Did we get any?
		if ( $this->all_roles['is_error'] == '0' AND count( $this->all_roles['values'] ) > 0 ) {

			// Get the values array.
			$roles = $this->all_roles['values'];

			// Init options.
			$options = array();

			// Get default role ID.
			$default_id = $this->plugin->civicrm_eo->civi->get_participant_role( $post );

			// Loop.
			foreach( $roles AS $key => $role ) {

				// Get role.
				$role_id = absint( $role['value'] );

				// Init selected.
				$selected = '';

				// Override selected when this value is the same as the given participant_role_id.
				if ( ! is_null( $participant_role_id ) AND $participant_role_id == $role_id ) {
					$selected = ' selected="selected"';
				}

				// Override selected when this value is the same as the default.
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

				// Construct option.
				$options[] = '<option value="' . $role_id . '"' . $selected . '>' . esc_html( $role['label'] ) . '</option>';

			}

			// Create html.
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
		echo $this->form;

	}



	/**
	 * Process a form submitted via AJAX.
	 *
	 * @since 0.4.8
	 */
	public function rv_form_process() {

		// Show something when there's an error.
		$error_markup = __( 'Oops! Something went wrong.', 'civicrm-eo-attendance' );

		// Get form data.
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? $_POST['civi_event_id'] : '0';
		$civi_event_id = absint( trim( $civi_event_id ) );
		$register_data = isset( $_POST['register'] ) ? $_POST['register'] : array();
		$unregister_data = isset( $_POST['unregister'] ) ? $_POST['unregister'] : array();

		// Init data.
		$data = array(
			'civi_event_id' => $civi_event_id,
			'error' => '0',
			'markup' => '',
		);

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			$data['error'] = '1';
			$data['markup'] = $error_markup;
			$this->send_data( $data );
		}

		// Try and init CiviCRM.
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

		// Sanity check.
		if ( ! is_array( $register_data ) OR ! is_array( $unregister_data ) ) {
			$data['error'] = '1';
			$data['markup'] = $error_markup;
			$this->send_data( $data );
		}

		// Get now in appropriate format.
		$now_obj = new DateTime( 'now', eo_get_blog_timezone() );
		$now = $now_obj->format( 'Y-m-d H:i:s' );

		// Get event details.
		$event_id = $this->db->get_eo_event_id_by_civi_event_id( $civi_event_id );
		$occurrence_id = $this->db->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );
		$event_title = get_the_title( $event_id );
		$event_link = get_permalink( $event_id );

		// Construct title.
		$linked_title = '<a href="' . esc_url( $event_link ) . '">' . esc_html( $event_title ) . '</a>';

		// Handle registrations.
		if ( count( $register_data ) > 0 ) {

			// Register each attendee.
			foreach( $register_data AS $attendee_id => $role_id ) {

				// Get the CiviCRM contact ID.
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// Skip if no contact ID found.
				if( empty( $contact_id ) ) continue;

				// Build params to create participant.
				$params = array(
					'version' => 3,
					'contact_id' => $contact_id,
					'event_id' => $civi_event_id,
					'status_id' => 1, // Registered.
					'role_id' => $role_id,
					'register_date' => $now, // As in: '2007-07-21 00:00:00'.
					'source' => __( 'Registration via Rendez Vous', 'civicrm-eo-attendance' ),
				);

				// Get current participant data.
				$participant = $this->plugin->custom_data_participant->participant_get( $contact_id, $civi_event_id );

				// Trigger update if participant is found.
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

				// Use CiviCRM API to create participant.
				$result = civicrm_api( 'participant', 'create', $params );

				// Error check.
				if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

					// Log error.
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

					// Set up message.
					$subject = sprintf(
						__( 'You have been registered for %s', 'civicrm-eo-attendance' ),
						esc_html( $event_title )
					);

					// Construct message body.
					$message = sprintf(
						__( 'This is to let you know that you have been registered as attending the event "%1$s" on %2$s at %3$s. If you think this has been done in error, or you have anything else you want to say to the organiser, please follow the link below and reply to this conversation on the Spirit of Football website.', 'civicrm-eo-attendance' ),
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
		if ( count( $unregister_data ) > 0 ) {

			// De-register each attendee.
			foreach( $unregister_data AS $attendee_id => $role_id ) {

				// Get the CiviCRM contact ID.
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

				// Skip if no contact ID found.
				if( empty( $contact_id ) ) continue;

				// Get participant data.
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

				// Skip if no participant found.
				if( $participant === false ) continue;

				// Build params to delete participant.
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

				// Use CiviCRM API to delete participant.
				$result = civicrm_api( 'participant', 'delete', $params );

				// Error check.
				if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

					// Log error.
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

					// Set up message.
					$subject = sprintf(
						__( 'You have been unregistered from %s', 'civicrm-eo-attendance' ),
						esc_html( $event_title )
					);

					// Construct message body.
					$message = sprintf(
						__( 'This is to let you know that your registration for the event "%1$s" on %2$s at %3$s has been cancelled and that you will no longer be attending. If you think this has been done in error, please follow the link below and reply to this conversation on the Spirit of Football website.', 'civicrm-eo-attendance' ),
						$linked_title,
						eo_get_the_start( 'j\/m\/Y', $event_id, $occurrence_id ),
						eo_get_the_start( 'g:ia', $event_id, $occurrence_id )
					);

					// Send message.
					$this->rv_form_notify_user( $attendee_id, $subject, $message );

				}

			}

		}

		// What to return?
		$markup = __( 'Thanks!', 'civicrm-eo-attendance' );

		// Amend data.
		$data['markup'] = $markup;

		// Send data to browser.
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

		// Bail if anything is amiss.
		if ( empty( $recipient_id ) ) return;
		if ( empty( $subject ) ) return;
		if ( empty( $content ) ) return;

		// Get current user.
		$current_user = wp_get_current_user();

		// Don't notify the recipient if they are the current user.
		if ( $recipient_id == $current_user->ID ) return;

		// Set up message.
		$msg_args = array(
			'sender_id'  => $current_user->ID,
			'thread_id'  => false,
			'recipients' => array( $recipient_id ), // Can be an array of usernames, user_ids or mixed.
			'subject'    => $subject,
			'content'    => $content,
		);

		// Send message.
		messages_new_message( $msg_args );

	}



	/**
	 * Check if a user can access participant management functionality.
	 *
	 * @since 0.5
	 *
	 * @param obj $rendez_vous The Rendez Vous to check.
	 * @return bool $allowed True if allowed, false otherwise.
	 */
	public function rv_form_access_granted( $rendez_vous ) {

		// Get current user.
		$current_user = wp_get_current_user();

		// Allow if user is the rendez vous 'organizer'.
		if ( $rendez_vous->organizer == $current_user->ID ) return true;

		// Allow if user is a group admin.
		if ( groups_is_user_admin( $current_user->ID, $rendez_vous->group_id ) ) return true;

		// Allow if user is a group mod.
		if ( groups_is_user_mod( $current_user->ID, $rendez_vous->group_id ) ) return true;

		// Disallow
		return false;

	}



	//##########################################################################



	/**
	 * Check if a Rendez Vous has a term.
	 *
	 * @since 0.4.7
	 *
	 * @param string $item The Rendez Vous to check.
	 * @return bool $term True if the Rendez Cous has the term, false otherwise.
	 */
	public function has_term( $item ) {

		// Does this Rendez Vous have a term?
		if ( isset( $item->type ) AND is_array( $item->type ) ) {
			foreach( $item->type AS $type ) {

				// Is this our term?
				if ( $type->term_id == $this->db->option_get( $this->term_option ) ) {
					return true;
				}

			}
		}

		// Fallback.
		return false;

	}



	/**
	 * Create a Rendez Vous term.
	 *
	 * @since 0.4.7
	 *
	 * @param string $term_name The term to add.
	 * @return array|WP_Error $term An array containing the `term_id` and `term_taxonomy_id`.
	 */
	public function term_create( $term_name ) {

		// Create a rendez vous term.
		$term = rendez_vous_insert_term( $term_name );

		// --<
		return $term;

	}



	/**
	 * Delete a Rendez Vous term.
	 *
	 * @since 0.4.7
	 *
	 * @param int $term_id The ID of the term.
	 * @return bool|WP_Error $retval Returns false if not term; true if term deleted.
	 */
	public function term_delete( $term_id ) {

		// Delete a rendez vous term.
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
	 * @param string $item The Rendez Vous to check.
	 * @return bool True if the Rendez Cous has the meta value, false otherwise.
	 */
	public function get_meta( $item ) {

		// Bail if this isn't a Rendez Vous object.
		if ( ! ( $item instanceof Rendez_Vous_Item ) ) return false;

		// Get meta for this Rendez Vous.
		$rv_meta = get_post_meta( $item->id, $this->month_meta_key, true );

		// There's no meta if we get the default empty string returned.
		if ( $rv_meta === '' ) return false;

		// We're good.
		return $rv_meta;

	}



	/**
	 * Check if a Rendez Vous has our custom meta value.
	 *
	 * @since 0.5
	 *
	 * @param string $item The Rendez Vous to check.
	 * @return bool True if the Rendez Cous has the meta value, false otherwise.
	 */
	public function has_meta( $item ) {

		// Bail if this isn't a Rendez Vous object.
		if ( ! ( $item instanceof Rendez_Vous_Item ) ) return false;

		// Get meta for this Rendez Vous.
		$rv_meta = get_post_meta( $item->id, $this->month_meta_key, true );

		// There's no meta if we get the default empty string returned.
		if ( $rv_meta === '' ) return false;

		// We're good.
		return true;

	}



	//##########################################################################



	/**
	 * Inject a checkbox into group manage screen.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The numeric ID of the group.
	 */
	public function group_manage_form_amend( $group_id ) {

		// Get the attendance enabled group meta.
		$attendance_enabled = groups_get_groupmeta( $group_id, $this->group_meta_key );

		// Set checkbox checked attribute.
		$checked = '';
		if ( ! empty( $attendance_enabled ) ) {
			$checked = ' checked="checked"';
		}

		// Construct checkbox.
		$checkbox = '<input type="checkbox" id="' . $this->group_meta_key . '" name="' . $this->group_meta_key . '" value="1"' . $checked . '> ';

		// Construct label.
		$label = '<label for="' . $this->group_meta_key . '">' .
					$checkbox .
					__( 'Enable Attendance', 'civicrm-eo-attendance' ) .
				 '</label>';

		// Wrap in divs.
		$enabled_div = '<div class="field-group civicrm-eo-group-enabled"><div class="checkbox">' .
							$label .
						'</div></div>';

		// Get the organizer ID from group meta.
		$organizer_id = groups_get_groupmeta( $group_id, $this->organizer_meta_key );

		// Init options.
		$options = array();

		// Add empty value if none set.
		if ( empty( $organizer_id ) ) {
			$options[] = '<option value="0" selected="selected">' .
							__( 'Select an Organiser', 'civicrm-eo-attendance' ) .
						 '</option>';
		}

		// Get group admins.
		$admins = groups_get_group_admins( $group_id );

		// Get group mods.
		$mods = groups_get_group_mods( $group_id );

		// Merge arrays.
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

		// Loop.
		foreach( $organizers AS $user ) {

			// Init selected.
			$selected = '';

			// Override selected if this is the organizer.
			if ( $organizer_id == $user->user_id ) {
				$selected = ' selected="selected"';
			}

			// Construct option.
			$options[] = '<option value="' . $user->user_id . '"' . $selected . '>' .
							bp_core_get_user_displayname( $user->user_id ) .
						 '</option>';

		}

		// Create options markup.
		$organizer_options = implode( "\n", $options );

		// Wrap in select.
		$organizer_options = '<select id="' . $this->organizer_meta_key . '" name="' . $this->organizer_meta_key . '">' .
								 $organizer_options .
							 '</select>';

		// Costruct label.
		$organizer_label = '<label for="' . $this->organizer_meta_key . '">' .
							__( 'Choose who is responsible for Attendance', 'civicrm-eo-attendance' ) .
							'</label>';

		// Wrap in divs.
		$organizer_div = '<div class="field-group civicrm-eo-group-organizer"><div class="select">' .
							$organizer_label .
							$organizer_options .
						 '</div></div>';

		// Construct markup.
		$markup = $enabled_div . $organizer_div;

		// Show markup.
		echo $markup;

	}



	/**
	 * Check the value of the checkbox on the group manage screen.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The numeric ID of the group.
	 * @param array $settings The Rendez Vous settings array.
	 */
	public function group_manage_form_submit( $group_id, $settings ) {

		// Sanity check.
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		// Init options.
		$options = array(
			$this->group_meta_key => 0,
			$this->organizer_meta_key => 0,
		);

		// Sanitise input.
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

		// Go ahead and save the meta values.
		groups_update_groupmeta( $group_id, $this->group_meta_key, $options[$this->group_meta_key] );
		groups_update_groupmeta( $group_id, $this->organizer_meta_key, $options[$this->organizer_meta_key] );

	}



	/**
	 * Get group meta value, use default if meta value is not set.
	 *
	 * @since 0.5.1
	 *
	 * @param int $group_id The group ID.
	 * @param str $meta_key The meta key.
	 * @param mixed $default The default value to fallback to.
	 * @return mixed The meta value, or false on failure.
	 */
	public function group_get_option( $group_id = 0, $meta_key = '', $default = '' ) {

		// Sanity check.
		if ( empty( $group_id ) OR empty( $meta_key ) ) {
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
	 * Update registrations when a user updates their attendance.
	 *
	 * @since 0.5.1
	 *
	 * @param array $args The arguments.
	 * @param int $attendee_id The user ID of the attendee.
	 * @param object $rendez_vous The Rendez Vous.
	 */
	public function registrations_check( $args = array() ) {

		// Bail if something's not right with the array.
		if ( ! isset( $args['id'] ) ) return;
		if ( ! is_int( absint( $args['id'] ) ) ) return;

		// Get reference meta and sanity check.
		$references = get_post_meta( $args['id'], $this->reference_meta_key, true );
		if ( ! is_array( $references ) ) return;

		// Get Rendez Vous.
		$rendez_vous = rendez_vous_get_item( $args['id'] );

		// Bail if this is not an attendance Rendez Vous.
		$rv_meta = $this->get_meta( $rendez_vous );
		if ( $rv_meta === false ) return;

		// Bail if this fails for some reason.
		if ( ! ( $rendez_vous instanceof Rendez_Vous_Item ) ) return;

		// Get current user.
		$current_user = wp_get_current_user();

		// Get the days that this attendee used to have.
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
	 * @param array $args The arguments.
	 * @param int $attendee_id The user ID of the attendee.
	 * @param object $rendez_vous The Rendez Vous.
	 */
	public function registrations_update( $args = array(), $attendee_id = 0, $rendez_vous = null ) {

		// Bail if we don't have our attending array.
		if ( ! isset( $this->attending ) ) return;
		if ( ! is_array( $this->attending ) ) return;
		if ( count( $this->attending ) === 0 ) return;

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) return;

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Get user matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get the CiviCRM contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $attendee_id );

		// Look for missing items.
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

		// Bail if there are no missing items.
		if ( count( $missing_items ) === 0 ) return;

		// Loop through the rest.
		foreach( $missing_items AS $timestamp => $civi_event_id ) {

			// Skip if not a timestamp.
			if ( ! is_long( $timestamp ) ) continue;

			// Get date of event.
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

			// Skip to next if it is a past event.
			if ( $this->is_past( $date ) ) continue;

			// Build params to get participant.
			$params = array(
				'version' => 3,
				'contact_id' => $contact_id,
				'event_id' => $civi_event_id,
			);

			// Get participant instances.
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

			// Error check.
			if ( isset( $participants['is_error'] ) AND $participants['is_error'] == '1' ) {

				// Log and skip.
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

			// Skip if not registered.
			if ( count( $participants['values'] ) === 0 ) continue;

			// We should only have one registration.
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

			// Build params to delete participant.
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

			// Use CiviCRM API to delete participant.
			$result = civicrm_api( 'participant', 'delete', $params );

			// Error check.
			if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

				// Log error and skip.
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

			// Send message if component is available.
			if ( bp_is_active( 'messages' ) ) {

				// Construct subject.
				$subject = sprintf(
					__( '%1$s cannot attend "%2$s"', 'civicrm-eo-attendance' ),
					$registration['display_name'],
					$registration['event_title']
				);

				// Construct link to Rendez Vous.
				$link = rendez_vous_get_single_link( $rendez_vous->id, $rendez_vous->organizer );
				$rendez_vous_link = '<a href="'. esc_url( $link ) .'">' . esc_html( $rendez_vous->title ) . '</a>';

				// Construct content..
				$content = sprintf(
					__( 'Unfortunately %1$s can no longer attend "%2$s" on %3$s at %4$s and their registration for this event has been cancelled. Please visit the "%5$s" to review registrations for this event.', 'civicrm-eo-attendance' ),
					$registration['display_name'],
					$registration['event_title'],
					date_i18n( get_option('date_format'), $timestamp ),
					date_i18n( get_option('time_format'), $timestamp ),
					$rendez_vous_link
				);

				// Set up message.
				$msg_args = array(
					'sender_id'  => $attendee_id,
					'thread_id'  => false,
					'recipients' => $rendez_vous->organizer,
					'subject'    => $subject,
					'content'    => $content,
				);

				// Send message.
				messages_new_message( $msg_args );

			}

		}

	}



	/**
	 * Check if an event is a past event.
	 *
	 * @since 0.4.6
	 *
	 * @param str $end_date The end date string for a CiviEvent.
	 * @return bool $past_event True if the event is past, false otherwise.
	 */
	public function is_past( $end_date ) {

		// Init past event flag.
		$past_event = false;

		// Override if it's a past event.
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

		// Is this an AJAX request?
		if ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) {

			// Set reasonable headers.
			header('Content-type: text/plain');
			header("Cache-Control: no-cache");
			header("Expires: -1");

			// Echo.
			echo json_encode( $data );

			// Die.
			exit();

		}

	}

} // Class ends.



