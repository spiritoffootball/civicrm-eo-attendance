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
	 * Plugin object.
	 *
	 * @since 0.2.2
	 * @access public
	 * @var CiviCRM_Event_Organiser_Attendance
	 */
	public $plugin;

	/**
	 * Group ID Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var string
	 */
	public $group_name = 'civicrm_eo_event_custom_group_id';

	/**
	 * Field IDs Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var string
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

		// Show list in EO Event template.
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
		require_once CIVICRM_EO_ATTENDANCE_PATH . 'assets/widgets/class-event-widget.php';

		// Register widgets.
		register_widget( 'CiviCRM_EO_Attendance_CDE_Widget' );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create our CiviCRM Custom Fields for all CiviEvents.
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

		// Create Group and Field and store as plugin options.
		$custom_group_id = $this->group_create( $title );

		// Init Fields array.
		$custom_field_ids = [];

		// Which Fields do we want?
		$custom_fields = [
			'total' => __( 'Number of Attendees', 'civicrm-eo-attendance' ),
			'boys'  => __( 'Number of Boys', 'civicrm-eo-attendance' ),
			'girls' => __( 'Number of Girls', 'civicrm-eo-attendance' ),
			'low'   => __( 'Age (Youngest)', 'civicrm-eo-attendance' ),
			'high'  => __( 'Age (Oldest)', 'civicrm-eo-attendance' ),
		];

		// Init weight.
		$weight = 1;

		// Create Fields.
		foreach ( $custom_fields as $key => $label ) {

			// Create Field.
			$field_id = $this->field_create( $custom_group_id, $label, $weight );

			// Skip on failure.
			if ( false === $field_id ) {
				continue;
			}

			// Add to array.
			$custom_field_ids[ $key ] = $field_id;

			$weight++;

		}

		// Store as plugin options.
		$this->plugin->civicrm_eo->db->option_save( $this->group_name, $custom_group_id );
		$this->plugin->civicrm_eo->db->option_save( $this->field_ids_name, $custom_field_ids );

		// We can then allow renaming either via l18n or admin Field.

	}

	/**
	 * Create a Custom Group for all CiviEvents.
	 *
	 * @since 0.3
	 *
	 * @param str $title The title of the Custom Group.
	 * @return int|bool The ID of the new Custom Group, false on failure.
	 */
	public function group_create( $title ) {

		// Bail if we fail to init CiviCRM.
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {
			return false;
		}

		// Create a Custom Group.
		$params = [
			'version'   => 3,
			'extends'   => [ 'Event' ],
			'title'     => $title,
			'is_active' => 1,
		];

		// Let's go.
		$result = civicrm_api( 'CustomGroup', 'create', $params );

		// If error.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $result['error_message'],
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// Grab first value.
		$value = array_pop( $result['values'] );

		// Return Group ID.
		return $value['id'];

	}

	/**
	 * Create a Custom Field for a given Custom Group.
	 *
	 * @since 0.3
	 *
	 * @param int $custom_group_id The ID of the Custom Group.
	 * @param str $label The descriptive Field label.
	 * @param int $weight The weight to give the Field (default 1).
	 * @return int|bool $custom_field_id The ID of the new Custom Field, false on failure.
	 */
	public function field_create( $custom_group_id, $label, $weight = 1 ) {

		// Bail if we fail to init CiviCRM.
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {
			return false;
		}

		// Create a numeric Custom Field.
		$params = [
			'version'         => 3,
			'label'           => $label,
			'custom_group_id' => $custom_group_id,
			'is_active'       => 1,
			'data_type'       => 'Int',
			'html_type'       => 'Text',
			'is_searchable'   => 1,
			'is_search_range' => 1,
			'weight'          => $weight,
		];

		// Let's go.
		$result = civicrm_api( 'CustomField', 'create', $params );

		// If error.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $result['error_message'],
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// Grab first value.
		$value = array_pop( $result['values'] );

		// Return Field ID.
		return $value['id'];

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a CiviEvent's dates and Custom Fields.
	 *
	 * @since 0.3.1
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @return array|bool $result CiviCRM API return array - or false on failure.
	 */
	public function event_get( $civi_event_id ) {

		// Bail if we fail to init CiviCRM.
		if ( ! $this->plugin->civicrm_eo->civi->is_active() ) {
			return false;
		}

		// Get our list of Custom Fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Construct returns array.
		$returns = [];
		foreach ( $fields as $key => $field_id ) {
			$returns[] = 'custom_' . $field_id;
		}

		// Add start and end dates.
		$returns[] = 'start_date';
		$returns[] = 'end_date';

		// Build params to get Fields.
		$params = [
			'version' => 3,
			'id'      => $civi_event_id,
			'return'  => $returns,
		];

		// Get Fields for this Grouping.
		$result = civicrm_api( 'Event', 'getsingle', $params );

		// Error check.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $result['error_message'],
				'params'    => $params,
				'event'     => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// --<
		return $result;

	}

	/**
	 * Check if a CiviEvent has Custom Fields.
	 *
	 * Right now, this only checks for a non-zero value for "total". It can be
	 * updated to check all Fields for values if need be, but given that Events
	 * must have more than one attendee... well...
	 *
	 * @since 0.3.1
	 *
	 * @param array $civi_event The array of CiviEvent data.
	 * @return bool $has_data True if Event has data, false otherwise.
	 */
	public function event_has_data( $civi_event ) {

		// Get Fields.
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

	// -----------------------------------------------------------------------------------

	/**
	 * Get the Event Feedback list for an Event.
	 *
	 * @since 0.2.2
	 *
	 * @param int $post_id The numeric ID of the WordPress Post.
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
			$spinner     = '<img src="' . $spinner_src . '" class="civicrm-eo-spinner" />';
			$loading     = '<div class="civicrm-eo-loading">' . $spinner . '</div>';

			// Combine into list.
			$list = implode( $loading . '</li>' . "\n" . '<li class="civicrm-eo-custom-data-event">', $links );

			// Top and tail.
			$list = '<li class="civicrm-eo-custom-data-event">' . $list . $loading . '</li>' . "\n";

			// Handle recurring Events.
			if ( eo_recurs() ) {

				// Wrap in unordered list.
				$list = '<ul class="civicrm-eo-custom-data-events">' . $list . '</ul>';

				// Open a list item.
				echo '<li class="civicrm-eo-custom-data-events">';

				// Show a title.
				echo '<strong>' . esc_html__( 'Event Feedback', 'civicrm-eo-attendance' ) . ':</strong>';

				// Show links.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $list;

				// Finish up.
				echo '</li>' . "\n";

			} else {

				// Show links list.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			'empty'      => __( 'You must enter values for each field', 'civicrm-eo-attendance' ),
			'negative'   => __( 'You must enter positive values for each field', 'civicrm-eo-attendance' ),
			'positive'   => __( 'You must enter positive values for ages', 'civicrm-eo-attendance' ),
			'match'      => __( 'Number of attendees must be the sum of boys and girls', 'civicrm-eo-attendance' ),
			'range'      => __( 'Youngest must be younger than oldest', 'civicrm-eo-attendance' ),
		];

		// Define settings.
		$settings = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		];

		// Localisation array.
		$vars = [
			'localisation' => $localisation,
			'settings'     => $settings,
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
	 * @param int $post_id The numeric ID of the WordPress Post.
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
		 * @param int $post_id The numeric ID of the WordPress Post.
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

		// Get current User.
		$current_user = wp_get_current_user();

		// Get the CiviCRM Contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// Bail if no Contact ID found.
		if ( empty( $contact_id ) ) {
			return $links;
		}

		// Need the Post ID.
		$post_id = absint( empty( $post_id ) ? get_the_ID() : $post_id );

		// Bail if not present.
		if ( empty( $post_id ) ) {
			return $links;
		}

		// Get CiviEvents.
		$civi_event_ids = $this->plugin->civicrm_eo->mapping->get_civi_event_ids_by_eo_event_id( $post_id );

		// Sanity check.
		if ( empty( $civi_event_ids ) ) {
			return $links;
		}

		// Did we get more than one?
		$multiple = ( count( $civi_event_ids ) > 1 ) ? true : false;

		// Escalate permissions to view Participants.
		add_action( 'civicrm_permission_check', [ $this, 'permissions_escalate' ], 10, 2 );

		// Loop through them.
		foreach ( $civi_event_ids as $civi_event_id ) {

			// Get Event data.
			$civi_event = $this->event_get( $civi_event_id );

			// Skip if it's not a past Event.
			$now = new DateTime( 'now', eo_get_blog_timezone() );
			$end = new DateTime( $civi_event['end_date'], eo_get_blog_timezone() );
			if ( $end > $now ) {
				continue;
			}

			// Skip if this Event already has data.
			if ( $this->event_has_data( $civi_event ) ) {
				continue;
			}

			// Skip if this User isn't the Event Leader.
			if ( ! $this->plugin->event_leader->user_is_leader( $civi_event_id ) ) {
				continue;
			}

			// Set different link text for single and multiple Occurrences.
			if ( $multiple ) {

				// Get Occurrence ID for this CiviEvent.
				$occurrence_id = $this->plugin->civicrm_eo->mapping->get_eo_occurrence_id_by_civi_event_id( $civi_event_id );

				// Define text.
				$text = sprintf(
					/* translators: %s The formatted Event title. */
					__( 'Feedback for %s', 'civicrm-eo-attendance' ),
					eo_format_event_occurrence( $post_id, $occurrence_id )
				);

			} else {
				$text = __( 'Event Feedback', 'civicrm-eo-attendance' );
			}

			// Construct class name.
			$class = 'civicrm-eo-cde-event-id-' . $civi_event['id'];

			// Construct span if we get one.
			$span = '<span class="civicrm-eo-custom-data-event ' . $class . '">' . $text . '</span>';

			/**
			 * Filter Event data element.
			 *
			 * @since 0.2.2
			 *
			 * @param string $span The HTML element.
			 * @param string $text The text content of the element.
			 * @param int $post_id The numeric ID of the WordPress Post.
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_custom_data_event_element', $span, $text, $post_id );

		}

		// Remove permission to view Participants.
		remove_action( 'civicrm_permission_check', [ $this, 'permissions_escalate' ], 10 );

		// --<
		return $links;

	}

	/**
	 * Grant the necessary permissions.
	 *
	 * @since 0.5.1
	 *
	 * @param str  $permission The requested permission.
	 * @param bool $granted True if permission granted, false otherwise.
	 */
	public function permissions_escalate( $permission, &$granted ) {

		// Allow the relevant ones.
		if (
			'access CiviCRM' === $permission ||
			'access CiviEvent' === $permission ||
			'view event info' === $permission ||
			'view event participants' === $permission
		) {
			$granted = 1;
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a form for filling in Event Custom Fields.
	 *
	 * @since 0.2.2
	 */
	public function form_get() {

		// Get Event ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['civi_event_id'] ) ) : '0';

		// Always cast as integer.
		$event_id = (int) $civi_event_id;

		// TODO: Handle failures.

		// Init data.
		$data = [
			'civi_event_id' => $event_id,
			'markup'        => '',
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
		wp_send_json( $data );

	}

	/**
	 * Save Event Custom Fields.
	 *
	 * @since 0.2.2
	 */
	public function form_process() {

		// Show something when there's an error.
		$error_markup = '<ul class="civicrm_eo_cde_oops"><li>' .
							__( 'Oops! Something went wrong.', 'civicrm-eo-attendance' ) .
						'</li></ul>';

		// Get form data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$civi_event_id = isset( $_POST['civi_event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['civi_event_id'] ) ) : '0';

		// Always cast as integer.
		$event_id = (int) $civi_event_id;

		// Init data.
		$data = [
			'civi_event_id' => $civi_event_id,
			'error'         => '0',
			'markup'        => '',
		];

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			$data['error']  = '1';
			$data['markup'] = $error_markup;
			wp_send_json( $data );
		}

		// Try and init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			$data['error']  = '1';
			$data['markup'] = $error_markup;
			wp_send_json( $data );
		}

		// Build params to save Fields.
		$params = [
			'version' => 3,
			'id'      => $civi_event_id,
		];

		// Get Fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Process each Field.
		foreach ( $fields as $key => $field_id ) {

			// Construct key defined in POST and grab value.
			$post_key = 'civicrm_eo_cde_' . $key;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $post_key ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : 0;

			// Add to params.
			$params[ 'custom_' . $field_id ] = $value;

		}

		// Update Fields for this Event.
		$result = civicrm_api( 'Event', 'create', $params );

		// Error check.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $result['error_message'],
				'params'    => $params,
				'events'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );

			$data['error']  = '1';
			$data['markup'] = $error_markup;
			wp_send_json( $data );

		}

		// What to return?
		$markup = '<ul class="civicrm_eo_cde_thanks"><li>' . __( 'Thanks!', 'civicrm-eo-attendance' ) . '</li></ul>';

		// Amend data.
		$data['markup'] = $markup;

		// Send data to browser.
		wp_send_json( $data );

	}

}
