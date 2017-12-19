<?php

/**
 * CiviCRM Event Organiser Attendance Participant Listing Class.
 *
 * A class that encapsulates Participant Listing functionality.
 *
 * @since 0.1
 */
class CiviCRM_EO_Attendance_Participant_Listing {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $plugin The plugin object
	 */
	public $plugin;

	/**
	 * Plugin Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $option_name The option name
	 */
	public $option_name = 'civicrm_eo_event_default_listing';

	/**
	 * Post Meta name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $meta_name The post meta name
	 */
	public $meta_name = '_civi_participant_listing_profile';



	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// register hooks
		$this->register_hooks();

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.1
	 *
	 * @param object $parent The parent object
	 */
	public function set_references( $parent ) {

		// store
		$this->plugin = $parent;

	}



	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// apply changes to CiviEvent when an EO event is synced to CiviCRM
		add_filter( 'civicrm_event_organiser_prepared_civi_event', array( $this, 'prepare_civi_event' ), 10, 2 );

		// post-process an EO event when a CiviEvent is synced to WordPress
		add_action( 'civicrm_event_organiser_eo_event_updated', array( $this, 'process_eo_event' ), 10, 2 );

		// add our settings to the settings table
		add_action( 'civicrm_event_organiser_settings_table_last_row', array( $this, 'settings_table' ) );

		// save our settings on plugin settings save
		add_action( 'civicrm_event_organiser_settings_updated', array( $this, 'settings_update' ) );

		// add our components to the event metabox
		add_action( 'civicrm_event_organiser_event_meta_box_after', array( $this, 'components_metabox' ) );

		// save our event components on event components save
		add_action( 'civicrm_event_organiser_event_components_updated', array( $this, 'components_update' ) );

		// show links in EO event template
		add_action( 'eventorganiser_additional_event_meta', array( $this, 'list_render' ), 9 );

