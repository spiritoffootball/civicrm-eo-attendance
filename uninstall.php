<?php
/**
 * Uninstaller.
 *
 * @since 0.1
 * @package CiviCRM_Event_Organiser_Attendance
 */

// Kick out if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Access plugin.
global $civicrm_wp_event_organiser;

// Delete Custom Group and Custom Fields for Event.
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_custom_group_id' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_custom_field_ids' );

// Delete Custom Group and Custom Fields for Participant.
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_participant_custom_group_id' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_participant_custom_field_ids' );

// Delete default settings for Events.
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_leader_role' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_default_sharing' );
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_default_listing' );

// Delete Rendez Vous Term ID option.
$civicrm_wp_event_organiser->db->option_delete( 'civicrm_eo_event_rv_term_id' );

/**
 * This procedure leaves the CiviCRM Custom Fields intact.
 *
 * Should delete the Custom Fields and data in CiviCRM as well? My current
 * opinion is not to delete them in case they contain data that needs to be
 * retained for some reason.
 *
 * The trouble with leaving this in place is that if the plugin is reactivated
 * then the tables will still exist and the method that creates the entities
 * will fail, leaving us with no means for discovering the IDs of the Groups
 * and Fields.
 *
 * Hmm.
 */
