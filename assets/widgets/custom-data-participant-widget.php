<?php
/**
 * Participant Feedback Widget.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates a Widget for displaying a list of feedback for Participants.
 *
 * @since 0.4.6
 */
class CiviCRM_EO_Attendance_CDP_Widget extends WP_Widget {

	/**
	 * Constructor registers widget with WordPress.
	 *
	 * @since 0.4.6
	 */
	public function __construct() {

		// Init parent.
		parent::__construct(
			// Base ID.
			'civicrm_eo_participant_feedback_widget',
			// Name.
			__( 'Participant Feedback', 'civicrm-eo-attendance' ),
			// Args.
			[
				'description' => __( 'Use this widget to show a list of feedback for Participants.', 'civicrm-eo-attendance' ),
			]
		);

	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 0.4.6
	 *
	 * @param array $args An array of standard parameters for widgets in this theme.
	 * @param array $instance An array of settings for this widget instance.
	 */
	public function widget( $args, $instance ) {

		// Get filtered title.
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		// Show widget prefix.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo isset( $args['before_widget'] ) ? $args['before_widget'] : '';

		// Show title if there is one.
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo isset( $args['before_title'] ) ? $args['before_title'] : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $title;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo isset( $args['after_title'] ) ? $args['after_title'] : '';
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
			$list = implode( '</li>' . "\n" . '<li class="civicrm-eo-cdp-widget">', $links );

			// Top and tail.
			$list = '<li class="civicrm-eo-cdp-widget">' . $list . '</li>' . "\n";

		} else {

			// Show something.
			$list = '<li class="civicrm-eo-cdp-widget cdp-up-to-date">' .
						__( 'You are up-to-date with your feedback.', 'civicrm-eo-attendance' ) .
					'</li>' . "\n";

		}

		// Wrap in unordered list.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<ul class="civicrm-eo-cdp-widget">' . $list . '</ul>';

		// Show widget suffix.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo isset( $args['after_widget'] ) ? $args['after_widget'] : '';

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 * @since 0.4.6
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		// Get title.
		if ( isset( $instance['title'] ) ) {
			$title = wp_strip_all_tags( $instance['title'] );
		} else {
			$title = __( 'Participant Feedback Required', 'civicrm-eo-attendance' );
		}

		// Get max items.
		if ( isset( $instance['max_items'] ) ) {
			$max_items = wp_strip_all_tags( $instance['max_items'] );
		} else {
			$max_items = 5;
		}

		?>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'civicrm-eo-attendance' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></label>
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'max_items' ) ); ?>"><?php esc_html_e( 'Max number to show:', 'civicrm-eo-attendance' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_items' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_items' ) ); ?>" type="text" value="<?php echo esc_attr( $max_items ); ?>" style="width: 30%" /></label>
		</p>

		<?php

	}

	/**
	 * Sanitize widget form values as they are saved.
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

		// Let's alias the CDP object.
		$cdp = civicrm_eo_attendance()->custom_data_participant;

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

		// Get the CiviCRM Contact ID.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// Get all Participant data for this Contact.
		$participants = $cdp->participants_get( $contact_id );

		// Bail if we didn't get any.
		if ( empty( $participants ) ) {
			return $links;
		}

		// Loop through them.
		foreach ( $participants as $participant ) {

			// Skip to next if it is not a past Event.
			if ( ! $this->is_past( $participant['event_end_date'] ) ) {
				continue;
			}

			// Skip if this Participant already has data.
			if ( $cdp->participant_has_data( $participant ) ) {
				continue;
			}

			// Alias Event ID.
			$event_id = $participant['event_id'];

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
				$e     = new Exception();
				$trace = $e->getTraceAsString();
				$log   = [
					'method'        => __METHOD__,
					'message'       => '=============== EO Event is missing ===============',
					'post_id'       => $post_id,
					'occurrence_id' => $occurrence_id,
					'participant'   => $participant,
					'event_data'    => $event_data,
					'backtrace'     => $trace,
				];
				civicrm_eo_attendance()->plugin->log_error( $log );

				// Skip to next.
				continue;

			}

			// Let's have a title.
			$text  = '<span class="civicrm-eo-cdp-widget-event-title">' . $participant['event_title'] . '</span>';
			$text .= '<br>';
			$text .= eo_format_event_occurrence( $post_id, $occurrence_id );

			// Construct class name.
			$class = 'civicrm-eo-cdp-widget-participant-id-' . $participant['id'];

			// Construct span if we get one.
			$span = '<span class="civicrm-eo-cdp-widget ' . $class . '">' . $text . '</span>';

			// Wrap in link.
			$span = '<a href="' . get_permalink( $post_id ) . '">' . $span . '</a>';

			// Add form toggle.
			$span .= '<span id="civicrm-eo-cdp-feedback-' . $participant['id'] . '" class="civicrm-eo-feedback civicrm-eo-cdp-feedback">' .
				__( 'Leave feedback', 'civicrm-eo-attendance' ) .
			'</span>';

			// Add form.
			$span .= $this->get_form( $participant );

			/**
			 * Filter Participant element.
			 *
			 * @since 0.4.6
			 *
			 * @param string $span The HTML element.
			 * @param string $text The text content of the element.
			 * @param int $post_id The numeric ID of the WordPress Post.
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_cdp_widget_element', $span, $text, $post_id );

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

		// --<
		return $past_event;

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
		$participant_id = $participant['id'];

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

		// Start buffering.
		ob_start();

		// Include template file.
		include CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/custom-data-participant/event-form.php';

		// Save the output and flush the buffer.
		$form = ob_get_clean();

		// --<
		return $form;

	}

	/**
	 * Add our Javascript for the Participant Feedback links.
	 *
	 * @since 0.5.2
	 */
	public function enqueue_scripts() {

		// Add script to footer.
		wp_enqueue_script(
			'civicrm-eo-attendance-cdp-widget',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-cdp-widget.js',
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
			'complete'   => __( 'You are up-to-date with your feedback.', 'civicrm-eo-attendance' ),
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
			'civicrm-eo-attendance-cdp-widget',
			'CiviCRM_EO_Attendance_CDP_Widget_Settings',
			$vars
		);

	}

}
