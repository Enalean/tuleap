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

require($DOCUMENT_ROOT.'/include/pre.php');
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

$Language->loadLanguageMsg('tracker/tracker');

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
		exit_error($Language->getText('global','error'),$Language->getText('tracker_index','not_create_at'));
	}
	if ($ath->isError()) {
		exit_error($Language->getText('global','error'),$ath->getErrorMessage());
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
	
            if (browser_is_netscape4()) {
	      exit_error($Language->getText('global','error'),$Language->getText('tracker_index','browser_not_supported','a tracker'));
                return;
            }

		$ath->adminTrackersHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_index','create_tracker'),'help' => 'TrackerCreation.html'));
		$ath->displayCreateTracker($group_id,$codex_template,$group_id_template,$atid_template,$name,$description,$itemname,$feedback);
		$ath->footer(array());
		break;
		
	case 'docreate':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !user_ismember($group_id,'A') ) {
			exit_permission_denied();
			return;
		}

		if ( !$ath->create($group_id,$group_id_chosen,$atid_chosen,$name,$description,$itemname) ) {
			exit_error($Language->getText('global','error'),$ath->getErrorMessage());
		} else {
			$feedback = $Language->getText('tracker_admin_index','tracker_created');
		}
		require('./admin_trackers.php');
		break;

	default:
		require('./admin_trackers.php');

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
		exit_error($Language->getText('global','error'),$Language->getText('tracker_index','not_create_at'));
	}
	if ($ath->isError()) {
		exit_error($Language->getText('global','error'),$ath->getErrorMessage());
	}
	// Check if this tracker is valid (not deleted)
	if ( !$ath->isValid() ) {
		exit_error($Language->getText('global','error'),$Language->getText('tracker_add','invalid'));
	}

	$ach = new ArtifactCannedHtml($ath);
	if (!$ach || !is_object($ach)) {
	  exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_create_canned'));
	}
	if ($ach->isError()) {
	  exit_error($Language->getText('global','error'),$ach->getErrorMessage());
	}

	$atf = new ArtifactTypeFactory($group);

	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($ath);

	switch ( $func ) {
	case 'report':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
		$arh = new ArtifactReportHtml($report_id, $atid);
		if (!$arh) {
			exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_retrieved_report',$arh->getErrorMessage()));
		}
		if ($post_changes) {
			// apply update or create in bd
			if ($update_report) {
				$updated = $arh->recreate(user_getid(), $rep_name, $rep_desc, $rep_scope);
				if (!$updated) {
					if ($arh->isError())
						exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_updated_report').': '.$arh->getErrorMessage());
					exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_updated_report'));
				}
				$feedback = "Report definition updated";
			} else {
				$report_id = $arh->create(user_getid(), $rep_name, $rep_desc, $rep_scope);
				if (!$report_id) {
					if ($arh->isError())
						exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_created_report').': '.$arh->getErrorMessage());
					exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_created_report'));
				};
				$feedback = $Language->getText('tracker_admin_index','new_report_created');
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
			$feedback = $Language->getText('tracker_admin_index','report_deleted');
		}
		
		if ($new_report) {
		
			$arh->createReportForm();
		} else if ($show_report) {
			$arh = new ArtifactReportHtml($report_id,$atid);
			if ( ($arh->scope == 'P') && 
			     !$ath->userIsAdmin() ) {
				exit_permission_denied();
			}
			if ( ($arh->scope == 'S') && 
			     !user_is_super_user() ) {
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
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
	    if ($post_changes) {
			if ($create_canned) {
				$aci = $ach->create($title, $body);
				if (!$aci) {
					exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_create_canneditem'));
				} 
			} else if ($update_canned) {
				$aci = $ach->fetchData($artifact_canned_id);
				if (!$aci) {
					exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_found_canneditem',$artifact_canned_id));
				}
				if (!$ach->update($title, $body)) {
					exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_update_canneditem',$artifact_canned_id));
				}
				if ($ach->isError()) {
					exit_error($Language->getText('global','error'), $ach->getErrorMessage());
				}
				$feedback .= $Language->getText('tracker_admin_index','updated_cannedresponse');
			
			}
		} else if ($delete_canned) {
		    if (!$ach->delete($artifact_canned_id)) {
		      exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_delete_canneditem',$artifact_canned_id));
		    }
		    if ($ach->isError()) {
		       exit_error($Language->getText('global','error'), $ach->getErrorMessage());
		    }
		    $feedback .= $Language->getText('tracker_admin_index','deleted_cannedresponse');

		} // End of post_changes
		// Display the UI Form
		if ($update_canned && !$post_changes) {
		  $ath->adminHeader(array ('title'=>$Language->getText('tracker_admin_index','modify_cannedresponse'),
					   'help' => 'TrackerAdministration.html#TrackerCannedResponses'));
			$aci = $ach->fetchData($artifact_canned_id);
			if (!$aci) {
			  exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_found_canned_item',$artifact_canned_id));
			}
			$ach->displayUpdateForm();
		} else {
		  $ath->adminHeader(array ('title'=>$Language->getText('tracker_admin_index','create_modify_cannedresponse'),
		   'help' => 'TrackerAdministration.html#TrackerCannedResponses'));
			$ach->displayCannedResponses();
			
			$ach->displayCreateForm();
		}
		$ath->footer(array());
		break;

	case 'notification':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}

		$ath->adminHeader(
		array ('title'=>$Language->getText('tracker_admin_index','art_admin'),
		   'help' => 'TrackerAdministration.html#TrackerEmailNotificationSettings'));
		if ($submit) {
			$res_new = $ath->updateNotificationSettings($send_all_artifacts, ($new_artifact_address?$new_artifact_address : ''), user_getid(), $watchees,$feedb);
 

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
				$feedback .= $Language->getText('tracker_admin_index','update_success');
			} else {
			  if (!$res_new && $feedb) {
			    $feedback .= $Language->getText('tracker_admin_index','update_failed',$feedb);
			  } else {
			    $feedback .= $Language->getText('tracker_admin_index','update_failed',$ath->getErrorMessage());
			  }
			}
			$ath->fetchData($ath->getID());
		
		}
		$ath->displayNotificationForm(user_getid());
		$ath->footer(array());
		break;
	  
	case 'editoptions':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		if ( $update ) {
			if ( !$ath->update($name,$description,$itemname,$is_public,$allow_anon,
                                           $submit_instructions,$browse_instructions,$instantiate_for_new_projects) ) {
				exit_error($Language->getText('global','error'),$ath->getErrorMessage());
			} else {
				$succeed = true;
			}
		}
	
		$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_index','options'),'help' => 'TrackerAdministration.html#TrackerGeneralSettings'));
		if ( $succeed ) {
			echo '<H3><span class="feedback">'.$Language->getText('tracker_admin_index','update_success_title').'</span></H3>';
		}
		$ath->displayOptions($group_id,$atid);
		$ath->footer(array());
		break;
		

	case 'field_values':
		require('./field_values.php');
		break;
		
	case 'update_binding':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->updateValueFunction($atid,$value_function) ) {
				exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
			} else {
				$feedback = $Language->getText('tracker_admin_index','values_updated');
			}
		}
		require('./field_values.php');
		break;
				
	case 'update_default_value':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->updateDefaultValue($atid,$default_value) ) {
				exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
			} else {
				$feedback = $Language->getText('tracker_admin_index','values_updated');
			}
		}
		require('./field_values.php');
		break;

	case 'display_field_values':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
		    require('./field_values_details.php');
		}
		break;
		
	case 'display_field_value':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').
						$Language->getText('tracker_admin_field_values_details','values_admin'),
						'help' => 'TrackerAdministration.html#TrackerUpdatingaTrackerFieldValue'));
			echo "<H2>".$Language->getText('tracker_import_admin','tracker').
			  " '<a href=\"/tracker/admin/?group_id=".$group_id."&atid=".$atid."\">".$ath->getName()."</a>'".
			  $Language->getText('tracker_admin_field_values_details','manage_for',$field->getLabel())."'</H2>";

			$value_array = $field->getFieldValue($atid,$value_id);
			$ath->displayFieldValueForm("value_update",$field_id,$value_array['value_id'],$value_array['value'],$value_array['order_id'],$value_array['status'],$value_array['description']);
			$ath->footer(array());
		}
		break;
		
	case 'value_create':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->createValueList($atid,$value,$description,$order_id) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
				$feedback = $Language->getText('tracker_admin_index','value_created');
			}
			require('./field_values_details.php');
		}
		break;
			
	case 'value_update':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->updateValueList($atid,$value_id,$value,$description,$order_id,$status) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
				$feedback = $Language->getText('tracker_admin_index','value_updated');
			}
			require('./field_values_details.php');
		}
		break;

	case 'value_delete':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->deleteValueList($atid,$value_id) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
				$feedback = $Language->getText('tracker_admin_index','value_deleted');
			}
			require('./field_values_details.php');
		}
		break;

	case 'field_usage':
		require('./field_usage.php');
		break;
		
	case 'field_create':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		if ( !$art_field_fact->createField($description,$label,$data_type,$display_type,
						 $display_size,$rank_on_screen,$show_on_add,$show_on_add_members,
						 $empty_ok,$keep_history,$special,$use_it) ) {
			exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
		} else {
		  $feedback = $Language->getText('tracker_admin_index','field_created');
		}
		require('./field_usage.php');
		break;

	case 'field_update':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->update($atid,$field_name,$description,$label,$data_type,$display_type,
							 ($display_size=="N/A"?"":$display_size),$rank_on_screen,$show_on_add,$show_on_add_members,
							 $empty_ok,$keep_history,$special,$use_it) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
				// Reload the field factory
				$art_field_fact = new ArtifactFieldFactory($ath);

				$feedback = $Language->getText('tracker_admin_index','field_updated');
			}
		}
		require('./field_usage.php');
		break;

	case 'field_delete':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			if ( !$field->delete($atid) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
				// Reload the field factory
				$art_field_fact = new ArtifactFieldFactory($ath);
				
				$feedback = $Language->getText('tracker_admin_index','field_deleted');
			}
		}
		require('./field_usage.php');
		break;

	case 'display_field_update':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( $field ) {
			$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_index','modify_usage'),
						'help' => 'TrackerAdministration.html#CreationandModificationofaTrackerField'));
			echo "<H2>".$Language->getText('tracker_import_admin','tracker').
			  " '<a href=\"/tracker/admin/?group_id=".$group_id."&atid=".$atid."\">".$ath->getName()."</a>' ".
			  $Language->getText('tracker_admin_index','modify_usage_for',$field->getLabel())."'</H2>";
			$ath->displayFieldUsageForm("field_update",$field->getID(),
						    $field->getName(),$field->getDescription(),$field->getLabel(),
						    $field->getDataType(),$field->getDefaultValue(),$field->getDisplayType(),
						    $field->getDisplaySize(),$field->getPlace(),$field->getShowOnAdd(),$field->getShowOnAddMembers(),
						    $field->getEmptyOk(),$field->getKeepHistory(),$field->isSpecial(),$field->getUseIt(),true);
			$ath->footer(array());
		}
		break;

	case 'delete_tracker':
	  if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
	
	    if ( !user_ismember($group_id,'A') ) {
			exit_permission_denied();
			return;
		}

		$ath->adminHeader(array('title'=>$ath->getName().' '.$Language->getText('tracker_admin_field_usage','tracker_admin'),
					'help' => 'TrackerAdministration.html'));
		if (!$ath->preDelete()) {
		  $feedback = $Language->getText('tracker_admin_index','deletion_failed',$ath->getName());
		} else {
		  $feedback = $Language->getText('tracker_admin_index','delete_success',$ath->getName());
		  echo $Language->getText('tracker_admin_index','tracker_deleted',array($ath->getName(),$GLOBALS['sys_email_admin']));
		} 
		$ath->footer(array());
	  break;
	  
	default:    
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
		$ath->adminHeader(array('title'=>$ath->getName().' '.$Language->getText('tracker_admin_field_usage','tracker_admin'),'help' => 'TrackerAdministration.html'));
		$ath->displayAdminTracker($group_id,$atid);
		$ath->footer(array());
	} // switch
	

} else {

    //browse for group first message

	exit_no_group();

}
?>
