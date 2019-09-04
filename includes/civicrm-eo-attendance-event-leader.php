<?php

/**
 * CiviCRM Event Organiser Attendance Event Leader Class.
 *
 * A class that encapsulates Event Leader functionality.
 *
 * @since 0.3
 */
class CiviCRM_EO_Attendance_Event_Leader {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.3
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Plugin Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $option_name The option name.
	 */
	public $option_name = 'civicrm_eo_event_leader_role';

	/**
	 * Post Meta name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $meta_name The post meta name.
	 */
	public $meta_name = '_civi_leader_role';



	/**
	 * Initialises this object.
	 *
	 * @since 0.3
	 */
	public function __construct() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.3
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
	 * @since 0.3
	 */
	public function register_hooks() {

		// Add our settings to the settings table.
		add_action( 'civicrm_event_organiser_settings_table_last_row', array( $this, 'settings_table' ) );

		// Save our settings on plugin settings save.
		add_action( 'civicrm_event_organiser_settings_updated', array( $this, 'settings_update' ) );

		// Add our components to the event metabox.
		add_action( 'civicrm_event_organiser_event_meta_box_after', array( $this, 'components_metabox' ) );

		// Save our event components on event components save.
		add_action( 'civicrm_event_organiser_event_components_updated', array( $this, 'components_update' ) );

	}



	//##########################################################################



	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.3
	 */
	public function settings_table() {

		// Get all roles.
		$roles = $this->role_select_get();

		// Bail if there aren't any.
		if ( empty( $roles ) ) return;

		// Include template file.
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-leader/setting-admin.php' );

	}



	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.3
	 */
	public function settings_update() {

		// Set defaults.
		$civicrm_eo_event_leader_role = '0';

		// Get variables.
		extract( $_POST );

		// Sanitise.
		$civicrm_eo_event_leader_role = absint( $civicrm_eo_event_leader_role );

		// Save option.
		$this->plugin->civicrm_eo->db->option_save( $this->option_name, $civicrm_eo_event_leader_role );

	}



	/**
	 * Add our components to the event metabox.
	 *
	 * @since 0.3
	 *
	 * @param object $event The EO event object.
	 */
	public function components_metabox( $event ) {

		// Get all roles.
		$roles = $this->role_select_get( $event );

		// Bail if there aren't any.
		if ( empty( $roles ) ) return;

		// Include template file.
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-leader/setting-metabox.php' );

	}



	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.3
	 *
	 * @param int $event_id The numeric ID of the EO event.
	 */
	public function components_update( $event_id ) {

		// Save event sharing value.
		$this->role_event_update( $event_id );

	}



	//##########################################################################



