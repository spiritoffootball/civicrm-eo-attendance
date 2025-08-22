<?php
/**
 * Event Sharing Class.
 *
 * Handles Event Sharing functionality.
 *
 * @since 0.2.2
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Event Sharing Class.
 *
 * A class that encapsulates Event Sharing functionality.
 *
 * @since 0.2.2
 */
class CiviCRM_EO_Attendance_Event_Sharing {

	/**
	 * Plugin object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var CiviCRM_Event_Organiser_Attendance
	 */
	public $plugin;

	/**
	 * Plugin Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var string
	 */
	public $option_name = 'civicrm_eo_event_default_sharing';

	/**
	 * Post Meta name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var string
	 */
	public $meta_name = '_civi_sharing';

	/**
	 * Initialises this object.
	 *
	 * @since 0.2.2
	 */
	public function __construct() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Set references to other objects.
	 *
	 * @since 0.2.2
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
	 * @since 0.2.2
	 */
	public function register_hooks() {

		// Apply changes to CiviEvent when an EO Event is synced to CiviCRM.
		add_filter( 'ceo/civicrm/event/prepared', [ $this, 'prepare_civi_event' ], 10, 2 );

		// Post-process an EO Event when a CiviEvent is synced to WordPress.
		add_action( 'ceo/eo/event/updated', [ $this, 'process_eo_event' ], 10, 2 );

		// Add our settings to the settings table.
		add_action( 'ceo/admin/settings/metabox/general/table/last_row', [ $this, 'settings_table' ] );

		// Save our settings on plugin settings save.
		add_action( 'ceo/admin/settings/updated', [ $this, 'settings_update' ] );

		// Add our components to the Event metabox.
		add_action( 'ceo/event/metabox/event/sync/after', [ $this, 'components_metabox' ] );

		// Save our Event components on Event components save.
		add_action( 'ceo/admin/settings/metabox/general/table/last_row', [ $this, 'components_update' ] );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Update a CiviEvent when an EO Event is synced to CiviCRM.
	 *
	 * @since 0.2.2
	 *
	 * @param array  $civi_event The array of data for the CiviEvent.
	 * @param object $post The WordPress Post object.
	 * @return array $civi_event The modified array of data for the CiviEvent.
	 */
	public function prepare_civi_event( $civi_event, $post ) {

		// Get existing Event sharing status.
		$existing_id = $this->sharing_default_get( $post );

		// Override param with our value.
		$civi_event['is_share'] = $existing_id;

		// --<
		return $civi_event;

	}

	/**
	 * Update an EO Event when a CiviEvent is synced to WordPress.
	 *
	 * @since 0.2.2
	 *
	 * @param int   $event_id The numeric ID of the EO Event.
	 * @param array $civi_event An array of data for the CiviEvent.
	 */
	public function process_eo_event( $event_id, $civi_event ) {

		// If the Event has a Participant Listing Profile specified.
		if ( ! empty( $civi_event['is_share'] ) ) {

			// Save specified Event sharing.
			$this->sharing_set( $event_id, absint( $civi_event['is_share'] ) );

		} else {

			// Set default Event sharing.
			$this->sharing_set( $event_id );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.2.2
	 */
	public function settings_table() {

		// Get current default Event sharing status.
		$sharing = $this->sharing_default_get();

		// Set checkbox checked.
		$sharing_checked = false;
		if ( 0 !== $sharing ) {
			$sharing_checked = true;
		}

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-sharing/setting-admin.php';

	}

	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.2.2
	 */
	public function settings_update() {

		// Set value based on whether the checkbox is ticked.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$value = ( isset( $_POST[ $this->option_name ] ) ) ? 1 : 0;

		// Save option.
		$this->plugin->civicrm_eo->admin->option_save( $this->option_name, $value );

	}

	/**
	 * Add our components to the Event metabox.
	 *
	 * @since 0.2.2
	 *
	 * @param object $event The EO Event object.
	 */
	public function components_metabox( $event ) {

		// Get current default Event sharing status.
		$sharing = $this->sharing_default_get( $event );

		// Set checkbox checked.
		$sharing_checked = false;
		if ( 0 !== $sharing ) {
			$sharing_checked = true;
		}

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-sharing/setting-metabox.php';

	}

	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.2.2
	 *
	 * @param int $event_id The numeric ID of the EO Event.
	 */
	public function components_update( $event_id ) {

		// Save Event sharing value.
		$this->sharing_update( $event_id );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the default CiviEvent sharing value for an Event.
	 *
	 * Falls back to the default as set on the plugin Settings screen.
	 * Falls back to zero (disabled) otherwise.
	 *
	 * @since 0.2.2
	 *
	 * @param object $post The WordPress Event object.
	 * @return int $existing_id The numeric ID of the CiviEvent sharing setting (or 0 if none exists).
	 */
	public function sharing_default_get( $post = null ) {

		// Init as disabled.
		$existing_id = 0;

		// Do we have a default set?
		$default = $this->plugin->civicrm_eo->admin->option_get( $this->option_name );

		// Override with default if we get one.
		if ( '' !== $default && is_numeric( $default ) ) {
			$existing_id = absint( $default );
		}

		// If we have a Post.
		if ( isset( $post ) && is_object( $post ) ) {

			// Get stored value.
			$stored_id = $this->sharing_get( $post->ID );

			// Override with stored value if we have one.
			if ( '' !== $stored_id && is_numeric( $stored_id ) ) {
				$existing_id = absint( $stored_id );
			}

		}

		// --<
		return $existing_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Update Event sharing value.
	 *
	 * @since 0.2.2
	 *
	 * @param int $event_id The numeric ID of the Event.
	 * @param int $value Whether sharing is enabled or not.
	 */
	public function sharing_update( $event_id, $value = null ) {

		// If no value specified.
		if ( is_null( $value ) ) {

			// Set value based on whether the checkbox is ticked.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = ( isset( $_POST[ $this->option_name ] ) ) ? 1 : 0;

		}

		// Go ahead and set the value.
		$this->sharing_set( $event_id, $value );

	}

	/**
	 * Get Event sharing value.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 * @return bool $value The Event sharing value for the CiviEvent.
	 */
	public function sharing_get( $post_id ) {

		// Get the meta value.
		$value = get_post_meta( $post_id, $this->meta_name, true );

		// If it's not yet set it will be an empty string, so cast as boolean.
		if ( '' === $value ) {
			$value = 0;
		}

		// --<
		return absint( $value );

	}

	/**
	 * Set Event sharing value.
	 *
	 * @since 0.2.2
	 *
	 * @param int  $post_id The numeric ID of the WordPress Post.
	 * @param bool $value Whether sharing is enabled or not.
	 */
	public function sharing_set( $post_id, $value = 0 ) {

		// Update Event meta.
		update_post_meta( $post_id, $this->meta_name, $value );

	}

	/**
	 * Delete Event sharing value for a CiviEvent.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 */
	public function sharing_clear( $post_id ) {

		// Delete the meta value.
		delete_post_meta( $post_id, $this->meta_name );

	}

}
