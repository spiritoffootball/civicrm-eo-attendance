<!-- assets/templates/custom-data-event/event-form.php -->
<form class="civicrm_eo_cde" id="civicrm_eo_cde_<?php echo $event_id; ?>">

	<input type="hidden" name="civicrm_eo_cde_event_id" id="civicrm_eo_cde_event_id" value="<?php echo $event_id; ?>">

	<p class="description"><?php _e( 'Please fill out some figures for this event.', 'civicrm-eo-attendance' ); ?></p>

	<ul>

		<li>
			<label for="civicrm_eo_cde_total_<?php echo $event_id; ?>"><?php _e( 'Number of Attendees', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="6" name="civicrm_eo_cde_total_<?php echo $event_id; ?>" id="civicrm_eo_cde_total_<?php echo $event_id; ?>" class="civicrm-eo-form-text">
		</li>

		<li>
			<label for="civicrm_eo_cde_boys_<?php echo $event_id; ?>"><?php _e( 'Number of Boys', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="6" name="civicrm_eo_cde_boys_<?php echo $event_id; ?>" id="civicrm_eo_cde_boys_<?php echo $event_id; ?>" class="civicrm-eo-form-text">
		</li>

		<li>
			<label for="civicrm_eo_cde_girls_<?php echo $event_id; ?>"><?php _e( 'Number of Girls', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="6" name="civicrm_eo_cde_girls_<?php echo $event_id; ?>" id="civicrm_eo_cde_girls_<?php echo $event_id; ?>" class="civicrm-eo-form-text">
		</li>

		<li>
			<label for="civicrm_eo_cde_low_<?php echo $event_id; ?>"><?php _e( 'Age (Youngest)', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="3" name="civicrm_eo_cde_low_<?php echo $event_id; ?>" id="civicrm_eo_cde_low_<?php echo $event_id; ?>" class="civicrm-eo-form-text">
		</li>

		<li>
			<label for="civicrm_eo_cde_high_<?php echo $event_id; ?>"><?php _e( 'Age (Oldest)', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="3" name="civicrm_eo_cde_high_<?php echo $event_id; ?>" id="civicrm_eo_cde_high_<?php echo $event_id; ?>" class="civicrm-eo-form-text">
		</li>

	</ul>

	<div class="civicrm_eo_cde_error_<?php echo $event_id; ?>"></div>

	<p class="submit">
		<input type="submit" name="civicrm_eo_cde_submit_<?php echo $event_id; ?>" id="civicrm_eo_cde_submit_<?php echo $event_id; ?>" value="<?php _e( 'Submit', 'civicrm-eo-attendance' ); ?>">
	</p>

</form>

