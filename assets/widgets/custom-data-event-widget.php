<?php
/**
 * Event Feedback Widget.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates a Widget for displaying a list of feedback for Events.
 *
 * @since 0.4.6
 */
class CiviCRM_EO_Attendance_CDE_Widget extends WP_Widget {

	/**
	 * Constructor registers Widget with WordPress.
	 *
	 * @since 0.4.6
	 */
	public function __construct() {

		// Init parent.
		parent::__construct(
			// Base ID.
			'civicrm_eo_event_feedback_widget',
			// Name.
			__( 'Event Feedback', 'civicrm-eo-attendance' ),
			// Args.
			[
				'description' => __( 'Use this Widget to show a list of feedback for Events.', 'civicrm-eo-attendance' ),
			]
		);

	}

	/**
	 * Outputs the HTML for this Widget.
	 *
	 * @since 0.4.6
	 *
	 * @param array $args An array of standard parameters for Widgets in this theme.
	 * @param array $instance An array of settings for this Widget instance.
	 */
	public function widget( $args, $instance ) {

		// Get filtered title.
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		// Show Widget prefix.
		echo ( isset( $args['before_widget'] ) ? $args['before_widget'] : '' );

		// Show title if there is one.
		if ( ! empty( $title ) ) {
			echo ( isset( $args['before_title'] ) ? $args['before_title'] : '' );
			echo $title;
			echo ( isset( $args['after_title'] ) ? $args['after_title'] : '' );
		}

		// Set default max items if absent.
		if ( empty( $instance['max_items'] ) || ! is_numeric( $instance['max_items'] ) ) {
			$instance['max_items'] = 5;
		}

		// Get list.
		$links = $this->list_populate();

		// Show them if we have any.
		if ( ! empty( $links ) ) {

			// Combine into list.
			$list = implode( '</li>' . "\n" . '<li class="civicrm-eo-cde-widget">', $links );

			// Top and tail.
			$list = '<li class="civicrm-eo-cde-widget">' . $list . '</li>' . "\n";

		} else {

			// Show something.
			$list = '<li class="civicrm-eo-cde-widget cde-up-to-date">' .
						__( 'You are up-to-date with your feedback.', 'civicrm-eo-attendance' ) .
					'</li>' . "\n";

		}

		// Wrap in unordered list.
		echo '<ul class="civicrm-eo-cde-widget">' . $list . '</ul>';

		// Show Widget suffix.
		echo ( isset( $args['after_widget'] ) ? $args['after_widget'] : '' );

	}

