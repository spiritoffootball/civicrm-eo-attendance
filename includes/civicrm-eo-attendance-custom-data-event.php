<?php
/**
 * Event Custom Data Class.
 *
 * Handles Event Custom Data functionality.
 *
 * @since 0.2.2
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Event Custom Data Class.
 *
 * A class that encapsulates Event Custom Data functionality.
 *
 * @since 0.2.2
 */
class CiviCRM_EO_Attendance_Custom_Data_Event {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Group ID Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $group_name The group ID option name.
	 */
	public $group_name = 'civicrm_eo_event_custom_group_id';

	/**
	 * Field IDs Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var str $group_name The field IDs option name.
	 */
	public $field_ids_name = 'civicrm_eo_event_custom_field_ids';

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
	 * Do stuff on plugin activation.
	 *
	 * @since 0.3
	 */
	public function activate() {

		// Create data entities.
		$this->entities_create();

	}

	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.2.2
	 */
	public function register_hooks() {

		// Add AJAX handlers.
		add_action( 'wp_ajax_event_custom_data_form_get', [ $this, 'form_get' ] );
		add_action( 'wp_ajax_event_custom_data_form_process', [ $this, 'form_process' ] );

		// Show list in EO event template.
		add_action( 'eventorganiser_additional_event_meta', [ $this, 'list_render' ], 11 );

		// Register widget.
		add_action( 'widgets_init', [ $this, 'register_widgets' ] );

	}

	/**
	 * Register widgets for this component.
	 *
	 * @since 0.4.6
	 */
	public function register_widgets() {

		// Include widgets.
		require_once CIVICRM_EO_ATTENDANCE_PATH . 'assets/widgets/custom-data-event-widget.php';

		// Register widgets.
		register_widget( 'CiviCRM_EO_Attendance_CDE_Widget' );

	}

	//##########################################################################

	/**
	 * Create our CiviCRM custom data entities for all CiviEvents.
	 *
	 * @since 0.3
	 */
	public function entities_create() {

		// Bail if we've already done this.
		if ( 'fgffgs' !== $this->plugin->civicrm_eo->db->option_get( $this->group_name, 'fgffgs' ) ) {
			return;
		}

		// Define title.
		$title = __( 'Event Statistics', 'civicrm-eo-attendance' );

		// Create group and field and store as plugin options.
		$custom_group_id = $this->group_create( $title );

		// Init fields array.
		$custom_field_ids = [];

		// Which fields do we want?
		$custom_fields = [
			'total' => __( 'Number of Attendees', 'civicrm-eo-attendance' ),
			'boys' => __( 'Number of Boys', 'civicrm-eo-attendance' ),
			'girls' => __( 'Number of Girls', 'civicrm-eo-attendance' ),
			'low' => __( 'Age (Youngest)', 'civicrm-eo-attendance' ),
			'high' => __( 'Age (Oldest)', 'civicrm-eo-attendance' ),
		];

		// Init weight.
		$weight = 1;

		// Create fields.
		foreach ( $custom_fields as $key => $label ) {

			// Create field.
			$field_id = $this->field_create( $custom_group_id, $label, $weight );

			// Skip on failure.
			if ( $field_id === false ) {
				continue;
			}

			// Add to array.
			$custom_field_ids[ $key ] = $field_id;

			$weight++;

		}

		// Store as plugin options.
		$this->plugin->civicrm_eo->db->option_save( $this->group_name, $custom_group_id );
		$this->plugin->civicrm_eo->db->option_save( $this->field_ids_name, $custom_field_ids );

		// We can then allow renaming either via l18n or admin field.

	}

	/**
	 * Create a Custom Group for all CiviEvents.
	 *
	 * @since 0.3
	 *
	 * @param str $title The title of the custom group.
	 * @return int|bool The ID of the new custom group, false on failure.
	 */
	public function group_create( $title ) {

		// Bail if we fail to init CiviCRM.
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {
			return false;
		}

		// Create a custom group.
		$params = [
			'version' => 3,
			'extends' => [ 'Event' ],
			'title' => $title,
			'is_active' => 1,
		];

		// Let's go.
		$result = civicrm_api( 'custom_group', 'create', $params );

		// If error.
		if ( $result['is_error'] == 1 ) {

			// Log and bail.
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );

			// --<
			return false;

		}

