<?php
/**
 * Event Attendance Form template.
 *
 * Handles markup for the Event Attendance Form.
 *
 * @since 0.4.6
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/event-attendance/attendance-form.php -->
<div class="civicrm_eo_rvm" id="civicrm_eo_rvm_<?php echo esc_attr( $civi_event_id ); ?>">
	<div class="civicrm_eo_rvm_inner">

		<input type="hidden" id="civicrm_eo_rvm_<?php echo esc_attr( $civi_event_id ); ?>_leader" value="<?php echo esc_attr( $event_leader_role ); ?>">

		<p class="description"><?php esc_html_e( 'The following people have indicated that they are available for this Event. Choose which people to register for the Event and select their role.', 'civicrm-eo-attendance' ); ?></p>

		<ul>

			<?php if ( ! empty( $attendee_ids ) ) : ?>
				<?php foreach ( $attendee_ids as $attendee_id ) : ?>
					<li class="civicrm_eo_rvm_attendee_<?php echo esc_attr( $attendee_id ); ?>">
						<input type="checkbox" name="civicrm_eo_rvm_event_<?php echo esc_attr( $civi_event_id ); ?>_attendee_<?php echo esc_attr( $attendee_id ); ?>" id="civicrm_eo_rvm_event_<?php echo esc_attr( $civi_event_id ); ?>_attendee_<?php echo esc_attr( $attendee_id ); ?>" class="civicrm-eo-form-checkbox" value="1" <?php checked( $checked[ $attendee_id ] ); ?>>
						<label for="civicrm_eo_rvm_event_<?php echo esc_attr( $civi_event_id ); ?>_attendee_<?php echo esc_attr( $attendee_id ); ?>"><?php echo esc_html( bp_core_get_user_displayname( $attendee_id ) ); ?></label>
						<select name="civicrm_eo_rvm_role_event_<?php echo esc_attr( $civi_event_id ); ?>_attendee_<?php echo esc_attr( $attendee_id ); ?>" id="civicrm_eo_rvm_role_event_<?php echo esc_attr( $civi_event_id ); ?>_attendee_<?php echo esc_attr( $attendee_id ); ?>" class="civicrm-eo-form-select">
							<?php echo $select[ $attendee_id ]; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
						</select>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>

		</ul>

		<div class="civicrm_eo_rvm_error" id="civicrm_eo_rvm_error_<?php echo esc_attr( $civi_event_id ); ?>"></div>
		<div class="civicrm_eo_rvm_success" id="civicrm_eo_rvm_success_<?php echo esc_attr( $civi_event_id ); ?>"></div>

		<p class="submit-buttons">
			<button type="button" name="civicrm_eo_rvm_submit_<?php echo esc_attr( $civi_event_id ); ?>" id="civicrm_eo_rvm_submit_<?php echo esc_attr( $civi_event_id ); ?>" class="civicrm_eo_rvm_submit"><?php echo esc_html( $submit_label ); ?></button>
			<button type="button" id="civicrm_eo_rvm_cancel_<?php echo esc_attr( $civi_event_id ); ?>" class="civicrm_eo_rvm_cancel"><?php esc_html_e( 'Close', 'civicrm-eo-attendance' ); ?></button>
		</p>

	</div>
</div>
