<?php
/**
 * Event Leader Class.
 *
 * Handles Event Leader functionality.
 *
 * @since 0.3
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Event Leader Class.
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
	 * @var str $meta_name The Post meta name.
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
		add_action( 'ceo/admin/settings/metabox/general/table/last_row', [ $this, 'settings_table' ] );

		// Save our settings on plugin settings save.
		add_action( 'ceo/admin/settings/updated', [ $this, 'settings_update' ] );

		// Add our components to the Event metabox.
		add_action( 'ceo/event/metabox/event/sync/after', [ $this, 'components_metabox' ] );

		// Save our Event components on Event components save.
		add_action( 'ceo/admin/settings/metabox/general/table/last_row', [ $this, 'components_update' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.3
	 */
	public function settings_table() {

		// Get all roles.
		$roles = $this->role_select_get();

		// Bail if there aren't any.
		if ( empty( $roles ) ) {
			return;
		}

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-leader/setting-admin.php';

	}

	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.3
	 */
	public function settings_update() {

		// Get Leader Role.
		$key = 'civicrm_eo_event_leader_role';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$leader_role = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '0';

		// Always cast as integer.
		$leader_role = (int) $leader_role;

		// Save option.
		$this->plugin->civicrm_eo->db->option_save( $this->option_name, $leader_role );

	}

	/**
	 * Add our components to the Event metabox.
	 *
	 * @since 0.3
	 *
	 * @param object $event The EO Event object.
	 */
	public function components_metabox( $event ) {

		// Get all roles.
		$roles = $this->role_select_get( $event );

		// Bail if there aren't any.
		if ( empty( $roles ) ) {
			return;
		}

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-leader/setting-metabox.php';

	}

	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.3
	 *
	 * @param int $event_id The numeric ID of the EO Event.
	 */
	public function components_update( $event_id ) {

		// Save Event sharing value.
		$this->role_event_update( $event_id );

	}

	// -------------------------------------------------------------------------

	/**
	 * Check if the current User was the Leader of an Event.
	 *
	 * @since 0.3
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @return bool $is_leader True if current User was Event Leader.
	 */
	public function user_is_leader( $civi_event_id ) {

		// Assume not.
		$is_leader = false;

		// Not if they're not logged in.
		if ( ! is_user_logged_in() ) {
			return $is_leader;
		}

		// Get current User.
		$current_user = wp_get_current_user();

		// Not if there's no CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $is_leader;
		}

		// Get User matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get the CiviCRM Contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// Not if there's no matching Contact.
		if ( is_null( $contact_id ) ) {
			return $is_leader;
		}

		// Get Participant role for this User.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'event_id' => $civi_event_id,
		];

		// Query via API.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Log failures and bail.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {

			// Log error.
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'civi_event_id' => $civi_event_id,
				'backtrace' => $trace,
			], true ) );

			// Return false.
			return $is_leader;

		}

		// Sanity check.
		if (
			! isset( $result['values'] ) ||
			! is_array( $result['values'] ) ||
			count( $result['values'] ) == 0
		) {

			// Return false.
			return $is_leader;

		}

		// Get EO Event ID.
		$post_id = $this->plugin->civicrm_eo->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// Get the Event.
		$post = get_post( $post_id );

		// Get Leader Role for this Event.
		$leader_role = $this->role_default_get( $post );

		// Loop through results.
		foreach ( $result['values'] as $participant_id => $data ) {

			// Get role from Participant data.
			$role = isset( $data['participant_role_id'] ) ? absint( $data['participant_role_id'] ) : 0;

			// Is it the same as the Event default?
			if ( $role == $leader_role ) {

				// Yes, bail.
				return true;

			}

		}

		// Fall back to false.
		return false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Builds a form element for Event Leader Roles.
	 *
	 * @since 0.3
	 *
	 * @param object $post An EO Event object.
	 * @return str $html Markup to display in the form.
	 */
	public function role_select_get( $post = null ) {

		// Init html.
		$html = '';

		// Init CiviCRM or bail.
		if ( ! civi_wp()->initialize() ) {
			return $html;
		}

		// First, get all Participant Roles.
		$all_roles = $this->plugin->civicrm_eo->civi->registration->get_participant_roles();

		// Did we get any?
		if ( $all_roles['is_error'] == '0' && count( $all_roles['values'] ) > 0 ) {

			// Get the values array.
			$roles = $all_roles['values'];

			// Init options.
			$options = [];

			// Get existing role ID.
			$existing_id = $this->role_default_get( $post );

			// Loop.
			foreach ( $roles as $key => $role ) {

				// Get role.
				$role_id = absint( $role['value'] );

				// Init selected.
				$selected = '';

				// Override selected if same as in Post.
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
	 * Get the existing Event Leader role for an Event, but fall back to the default
	 * as set on the admin screen. Fall back to false otherwise.
	 *
	 * @since 0.3
	 *
	 * @param object $post An EO Event object.
	 * @return mixed $existing_id The numeric ID of the role, false if none exists.
	 */
	public function role_default_get( $post = null ) {

		// Init with impossible ID.
		$existing_id = false;

		// Do we have a default set?
		$default = $this->plugin->civicrm_eo->db->option_get( $this->option_name );

		// Override with default value if we get one.
		if ( $default !== '' && is_numeric( $default ) ) {
			$existing_id = absint( $default );
		}

		// If we have a Post.
		if ( isset( $post ) && is_object( $post ) ) {

			// Get stored value.
			$stored_id = $this->role_event_get( $post->ID );

			// Override with stored value if we get one.
			if ( $stored_id !== '' && is_numeric( $stored_id ) && $stored_id > 0 ) {
				$existing_id = absint( $stored_id );
			}

		}

		// --<
		return $existing_id;

	}

	// -------------------------------------------------------------------------

	/**
	 * Update Event Leader Role value.
	 *
	 * @since 0.3
	 *
	 * @param int $event_id The numeric ID of the Event.
	 */
	public function role_event_update( $event_id ) {

		// Kick out if not set.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $this->option_name ] ) ) {
			return;
		}

		// Retrieve meta value.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$value = absint( wp_unslash( $_POST[ $this->option_name ] ) );

		// Update Event meta.
		$this->role_event_set( $event_id, $value );

	}

	/**
	 * Update Event Leader Role value.
	 *
	 * @since 0.3
	 *
	 * @param int $event_id The numeric ID of the Event.
	 * @param int $value The Event Participant Role value for the CiviEvent.
	 */
	public function role_event_set( $event_id, $value = null ) {

		// If not set.
		if ( is_null( $value ) ) {

			// Do we have a default set?
			$default = $this->plugin->db->option_get( $this->option_name );

			// Override with default value if we get one.
			if ( $default !== '' && is_numeric( $default ) ) {
				$value = absint( $default );
			}

		}

		// Update Event meta.
		update_post_meta( $event_id, $this->meta_name, $value );

	}

	/**
	 * Get Event Leader Role value.
	 *
	 * @since 0.3
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 * @return int $civi_role The Event Participant Role value for the CiviEvent.
	 */
	public function role_event_get( $post_id ) {

		// Get the meta value.
		$civi_role = get_post_meta( $post_id, $this->meta_name, true );

		// If it's not yet set it will be an empty string, so cast as number.
		if ( $civi_role === '' ) {
			$civi_role = 0;
		}

		// --<
		return absint( $civi_role );

	}

	/**
	 * Delete Event Leader Role value for a CiviEvent.
	 *
	 * @since 0.3
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 */
	public function role_event_clear( $post_id ) {

		// Delete the meta value.
		delete_post_meta( $post_id, $this->meta_name );

	}

}
