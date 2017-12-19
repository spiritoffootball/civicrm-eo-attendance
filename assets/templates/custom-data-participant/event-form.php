<!-- assets/templates/custom-data-participant/event-form.php -->
<form class="civicrm_eo_cdp" id="civicrm_eo_cdp_<?php echo $participant_id; ?>">

	<input type="hidden" name="civicrm_eo_cdp_participant_id" id="civicrm_eo_cdp_participant_id" value="<?php echo $participant_id; ?>">

	<p class="description"><?php _e( 'How much time did you spend working on this event?', 'civicrm-eo-attendance' ); ?></p>

	<ul>

		<li>
			<label for="civicrm_eo_cdp_hours_<?php echo $participant_id; ?>"><?php _e( 'Hours', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="4" name="civicrm_eo_cdp_hours_<?php echo $participant_id; ?>" id="civicrm_eo_cdp_hours_<?php echo $participant_id; ?>" class="civicrm-eo-form-text" value="<?php echo $hours; ?>">
		</li>

		<li>
			<label for="civicrm_eo_cdp_minutes_<?php echo $participant_id; ?>"><?php _e( 'Minutes', 'civicrm-eo-attendance' ); ?></label>
			<input type="text" maxlength="2" name="civicrm_eo_cdp_minutes_<?php echo $participant_id; ?>" id="civicrm_eo_cdp_minutes_<?php echo $participant_id; ?>" class="civicrm-eo-form-text" value="<?php echo $minutes; ?>">
		</li>

	</ul>

	<div class="civicrm_eo_cdp_error_<?php echo $participant_id; ?>"></div>

	<p class="submit">
		<input type="submit" name="civicrm_eo_cdp_submit_<?php echo $participant_id; ?>" id="civicrm_eo_cdp_submit_<?php echo $participant_id; ?>" value="<?php _e( 'Submit', 'civicrm-eo-attendance' ); ?>">
	</p>

</form>

