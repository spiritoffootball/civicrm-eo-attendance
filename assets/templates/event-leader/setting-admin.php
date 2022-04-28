<?php
/**
 * Event Leader Admin Settings template.
 *
 * Handles markup for the Event Leader Admin Settings.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/event-leader/setting-admin.php -->
<tr valign="top">
	<th scope="row"><label for="civicrm_eo_event_leader_role"><?php esc_html_e( 'Default Event Leader Role', 'civicrm-eo-attendance' ); ?></label></th>
	<td>
		<select id="civicrm_eo_event_leader_role" name="civicrm_eo_event_leader_role">
			<?php echo $roles; ?>
		</select>
		<p class="description"><?php esc_html_e( 'The Event Leader Role is responsible for providing feedback on an Event.', 'civicrm-eo-attendance' ); ?></p>
	</td>
</tr>
