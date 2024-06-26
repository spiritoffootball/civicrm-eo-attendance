<?php
/**
 * Participant Custom Data Class.
 *
 * Handles Participant Custom Data functionality.
 *
 * @since 0.2.2
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Participant Custom Data Class.
 *
 * A class that encapsulates Participant Custom Data functionality.
 *
 * @since 0.2.2
 */
class CiviCRM_EO_Attendance_Custom_Data_Participant {

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
	public $group_name = 'civicrm_eo_participant_custom_group_id';

	/**
	 * Field IDs Option name.
	 *
	 * @since 0.3.1
	 * @access public
	 * @var string
	 */
	public $field_ids_name = 'civicrm_eo_participant_custom_field_ids';

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

		// Add AJAX handlers.
		add_action( 'wp_ajax_participant_custom_data_form_get', [ $this, 'form_get' ] );
		add_action( 'wp_ajax_participant_custom_data_form_process', [ $this, 'form_process' ] );

		// Show list in EO Event template.
		add_action( 'eventorganiser_additional_event_meta', [ $this, 'list_render' ], 12 );

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
		require_once CIVICRM_EO_ATTENDANCE_PATH . 'assets/widgets/class-participant-widget.php';

		// Register widgets.
		register_widget( 'CiviCRM_EO_Attendance_CDP_Widget' );

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

	// -----------------------------------------------------------------------------------

	/**
	 * Create our CiviCRM Custom Fields for all Participants.
	 *
	 * @since 0.3
	 */
	public function entities_create() {

		// Bail if we've already done this.
		if ( 'fgffgs' !== $this->plugin->civicrm_eo->db->option_get( $this->group_name, 'fgffgs' ) ) {
			return;
		}

		// Define title.
		$title = __( 'Participant Statistics', 'civicrm-eo-attendance' );

		// Create Group and Field and store as plugin options.
		$custom_group_id = $this->group_create( $title );

		// Init Fields array.
		$custom_field_ids = [];

		// Which Fields do we want?
		$custom_fields = [
			'hours'   => __( 'Hours Worked', 'civicrm-eo-attendance' ),
			'minutes' => __( 'Minutes Worked', 'civicrm-eo-attendance' ),
		];

		// Create Fields.
		foreach ( $custom_fields as $key => $label ) {

			// Create Field.
			$field_id = $this->field_create( $custom_group_id, $label );

			// Skip on failure.
			if ( false === $field_id ) {
				continue;
			}

			// Add to array.
			$custom_field_ids[ $key ] = $field_id;

		}

		// Store as plugin options.
		$this->plugin->civicrm_eo->db->option_save( $this->group_name, $custom_group_id );
		$this->plugin->civicrm_eo->db->option_save( $this->field_ids_name, $custom_field_ids );

		// We can then allow renaming either via l18n or admin Field.

	}

	/**
	 * Create a Custom Group for all Participants.
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
			'extends'   => [ 'Participant' ],
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
	 * Get all of a Contact's Participant data.
	 *
	 * @since 0.4.6
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array|bool $participants CiviCRM API return array - or false on failure.
	 */
	public function participants_get( $contact_id ) {

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

		// Add Event ID.
		$returns[] = 'event_id';

		// Add start and end dates.
		$returns[] = 'event_start_date';
		$returns[] = 'event_end_date';

		// Build params to get Fields.
		$params = [
			'version'    => 3,
			'contact_id' => $contact_id,
			'return'     => $returns,
		];

		try {

			// Get Fields for this Participant.
			$result = civicrm_api( 'Participant', 'get', $params );

		} catch ( Exception $e ) {

			// Log and bail.
			$log = [
				'method'    => __METHOD__,
				'message'   => $e->getMessage(),
				'backtrace' => $e->getTraceAsString(),
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// Error check.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'       => __METHOD__,
				'message'      => $result['error_message'],
				'params'       => $params,
				'participants' => $result,
				'backtrace'    => $trace,
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// --<
		return $result['values'];

	}

	/**
	 * Get a Contact's Participant data for an Event.
	 *
	 * @since 0.3.1
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @return array|bool $participant CiviCRM API return array - or false on failure.
	 */
	public function participant_get( $contact_id, $civi_event_id ) {

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

		// Add Event and status IDs.
		$returns[] = 'event_id';
		$returns[] = 'participant_status_id';
		$returns[] = 'participant_role_id';

		// Add start and end dates.
		$returns[] = 'event_start_date';
		$returns[] = 'event_end_date';

		// Build params to get Fields.
		$params = [
			'version'    => 3,
			'contact_id' => $contact_id,
			'event_id'   => $civi_event_id,
			'return'     => $returns,
		];

		// Get Fields for this Participant.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Error check.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'       => __METHOD__,
				'message'      => $result['error_message'],
				'params'       => $params,
				'participants' => $result,
				'backtrace'    => $trace,
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// Sanity check.
		if ( empty( $result['values'] ) ) {
			return false;
		}

		// We should only have one value.
		$participant = array_pop( $result['values'] );

		// --<
		return $participant;

	}

	/**
	 * Get the Participant data for an Event by Participant ID.
	 *
	 * @since 0.4.6
	 *
	 * @param int $participant_id The numeric ID of the Participant record.
	 * @return array|bool $participant CiviCRM API return array - or false on failure.
	 */
	public function participant_get_by_id( $participant_id ) {

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

		// Add Event ID.
		$returns[] = 'event_id';

		// Add start and end dates.
		$returns[] = 'event_start_date';
		$returns[] = 'event_end_date';

		// Build params to get Fields.
		$params = [
			'version' => 3,
			'id'      => $participant_id,
			'return'  => $returns,
		];

		// Get Fields for this Participant.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Error check.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'       => __METHOD__,
				'message'      => $result['error_message'],
				'params'       => $params,
				'participants' => $result,
				'backtrace'    => $trace,
			];
			$this->plugin->log_error( $log );

			// --<
			return false;

		}

		// We should only have one value.
		$participant = array_pop( $result['values'] );

		// --<
		return $participant;

	}