	/**
	 * Check if the current user was the leader of an event.
	 *
	 * @since 0.3
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @return bool $is_leader True if current user was event leader.
	 */
	public function user_is_leader( $civi_event_id ) {

		// Assume not.
		$is_leader = false;

		// Not if they're not logged in.
		if ( ! is_user_logged_in() ) return $is_leader;

		// Get current user.
		$current_user = wp_get_current_user();

		// Not if there's no CiviCRM.
		if ( ! civi_wp()->initialize() ) return $is_leader;

		// Get user matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get the CiviCRM contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// Not if there's no matching contact.
		if ( is_null( $contact_id ) ) return $is_leader;

		// Get participant role for this user.
		$params = array(
			'version' => 3,
			'contact_id' => $contact_id,
			'event_id' => $civi_event_id,
		);

		// Query via API.
		$result = civicrm_api( 'participant', 'get', $params );

		// Log failures and bail.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {

			// Log error.
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'civi_event_id' => $civi_event_id,
				'backtrace' => $trace,
			), true ) );

			// Return false.
			return $is_leader;

		}

		// Sanity check.
		if (
			! isset( $result['values'] ) OR
			! is_array( $result['values'] ) OR
			count( $result['values'] ) == 0
		) {

			// Return false.
			return $is_leader;

		}

		// Get EO post ID.
		$post_id = $this->plugin->civicrm_eo->db->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// Get the post.
		$post = get_post( $post_id );

		// Get leader role for this post.
		$leader_role = $this->role_default_get( $post );

		// Loop through results.
		foreach( $result['values'] AS $participant_id => $data ) {

			// Get role from participant data.
			$role = isset( $data['participant_role_id'] ) ? absint( $data['participant_role_id'] ) : 0;

			// Is it the same as the event default?
			if ( $role == $leader_role ) {

				// Yes, bail.
				return true;

			}

		}

		// Fall back to false.
		return false;

	}



	//##########################################################################



	/**
	 * Builds a form element for Event Leader Roles.
	 *
	 * @since 0.3
	 *
	 * @param object $post An EO event object.
	 * @return str $html Markup to display in the form.
	 */
	public function role_select_get( $post = null ) {

		// Init html.
		$html = '';

		// Init CiviCRM or bail.
		if ( ! civi_wp()->initialize() ) return $html;

		// First, get all participant_roles.
		$all_roles = $this->plugin->civicrm_eo->civi->get_participant_roles();

		// Did we get any?
		if ( $all_roles['is_error'] == '0' AND count( $all_roles['values'] ) > 0 ) {

			// Get the values array.
			$roles = $all_roles['values'];

			// Init options.
			$options = array();

			// Get existing role ID.
			$existing_id = $this->role_default_get( $post );

			// Loop.
			foreach( $roles AS $key => $role ) {

				// Get role.
				$role_id = absint( $role['value'] );

				// Init selected.
				$selected = '';

				// Override selected if same as in post.
				if ( $existing_id === $role_id ) {
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
	 * Get the existing Event Leader role for a post, but fall back to the default
	 * as set on the admin screen. Fall back to false otherwise.
	 *
	 * @since 0.3
	 *
	 * @param object $post An EO event object.
	 * @return mixed $existing_id The numeric ID of the role, false if none exists.
	 */
	public function role_default_get( $post = null ) {

		// Init with impossible ID.
		$existing_id = false;

		// Do we have a default set?
		$default = $this->plugin->civicrm_eo->db->option_get( $this->option_name );

		// Override with default value if we get one.
		if ( $default !== '' AND is_numeric( $default ) ) {
			$existing_id = absint( $default );
		}

		// If we have a post.
		if ( isset( $post ) AND is_object( $post ) ) {

			// Get stored value.
			$stored_id = $this->role_event_get( $post->ID );

			// Override with stored value if we get one.
			if ( $stored_id !== '' AND is_numeric( $stored_id ) AND $stored_id > 0 ) {
				$existing_id = absint( $stored_id );
			}

		}

		// --<
		return $existing_id;

	}



	//##########################################################################



	/**
	 * Update event leader role value.
	 *
	 * @since 0.3
	 *
	 * @param int $event_id The numeric ID of the event.
	 */
	public function role_event_update( $event_id ) {

		// Kick out if not set.
		if ( ! isset( $_POST[$this->option_name] ) ) return;

		// Retrieve meta value.
		$value = absint( $_POST[$this->option_name] );

		// Update event meta.
		$this->role_event_set( $event_id, $value );

	}



	/**
	 * Update event leader role value.
	 *
	 * @since 0.3
	 *
	 * @param int $event_id The numeric ID of the event.
	 * @param int $value The event participant role value for the CiviEvent.
	 */
	public function role_event_set( $event_id, $value = null ) {

		// If not set.
		if ( is_null( $value ) ) {

			// Do we have a default set?
			$default = $this->plugin->db->option_get( $this->option_name );

			// Override with default value if we get one.
			if ( $default !== '' AND is_numeric( $default ) ) {
				$value = absint( $default );
			}

		}

		// Update event meta.
		update_post_meta( $event_id,  $this->meta_name, $value );

	}



	/**
	 * Get event leader role value.
	 *
	 * @since 0.3
	 *
	 * @param int $post_id The numeric ID of the WP post.
	 * @return int $civi_role The event participant role value for the CiviEvent.
	 */
	public function role_event_get( $post_id ) {

		// Get the meta value.
		$civi_role = get_post_meta( $post_id, $this->meta_name, true );

		// If it's not yet set it will be an empty string, so cast as number.
		if ( $civi_role === '' ) { $civi_role = 0; }

		// --<
		return absint( $civi_role );

	}



	/**
	 * Delete event leader role value for a CiviEvent.
	 *
	 * @since 0.3
	 *
	 * @param int $post_id The numeric ID of the WP post.
	 */
	public function role_event_clear( $post_id ) {

		// Delete the meta value.
		delete_post_meta( $post_id, $this->meta_name );

	}



} // Class ends.



