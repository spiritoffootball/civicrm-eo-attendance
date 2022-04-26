<?php
/**
 * Event Sharing Settings Metabox template.
 *
 * Handles markup for the Event Sharing Settings Metabox.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/event-sharing/setting-metabox.php -->
<div class="civi_eo_event_option_block">
	<p>
		<label for="civicrm_eo_event_default_sharing"><?php esc_html_e( 'Display Sharing Panel:', 'civicrm-eo-attendance' ); ?></label>
		<input type="checkbox" id="civicrm_eo_event_default_sharing" name="civicrm_eo_event_default_sharing" value="1"<?php echo $sharing_checked; ?> />
	</p>

	<p class="description">
		<?php esc_html_e( 'Check this to allow CiviCRM to show the CiviEvent Sharing Panel.', 'civicrm-eo-attendance' ); ?>
	</p>
</div>
