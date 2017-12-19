<?php

/**
 * CiviCRM Event Organiser Attendance Event Paid Class.
 *
 * A class that encapsulates SOF Paid Event functionality.
 *
 * @since 0.3.1
 */
class CiviCRM_EO_Attendance_Event_Paid {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.3.1
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
	public $option_name = 'civicrm_eo_event_default_paid';

	/**
	 * Post Meta name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $meta_name The post meta name
	 */
	public $meta_name = '_civi_paid';



	/**
	 * Initialises this object.
	 *
	 * @since 0.3.1
	 */
	public function __construct() {

		// register hooks
		$this->register_hooks();

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.3.1
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
	 * @since 0.3.1
	 */
	public function register_hooks() {

		// add our settings to the settings table
		add_action( 'civicrm_event_organiser_settings_table_last_row', array( $this, 'settings_table' ) );

		// save our settings on plugin settings save
		add_action( 'civicrm_event_organiser_settings_updated', array( $this, 'settings_update' ) );

		// add our components to the event metabox
		add_action( 'civicrm_event_organiser_event_meta_box_after', array( $this, 'components_metabox' ) );

		// save our event components on event components save
		add_action( 'civicrm_event_organiser_event_components_updated', array( $this, 'components_update' ) );

		// filter events for Rendez Vous to include only paid events
		add_action( 'civicrm_event_organiser_rendez_vous_event_args', array( $this, 'paid_filter' ), 10, 1 );

		// filter access to custom elements on events
		add_filter( 'civicrm_eo_cde_access', array( $this, 'paid_permissions' ), 10, 2 );
		add_filter( 'civicrm_eo_cdp_access', array( $this, 'paid_permissions' ), 10, 2 );
		add_filter( 'civicrm_eo_pl_access', array( $this, 'paid_permissions' ), 10, 2 );

		// listen to "access denied" on Participant Listings
		add_filter( 'civicrm_eo_pl_access_denied', array( $this, 'paid_permission_denied' ), 10, 1 );

	}



	//##########################################################################



	/**
	 * Add our settings to the settings table.
	 *
	 * @since 0.3.1
	 */
	public function settings_table() {

		// get current default event paid status
		$paid = $this->paid_default_get();

		// set checkbox checked
		$paid_checked = '';
		if ( $paid !== 0 ) {
			$paid_checked = ' checked="checked"';
		}

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-paid/setting-admin.php' );

	}



	/**
	 * Update our settings when the settings are updated.
	 *
	 * @since 0.3.1
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
	 * @since 0.3.1
	 *
	 * @param object $event The EO event object
	 */
	public function components_metabox( $event ) {

		// get current default event paid status
		$paid = $this->paid_default_get( $event );

		// set checkbox checked
		$paid_checked = '';
		if ( $paid !== 0 ) {
			$paid_checked = ' checked="checked"';
		}

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/event-paid/setting-metabox.php' );

	}



	/**
	 * Update our components when the components are updated.
	 *
	 * @since 0.3.1
	 *
	 * @param int $event_id The numeric ID of the EO event
	 */
	public function components_update( $event_id ) {

		// save event paid value
		$this->paid_update( $event_id );

	}



	//##########################################################################



	/**
	 * Get the default Event Paid value for a post.
	 *
	 * Falls back to the default as set on the plugin Settings screen.
	 * Falls back to zero (disabled) otherwise.
	 *
	 * @since 0.3.1
	 *
	 * @param object $post The WP event object
	 * @return int $is_paid A paid event returns 1, otherwise 0 (also 0 if none exists)
	 */
	public function paid_default_get( $post = null ) {

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
			$stored_id = $this->paid_get( $post->ID, $existing_id );

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
	 * Update event paid value.
	 *
	 * @since 0.3.1
	 *
	 * @param int $event_id The numeric ID of the event
	 * @param int $value Whether paid is enabled or not
	 */
	public function paid_update( $event_id, $value = null ) {

		// if no value specified
		if ( is_null( $value ) ) {

			// set value based on whether the checkbox is ticked
			$value = ( isset( $_POST[$this->option_name] ) ) ? 1 : 0;

		}

		// go ahead and set the value
		$this->paid_set( $event_id, $value );

	}



	/**
	 * Get event paid value.
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id The numeric ID of the WP post
	 * @param int $default_value The default value at the plugin level
	 * @return int $value The event paid value for the CiviEvent
	 */
	public function paid_get( $post_id, $default_value = 0 ) {

		// get the meta value
		$value = get_post_meta( $post_id, $this->meta_name, true );

		// empty string if not yet set, so override with default
		if ( $value === '' ) { $value = $default_value; }

		// --<
		return absint( $value );

	}



	/**
	 * Set event paid value.
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id The numeric ID of the WP post
	 * @param int $value Whether paid is enabled or not (1 or 0)
	 */
	public function paid_set( $post_id, $value = 0 ) {

		// update event meta
		update_post_meta( $post_id,  $this->meta_name, $value );

	}



	/**
	 * Delete event paid value for an event.
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id The numeric ID of the WP post
	 */
	public function paid_clear( $post_id ) {

		// delete the meta value
		delete_post_meta( $post_id, $this->meta_name );

	}



	/**
	 * Filter event query to include only paid events.
	 *
	 * @since 0.5
	 *
	 * @param array $query_args The array of query args
	 */
	public function paid_filter( $query_args ) {

		// find only events with our meta value
		$meta_query = array(
			array(
				'key'     => '_civi_paid',
				'value'   => 1,
				'compare' => '=',
			),
		);

		// amend query args
		if ( empty( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array( $meta_query );
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
	 * @param bool $granted False by default - assumes access not granted
	 * @param int $post_id The numeric ID of the WP post
	 * @return bool $granted True if access granted, false otherwise
	 */
	public function paid_permissions( $granted, $post_id = null ) {

		// need the post ID
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// get post
		$post = get_post( $post_id );

		// get event paid status
		$paid = $this->paid_default_get( $post );

		// disallow by default, but allow if this is a paid event
		$granted = ( $paid != 0 ) ? true : false;

		// --<
		return $granted;

	}



	/**
	 * When access to Participant Listings is denied, check if event is paid and
	 * if so, remove the standard Registration Links.
	 *
	 * @since 0.5.1
	 *
	 * @param int $post_id The numeric ID of the WP post
	 */
	public function paid_permission_denied( $post_id = null ) {

		// need the post ID
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// get post
		$post = get_post( $post_id );

		// get event paid status
		$paid = $this->paid_default_get( $post );

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'post_id' => $post_id,
			'paid' => $paid,
			//'backtrace' => $trace,
		), true ) );
		*/

		// if this is a paid event
		if ( $paid != 0 ) {

			// remove core plugin's list renderer
			remove_action( 'eventorganiser_additional_event_meta', 'civicrm_event_organiser_register_links' );

		}

	}



} // class ends



