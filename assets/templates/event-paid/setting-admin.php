<!-- assets/templates/event-paid/setting-admin.php -->
<tr valign="top">
	<th scope="row"><label for="civicrm_eo_event_default_paid"><?php _e( 'Are Events to be Paid Events by default?', 'civicrm-eo-attendance' ); ?></label></th>
	<td>
		<input type="checkbox" id="civicrm_eo_event_default_paid" name="civicrm_eo_event_default_paid" value="1"<?php echo $paid_checked; ?> />
		<p class="description"><?php _e( 'Check the box if you want events to be paid events. If most events are not paid, leave the box unchecked.', 'civicrm-eo-attendance' ); ?></p>
	</td>
</tr>