		// add AJAX handler
		add_action( 'wp_ajax_participants_list_get', array( $this, 'participants_list_get' ) );

	}



	//##########################################################################



	/**
	 * Update a CiviEvent when an EO event is synced to CiviCRM.
	 *
	 * @since 0.1
	 *
	 * @param array $civi_event The array of data for the CiviEvent
	 * @param object $post The WP post object
	 * @return array $civi_event The modified array of data for the CiviEvent
	 */
	public function prepare_civi_event( $civi_event, $post ) {

		// get existing participant listing profile ID
		$existing_id = $this->get_default_value( $post );

		// did we get one?
		if ( $existing_id !== false AND is_numeric( $existing_id ) AND $existing_id != 0 ) {

			// add to our params
			$civi_event['participant_listing_id'] = $existing_id;

		}

		// --<
		return $civi_event;

	}



	/**
	 * Update an EO event when a CiviEvent is synced to WordPress.
	 *
	 * @since 0.1
	 *
	 * @param int $event_id The numeric ID of the EO event
	 * @param array $civi_event An array of data for the CiviEvent
	 */
	public function process_eo_event( $event_id, $civi_event ) {

		// if the event has a participant listing profile specified
		if (
			isset( $civi_event['participant_listing_id'] ) AND
			! empty( $civi_event['participant_listing_id'] ) AND
			is_numeric( $civi_event['participant_listing_id'] )
		) {

			// save specified participant listing profile
			$this->profile_set( $event_id, $civi_event['participant_listing_id'] );

		} else {

			// set default participant listing profile
			$this->profile_set( $event_id );

		}

	}



	//##########################################################################



	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.1
	 */
	public function settings_table() {

		// get all participant listing profiles
		$profiles = $this->get_select();

		// bail if there aren't any
		if ( empty( $profiles ) ) return;

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/participant-listing/setting-admin.php' );

	}



	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.1
	 */
	public function settings_update() {

		// set defaults
		$civicrm_eo_event_default_listing = '0';

		// get variables
		extract( $_POST );

		// sanitise
		$civicrm_eo_event_default_listing = absint( $civicrm_eo_event_default_listing );

		// save option
		$this->plugin->civicrm_eo->db->option_save( $this->option_name, $civicrm_eo_event_default_listing );

	}



	/**
	 * Add our components to the event metabox.
	 *
	 * @since 0.1
	 *
	 * @param object $event The EO event object
	 */
	public function components_metabox( $event ) {

		// get all participant listing profiles
		$profiles = $this->get_select( $event );

		// bail if there aren't any
		if ( empty( $profiles ) ) return;

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/participant-listing/setting-metabox.php' );

	}



	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.1
	 *
	 * @param int $event_id The numeric ID of the EO event
	 */
	public function components_update( $event_id ) {

		// save participant listing profile
		$this->profile_update( $event_id );

	}



	//##########################################################################



	/**
	 * Get all CiviEvent participant listing profiles.
	 *
	 * @since 0.1
	 *
	 * @return mixed $profiles CiviCRM API return array (or false on failure)
	 */
	public function get_profiles() {

		// if we fail to init CiviCRM...
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) return false;

		// get option group ID
		$opt_group_id = $this->get_optgroup_id();

		// error check
		if ( $opt_group_id === false ) return false;

		// define params to get items sorted by weight
		$params = array(
			'option_group_id' => $opt_group_id,
			'version' => 3,
			'options' => array(
				'sort' => 'weight ASC',
			),
		);

		// get them (descriptions will be present if not null)
		$profiles = civicrm_api( 'option_value', 'get', $params );

		// error check
		if ( $profiles['is_error'] == '1' ) {

			// log and bail
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => $profiles['error_message'],
				'params' => $params,
				'profiles' => $profiles,
				'backtrace' => $trace,
			), true ) );

			// --<
			return false;

		}

		// --<
		return $profiles;

	}



	/**
	 * Get all CiviEvent participant listing profiles formatted as a dropdown list.
	 *
	 * The pseudo-ID is actually the participant listing profile "value" rather
	 * than the participant listing profile ID.
	 *
	 * @since 0.1
	 *
	 * @param object $post An EO event object
	 * @return str $html Markup containing select options
	 */
	public function get_select( $post = null ) {

		// init return
		$html = '';

		// init CiviCRM or die
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) return $html;

		// get all participant listing profiles
		$result = $this->get_profiles();

		// did we get any?
		if (
			$result !== false AND
			$result['is_error'] == '0' AND
			count( $result['values'] ) > 0
		) {

			// get the values array
			$profiles = $result['values'];

			// prepend a "disabled" item
			array_unshift( $profiles, array(
				'value' => 0,
				'label' => __( 'Disabled', 'civicrm-eo-attendance' ),
			) );

			// init options
			$options = array();

			// get existing profile value for the post (if defined)
			$existing_value = $this->get_default_value( $post );

			// loop
			foreach( $profiles AS $key => $profile ) {

				// get profile value
				$value = absint( $profile['value'] );

				// init selected
				$selected = '';

				// override selected if this value is the same as in the post
				if ( $existing_value === $value ) {
					$selected = ' selected="selected"';
				}

				// construct option
				$options[] = '<option value="' . $value . '"' . $selected . '>' . esc_html( $profile['label'] ) . '</option>';

			}

			// create html
			$html = implode( "\n", $options );

		}

		// return
		return $html;

	}



	/**
	 * Get the default participant listing profile value for a post.
	 *
	 * Falls back to the default as set on the plugin Settings screen.
	 * Falls back to zero (disabled) otherwise.
	 *
	 * @since 0.1
	 *
	 * @param object $post The WP event object
	 * @return int $existing_id The numeric ID of the CiviEvent participant listing profile (or 0 if none exists)
	 */
	public function get_default_value( $post = null ) {

		// init as disabled
		$existing_value = 0;

		// do we have a default set?
		$default = $this->plugin->civicrm_eo->db->option_get( $this->option_name );

		// did we get one?
		if ( $default !== '' AND is_numeric( $default ) ) {

			// override with default value
			$existing_value = absint( $default );

		}

		// if we have a post
		if ( isset( $post ) AND is_object( $post ) ) {

			// get stored value
			$stored_id = $this->profile_get( $post->ID );

			// did we get one?
			if ( $stored_id !== '' AND is_numeric( $stored_id ) AND $stored_id > 0 ) {

				// override with stored value
				$existing_value = absint( $stored_id );

			}

		}

		// --<
		return $existing_value;

	}



	/**
	 * Get a CiviEvent participant listing profile by ID.
	 *
	 * @since 0.1
	 *
	 * @param int $profile_id The numeric ID of a CiviEvent participant listing profile
	 * @return array $profile CiviEvent participant listing profile data
	 */
	public function get_by_id( $profile_id ) {

		// if we fail to init CiviCRM...
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) return false;

		// get option group ID
		$opt_group_id = $this->get_optgroup_id();

		// error check
		if ( $opt_group_id === false ) return false;

		// define params to get item
		$params = array(
			'version' => 3,
			'option_group_id' => $opt_group_id,
			'id' => $profile_id,
		);

		// get them (descriptions will be present if not null)
		$profile = civicrm_api( 'option_value', 'getsingle', $params );

		// --<
		return $profile;

	}



	/**
	 * Get a CiviEvent participant listing profile by "value" pseudo-ID.
	 *
	 * @since 0.1
	 *
	 * @param int $value The numeric value of a CiviEvent participant listing profile
	 * @return array $profile CiviEvent participant listing profile data
	 */
	public function get_by_value( $value ) {

		// if we fail to init CiviCRM...
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) return false;

		// get option group ID
		$opt_group_id = $this->get_optgroup_id();

		// error check
		if ( $opt_group_id === false ) return false;

		// define params to get item
		$params = array(
			'version' => 3,
			'option_group_id' => $opt_group_id,
			'value' => $value,
		);

		// get them (descriptions will be present if not null)
		$profile = civicrm_api( 'option_value', 'getsingle', $params );

		// --<
		return $profile;

	}



	/**
	 * Get the CiviEvent participant_listing option group ID.
	 *
	 * Multiple calls to the db are avoided by setting the static variable.
	 *
	 * @since 0.1
	 *
	 * @return array|bool $optgroup_id The ID of the participant listing option group (or false on failure)
	 */
	public function get_optgroup_id() {

		// init
		static $optgroup_id;

		// do we have it?
		if ( ! isset( $optgroup_id ) ) {

			// if we fail to init CiviCRM...
			if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {

				// set flag to false for future reference
				$optgroup_id = false;

				// --<
				return $optgroup_id;

			}

			// define params to get participant listing profile option group
			$params = array(
				'version' => 3,
				'name' => 'participant_listing',
			);

			// get it via API
			$opt_group = civicrm_api( 'option_group', 'getsingle', $params );

			// error check
			if ( isset( $opt_group['id'] ) AND is_numeric( $opt_group['id'] ) AND $opt_group['id'] > 0 ) {

				// set flag to found ID for future reference
				$optgroup_id = $opt_group['id'];

				// --<
				return $optgroup_id;

			}

		}

		// --<
		return $optgroup_id;

	}



	//##########################################################################



	/**
	 * Update participant listing profile value for an event.
	 *
	 * @since 0.1
	 *
	 * @param int $event_id The numeric ID of the event
	 */
	public function profile_update( $event_id ) {

		// kick out if not set
		if ( ! isset( $_POST['civicrm_eo_event_listing'] ) ) return;

		// retrieve meta value
		$profile_id = absint( $_POST['civicrm_eo_event_listing'] );

		// update event meta
		update_post_meta( $event_id,  $this->meta_name, $profile_id );

	}



	/**
	 * Update participant listing profile value for an event.
	 *
	 * @since 0.1
	 *
	 * @param int $event_id The numeric ID of the event
	 * @param int $profile_id The participant listing profile ID for the CiviEvent
	 */
	public function profile_set( $event_id, $profile_id = null ) {

		// if not set
		if ( is_null( $profile_id ) ) {

			// do we have a default set?
			$default = $this->plugin->civicrm_eo->db->option_get( $this->option_name );

			// did we get one?
			if ( $default !== '' AND is_numeric( $default ) ) {

				// override with default value
				$profile_id = absint( $default );

			}

		}

		// update event meta
		update_post_meta( $event_id,  $this->meta_name, $profile_id );

	}



	/**
	 * Get participant listing profile value for an event.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The numeric ID of the WP post
	 * @return int $profile_id The participant listing profile ID for the CiviEvent
	 */
	public function profile_get( $post_id ) {

		// get the meta value
		$profile_id = get_post_meta( $post_id, $this->meta_name, true );

		// if it's not yet set it will be an empty string, so cast as number
		if ( $profile_id === '' ) { $profile_id = 0; }

		// --<
		return absint( $profile_id );

	}



	/**
	 * Delete participant listing profile value for an event.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The numeric ID of the WP post
	 */
	public function profile_clear( $post_id ) {

		// delete the meta value
		delete_post_meta( $post_id, $this->meta_name );

	}



	//##########################################################################



	/**
	 * Add a list of Participant links for an event to the EO event meta list.
	 *
	 * @since 0.2
	 *
	 * @param int $post_id The numeric ID of the WP post
	 */
	public function list_render( $post_id = null ) {

		// always deny to anonymous users
		if ( ! is_user_logged_in() ) return;

		// deny to users who aren't a member of a group that has Attendance enabled

		/**
		 * Filter allows other plugins to modify access.
		 *
		 * @since 0.5.1
		 *
		 * @param bool $granted False by default - assume access not granted
		 * @param int $post_id The numeric ID of the WP post
		 * @return bool $granted True if access granted, false otherwise
		 */
		if ( false === apply_filters( 'civicrm_eo_pl_access', false, $post_id ) ) {

			/**
			 * Fire "access denied" action so other plugins can post-process.
			 *
			 * In my specific use case, this is listened to by the "Paid Event"
			 * class - because Paid Events must have their registrations open
			 * but we don't want to show the standard register links. This means
			 * calling remove_action (as per below) when we deny access, but
			 * only when it's a Paid Event.
			 *
			 * @since 0.5.1
			 *
			 * @param int $post_id The numeric ID of the WP post
			 */
			do_action( 'civicrm_eo_pl_access_denied', $post_id );

			// --<
			return;

		}

		// remove core plugin's list renderer
		remove_action( 'eventorganiser_additional_event_meta', 'civicrm_event_organiser_register_links' );

		// get links array
		$links = $this->list_populate( $post_id );

		// show them if we have any
		if ( ! empty( $links ) ) {

			// construct visual cue for AJAX loading
			$spinner_src = CIVICRM_EO_ATTENDANCE_URL . 'assets/images/loading.gif';
			$spinner = '<img src="' . $spinner_src . '" class="civicrm-eo-spinner" />';
			$loading = '<div class="civicrm-eo-loading">' . $spinner . '</div>';

			// combine into list
			$list = implode( $loading . '</li>' . "\n" . '<li class="civicrm-eo-participant">', $links );

			// top and tail
			$list = '<li class="civicrm-eo-participant">' . $list . $loading . '</li>' . "\n";

			// handle recurring events
			if ( eo_recurs() ) {

				// wrap in unordered list
				$list = '<ul class="civicrm-eo-participants">' . $list . '</ul>';

				// open a list item
				echo '<li class="civicrm-eo-participants">';

				// show a title
				echo '<strong>' . __( 'Participants and Registration', 'civicrm-eo-attendance' ) . ':</strong>';

				// show links
				echo $list;

				// finish up
				echo '</li>' . "\n";

			} else {

				// show links list
				echo $list;

			}

			// add javascript
			$this->list_scripts();

		}

	}



	/**
	 * Add our Javascript to the EO event meta list.
	 *
	 * @since 0.3
	 */
	public function list_scripts() {

		// add script to footer
		wp_enqueue_script(
			'civicrm-eo-attendance-pl',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-pl.js',
			array( 'jquery' ),
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // in footer
		);

		// translations
		$localisation = array();

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
			'civicrm-eo-attendance-pl',
			'CiviCRM_EO_Attendance_PL_Settings',
			$vars
		);

	}



	/**
	 * Get the Participant Listing page links for an EO Event.
	 *
	 * @since 0.2
	 *
	 * @param int $post_id The numeric ID of the WP post
	 * @return array $links The HTML links to the CiviCRM Participant pages
	 */
	public function list_populate( $post_id = null ) {

		// init return
		$links = array();

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return $links;

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) return $links;

		// need the post ID
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// bail if not present
		if( empty( $post_id ) ) return $links;

		// get CiviEvents
		$civi_events = $this->plugin->civicrm_eo->db->get_civi_event_ids_by_eo_event_id( $post_id );

		// sanity check
		if ( empty( $civi_events ) ) return $links;

		// did we get more than one?
		$multiple = ( count( $civi_events ) > 1 ) ? true : false;

		// escalate permissions to view participants
		add_action( 'civicrm_permission_check', array( $this, 'permissions_escalate' ), 10, 2 );

		// loop through them
		foreach( $civi_events AS $civi_event_id ) {

			// get the full CiviEvent
			$civi_event = $this->plugin->civicrm_eo->civi->get_event_by_id( $civi_event_id );

			// init past event flag
			$past_event = false;

			// override if it's a past event
			$now = new DateTime( 'now', eo_get_blog_timezone() );
			$end = new DateTime( $civi_event['end_date'], eo_get_blog_timezone() );
			if ( $end < $now ) {
				//$past_event = true;
			}

			/**
			 * Skip to next if past event but allow overrides.
			 *
			 * @since 0.4.5
			 *
			 * @param bool $past_event True if the event is past, false otherwise
			 * @return bool $past_event True if the event is past, false otherwise
			 */
			$skip_past_events = apply_filters(  'civicrm_event_organiser_participant_list_past', $past_event );

			// maybe skip past events
			if ( $skip_past_events ) continue;

			// get link for the participants page
			$url = $this->page_link_get( $civi_event );

			// skip to next if empty
			if ( empty( $url ) ) continue;

			/**
			 * Filter participant URL.
			 *
			 * @since 0.2
			 *
			 * @param string $url The raw URL to the CiviCRM Registration page
			 * @param array $civi_event The array of data that represents a CiviEvent
			 * @param int $post_id The numeric ID of the WP post
			 */
			$url = apply_filters( 'civicrm_event_organiser_participant_url', $url, $civi_event, $post_id );

			// set different link text for single and multiple occurrences
			if ( $multiple ) {

				// get occurrence ID for this CiviEvent
				$occurrence_id = $this->plugin->civicrm_eo->db->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );

				// define text
				$text = sprintf(
					__( 'Participants for %s', 'civicrm-eo-attendance' ),
					eo_format_event_occurrence( $post_id, $occurrence_id )
				);

			} else {
				$text = __( 'Participants and Registration', 'civicrm-eo-attendance' );
			}

			// construct custom class name
			$class = 'civicrm-eo-pl-event-id-' . $civi_event['id'];

			// construct link if we get one
			$link = '<a class="civicrm-eo-participant-link ' . $class . '" href="' . $url . '">' . $text . '</a>';

			/**
			 * Filter participant link.
			 *
			 * @since 0.2
			 *
			 * @param string $link The HTML link to the CiviCRM Participant page
			 * @param string $url The raw URL to the CiviCRM Participant page
			 * @param string $text The text content of the link
			 * @param int $post_id The numeric ID of the WP post
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_participant_link', $link, $url, $text, $post_id );

		}

		// remove permission to view participants
		remove_action( 'civicrm_permission_check', array( $this, 'permissions_escalate' ), 10 );

		// --<
		return $links;

	}



	/**
	 * Get a CiviEvent's Participants link.
	 *
	 * @since 0.2
	 *
	 * @param array $civi_event An array of data for the CiviEvent
	 * @return string $link The URL of the CiviCRM Participants page
	 */
	public function page_link_get( $civi_event ) {

		// init link
		$link = '';

		// check permission
		if ( ! $this->plugin->civicrm_eo->civi->check_permission( 'view event participants' ) ) {
			return $link;
		}

		// if this event has participant listings enabled
		if (
			isset( $civi_event['participant_listing_id'] ) AND
			is_numeric( $civi_event['participant_listing_id'] ) AND
			absint( $civi_event['participant_listing_id'] ) > 0
		) {

			// init CiviCRM or bail
			if ( ! $this->plugin->civicrm_eo->civi->is_active() ) return $link;

			// use CiviCRM to construct link
			$link = CRM_Utils_System::url(
				'civicrm/event/participant', 'reset=1&id=' . $civi_event['id'],
				TRUE,
				NULL,
				FALSE,
				TRUE
			);

		}

		// --<
		return $link;

	}



	//##########################################################################



	/**
	 * Get a CiviEvent's Participants.
	 *
	 * @since 0.2
	 *
	 * @param int $civi_event_id The numeric ID of a CiviEvent
	 * @return array $participants The array of participants data
	 */
	public function participants_get( $civi_event_id ) {

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return array();

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) return array();

		// define query params
		$params = array(
			'version' => 3,
			'event_id' => $civi_event_id,
		);

		// query via API
		$result = civicrm_api( 'participant', 'get', $params );

		// log failures and bail
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

			// log error
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'civi_event_id' => $civi_event_id,
				'backtrace' => $trace,
			), true ) );

			// return empty array
			return array();

		}

		// return empty array if not set for some reason
		if ( ! isset( $result['values'] ) ) return array();

		// --<
		return $result['values'];

	}



	/**
	 * Get a list of Participants for an event.
	 *
	 * @since 0.2
	 */
	public function participants_list_get() {

		// get event ID
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? $_POST['civi_event_id'] : '';

		// sanitise
		$civi_event_id = absint( trim( $civi_event_id ) );

		// init data
		$data = array(
			'count' => 0,
			'civi_event_id' => $civi_event_id,
			'markup' => '',
		);

		// check we got something
		if ( ! empty( $civi_event_id ) ) {

			// init contact IDs
			$contact_ids = array();

			// escalate permissions to view participants
			add_action( 'civicrm_permission_check', array( $this, 'permissions_escalate' ), 10, 2 );

			// get participants array
			$participants = $this->participants_get( $civi_event_id );

			// remove permission to view participants
			remove_action( 'civicrm_permission_check', array( $this, 'permissions_escalate' ), 10 );

			// show them if we have any
			if ( ! empty( $participants ) ) {

				// init arrays
				$names = array();

				// grab all participant names and contact IDs
				foreach( $participants AS $participant ) {
					$names[] = $participant['display_name'];
					$contact_ids[] = $participant['contact_id'];
				}

				// add to data
				$data['count'] = count( $names );

			} else {

				// init names
				$names = array( __( 'No participants for this event', 'civicrm-eo-attendance' ) );

			}

			// get the full CiviEvent
			$civi_event = $this->plugin->civicrm_eo->civi->get_event_by_id( $civi_event_id );

			// show link if registration is not closed
			if ( ! $this->plugin->civicrm_eo->civi->is_registration_closed( $civi_event ) ) {

				// get current user
				$current_user = wp_get_current_user();

				// get user matching file
				require_once 'CRM/Core/BAO/UFMatch.php';

				// get the CiviCRM contact ID
				$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

				// do not show link if user is already registered
				$show_link = in_array( $contact_id, $contact_ids ) ? false : true;

				/*
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'participants' => $participants,
					'civi_event' => $civi_event,
					'contact_ids' => $contact_ids,
					'contact_id' => $contact_id,
					'backtrace' => $trace,
				), true ) );
				*/

				/**
				 * Override the decision to show the registration link.
				 *
				 * @since 0.4.5
				 *
				 * @param bool $show_link True if the link is to be shown, false otherwise
				 * @param array $civi_event The data array for the CiviEvent
				 * @return bool $show_link True if the link is to be shown, false otherwise
				 */
				$show_link = apply_filters( 'civicrm_event_organiser_show_register_link', $show_link, $civi_event );

				// (super) admins always see links
				if ( is_super_admin() ) {
					$show_link = true;
				}

				// if we're showing the link
				if ( $show_link === true ) {

					// get url for the registration page
					$url = $this->plugin->civicrm_eo->civi->get_registration_link( $civi_event );

					// link text
					$text = __( 'Register for this event', 'civicrm-eo-attendance' );

					// construct link if we get one
					$link = '<a class="civicrm-eo-register-link" href="' . $url . '">' . $text . '</a>';

					// maybe add registration link
					if ( ! empty( $url ) ) {
						$names[] = $link;
					}

				}

			} else {

				// add notice
				$names[] = __( 'Registration is closed', 'civicrm-eo-attendance' );

			}

			/**
			 * Filter participant names array.
			 *
			 * @since 0.2.1
			 *
			 * @param array $names The existing names array
			 * @param int $civi_event_id The numeric ID of the CiviEvent
			 * @return array $names The modified names array
			 */
			$names = apply_filters( 'civicrm_event_organiser_participant_names', $names, $civi_event_id );

			// combine into list
			$markup = implode( '</li>' . "\n" . '<li class="civicrm-eo-participant-name">', $names );

			// top and tail
			$markup = '<li class="civicrm-eo-participant-name">' . $markup . '</li>' . "\n";

			// wrap in unordered list
			$markup = '<ul class="civicrm-eo-participant-names">' . $markup . '</ul>';

			// add to data
			$data['markup'] = $markup;

		}

		// send data to browser
		$this->send_data( $data );

	}



	/**
	 * Send JSON data to the browser.
	 *
	 * @since 0.2.1
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



	/**
	 * Grant the permissions necessary for participant listing functionality.
	 *
	 * @since 0.5.1
	 *
	 * @param str $permission The requested permission
	 * @param bool $granted True if permission granted, false otherwise
	 */
	public function permissions_escalate( $permission, &$granted ) {

		// allow the relevant ones
		if (
			$permission == 'access CiviCRM' OR
			$permission == 'access CiviEvent' OR
			$permission == 'view event info' OR
			$permission == 'view event participants'
		) {
			$granted = 1;
		}

	}

} // class ends



