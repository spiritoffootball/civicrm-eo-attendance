<?php
/**
 * Participant Listing Settings Metabox template.
 *
 * Handles markup for the Participant Listing Settings Metabox.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/participant-listing/setting-metabox.php -->
<div class="civi_eo_event_option_block">
	<p>
		<label for="civicrm_eo_event_listing"><?php esc_html_e( 'Participant Listing Profile:', 'civicrm-eo-attendance' ); ?></label>
		<select id="civicrm_eo_event_listing" name="civicrm_eo_event_listing">
			<?php echo $profiles; ?>
		</select>
	</p>

	<p class="description">
		<?php esc_html_e( 'The profile shown on the Participant Listing page. Select "Disabled" if you do not want Participants to be shown.', 'civicrm-eo-attendance' ); ?>
	</p>
</div>
