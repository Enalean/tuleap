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
require($DOCUMENT_ROOT.'/../common/include/GroupFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactCanned.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReport.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReportFactory.class');
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

	switch ( $func ) {
	case 'create':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !user_ismember($group_id,'A') ) {
			exit_permission_denied();
			return;
		}
	
		$ath->adminTrackersHeader(array('title'=>'Tracker Administration - Create tracker','help' => 'HELP_FIXME.html'));
		$ath->displayCreateTracker($group_id,$codex_template,$group_id_template,$atid_template,$name,$description,$itemname,$feedback);
		$ath->footer(array());
		break;
		
	case 'docreate':
		if (!$this->userIsAdmin()) {
			$this->setError('ArtifactType: Permission Denied');
			return false;
		}

		if ( !$ath->create($group_id,$group_id_chosen,$atid_chosen,$name,$description,$itemname) ) {
			exit_error('Error',$ath->getErrorMessage());
		} else {
			$feedback = "Tracker created - Click on the new tracker ID to complete the tracker configuration";
		}
		include './admin_trackers.php';
		break;

	default:
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
		if ( !user_ismember($group_id,'A') ) {
			exit_permission_denied();
			return;
		}
	
		$ath->adminTrackersHeader(array('title'=>'All Trackers Administration','help' => 'HELP_FIXME.html'));
		echo $ath->displayAdminTrackers();
		$ath->footer(array());
	}
				
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

	$atf = new ArtifactTypeFactory($group);

	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($ath);

	switch ( $func ) {
	case 'permissions':
		include './browse_perm.php';
		break;
		
	case 'report':
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
				$feedback = "Report definition updated";
			} else {
				$report_id = $arh->create(user_getid(), $rep_name, $rep_desc, $rep_scope);
				if (!$report_id) {
					if ($arh->isError())
						exit_error('Error','ArtifactReport could not be created:'.$arh->getErrorMessage());
					exit_error('Error','ArtifactReport could not be created');
				};
				$feedback = "New report created";
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
			     !$ath->userIsAdmin() ) {
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
			     !$ath->userIsAdmin() ) {
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
		} else if ($delete_canned) {
		    if (!$ach->delete($artifact_canned_id)) {
		       exit_error('Error','ArtifactCanned Item # '.$artifact_canned_id.' could not be deleted');
		    }
		    if ($ach->isError()) {
		       exit_error('Error', $ach->getErrorMessage());
		    }
		    $feedback .= ' Canned response deleted';

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
		$ath->footer(array());
		break;

	case 'notification':
		$ath->adminHeader(
		array ('title'=>'Artifact Administration - Personal Email Notification Settings',
		   'help' => 'HELP_FIXME.html#HELP_FIXME'));
		if ($submit) {
			$res_new = $ath->updateNotificationSettings($send_all_artifacts, ($new_artifact_address?$new_artifact_address : ''), user_getid(), $watchees);
		    // Event/Role specific settings
			//echo "num_roles : ".$ath->num_roles.", num_events : ".$ath->num_events." <br>";
			
			for ($i=0; $i<$ath->num_roles; $i++) {
				$role_id = $ath->arr_roles[$i]['role_id'];
				for ($j=0; $j<$ath->num_events; $j++) {
					$event_id = $ath->arr_events[$j]['event_id'];
					$cbox_name = 'cb_'.$role_id.'_'.$event_id;
					//echo "DBG $cbox_name -> '".$$cbox_name."'<br>";
					$arr_notif[$role_id][$event_id] = ( $$cbox_name ? 1 : 0);
				}
			}

			$ath->deleteNotification(user_getid());
			$res_notif = $ath->setNotification(user_getid(), $arr_notif);
			
			// Give Feedback
			if ($res_notif && $res_new) {
				$feedback .= ' - Successful Update';
			} else {
				$feedback .= ' - Update Failed : '.$ath->getErrorMessage();
			}
			$ath->fetchData($ath->getID());
		
		}
		$ath->displayNotificationForm(user_getid());
		$ath->footer(array());
		break;
	  
	case 'adduser':
		$user_name = user_getname($user_id);

		if ( $ath->existUser($user_id) ) {
			exit_error('Error','The user \''.$user_name.'\' is already is the tracker permissions.');
		}
		if ( !$ath->addUser($user_id,0) ) {
			exit_error('Error',$ath->getErrorMessage());
		}

		include './browse_perm.php';
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
			if ( !$ath->update($name,$description,$itemname,$is_public,$allow_anon,
							   $submit_instructions,$browse_instructions) ) {
				exit_error('Error',$ath->getErrorMessage());
			} else {
				$succeed = true;
			}
		}
	
		$ath->adminHeader(array('title'=>'Tracker Administration - Options','help' => 'HELP_FIXME.html'));
		if ( $succeed ) {
			echo '<H3><span class="feedback">SUCCESSFUL UPDATE</span></H3>';
		}
		$ath->displayOptions($group_id,$atid);
		$ath->footer(array());
		break;
		
	case 'delete_tracker':
		// Create field factory
		$art_field_fact = new ArtifactFieldFactory($ath);

		// Then delete all the fields informations
		if ( !$art_field_fact->deleteFields($atid) ) {
			exit_error('Error',$art_field_fact->getErrorMessage());
			return false;
		}
		
		// Then delete all the reports informations
		// Create field factory
		$art_report_fact = new ArtifactReportFactory();

		if ( !$art_report_fact->deleteReports($atid) ) {
			exit_error('Error',$art_report_fact->getErrorMessage());
			return false;
		}
		
		// Delete the artifact type itself
		if ( !$atf->deleteArtifactType($atid) ) {
			exit_error('Error',$atf->getErrorMessage());
		}
		
		include './admin_trackers.php';
		break;

	case 'field_values':
		include './field_values.php';
		break;
		
	case 'update_binding':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->updateValueFunction($atid,$value_function) ) {
				exit_error('Error',$art_field_fact->getErrorMessage());
			} else {
				$feedback = "Field values updated";
			}
		}
		include './field_values.php';
		break;
		
	case 'display_field_values':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			include './field_values_details.php';
		}
		break;
		
	case 'display_field_value':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			$ath->adminHeader(array('title'=>'Tracker Administration - Field Values Administration','help' => 'HELP_FIXME.html'));
			echo "<H2>Tracker '".$ath->getName()."' - Manage Field Values for '".$field->getLabel()."'</H2>";
			$value_array = $field->getFieldValue($atid,$value_id);
			$ath->displayFieldValueForm("value_update",$field_id,$value_array['value_id'],$value_array['value'],$value_array['order_id'],$value_array['status'],$value_array['description']);
			
			$ath->footer(array());
		}
		break;
		
	case 'value_create':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->createValueList($atid,$value_id,$value,$description,$order_id) ) {
				exit_error('Error',$field->getErrorMessage());
			} else {
				$feedback = "Field value created";
			}
			include './field_values_details.php';
		}
		break;
			
	case 'value_update':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->updateValueList($atid,$value_id,$value,$description,$order_id,$status) ) {
				exit_error('Error',$field->getErrorMessage());
			} else {
				$feedback = "Field value updated";
			}
			include './field_values_details.php';
		}
		break;

	case 'value_delete':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->deleteValueList($atid,$value_id) ) {
				exit_error('Error',$field->getErrorMessage());
			} else {
				$feedback = "Field value deleted";
			}
			include './field_values_details.php';
		}
		break;

	case 'field_usage':
		include './field_usage.php';
		break;
		
	case 'field_create':
		if ( !$art_field_fact->createField($field_id,$field_name,$description,$label,$data_type,$default_value,$display_type,
						 $display_size,$rank_on_screen,$show_on_add,$show_on_add_members,
						 $empty_ok,$keep_history,$special,$use_it) ) {
			exit_error('Error',$art_field_fact->getErrorMessage());
		} else {
			$feedback = "Field created";
		}
		include './field_usage.php';
		break;

	case 'field_update':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->update($atid,$field_name,$description,$label,$data_type,$default_value,$display_type,
							 $display_size,$rank_on_screen,$show_on_add,$show_on_add_members,
							 $empty_ok,$keep_history,$special,$use_it) ) {
				exit_error('Error',$field->getErrorMessage());
			} else {
				// Reload the field factory
				$art_field_fact = new ArtifactFieldFactory($ath);

				$feedback = "Field updated";
			}
		}
		include './field_usage.php';
		break;

	case 'field_delete':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->delete($atid) ) {
				exit_error('Error',$field->getErrorMessage());
			} else {
				// Reload the field factory
				$art_field_fact = new ArtifactFieldFactory($ath);
				
				$feedback = "Field deleted";
			}
		}
		include './field_usage.php';
		break;

	case 'display_field_update':
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			$ath->adminHeader(array('title'=>'Tracker Administration - Field Usage Administration','help' => 'HELP_FIXME.html'));
			echo "<H2>Tracker '<a href=\"/tracker?group_id=".$group_id."&atid=".$atid."\">".$ath->getName()."</a>' - Field Usage Administration</H2>";
			$ath->displayFieldUsageForm("field_update",$field->getID(),$field->getName(),$field->getDescription(),$field->getLabel(),$field->getDataType(),$field->getDefaultValue(),$field->getDisplayType(),
									   $field->getDisplaySize(),$field->getPlace(),$field->getShowOnAdd(),$field->getShowOnAddMembers(),
									   $field->getEmptyOk(),$field->getKeepHistory(),$field->isSpecial(),$field->getUseIt(),true);
			$ath->footer(array());
		}
		break;

	default:    
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
	
		$ath->adminHeader(array('title'=>$ath->getName().' Tracker Administration','help' => 'HELP_FIXME.html'));
		$ath->displayAdminTracker($group_id,$atid);
		$ath->footer(array());
	} // switch
	

} else {

    //browse for group first message

	exit_no_group();

}
?>
