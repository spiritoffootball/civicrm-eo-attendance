<!-- assets/templates/participant-listing/setting-admin.php -->
<tr valign="top">
	<th scope="row"><label for="civicrm_eo_event_default_listing"><?php _e( 'Default CiviCRM Participant Listing Profile', 'civicrm-eo-attendance' ); ?></label></th>
	<td>
		<select id="civicrm_eo_event_default_listing" name="civicrm_eo_event_default_listing">
			<?php echo $profiles; ?>
		</select>
		<p class="description"><?php _e( 'Select "Disabled" if you prefer to set the visibility of Participant Listings manually on a per-CiviEvent basis.', 'civicrm-eo-attendance' ); ?></p>
	</td>
</tr>

