<?php
/**
 * Paid Event Settings Metabox template.
 *
 * Handles markup for the Paid Event Settings Metabox.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/event-paid/setting-metabox.php -->
<div class="civi_eo_event_option_block">
	<p>
		<label for="civicrm_eo_event_default_paid"><?php esc_html_e( 'Paid Event:', 'civicrm-eo-attendance' ); ?></label>
		<input type="checkbox" id="civicrm_eo_event_default_paid" name="civicrm_eo_event_default_paid" value="1"<?php checked( $paid_checked ); ?> />
	</p>

	<p class="description">
		<?php esc_html_e( 'Check this box if this is a paid Event.', 'civicrm-eo-attendance' ); ?>
	</p>
</div>
