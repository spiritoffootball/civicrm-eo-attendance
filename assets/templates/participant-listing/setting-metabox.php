<!-- assets/templates/participant-listing/setting-metabox.php -->
<div class="civi_eo_event_option_block">
	<p>
		<label for="civicrm_eo_event_listing"><?php _e( 'Participant Listing Profile:', 'civicrm-eo-attendance' ); ?></label>
		<select id="civicrm_eo_event_listing" name="civicrm_eo_event_listing">
			<?php echo $profiles; ?>
		</select>
	</p>

	<p class="description">
		<?php _e( 'The profile shown on the Participant Listing page. Select "Disabled" if you do not want participants to be shown.', 'civicrm-eo-attendance' ); ?>
	</p>
</div>
