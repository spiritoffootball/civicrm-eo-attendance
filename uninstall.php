<?php /*
================================================================================
CiviCRM Event Organiser Attendance Uninstaller
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====


--------------------------------------------------------------------------------
*/



// kick out if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();



// access plugin
global $civicrm_wp_event_organiser;

// delete custom group and fields for event
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_custom_group_id' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_custom_field_ids' );

// delete custom group and fields for participant
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_participant_custom_group_id' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_participant_custom_field_ids' );

// delete default settings for events
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_leader_role' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_default_sharing' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_default_listing' );

// delete Rendez Vous term ID option
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_rv_term_id' );



/**
 * This procedure leaves the CiviCRM custom fields intact.
 *
 * Should delete the custom fields and data in CiviCRM as well? My current
 * opinion is not to delete them in case they contain data that needs to be
 * retained for some reason.
 *
 * The trouble with leaving this in place is that if the plugin is reactivated
 * then the tables will still exist and the method that creates the entities
 * will fail, leaving us with no means for discovering the IDs of the groups
 * and fields.
 *
 * Hmm.
 */



