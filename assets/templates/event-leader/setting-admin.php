<!-- assets/templates/event-leader/setting-admin.php -->
<tr valign="top">
	<th scope="row"><label for="civicrm_eo_event_leader_role"><?php _e( 'Default Event Leader Role', 'civicrm-eo-attendance' ); ?></label></th>
	<td>
		<select id="civicrm_eo_event_leader_role" name="civicrm_eo_event_leader_role">
			<?php echo $roles; ?>
		</select>
		<p class="description"><?php _e( 'The event leader role is responsible for providing feedback on an event.', 'civicrm-eo-attendance' ); ?></p>
	</td>
</tr>

