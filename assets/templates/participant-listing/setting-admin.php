<?php
/**
 * Participant Listing Admin Settings template.
 *
 * Handles markup for the Participant Listing Admin Settings.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/participant-listing/setting-admin.php -->
<tr valign="top">
	<th scope="row"><label for="civicrm_eo_event_default_listing"><?php esc_html_e( 'Default CiviCRM Participant Listing Profile', 'civicrm-eo-attendance' ); ?></label></th>
	<td>
		<select id="civicrm_eo_event_default_listing" name="civicrm_eo_event_default_listing">
			<?php echo $profiles; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Select "Disabled" if you prefer to set the visibility of Participant Listings manually on a per-CiviEvent basis.', 'civicrm-eo-attendance' ); ?></p>
	</td>
</tr>
