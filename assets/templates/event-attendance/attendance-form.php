<!-- assets/templates/event-attendance/attendance-form.php -->
<div class="civicrm_eo_rvm" id="civicrm_eo_rvm_<?php echo $civi_event_id; ?>">
	<div class="civicrm_eo_rvm_inner">

		<input type="hidden" id="civicrm_eo_rvm_<?php echo $civi_event_id; ?>_leader" value="<?php echo $event_leader_role; ?>">

		<p class="description"><?php _e( 'The following people have indicated that they are available for this event. Choose which people to register for the event and select their role.', 'civicrm-eo-attendance' ); ?></p>

		<ul>

			<?php if ( ! empty( $attendee_ids ) ) : ?>
				<?php foreach( $attendee_ids AS $attendee_id ) : ?>
					<li class="civicrm_eo_rvm_attendee_<?php echo $attendee_id; ?>">
						<input type="checkbox" name="civicrm_eo_rvm_event_<?php echo $civi_event_id; ?>_attendee_<?php echo $attendee_id; ?>" id="civicrm_eo_rvm_event_<?php echo $civi_event_id; ?>_attendee_<?php echo $attendee_id; ?>" class="civicrm-eo-form-checkbox" value="1"<?php echo $checked[$attendee_id]; ?>>
						<label for="civicrm_eo_rvm_event_<?php echo $civi_event_id; ?>_attendee_<?php echo $attendee_id; ?>"><?php echo bp_core_get_user_displayname( $attendee_id ); ?></label>
						<select name="civicrm_eo_rvm_role_event_<?php echo $civi_event_id; ?>_attendee_<?php echo $attendee_id; ?>" id="civicrm_eo_rvm_role_event_<?php echo $civi_event_id; ?>_attendee_<?php echo $attendee_id; ?>" class="civicrm-eo-form-select">
							<?php echo $select[$attendee_id]; ?>
						</select>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>

		</ul>

		<div class="civicrm_eo_rvm_error" id="civicrm_eo_rvm_error_<?php echo $civi_event_id; ?>"></div>
		<div class="civicrm_eo_rvm_success" id="civicrm_eo_rvm_success_<?php echo $civi_event_id; ?>"></div>

		<p class="submit-buttons">
			<button type="button" name="civicrm_eo_rvm_submit_<?php echo $civi_event_id; ?>" id="civicrm_eo_rvm_submit_<?php echo $civi_event_id; ?>" class="civicrm_eo_rvm_submit"><?php echo $submit_label; ?></button>
			<button type="button" id="civicrm_eo_rvm_cancel_<?php echo $civi_event_id; ?>" class="civicrm_eo_rvm_cancel"><?php _e( 'Close', 'civicrm-eo-attendance' ); ?></button>
		</p>

	</div>
</div>

