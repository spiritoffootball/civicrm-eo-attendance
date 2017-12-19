<?php

/**
 * Creates a custom Widget for displaying a list of feedback for participants.
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

		// init parent
		parent::__construct(

			// base ID
			'civicrm_eo_participant_feedback_widget',

			// name
			__( 'Participant Feedback', 'civicrm-eo-attendance' ),

			// args
			array(
				'description' => __( 'Use this widget to show a list of feedback for participants.', 'civicrm-eo-attendance' ),
			)

		);

	}



	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 0.4.6
	 *
	 * @param array $args An array of standard parameters for widgets in this theme
	 * @param array $instance An array of settings for this widget instance
	 */
	public function widget( $args, $instance ) {

		// get filtered title
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		// show widget prefix
		echo ( isset( $args['before_widget'] ) ? $args['before_widget'] : '' );

		// show title if there is one
		if ( ! empty( $title ) ) {
			echo ( isset( $args['before_title'] ) ? $args['before_title'] : '' );
			echo $title;
			echo ( isset( $args['after_title'] ) ? $args['after_title'] : '' );
		}

		// set default max items if absent
		if ( empty( $instance['max_items'] ) OR ! is_numeric( $instance['max_items'] ) ) {
			$instance['max_items'] = 5;
		}

		// get list
		$links = $this->list_populate();

		// show them if we have any
		if ( ! empty( $links ) ) {

			// combine into list
			$list = implode( '</li>' . "\n" . '<li class="civicrm-eo-cdp-widget">', $links );

			// top and tail
			$list = '<li class="civicrm-eo-cdp-widget">' . $list . '</li>' . "\n";

		} else {

			// show something
			$list = '<li class="civicrm-eo-cdp-widget cdp-up-to-date">' .
						__( 'You are up-to-date with your feedback.', 'civicrm-eo-attendance' ) .
					'</li>' . "\n";

		}

		// wrap in unordered list
		echo '<ul class="civicrm-eo-cdp-widget">' . $list . '</ul>';

		// show widget suffix
		echo ( isset( $args['after_widget'] ) ? $args['after_widget'] : '' );

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

		// get title
		if ( isset( $instance['title'] ) ) {
			$title = strip_tags( $instance['title'] );
		} else {
			$title = __( 'Participant Feedback Required', 'civicrm-eo-attendance' );
		}

		// get max items
		if ( isset( $instance['max_items'] ) ) {
			$max_items = strip_tags( $instance['max_items'] );
		} else {
			$max_items = 5;
		}

		?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'civicrm-eo-attendance' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></label>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'max_items' ); ?>"><?php _e( 'Max number to show:', 'civicrm-eo-attendance' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_items' ); ?>" name="<?php echo $this->get_field_name( 'max_items' ); ?>" type="text" value="<?php echo esc_attr( $max_items ); ?>" style="width: 30%" /></label>
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

		// never lose a value
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

		// access plugins
		global $civicrm_wp_event_organiser, $civicrm_eo_attendance;

		// let's alias the CDP object
		$cdp = $civicrm_eo_attendance->custom_data_participant;

		// init return
		$links = array();

		// bail if no CiviCRM init function
		if ( ! function_exists( 'civi_wp' ) ) return $links;

		// try and init CiviCRM
		if ( ! civi_wp()->initialize() ) return $links;

		// get current user
		$current_user = wp_get_current_user();

		// get user matching file
		require_once 'CRM/Core/BAO/UFMatch.php';

		// get the CiviCRM contact ID
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $current_user->ID );

		// get all participant custom data for this contact
		$participants = $cdp->participants_get( $contact_id );

		// bail if we didn't get any
		if ( empty( $participants ) ) return $links;

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'participants' => $participants,
			'backtrace' => $trace,
		), true ) );
		*/

		// loop through them
		foreach( $participants AS $participant ) {

			// skip to next if it is not a past event
			if ( ! $this->is_past( $participant['event_end_date'] ) ) continue;

			// skip if this participant already has data
			if ( $cdp->participant_has_data( $participant ) ) continue;

			/*
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'participant' => $participant,
				'backtrace' => $trace,
			), true ) );
			*/

			// alias event ID
			$event_id = $participant['event_id'];

			// get EO post ID
			$post_id = $civicrm_wp_event_organiser->db->get_eo_event_id_by_civi_event_id( $event_id );

			// get occurrence ID for this CiviEvent
			$occurrence_id = $civicrm_wp_event_organiser->db->get_eo_occurrence_id_by_civi_event_id( $event_id );

			/*
			 * If there are CiviEvents for which the EO Event has been deleted
			 * although the correspondence for the CiviEvent remains (this can
			 * happen because the delete code did not function properly until
			 * https://github.com/christianwach/civicrm-event-organiser/commit/d1baf0741e59d6884f84af2d7bf05c50f14cb9e2
			 * fixed the issue) then it is possible that the EO event does not
			 * exist. We need to protect against this and log the problem so
			 * the the data can be fixed. This means going in to CiviCRM and
			 * deleting the Participants for the CiviEvent, then deleting the
			 * CiviEvent itself.
			 */

			// get EO event
			$eo_event = get_post( $post_id );

			// if it's not there
			if ( ! ( $eo_event instanceof WP_Post ) ) {

				// write to log file
				$e = new Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'message' => '=============== EO Event is missing ===============',
					'post_id' => $post_id,
					'occurrence_id' => $occurrence_id,
					'participant' => $participant,
					'event_data' => $event_data,
					'backtrace' => $trace,
				), true ) );

				// skip to next
				continue;

			}

			// let's have a title
			$text = '<span class="civicrm-eo-cdp-widget-event-title">' . $participant['event_title'] . '</span>';
			$text .= '<br>';
			$text .= eo_format_event_occurrence( $post_id, $occurrence_id );

			// construct custom class name
			$class = 'civicrm-eo-cdp-widget-participant-id-' . $participant['id'];

			// construct span if we get one
			$span = '<span class="civicrm-eo-cdp-widget ' . $class . '">' . $text . '</span>';

			// wrap in link
			$span = '<a href="' . get_permalink( $post_id ) . '">' . $span . '</a>';

			// add form toggle
			$span .= '<span id="civicrm-eo-cdp-feedback-' . $participant['id'] . '" class="civicrm-eo-feedback civicrm-eo-cdp-feedback">' .
				__( 'Leave feedback', 'civicrm-eo-attendance' ) .
			'</span>';

			// add form
			$span .= $this->get_form( $participant );

			/**
			 * Filter event custom data element.
			 *
			 * @since 0.4.6
			 *
			 * @param string $span The HTML element
			 * @param string $text The text content of the element
			 * @param int $post_id The numeric ID of the WP post
			 */
			$links[] = apply_filters( 'civicrm_event_organiser_cdp_widget_element', $span, $text, $post_id );

		}

		// enqueue Javascript
		$this->enqueue_scripts();

		// --<
		return $links;

	}



	/**
	 * Check if an event is a past event.
	 *
	 * @since 0.4.6
	 *
	 * @param str $end_date The end date string for a CiviEvent
	 * @return bool $past_event True if the event is past, false otherwise
	 */
	public function is_past( $end_date ) {

		// init past event flag
		$past_event = false;

		// override if it's a past event
		$now = new DateTime( 'now', eo_get_blog_timezone() );
		$end = new DateTime( $end_date, eo_get_blog_timezone() );
		if ( $end < $now ) {
			$past_event = true;
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'end_date' => $end_date,
			'past_event' => $past_event ? 'yes' : 'no',
			'backtrace' => $trace,
		), true ) );
		*/

		// --<
		return $past_event;

	}



	/**
	 * Get the event form.
	 *
	 * @since 0.5.2
	 *
	 * @param array $participant The Participant data.
	 * @return str $form The form markup.
	 */
	public function get_form( $participant ) {

		// populate template vars
		$participant_id = $participant['id'];

		// find elapsed time
		$start = new DateTime( $participant['event_start_date'], eo_get_blog_timezone() );
		$end = new DateTime( $participant['event_end_date'], eo_get_blog_timezone() );
		$interval = $start->diff( $end );
		$elapsed = $interval->format( '%h:%i' );

		// grab hours and minutes
		$elements = explode( ':', $elapsed );
		$hours = isset( $elements[0] ) ? absint( $elements[0] ) : 0;
		$minutes = isset( $elements[1] ) ? absint( $elements[1] ) : 0;

		// format hours when zero
		if ( $hours === 0 ) {
			$hours = '0';
		}

		// format minutes when zero
		if ( $minutes === 0 ) {
			$minutes = '00';
		}

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'POST' => $_POST,
			'participant' => $participant,
			'elapsed' => $elapsed,
			'hours' => $hours,
			'minutes' => $minutes,
			'backtrace' => $trace,
		), true ) );
		*/

		// start buffering
		ob_start();

		// include template file
		include( CIVICRM_EO_ATTENDANCE_PATH . 'assets/templates/custom-data-participant/event-form.php' );

		// save the output and flush the buffer
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

		// add script to footer
		wp_enqueue_script(
			'civicrm-eo-attendance-cdp-widget',
			CIVICRM_EO_ATTENDANCE_URL . 'assets/js/civicrm-eo-attendance-cdp-widget.js',
			array( 'jquery' ),
			CIVICRM_EO_ATTENDANCE_VERSION,
			true // in footer
		);

		// translations
		$localisation = array(
			'processing' => __( 'Processing...', 'civicrm-eo-attendance' ),
			'empty' => __( 'You must enter values for each field.', 'civicrm-eo-attendance' ),
			'numeric' => __( 'You must enter numeric values for each field.', 'civicrm-eo-attendance' ),
			'integer' => __( 'You must enter whole numbers in each field.', 'civicrm-eo-attendance' ),
			'negative' => __( 'You must enter positive values for each field.', 'civicrm-eo-attendance' ),
			'zero' => __( 'Really? You worked no time at all?', 'civicrm-eo-attendance' ),
			'mins' => __( 'The number of minutes must be less than 60.', 'civicrm-eo-attendance' ),
			'complete' => __( 'You are up-to-date with your feedback.', 'civicrm-eo-attendance' ),
		);

		// define settings
		$settings = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);

		// localisation array
		$vars = array(
			'localisation' => $localisation,
			'settings' => $settings,
		);

		// localise the WordPress way
		wp_localize_script(
			'civicrm-eo-attendance-cdp-widget',
			'CiviCRM_EO_Attendance_CDP_Widget_Settings',
			$vars
		);

	}



} // ends class CiviCRM_EO_Attendance_CDP_Widget