	/**
	 * Back-end Widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @since 0.4.6
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		// Get title.
		if ( isset( $instance['title'] ) ) {
			$title = wp_strip_all_tags( $instance['title'] );
		} else {
			$title = __( 'Event Feedback Required', 'civicrm-eo-attendance' );
		}

		// Get max items.
		if ( isset( $instance['max_items'] ) ) {
			$max_items = wp_strip_all_tags( $instance['max_items'] );
		} else {
			$max_items = 5;
		}

		?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'civicrm-eo-attendance' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></label>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'max_items' ); ?>"><?php esc_html_e( 'Max number to show:', 'civicrm-eo-attendance' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_items' ); ?>" name="<?php echo $this->get_field_name( 'max_items' ); ?>" type="text" value="<?php echo esc_attr( $max_items ); ?>" style="width: 30%" /></label>
		</p>

		<?php

	}

	/**
	 * Sanitize Widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @since 0.4.6
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array $instance Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		// Never lose a value.
		$instance = wp_parse_args( $new_instance, $old_instance );

		// --<
		return $instance;

	}

	/**
	 * Get the Event Feedback links for an Event.
	 *
	 * @since 0.4.6
	 *
	 * @return array $links The HTML for the Event Feedback links
	 */
	public function list_populate() {

		// Let's alias the CDE object.
		$cde = civicrm_eo_attendance()->custom_data_event;

		// Init return.
		$links = [];

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

		// Get User matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Get the CiviCRM Contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// Bail if no Contact ID found.
		if ( empty( $contact_id ) ) {
			return $links;
		}

		// Build params to get Fields.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'options' => [
				'sort' => 'event_end_date ASC',
			],
		];

		// Get Participant entries for this Contact.
		$participants = civicrm_api( 'Participant', 'get', $params );

		// Error check.
		if ( isset( $participants['is_error'] ) && $participants['is_error'] == '1' ) {

			// Log and bail.
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $participants['error_message'],
				'params' => $params,
				'participants' => $participants,
				'backtrace' => $trace,
			], true ) );

			// --<
			return $links;

		}

		// Loop through them.
		foreach ( $participants['values'] as $participant ) {

			// Skip to next if it is not a past Event.
			if ( ! $this->is_past( $participant['event_end_date'] ) ) {
				continue;
			}

			// Alias Event ID.
			$event_id = $participant['event_id'];

			// Skip to next if not Event Leader.
			if ( ! $this->is_leader( $event_id, $participant['participant_role_id'] ) ) {
				continue;
			}

			// Get the Event data.
			$event_data = $cde->event_get( $event_id );

			// Skip if this Event already has data.
			if ( $cde->event_has_data( $event_data ) ) {
				continue;
			}

			// Get EO Event ID.
			$post_id = civicrm_eo()->mapping->get_eo_event_id_by_civi_event_id( $event_id );

			// Get Occurrence ID for this CiviEvent.
			$occurrence_id = civicrm_eo()->mapping->get_eo_occurrence_id_by_civi_event_id( $event_id );

			/*
			 * If there are CiviEvents for which the EO Event has been deleted
			 * although the correspondence for the CiviEvent remains (this can
			 * happen because the delete code did not function properly until
			 * https://github.com/christianwach/civicrm-event-organiser/commit/d1baf0741e59d6884f84af2d7bf05c50f14cb9e2
			 * fixed the issue) then it is possible that the EO Event does not
			 * exist. We need to protect against this and log the problem so
			 * the the data can be fixed. This means going in to CiviCRM and
			 * deleting the Participants for the CiviEvent, then deleting the
			 * CiviEvent itself.
			 */

			// Get EO Event.
			$eo_event = get_post( $post_id );

			// If it's not there.
			if ( ! ( $eo_event instanceof WP_Post ) ) {

				// Write to log file.
				$e = new Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					'message' => '=============== EO Event is missing ===============',
					'post_id' => $post_id,
					'occurrence_id' => $occurrence_id,
					'participant' => $participant,
					'event_data' => $event_data,
					'backtrace' => $trace,
				], true ) );

				// Skip to next.
				continue;

			}

			// Let's have a title.
			$text = '<span class="civicrm-eo-cde-widget-event-title">' . $participant['event_title'] . '</span>';
			$text .= '<br>';
			$text .= eo_format_event_occurrence( $post_id, $occurrence_id );

			// Construct class name.
			$class = 'civicrm-eo-cde-widget-event-id-' . $event_id;

			// Construct span if we get one.
			$span = '<span class="civicrm-eo-cde-widget ' . $class . '">' . $text . '</span>';

			// Wrap in link.
			$span = '<a href="' . get_permalink( $post_id ) . '">' . $span . '</a>';

			// Add form toggle.
			$span .= '<span id="civicrm-eo-cde-feedback-' . $event_id . '" class="civicrm-eo-feedback civicrm-eo-cde-feedback">' .
				__( 'Leave feedback', 'civicrm-eo-attendance' ) .
			'</span>';

			// Add form.
			$span .= $this->get_form( $participant );

			/**
			 * Filter Event element.
			 *
			 * @since 0.4.6
			 *
			 * @param string $span The HTML element.
			 * @param string $text The text content of the element.
			 * @param int $post_id The numeric ID of the WordPress Post.
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_cde_widget_element', $span, $text, $post_id );

		}

		// Enqueue Javascript.
		$this->enqueue_scripts();

		// --<
		return $links;

	}
	/**
	 * Check if an Event is a past Event.
	 *
	 * @since 0.4.6
	 *
	 * @param str $end_date The end date string for a CiviEvent.
	 * @return bool $past_event True if the Event is past, false otherwise.
	 */
	public function is_past( $end_date ) {

		// Init past Event flag.
		$past_event = false;

		// Override if it's a past Event.
		$now = new DateTime( 'now', eo_get_blog_timezone() );
		$end = new DateTime( $end_date, eo_get_blog_timezone() );
		if ( $end < $now ) {
			$past_event = true;
		}

		/*
		$ts = $now->getTimestamp();
		$ts = $end->getTimestamp();
		*/

		// --<
		return $past_event;

	}

	/**
	 * Check if an Event is a past Event.
	 *
	 * @since 0.4.6
	 *
	 * @param int $civi_event_id The numeric ID of the CiviEvent.
	 * @param int $participant_role_id The numeric ID of the Pariticpant's role.
	 * @return bool $is_leader True if User has Event Leader Role.
	 */
	public function is_leader( $civi_event_id, $participant_role_id ) {

		// Init return.
		$is_leader = false;

		// Get EO Event ID.
		$post_id = civicrm_eo()->mapping->get_eo_event_id_by_civi_event_id( $civi_event_id );

		// Get the Event.
		$event = get_post( $post_id );

		// Get Event Leader Role ID for this Event.
		$default_role_id = civicrm_eo_attendance()->event_leader->role_default_get( $event );

		// Override if Event Leader.
		if ( $default_role_id == $participant_role_id ) {
			$is_leader = true;
		}

		// --<
		return $is_leader;

	}

	/**
	 * Get the Event form.
	 *
	 * @since 0.5.2
	 *
	 * @param array $participant The Participant data.
	 * @return str $form The form markup.
	 */
	public function get_form( $participant ) {

		// Populate template vars.
		$event_id = $participant['event_id'];

		// Start buffering.
		ob_start();

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/custom-data-event/event-form.php';

		// Save the output and flush the buffer.
		$form = ob_get_clean();

		// --<
		return $form;

	}

	/**
	 * Add our Javascript for the Event Feedback links.
	 *
	 * @since 0.5.2
	 */
	public function enqueue_scripts() {

		// Add script to footer.
		wp_enqueue_script(
			'civicrm-eo-attendance-cde-widget',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-cde-widget.js',
			[ 'jquery' ],
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // In footer.
		);

		// Translations.
		$localisation = [
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'empty' => __( 'You must enter values for each field', 'civicrm-eo-attendance' ),
			'negative' => __( 'You must enter positive values for each field', 'civicrm-eo-attendance' ),
			'match' => __( 'Number of attendees must be the sum of boys and girls', 'civicrm-eo-attendance' ),
			'range' => __( 'Youngest must be younger than oldest', 'civicrm-eo-attendance' ),
			'positive' => __( 'You must enter positive values for ages', 'civicrm-eo-attendance' ),
			'complete' => __( 'You are up-to-date with your feedback.', 'civicrm-eo-attendance' ),
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
			'civicrm-eo-attendance-cde-widget',
			'CiviCRM_EO_Attendance_CDE_Widget_Settings',
			$vars
		);

	}

}
