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

require('pre.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactCanned.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReport.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReportField.class');
require($DOCUMENT_ROOT.'/../common/tracker/Artifact.class');
require('../include/ArtifactTypeHtml.class');
require('../include/ArtifactCannedHtml.class');
require('../include/ArtifactReportHtml.class');
require('../include/ArtifactHtml.class');


//  echo "gid, aid ".$group_id.", ".$atid;

if ($group_id && !$atid) {
	//
	// Manage trackers: create and delete
	
	//
	//	get the Group object
	//
	$group = group_get_object($group_id);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_no_group();
	}
	//
	//	Create the ArtifactType object
	//
	$ath = new ArtifactTypeHtml($group);
	if (!$ath || !is_object($ath)) {
		exit_error('Error','ArtifactType could not be created');
	}
	if ($ath->isError()) {
		exit_error('Error',$ath->getErrorMessage());
	}

	$atf = new ArtifactTypeFactory($group);

	$ath->adminTrackersHeader(array('title'=>'Trackers Administration'));
	
	echo $ath->displayAdminTrackers();
	
	$ath->footer(array());
	
} else if ($group_id && $atid) {

	//
	// Manage trackers: create and delete
	
	//
	//	get the Group object
	//
	$group = group_get_object($group_id);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_no_group();
	}
	//
	//	Create the ArtifactType object
	//
	$ath = new ArtifactTypeHtml($group,$atid);
	if (!$ath || !is_object($ath)) {
		exit_error('Error','ArtifactType could not be created');
	}
	if ($ath->isError()) {
		exit_error('Error',$ath->getErrorMessage());
	}
	$ach = new ArtifactCannedHtml($ath);
	if (!$ach || !is_object($ach)) {
	  exit_error('Error','ArtifactCanned could not be created');
	}
	if ($ach->isError()) {
	  exit_error('Error',$ach->getErrorMessage());
	}

	switch ( $func ) {
	case 'permissions':
		include './browse_perm.php';
		break;
	case report:
	  $arh = new ArtifactReportHtml($report_id, $atid);
	  if (!$arh) {
	    exit_error('Error','ArtifactReport could not be retrieved :'.$arh->getErrorMessage());
	  }
	  if ($post_changes) {
	    // apply update or create in bd
	    if ($update_report) {
	      $updated = $arh->recreate(user_getid(), $rep_name, $rep_desc, $rep_scope);
	      if (!$updated) {
		if ($arh->isError())
		  exit_error('Error','ArtifactReport could not be updated: '.$arh->getErrorMessage());
		exit_error('Error','ArtifactReport could not be updated');
	      }
	      $feedback = "report definition updated";
	    } else {
	      $report_id = $arh->create(user_getid(), $rep_name, $rep_desc, $rep_scope);
	      if (!$report_id) {
		if ($arh->isError())
		    exit_error('Error','ArtifactReport could not be created:'.$arh->getErrorMessage());
		exit_error('Error','ArtifactReport could not be created');
	      };
	      $feedback = "new report created";
	    }

	    // now insert all the field entries in the artifact_report_field table
	    $aff = new ArtifactFieldFactory(&$ath);
	    $fields = $aff->getAllUsedFields();
	    while ( list($key, $field) = each($fields) ) {
	      	$cb_search = 'CBSRCH_'.$field->getName();
		$cb_report = 'CBREP_'.$field->getName();
		$tf_search = 'TFSRCH_'.$field->getName();
		$tf_report = 'TFREP_'.$field->getName();
		$tf_colwidth = 'TFCW_'.$field->getName();

		$value = $$tf_report;
		//echo "$tf_report : $value <br>";

		if ($$cb_search || $$cb_report || $$tf_search || $$tf_report) {
		  $cb_search_val = ($$cb_search ? '1':'0');
		  $cb_report_val = ($$cb_report ? '1':'0');
		  $tf_search_val = ($$tf_search ? '\''.$$tf_search.'\'' : 'NULL');
		  $tf_report_val = ($$tf_report ? '\''.$$tf_report.'\'' : 'NULL');
		  $tf_colwidth_val = ($$tf_colwidth? '\''.$$tf_colwidth.'\'' : 'NULL');
		  $arh->add_report_field($field->getName(),$cb_search_val,$cb_report_val,$tf_search_val,$tf_report_val,$tf_colwidth_val);
		}
	    }
	    $arh->fetchData($report_id);
	      
	  } else if ($delete_report) {
	    if ( ($arh->scope == 'P') && 
		 !user_ismember($group_id,'A')) {
	      exit_permission_denied();
	    }	    
	    $arh->delete();
	    $feedback = "report deleted";
	  }

	  if ($new_report) {
	    
	    $arh->createReportForm();
	  } else if ($show_report) {
	    $arh = new ArtifactReportHtml($report_id,$atid);
	    if ( ($arh->scope == 'P') && 
		 !user_ismember($group_id,'A')) {
	      exit_permission_denied();
	    }
	    $arh->showReportForm();
	  } else {
	    // Front page
	    $arh = new ArtifactReportHtml(0, $atid);
	    $reports = $arh->getReports($atid, user_getid());
	    $arh->showAvailableReports($reports);
	  }
	  $ath->footer(array());
	break;
        case 'canned':
	        if ($post_changes) {
		  if ($create_canned) {
		    $aci = $ach->create($title, $body);
		    if (!$aci) {
		      exit_error('Error','ArtifactCanned Item could not be created');
		    } 
		  } else if ($update_canned) {
		    $aci = $ach->fetchData($artifact_canned_id);
		    if (!$aci) {
		      exit_error('Error','ArtifactCanned Item # $artifact_canned_id could not be found');
		    }
		    if (!$ach->update($title, $body)) {
		      exit_error('Error','ArtifactCanned Item # $artifact_canned_id could not be updated');
		    }
		    if ($ach->isError()) {
		      exit_error('Error', $ach->getErrorMessage());
		    }
		    $feedback .= ' Canned response updated';

		  }
		} // End of post_changes
		// Display the UI Form
	      if ($update_canned && !$post_changes) {
		$ath->adminHeader(array ('title'=>'Modify Canned Response'));
		$aci = $ach->fetchData($artifact_canned_id);
		if (!$aci) {
		  exit_error('Error','ArtifactCanned Item # '.$artifact_canned_id.' could not be found');
		}
		$ach->displayUpdateForm();
	      } else {
		$ath->adminHeader(array ('title'=>'Create/Modify Canned Responses'));
		$ach->displayCannedResponses();

		$ach->displayCreateForm();
	      }
	      break;

	case 'notification':
	  $ath->adminHeader(
       	array ('title'=>'Artifact Administration - Personal Email Notification Settings',
	       'help' => 'BTSAdministration.html#ArtifactEmailNotificationSettings'));
	  $ah = new ArtifactHtml(&$ath);
	  if ($submit) {
	    $res_new = $ath->updateNotificationSettings($send_all_artifacts, ($new_artifact_address?$new_artifact_address : ''), user_getid(), $watchees);
	        // Event/Role specific settings
	    //echo "num_roles : ".$ath->num_roles.", num_events : ".$ath->num_events." <br>";
	    for ($i=0; $i<$ath->num_roles; $i++) {
	      $role_id = $ath->arr_roles[$i]['role_id'];
	      for ($j=0; $j<$ath->num_events; $j++) {
		$event_id = $ath->arr_events[$j]['event_id'];
		$cbox_name = 'cb-'.$role_id.'-'.$event_id;
		//echo "DBG $cbox_name -> '".$$cbox_name."'<br>";
		$arr_notif[$role_id][$event_id] = ( $$cbox_name ? 1 : 0);
	      }
	    }
	    $ath->delete_notification(user_getid());
	    $res_notif = $ath->set_notification(user_getid(), $arr_notif);

	    // Give Feedback
	    if ($res_notif && $res_new) {
	      $feedback .= ' - Successful Update';
	      //$aff = new ArtifactFieldFactory(&$ath);
	      //$field = $aff->getFieldFromName('details');
	      //$ah->addHistory ($field, 'Changed Personal Notification Email Settings');
	    } else {
	      if ($ath->isError())
		$feedback .= ' - Update Failed : '.$ath->getErrorMessage();
	      else
		$feedback .= ' - Update Failed : '.db_error();;
	    }

	  }
	  $ah->displayNotificationForm(user_getid());
	  $ath->footer(array());
	  break;
	case 'adduser':
		$res_oa = user_get_result_set_from_unix($user_name);
		$user_id = db_result($res_oa,0,'user_id');

		if ( $user_id ) {
	
			if ( $ath->existUser($user_id) ) {
				exit_error('Error','The user \''.$user_name.'\' is already is the project permissions.');
			}
			if ( !$ath->addUser($user_id) ) {
				exit_error('Error',$ath->getErrorMessage());
			}

			include './browse_perm.php';
		} else {
			exit_error('Error','Unknow user:'.$user_name);
		}
		break;
		
	case 'deleteuser':

		if ( !$ath->deleteUser($user_id) ) {
			exit_error('Error',$ath->getErrorMessage());
		}

		include './browse_perm.php';
		break;
		
	case 'updateperm':
		if ( !$ath->updateUsers($atid,$user_name) ) {
			exit_error('Error',$ath->getErrorMessage());
		}

		include './browse_perm.php';
		break;
		
	case 'editoptions':
		if ( $update ) {
			if ( !$ath->update($name,$description,$itemname,$is_public,$allow_anon,$email_all,$email_address,
							   $submit_instructions,$browse_instructions) ) {
				exit_error('Error',$ath->getErrorMessage());
			}
		}
	
		$ath->adminHeader(array('title'=>'Tracker Administration - Options'));
		$ath->displayOptions($group_id,$atid);
		$ath->footer(array());
		break;
		
	default:    
		$ath->adminHeader(array('title'=>'Tracker Administration'));
		$ath->displayAdminTracker($group_id,$atid);
		$ath->footer(array());
	} // switch
	

} else {

    //browse for group first message

	exit_no_group();

}
?>
