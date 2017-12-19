<!-- assets/templates/event-leader/setting-metabox.php -->
<hr />

<p>
<label for="civicrm_eo_event_leader_role"><?php _e( 'Event Leader Role:', 'civicrm-eo-attendance' ); ?></label>
<select id="civicrm_eo_event_leader_role" name="civicrm_eo_event_leader_role">
	<?php echo $roles; ?>
</select>
</p>

<p class="description"><?php _e( 'The event leader role is responsible for providing feedback for this event.', 'civicrm-eo-attendance' ); ?></p>
