<?php

/**
 * CiviCRM Event Organiser Attendance Event Sharing Class.
 *
 * A class that encapsulates Event Sharing functionality.
 *
 * @since 0.2.2
 */
class CiviCRM_EO_Attendance_Event_Sharing {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.2.2
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
	public $option_name = 'civicrm_eo_event_default_sharing';

	/**
	 * Post Meta name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $meta_name The post meta name
	 */
	public $meta_name = '_civi_sharing';



	/**
	 * Initialises this object.
	 *
	 * @since 0.2.2
	 */
	public function __construct() {

		// register hooks
		$this->register_hooks();

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.2.2
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
	 * @since 0.2.2
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

	}



	//##########################################################################



	/**
	 * Update a CiviEvent when an EO event is synced to CiviCRM.
	 *
	 * @since 0.2.2
	 *
	 * @param array $civi_event The array of data for the CiviEvent
	 * @param object $post The WP post object
	 * @return array $civi_event The modified array of data for the CiviEvent
	 */
	public function prepare_civi_event( $civi_event, $post ) {

		// get existing event sharing status
		$existing_id = $this->sharing_default_get( $post );

		// override param with our value
		$civi_event['is_share'] = $existing_id;

		// --<
		return $civi_event;

	}



	/**
	 * Update an EO event when a CiviEvent is synced to WordPress.
	 *
	 * @since 0.2.2
	 *
	 * @param int $event_id The numeric ID of the EO event
	 * @param array $civi_event An array of data for the CiviEvent
	 */
	public function process_eo_event( $event_id, $civi_event ) {

		// if the event has a participant listing profile specified
		if (
			isset( $civi_event['is_share'] ) AND
			! empty( $civi_event['is_share'] )
		) {

			// save specified event sharing
			$this->sharing_set( $event_id, absint( $civi_event['is_share'] ) );

		} else {

			// set default event sharing
			$this->sharing_set( $event_id );

		}

	}



	//##########################################################################



	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.2.2
	 */
	public function settings_table() {

		// get current default event sharing status
		$sharing = $this->sharing_default_get();

		// set checkbox checked
		$sharing_checked = '';
		if ( $sharing !== 0 ) {
			$sharing_checked = ' checked="checked"';
		}

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-sharing/setting-admin.php' );

	}



	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.2.2
	 */
	public function settings_update() {

		// set value based on whether the checkbox is ticked
		$value = ( isset( $_POST[$this->option_name] ) ) ? 1 : 0;

		// save option
		$this->plugin->civicrm_eo->db->option_save( $this->option_name, $value );

	}



	/**
	 * Add our components to the event metabox.
	 *
	 * @since 0.2.2
	 *
	 * @param object $event The EO event object
	 */
	public function components_metabox( $event ) {

		// get current default event sharing status
		$sharing = $this->sharing_default_get( $event );

		// set checkbox checked
		$sharing_checked = '';
		if ( $sharing !== 0 ) {
			$sharing_checked = ' checked="checked"';
		}

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-sharing/setting-metabox.php' );

	}



	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.2.2
	 *
	 * @param int $event_id The numeric ID of the EO event
	 */
	public function components_update( $event_id ) {

		// save event sharing value
		$this->sharing_update( $event_id );

	}



	//##########################################################################



	/**
	 * Get the default CiviEvent sharing value for a post.
	 *
	 * Falls back to the default as set on the plugin Settings screen.
	 * Falls back to zero (disabled) otherwise.
	 *
	 * @since 0.2.2
	 *
	 * @param object $post The WP event object
	 * @return int $existing_id The numeric ID of the CiviEvent sharing setting (or 0 if none exists)
	 */
	public function sharing_default_get( $post = null ) {

		// init as disabled
		$existing_id = 0;

		// do we have a default set?
		$default = $this->plugin->civicrm_eo->db->option_get( $this->option_name );

		// override with default if we get one
		if ( $default !== '' AND is_numeric( $default ) ) {
			$existing_id = absint( $default );
		}

		// if we have a post
		if ( isset( $post ) AND is_object( $post ) ) {

			// get stored value
			$stored_id = $this->sharing_get( $post->ID );

			// override with stored value if we have one
			if ( $stored_id !== '' AND is_numeric( $stored_id ) ) {
				$existing_id = absint( $stored_id );
			}

		}

		// --<
		return $existing_id;

	}



	//##########################################################################



	/**
	 * Update event sharing value.
	 *
	 * @since 0.2.2
	 *
	 * @param int $event_id The numeric ID of the event
	 * @param int $value Whether sharing is enabled or not
	 */
	public function sharing_update( $event_id, $value = null ) {

		// if no value specified
		if ( is_null( $value ) ) {

			// set value based on whether the checkbox is ticked
			$value = ( isset( $_POST[$this->option_name] ) ) ? 1 : 0;

		}

		// go ahead and set the value
		$this->sharing_set( $event_id, $value );

	}



	/**
	 * Get event sharing value.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WP post
	 * @return bool $value The event sharing value for the CiviEvent
	 */
	public function sharing_get( $post_id ) {

		// get the meta value
		$value = get_post_meta( $post_id, $this->meta_name, true );

		// if it's not yet set it will be an empty string, so cast as boolean
		if ( $value === '' ) { $value = 0; }

		// --<
		return absint( $value );

	}



	/**
	 * Set event sharing value.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WP post
	 * @param bool $value Whether sharing is enabled or not
	 */
	public function sharing_set( $post_id, $value = 0 ) {

		// update event meta
		update_post_meta( $post_id,  $this->meta_name, $value );

	}



	/**
	 * Delete event sharing value for a CiviEvent.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WP post
	 */
	public function sharing_clear( $post_id ) {

		// delete the meta value
		delete_post_meta( $post_id, $this->meta_name );

	}



} // class ends