	/**
	 * Check if a Participant has data.
	 *
	 * For now, this only checks for a non-zero values for "hours" and "minutes".
	 *
	 * @since 0.3.1
	 *
	 * @param array $participant The array of Participant data.
	 * @return bool $has_data True if Participant has data, false otherwise.
	 */
	public function participant_has_data( $participant ) {

		// Get Fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Construct "hours" key.
		$key = 'custom_' . $fields['hours'];

		// Grab value if we have it.
		$hours = isset( $participant[ $key ] ) ? absint( $participant[ $key ] ) : 0;

		// Is it non-zero?
		if ( $hours > 0 ) {
			return true;
		}

		// Construct "minutes" key.
		$key = 'custom_' . $fields['minutes'];

		// Grab value if we have it.
		$minutes = isset( $participant[ $key ] ) ? absint( $participant[ $key ] ) : 0;

		// Is it non-zero?
		if ( $minutes > 0 ) {
			return true;
		}

		// Fall back to false.
		return false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the Participant Feedback list for an Event.
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
			$list = implode( $loading . '</li>' . "\n" . '<li class="civicrm-eo-custom-data-participant">', $links );

			// Top and tail.
			$list = '<li class="civicrm-eo-custom-data-participant">' . $list . $loading . '</li>' . "\n";

			// Handle recurring Events.
			if ( eo_recurs() ) {

				// Wrap in unordered list.
				$list = '<ul class="civicrm-eo-custom-data-participants">' . $list . '</ul>';

				// Open a list item.
				echo '<li class="civicrm-eo-custom-data-participants">';

				// Show a title.
				echo '<strong>' . esc_html__( 'Participant Feedback', 'civicrm-eo-attendance' ) . ':</strong>';

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
	 * Add our Javascript for the Participant Feedback links.
	 *
	 * @since 0.3
	 */
	public function list_scripts() {

		// Add script to footer.
		wp_enqueue_script(
			'civicrm-eo-attendance-cdp',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-cdp.js',
			[ 'jquery' ],
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // In footer.
		);

		// Translations.
		$localisation = [
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'empty'      => __( 'You must enter values for each field.', 'civicrm-eo-attendance' ),
			'numeric'    => __( 'You must enter numeric values for each field.', 'civicrm-eo-attendance' ),
			'integer'    => __( 'You must enter whole numbers in each field.', 'civicrm-eo-attendance' ),
			'negative'   => __( 'You must enter positive values for each field.', 'civicrm-eo-attendance' ),
			'zero'       => __( 'Really? You worked no time at all?', 'civicrm-eo-attendance' ),
			'mins'       => __( 'The number of minutes must be less than 60.', 'civicrm-eo-attendance' ),
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
			'civicrm-eo-attendance-cdp',
			'CiviCRM_EO_Attendance_CDP_Settings',
			$vars
		);

	}

	/**
	 * Get the Participant Feedback links for an Event.
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
		if ( false === apply_filters( 'civicrm_eo_cdp_access', false, $post_id ) ) {
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

			// Get Participant data for this Event.
			$civi_participant = $this->participant_get( $contact_id, $civi_event_id );

			// Skip on failure.
			if ( false === $civi_participant ) {
				continue;
			}
			if ( empty( $civi_participant ) ) {
				continue;
			}

			// Skip if it's not a past Event.
			$now = new DateTime( 'now', eo_get_blog_timezone() );
			$end = new DateTime( $civi_participant['event_end_date'], eo_get_blog_timezone() );
			if ( $end > $now ) {
				continue;
			}

			// Skip if this Participant already has data.
			if ( $this->participant_has_data( $civi_participant ) ) {
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
				$text = __( 'Participant Feedback', 'civicrm-eo-attendance' );
			}

			// Construct class name.
			$class = 'civicrm-eo-cdp-participant-id-' . $civi_participant['id'];

			// Construct span if we get one.
			$span = '<span class="civicrm-eo-custom-data-participant ' . $class . '">' . $text . '</span>';

			/**
			 * Filter Event data element.
			 *
			 * @since 0.2.2
			 *
			 * @param string $span The HTML element.
			 * @param string $text The text content of the element.
			 * @param int $post_id The numeric ID of the WordPress Post.
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_custom_data_participant_element', $span, $text, $post_id );

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
			'view event participants' === $permission ||
			'access all custom data' === $permission
		) {
			$granted = 1;
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a form for filling in Participant Custom Fields.
	 *
	 * @since 0.2.2
	 */
	public function form_get() {

		// Get Participant ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$participant_id = isset( $_POST['participant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['participant_id'] ) ) : '';

		// Always cast as integer.
		$participant_id = (int) $participant_id;

		// Get Participant data.
		$participant = $this->participant_get_by_id( $participant_id );

		// TODO: Handle failures.

		// Find elapsed time.
		$start    = new DateTime( $participant['event_start_date'], eo_get_blog_timezone() );
		$end      = new DateTime( $participant['event_end_date'], eo_get_blog_timezone() );
		$interval = $start->diff( $end );
		$elapsed  = $interval->format( '%h:%i' );

		// Grab hours and minutes.
		$elements = explode( ':', $elapsed );
		$hours    = isset( $elements[0] ) ? absint( $elements[0] ) : 0;
		$minutes  = isset( $elements[1] ) ? absint( $elements[1] ) : 0;

		// Format hours when zero.
		if ( 0 === $hours ) {
			$hours = '0';
		}

		// Format minutes when zero.
		if ( 0 === $minutes ) {
			$minutes = '00';
		}

		// Init data.
		$data = [
			'participant_id' => $participant_id,
			'markup'         => '',
		];

		// Start buffering.
		ob_start();

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/custom-data-participant/event-form.php';

		// Save the output and flush the buffer.
		$form = ob_get_clean();

		// Construct final markup.
		$data['markup'] = '<ul><li>' . $form . '</li></ul>';

		// Send data to browser.
		wp_send_json( $data );

	}

	/**
	 * Save Participant Custom Fields.
	 *
	 * @since 0.2.2
	 */
	public function form_process() {

		// Show something when there's an error.
		$error_markup = '<ul class="civicrm_eo_cdp_oops"><li>' .
							__( 'Oops! Something went wrong.', 'civicrm-eo-attendance' ) .
						'</li></ul>';

		// Get Participant ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$participant_id = isset( $_POST['participant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['participant_id'] ) ) : '0';

		// Always cast as integer.
		$participant_id = (int) $participant_id;

		// Init data.
		$data = [
			'participant_id' => $participant_id,
			'error'          => '0',
			'markup'         => '',
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
			'id'      => $participant_id,
		];

		// Get Fields.
		$fields = $this->plugin->civicrm_eo->db->option_get( $this->field_ids_name );

		// Process each Field.
		foreach ( $fields as $key => $field_id ) {

			// Construct key defined in POST and grab value.
			$post_key = 'civicrm_eo_cdp_' . $key;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $post_key ] ) ? (int) sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : 0;

			// Add to params.
			$params[ 'custom_' . $field_id ] = $value;

		}

		// Update Participant.
		$result = civicrm_api( 'Participant', 'create', $params );

		// Error check.
		if ( isset( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {

			// Log and bail.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'       => __METHOD__,
				'message'      => $result['error_message'],
				'params'       => $params,
				'participants' => $result,
				'backtrace'    => $trace,
			];
			$this->plugin->log_error( $log );

			$data['error']  = '1';
			$data['markup'] = $error_markup;
			wp_send_json( $data );

		}

		// What to return?
		$markup = '<ul class="civicrm_eo_cdp_thanks"><li>' . __( 'Thanks!', 'civicrm-eo-attendance' ) . '</li></ul>';

		// Amend data.
		$data['markup'] = $markup;

		// Send data to browser.
		wp_send_json( $data );

	}

}
