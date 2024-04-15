<?php
/**
 * Event Sharing Admin Settings template.
 *
 * Handles markup for the Event Sharing Admin Settings.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/event-sharing/setting-admin.php -->
<tr valign="top">
	<th scope="row"><label for="civicrm_eo_event_default_sharing"><?php esc_html_e( 'Default CiviEvent Sharing Panel visibility', 'civicrm-eo-attendance' ); ?></label></th>
	<td>
		<input type="checkbox" id="civicrm_eo_event_default_sharing" name="civicrm_eo_event_default_sharing" value="1" <?php checked( $sharing_checked ); ?> />
	</td>
</tr>

