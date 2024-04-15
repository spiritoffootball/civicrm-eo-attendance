<?php
/**
 * Event Leader Settings Metabox template.
 *
 * Handles markup for the Event Leader Settings Metabox.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/event-leader/setting-metabox.php -->
<div class="civi_eo_event_option_block">
	<p>
		<label for="civicrm_eo_event_leader_role"><?php esc_html_e( 'Event Leader Role:', 'civicrm-eo-attendance' ); ?></label>
		<select id="civicrm_eo_event_leader_role" name="civicrm_eo_event_leader_role">
			<?php echo $roles; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		</select>
	</p>

	<p class="description">
		<?php esc_html_e( 'The Event Leader Role is responsible for providing feedback for this Event.', 'civicrm-eo-attendance' ); ?>
	</p>
</div>
