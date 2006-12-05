<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//
//  Written for CodeX by Stephane Bouhet
//

require_once('pre.php');
require_once('common/include/GroupFactory.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactCanned.class');
require_once('common/tracker/ArtifactFieldFactory.class');
require_once('common/tracker/ArtifactField.class');
require_once('common/tracker/ArtifactFieldSetFactory.class');
require_once('common/tracker/ArtifactFieldSet.class');
require_once('common/tracker/ArtifactReport.class');
require_once('common/tracker/ArtifactReportFactory.class');
require_once('common/tracker/ArtifactReportField.class');
require_once('common/tracker/Artifact.class');
require_once('common/tracker/ArtifactRuleFactory.class');
require('../include/ArtifactTypeHtml.class');
require('../include/ArtifactCannedHtml.class');
require('../include/ArtifactReportHtml.class');
require('../include/ArtifactHtml.class');

$Language->loadLanguageMsg('tracker/tracker');

session_require(array('group'=>'1','admin_flags'=>'A'));


	switch ( $func ) {
	case 'restore':
	        $group = group_get_object($group_id);	
		$ath =  new ArtifactType($group, $atid);
		if (!$ath->restore()) {
		  $feedback = $Language->getText('tracker_admin_restore','restore_failed');
		} else {
		  $feedback = $Language->getText('tracker_admin_restore','tracker_restored');
		}
		break;
		
	case 'delay':
	        $group = group_get_object($group_id);	
		$ath =  new ArtifactType($group, $atid);
		// just check date >= today

		if (!$ath->delay($delay_date)) {
		  if ($ath->isError())
		    exit_error($Language->getText('global','error'),$ath->getErrorMessage()." | ".$Language->getText('tracker_admin_restore','delay_failed'));
		  exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_restore','delay_failed'));
		} else {
		  $feedback = $Language->getText('tracker_admin_restore','delayed_deletion');
		}
		break;

		
	case 'delete':
        // Create field factory
        $group = group_get_object($group_id);	
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
		break;


	default:  
	  break;
	} // switch
$group = group_get_object(1);	
$ath = new ArtifactTypeHtml($group);

$HTML->header(array('title'=>$Language->getText('tracker_admin_restore','pending_deletions')));
$atf = new ArtifactTypeFactory($group);
$ath->displayPendingTrackers();
$HTML->footer(array());

?>
