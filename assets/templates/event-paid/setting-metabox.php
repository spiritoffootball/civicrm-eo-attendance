<!-- assets/templates/event-paid/setting-metabox.php -->
<div class="civi_eo_event_option_block">
	<p>
		<label for="civicrm_eo_event_default_paid"><?php _e( 'Paid Event:', 'civicrm-eo-attendance' ); ?></label>
		<input type="checkbox" id="civicrm_eo_event_default_paid" name="civicrm_eo_event_default_paid" value="1"<?php echo $paid_checked; ?> />
	</p>

	<p class="description">
		<?php _e( 'Check this box if this is a paid event.', 'civicrm-eo-attendance' ); ?>
	</p>
</div>
