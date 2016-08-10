<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//
//  Written for Codendi by Stephane Bouhet
//

require_once('pre.php');
require_once('common/include/GroupFactory.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactCanned.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('common/tracker/ArtifactField.class.php');
require_once('common/tracker/ArtifactFieldSetFactory.class.php');
require_once('common/tracker/ArtifactFieldSet.class.php');
require_once('common/tracker/ArtifactReport.class.php');
require_once('common/tracker/ArtifactReportFactory.class.php');
require_once('common/tracker/ArtifactReportField.class.php');
require_once('common/tracker/Artifact.class.php');
require_once('common/tracker/ArtifactRuleFactory.class.php');
require_once('../include/ArtifactTypeHtml.class.php');
require_once('../include/ArtifactCannedHtml.class.php');
require_once('../include/ArtifactReportHtml.class.php');
require_once('../include/ArtifactHtml.class.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

$pm = ProjectManager::instance();
$request = HTTPRequest::instance();
$func = $request->get('func');
	switch ( $func ) {
	case 'restore':
	    if ($group = $pm->getProject($request->getValidated('group_id', 'GroupId'))) {
            $ath =  new ArtifactType($group, $atid);
            if (!$ath->restore()) {
              $feedback = $Language->getText('tracker_admin_restore','restore_failed');
            } else {
              $feedback = $Language->getText('tracker_admin_restore','tracker_restored');
            }
        }
		break;

	case 'delay':
	    if ($group = $pm->getProject($request->getValidated('group_id', 'GroupId'))) {
            $ath =  new ArtifactType($group, $request->getValidated('atid', 'uint'));
            // just check date >= today

            if (!$ath->delay($delay_date)) {
              if ($ath->isError())
                exit_error($Language->getText('global','error'),$ath->getErrorMessage()." | ".$Language->getText('tracker_admin_restore','delay_failed'));
              exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_restore','delay_failed'));
            } else {
              $feedback = $Language->getText('tracker_admin_restore','delayed_deletion');
            }
        }
		break;


	case 'delete':
        // Create field factory
        if ($group = $pm->getProject($request->getValidated('group_id', 'GroupId'))) {
            $atid = $request->getValidated('atid', 'uint');
            $ath =  new ArtifactType($group, $atid);
            $atf = new ArtifactTypeFactory($group);
            $art_field_fact = new ArtifactFieldFactory($ath);

            // Then delete all the fields informations
            if ( !$art_field_fact->deleteFields($atid) ) {
                exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
                return false;
            }

            // Then delete all the reports informations
            // Create field factory
            $art_report_fact = new ArtifactReportFactory();

            if ( !$art_report_fact->deleteReports($atid) ) {
                exit_error($Language->getText('global','error'),$art_report_fact->getErrorMessage());
                return false;
            }

            // Delete the artifact type itself
            if ( !$atf->deleteArtifactType($atid) ) {
                exit_error($Language->getText('global','error'),$atf->getErrorMessage());
            }
            $feedback = $Language->getText('tracker_admin_restore','tracker_deleted');
        }
		break;


	default:
	  break;
	} // switch
$group = $pm->getProject(1);
$ath = new ArtifactTypeHtml($group);
$HTML->includeCalendarScripts();
$HTML->header(array('title'=>$Language->getText('tracker_admin_restore','pending_deletions'), 'main_classes' => array('tlp-framed')));
EventManager::instance()->processEvent(
    Event::LIST_DELETED_TRACKERS,
    array()
);
if (TrackerV3::instance()->available()) {
    $atf = new ArtifactTypeFactory($group);
    $ath->displayPendingTrackers();
}
$HTML->footer(array());

?>
