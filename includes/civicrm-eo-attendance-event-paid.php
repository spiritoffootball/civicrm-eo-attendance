<?php
/**
 * Event Paid Class.
 *
 * Handles Paid Event functionality.
 *
 * @since 0.3.1
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Event Paid Class.
 *
 * A class that encapsulates Paid Event functionality.
 *
 * @since 0.3.1
 */
class CiviCRM_EO_Attendance_Event_Paid {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.3.1
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
	public $option_name = 'civicrm_eo_event_default_paid';

	/**
	 * Post Meta name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $meta_name The Post meta name.
	 */
	public $meta_name = '_civi_paid';

	/**
	 * Initialises this object.
	 *
	 * @since 0.3.1
	 */
	public function __construct() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Set references to other objects.
	 *
	 * @since 0.3.1
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
	 * @since 0.3.1
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

		// Filter Events for Rendez Vous to include only paid Events.
		add_action( 'civicrm_event_organiser_rendez_vous_event_args', [ $this, 'paid_filter' ], 10, 1 );

		// Filter access to Custom Fields on Events.
		add_filter( 'civicrm_eo_cde_access', [ $this, 'paid_permissions' ], 10, 2 );
		add_filter( 'civicrm_eo_cdp_access', [ $this, 'paid_permissions' ], 10, 2 );
		add_filter( 'civicrm_eo_pl_access', [ $this, 'paid_permissions' ], 10, 2 );

		// Listen to "access denied" on Participant Listings.
		add_filter( 'civicrm_eo_pl_access_denied', [ $this, 'paid_permission_denied' ], 10, 1 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.3.1
	 */
	public function settings_table() {

		// Get current default Event paid status.
		$paid = $this->paid_default_get();

		// Set checkbox checked.
		$paid_checked = false;
		if ( 0 !== $paid ) {
			$paid_checked = true;
		}

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-paid/setting-admin.php';

	}

	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.3.1
	 */
	public function settings_update() {

		// Set value based on whether the checkbox is ticked.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$value = ( isset( $_POST[ $this->option_name ] ) ) ? 1 : 0;

		// Save option.
		$this->plugin->civicrm_eo->db->option_save( $this->option_name, $value );

	}

	/**
	 * Add our components to the Event metabox.
	 *
	 * @since 0.3.1
	 *
	 * @param object $event The EO Event object.
	 */
	public function components_metabox( $event ) {

		// Get current default Event paid status.
		$paid = $this->paid_default_get( $event );

		// Set checkbox checked.
		$paid_checked = false;
		if ( 0 !== $paid ) {
			$paid_checked = true;
		}

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-paid/setting-metabox.php';

	}

	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.3.1
	 *
	 * @param int $event_id The numeric ID of the EO Event.
	 */
	public function components_update( $event_id ) {

		// Save Event paid value.
		$this->paid_update( $event_id );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the default Event Paid value for an Event.
	 *
	 * Falls back to the default as set on the plugin Settings screen.
	 * Falls back to zero (disabled) otherwise.
	 *
	 * @since 0.3.1
	 *
	 * @param object $post The WordPress Event object.
	 * @return int $is_paid A paid Event returns 1, otherwise 0 (also 0 if none exists).
	 */
	public function paid_default_get( $post = null ) {

		// Init as disabled.
		$existing_id = 0;

		// Do we have a default set?
		$default = $this->plugin->civicrm_eo->db->option_get( $this->option_name );

		// Override with default if we get one.
		if ( '' !== $default && is_numeric( $default ) ) {
			$existing_id = absint( $default );
		}

		// If we have a Post.
		if ( isset( $post ) && is_object( $post ) ) {

			// Get stored value.
			$stored_id = $this->paid_get( $post->ID, $existing_id );

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
	 * Update Event paid value.
	 *
	 * @since 0.3.1
	 *
	 * @param int $event_id The numeric ID of the Event.
	 * @param int $value Whether paid is enabled or not.
	 */
	public function paid_update( $event_id, $value = null ) {

		// If no value specified.
		if ( is_null( $value ) ) {

			// Set value based on whether the checkbox is ticked.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = ( isset( $_POST[ $this->option_name ] ) ) ? 1 : 0;

		}

		// Go ahead and set the value.
		$this->paid_set( $event_id, $value );

	}

	/**
	 * Get Event paid value.
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 * @param int $default_value The default value at the plugin level.
	 * @return int $value The Event paid value for the CiviEvent.
	 */
	public function paid_get( $post_id, $default_value = 0 ) {

		// Get the meta value.
		$value = get_post_meta( $post_id, $this->meta_name, true );

		// Empty string if not yet set, so override with default.
		if ( '' === $value ) {
			$value = $default_value;
		}

		// --<
		return absint( $value );

	}

	/**
	 * Set Event paid value.
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 * @param int $value Whether paid is enabled or not (1 or 0).
	 */
	public function paid_set( $post_id, $value = 0 ) {

		// Update Event meta.
		update_post_meta( $post_id, $this->meta_name, $value );

	}

	/**
	 * Delete Event paid value for an Event.
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 */
	public function paid_clear( $post_id ) {

		// Delete the meta value.
		delete_post_meta( $post_id, $this->meta_name );

	}

	/**
	 * Filter Event query to include only paid Events.
	 *
	 * @since 0.5
	 *
	 * @param array $query_args The array of query args.
	 * @return array $query_args The modified array of query args.
	 */
	public function paid_filter( $query_args ) {

		// Find only Events with our meta value.
		$meta_query = [
			[
				'key'     => '_civi_paid',
				'value'   => 1,
				'compare' => '=',
			],
		];

		// Amend query args.
		if ( empty( $query_args['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query_args['meta_query'] = [ $meta_query ];
		} else {
			$query_args['meta_query'][] = $meta_query;
		}

		// --<
		return $query_args;

	}

	/**
	 * Filter access to Participant Listings.
	 *
	 * @since 0.5.1
	 *
	 * @param bool $granted False by default - assumes access not granted.
	 * @param int  $post_id The numeric ID of the WordPress Post.
	 * @return bool $granted True if access granted, false otherwise.
	 */
	public function paid_permissions( $granted, $post_id = null ) {

		// Need the Post ID.
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// Get Post.
		$post = get_post( $post_id );

		// Get Event paid status.
		$paid = $this->paid_default_get( $post );

		// Disallow by default, but allow if this is a paid Event.
		$granted = ( 0 !== $paid ) ? true : false;

		// --<
		return $granted;

	}

	/**
	 * When access to Participant Listings is denied, check if Event is paid and
	 * if so, remove the standard Registration Links.
	 *
	 * @since 0.5.1
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
	 */
	public function paid_permission_denied( $post_id = null ) {

		// Need the Post ID.
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// Get Post.
		$post = get_post( $post_id );

		// Get Event paid status.
		$paid = $this->paid_default_get( $post );

		// If this is a paid Event.
		if ( 0 !== $paid ) {

			// Remove core plugin's list renderer.
			remove_action( 'eventorganiser_additional_event_meta', 'civicrm_event_organiser_register_links' );

		}

	}

}