		// Grab first value.
		$value = array_pop( $result['values'] );

		// Return group ID.
		return $value['id'];

	}

	/**
	 * Create a Custom Field for a given Custom Group.
	 *
	 * @since 0.3
	 *
	 * @param int $custom_group_id The ID of the custom group.
	 * @param str $label The descriptive field label.
	 * @param int $weight The weight to give the field (default 1).
	 * @return int|bool $custom_field_id The ID of the new custom field, false on failure.
	 */
	public function field_create( $custom_group_id, $label, $weight = 1 ) {

		// Bail if we fail to init CiviCRM.
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {
			return false;
		}

		// Create a numeric custom field.
		$params = [
			'version' => 3,
			'label' => $label,
			'custom_group_id' => $custom_group_id,
			'is_active' => 1,
			'data_type' => 'Int',
			'html_type' => 'Text',
			'is_searchable' => 1,
			'is_search_range' => 1,
			'weight' => $weight,
		];

		// Let's go.
		$result = civicrm_api( 'custom_field', 'create', $params );

		// If error.
		if ( $result['is_error'] == 1 ) {

			// Log and bail.
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );

			// --<
			return false;

		}

		// Grab first value.
		$value = array_pop( $result['values'] );

		// Return field ID.
		return $value['id'];

	}

	//##########################################################################

	/**
	 * Get a CiviEvent's dates and custom data.
	 *
	 * @since 0.3.1
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @return array|bool $event CiviCRM API return array - or false on failure.
	 */
	public function event_get( $civi_event_id ) {

		// Bail if we fail to init CiviCRM.
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {
			return false;
		}

		// Get our list of custom data fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Construct returns array.
		$returns = [];
		foreach ( $fields as $key => $field_id ) {
			$returns[] = 'custom_' . $field_id;
		}

		// Add start and end dates.
		$returns[] = 'start_date';
		$returns[] = 'end_date';

		// Build params to get fields.
		$params = [
			'version' => 3,
			'id' => $civi_event_id,
			'return' => $returns,
		];

		// Get fields for this grouping.
		$event = civicrm_api( 'event', 'getsingle', $params );

		// Error check.
		if ( isset( $event['is_error'] ) && $event['is_error'] == '1' ) {

			// Log and bail.
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $event['error_message'],
				'params' => $params,
				'event' => $event,
				'backtrace' => $trace,
			], true ) );

			// --<
			return false;

		}

		// --<
		return $event;

	}

	/**
	 * Check if a CiviEvent has custom data.
	 *
	 * Right now, this only checks for a non-zero value for "total". It can be
	 * updated to check all fields for values if need be, but given that events
	 * must have more than one attendee... well...
	 *
	 * @since 0.3.1
	 *
	 * @param array $civi_event The array of CiviEvent data.
	 * @return bool $has_data True if event has data, false otherwise.
	 */
	public function event_has_data( $civi_event ) {

		// Get fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Construct "total" key.
		$key = 'custom_' . $fields['total'];

		// Grab value if we have it.
		$value = isset( $civi_event[ $key ] ) ? absint( $civi_event[ $key ] ) : 0;

		// Is it non-zero?
		if ( $value > 0 ) {
			return true;
		}

		// Fall back to false.
		return false;

	}

	//##########################################################################

	/**
	 * Get the Event Feedback list for an Event.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WP post.
	 */
	public function list_render( $post_id = null ) {

		// Bail if not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get list items as array.
		$links = $this->list_populate( $post_id );

		// Show them if we have any.
		if ( ! empty( $links ) ) {

			// Construct visual cue for AJAX loading.
			$spinner_src = CIVICRM_EO_ATTENDANCE_URL . 'assets/images/loading.gif';
			$spinner = '<img src="' . $spinner_src . '" class="civicrm-eo-spinner" />';
			$loading = '<div class="civicrm-eo-loading">' . $spinner . '</div>';

			// Combine into list.
			$list = implode( $loading . '</li>' . "\n" . '<li class="civicrm-eo-custom-data-event">', $links );

			// Top and tail.
			$list = '<li class="civicrm-eo-custom-data-event">' . $list . $loading . '</li>' . "\n";

			// Handle recurring events.
			if ( eo_recurs() ) {

				// Wrap in unordered list.
				$list = '<ul class="civicrm-eo-custom-data-events">' . $list . '</ul>';

				// Open a list item.
				echo '<li class="civicrm-eo-custom-data-events">';

				// Show a title.
				echo '<strong>' . __( 'Event Feedback', 'civicrm-eo-attendance' ) . ':</strong>';

				// Show links.
				echo $list;

				// Finish up.
				echo '</li>' . "\n";

			} else {

				// Show links list.
				echo $list;

			}

			// Add javascript.
			$this->list_scripts();

		}

	}

	/**
	 * Add our Javascript for the Event Feedback links.
	 *
	 * @since 0.3
	 */
	public function list_scripts() {

		// Add script to footer.
		wp_enqueue_script(
			'civicrm-eo-attendance-cde',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-cde.js',
			[ 'jquery' ],
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // In footer.
		);

		// Translations.
		$localisation = [
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'empty' => __( 'You must enter values for each field', 'civicrm-eo-attendance' ),
			'negative' => __( 'You must enter positive values for each field', 'civicrm-eo-attendance' ),
			'positive' => __( 'You must enter positive values for ages', 'civicrm-eo-attendance' ),
			'match' => __( 'Number of attendees must be the sum of boys and girls', 'civicrm-eo-attendance' ),
			'range' => __( 'Youngest must be younger than oldest', 'civicrm-eo-attendance' ),
		];

		// Define settings.
		$settings = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		];

		// Localisation array.
		$vars = [
			'localisation' => $localisation,
			'settings' => $settings,
		];

		// Localise the WordPress way.
		wp_localize_script(
			'civicrm-eo-attendance-cde',
			'CiviCRM_EO_Attendance_CDE_Settings',
			$vars
		);

	}

	/**
	 * Get the Event Feedback links for an Event.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WP post.
	 * @return array $links The HTML links to the CiviCRM Participant pages.
	 */
	public function list_populate( $post_id = null ) {

		// Init return.
		$links = [];

		/**
		 * Fire access action so other plugins can modify access.
		 *
		 * @since 0.5.1
		 *
		 * @param bool $granted False by default - assume access not granted.
		 * @param int $post_id The numeric ID of the WP post.
		 * @return bool $granted True if access granted, false otherwise.
		 */
		if ( false === apply_filters( 'civicrm_eo_cde_access', false, $post_id ) ) {
			return $links;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return $links;
		}

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return $links;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get user matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get the CiviCRM contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// Bail if no contact ID found.
		if ( empty( $contact_id ) ) {
			return $links;
		}

		// Need the post ID.
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// Bail if not present.
		if ( empty( $post_id ) ) {
			return $links;
		}

		// Get CiviEvents.
		$civi_event_ids = $this->plugin->civicrm_eo->db->get_civi_event_ids_by_eo_event_id( $post_id );

		// Sanity check.
		if ( empty( $civi_event_ids ) ) {
			return $links;
		}

		// Did we get more than one?
		$multiple = ( count( $civi_event_ids ) > 1 ) ? true : false;

		// Escalate permissions to view participants.
		add_action( 'civicrm_permission_check', [ $this, 'permissions_escalate' ], 10, 2 );

		// Loop through them.
		foreach ( $civi_event_ids as $civi_event_id ) {

			// Get event data.
			$civi_event = $this->event_get( $civi_event_id );

			// Skip if it's not a past event.
			$now = new DateTime( 'now', eo_get_blog_timezone() );
			$end = new DateTime( $civi_event['end_date'], eo_get_blog_timezone() );
			if ( $end > $now ) {
				continue;
			}

			// Skip if this event already has data.
			if ( $this->event_has_data( $civi_event ) ) {
				continue;
			}

			// Skip if this user isn't the event leader.
			if ( ! $this->plugin->event_leader->user_is_leader( $civi_event_id ) ) {
				continue;
			}

			// Set different link text for single and multiple occurrences.
			if ( $multiple ) {

				// Get occurrence ID for this CiviEvent.
				$occurrence_id = $this->plugin->civicrm_eo->db->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );

				// Define text.
				$text = sprintf(
					__( 'Feedback for %s', 'civicrm-eo-attendance' ),
					eo_format_event_occurrence( $post_id, $occurrence_id )
				);

			} else {
				$text = __( 'Event Feedback', 'civicrm-eo-attendance' );
			}

			// Construct custom class name.
			$class = 'civicrm-eo-cde-event-id-' . $civi_event['id'];

			// Construct span if we get one.
			$span = '<span class="civicrm-eo-custom-data-event ' . $class . '">' . $text . '</span>';

			/**
			 * Filter event custom data element.
			 *
			 * @since 0.2.2
			 *
			 * @param string $span The HTML element.
			 * @param string $text The text content of the element.
			 * @param int $post_id The numeric ID of the WP post.
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_custom_data_event_element', $span, $text, $post_id );

		}

		// Remove permission to view participants.
		remove_action( 'civicrm_permission_check', [ $this, 'permissions_escalate' ], 10 );

		// --<
		return $links;

	}

	/**
	 * Grant the permissions necessary for custom data functionality.
	 *
	 * @since 0.5.1
	 *
	 * @param str $permission The requested permission.
	 * @param bool $granted True if permission granted, false otherwise.
	 */
	public function permissions_escalate( $permission, &$granted ) {

		// Allow the relevant ones.
		if (
			$permission == 'access CiviCRM' ||
			$permission == 'access CiviEvent' ||
			$permission == 'view event info' ||
			$permission == 'view event participants'
		) {
			$granted = 1;
		}

	}

	//##########################################################################

	/**
	 * Get a form for filling in Event custom data.
	 *
	 * @since 0.2.2
	 */
	public function form_get() {

		// Get event ID.
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? $_POST['civi_event_id'] : '';

		// Sanitise.
		$event_id = absint( trim( $civi_event_id ) );

		// Init data.
		$data = [
			'civi_event_id' => $event_id,
			'markup' => '',
		];

		// Start buffering.
		ob_start();

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/custom-data-event/event-form.php';

		// Save the output and flush the buffer.
		$form = ob_get_clean();

		// Construct final markup.
		$data['markup'] = '<ul><li>' . $form . '</li></ul>';

		// Send data to browser.
		$this->send_data( $data );

	}

	/**
	 * Save Event custom data.
	 *
	 * @since 0.2.2
	 */
	public function form_process() {

		// Show something when there's an error.
		$error_markup = '<ul class="civicrm_eo_cde_oops"><li>' .
							__( 'Oops! Something went wrong.', 'civicrm-eo-attendance' ) .
						'</li></ul>';

		// Get form data.
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? $_POST['civi_event_id'] : '0';
		$civi_event_id = absint( trim( $civi_event_id ) );

		// Init data.
		$data = [
			'civi_event_id' => $civi_event_id,
			'error' => '0',
			'markup' => '',
		];

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

		// Build params to save fields.
		$params = [
			'version' => 3,
			'id' => $civi_event_id,
		];

		// Get fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Process each field.
		foreach ( $fields as $key => $field_id ) {

			// Construct key defined in POST and grab value.
			$post_key = 'civicrm_eo_cde_' . $key;
			$value = isset( $_POST[ $post_key ] ) ? absint( trim( $_POST[ $post_key ] ) ) : 0;

			// Add to params.
			$params[ 'custom_' . $field_id ] = $value;

		}

		// Update fields for this event.
		$events = civicrm_api( 'event', 'create', $params );

		// Error check.
		if ( $events['is_error'] == '1' ) {

			// Log and bail.
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $events['error_message'],
				'params' => $params,
				'events' => $events,
				'backtrace' => $trace,
			], true ) );

			$data['error'] = '1';
			$data['markup'] = $error_markup;
			$this->send_data( $data );

		}

		// What to return?
		$markup = '<ul class="civicrm_eo_cde_thanks"><li>' . __( 'Thanks!', 'civicrm-eo-attendance' ) . '</li></ul>';

		// Amend data.
		$data['markup'] = $markup;

		// Send data to browser.
		$this->send_data( $data );

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
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			// Set reasonable headers.
			header( 'Content-type: text/plain' );
			header( 'Cache-Control: no-cache' );
			header( 'Expires: -1' );

			// Echo.
			echo json_encode( $data );

			// Die.
			exit();

		}

	}

}
