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
require_once('common/include/HTTPRequest.class');
require_once('common/include/GroupFactory.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactFieldSetFactory.class');
require_once('common/tracker/ArtifactFieldSet.class');
require_once('common/tracker/ArtifactCanned.class');
require_once('common/tracker/ArtifactFieldFactory.class');
require_once('common/tracker/ArtifactField.class');
require_once('common/tracker/ArtifactReport.class');
require_once('common/tracker/ArtifactReportFactory.class');
require_once('common/tracker/ArtifactReportField.class');
require_once('common/tracker/Artifact.class');
require_once('common/include/ReferenceManager.class');
require('../include/ArtifactTypeHtml.class');
require('../include/ArtifactCannedHtml.class');
require('../include/ArtifactReportHtml.class');
require('../include/ArtifactHtml.class');

require_once('common/include/SimpleSanitizer.class');
$sanitizer =& new SimpleSanitizer();

$Language->loadLanguageMsg('tracker/tracker');

if ($group_id && (!isset($atid) || !$atid)) {
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
    
    if (isset($_REQUEST['func'])) {
        $func = $_REQUEST['func'];
    } else {
        $func = '';
    }
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
            exit_error($Language->getText('global','error'),$Language->getText('tracker_index','browser_not_supported',$Language->getText('tracker_index','a_tracker')));
            return;
        }
        if (isset($_REQUEST['feedback'])) {
            $GLOBALS['feedback'] .= htmlspecialchars($_REQUEST['feedback']);
        }
        //{{{ define undefined variables
        $vars = array('codex_template', 'group_id_template', 'atid_template', 'name', 'description', 'itemname');
        foreach($vars as $var_name) {
            if (isset($_REQUEST[$var_name])) {
                $$var_name = $_REQUEST[$var_name];
            } else {
                $$var_name = '';
            }
        }
        //}}}
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
            $name        = $sanitizer->sanitize($name);
            $description = $sanitizer->sanitize($description);
		if ( !$atf->create($group_id,$group_id_chosen,$atid_chosen,$name,$description,$itemname) ) {
            exit_error($Language->getText('global','error'),$atf->getErrorMessage());
		} else {
			$feedback .= $Language->getText('tracker_admin_index','tracker_created');
                        // Create corresponding reference
                        $reference_manager =& ReferenceManager::instance();
                        $ref=& new Reference(0, // no ID yet
                                             strtolower($itemname),
                                             $Language->getText('project_reference','reference_art_desc_key'), // description
                                             '/tracker/?func=detail&aid=$1&group_id=$group_id', // link
                                             'P', // scope is 'project'
                                             '',  // service ID - N/A
                                             '1', // is_used
                                             $group_id);
                        $result=$reference_manager->createReference($ref);
                        if (!$result) {
                            $feedback .= " - ".$GLOBALS['Language']->getText('project_reference','create_for_tracker_fail');
                        } else {
                            $feedback .= " - ".$GLOBALS['Language']->getText('project_reference','r_create_success')." ";
                        }
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

    // Create fieldset factory
	$art_fieldset_fact = new ArtifactFieldSetFactory($ath);
	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($ath);

        if (!isset($func)) {
            $func = '';
        }
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
		if (isset($post_changes)) {
			// apply update or create in bd
                    if (isset($update_report)) {
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
		
		} else if (isset($delete_report)) {
			if ( ($arh->scope == 'P') && 
			     !$ath->userIsAdmin() ) {
				exit_permission_denied();
			}	    
			$arh->delete();
			$feedback = $Language->getText('tracker_admin_index','report_deleted');
		}
		
		if (isset($new_report)) {
		
			$arh->createReportForm();
		} else if (isset($show_report)) {
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
		
	    if (isset($post_changes)) {
                if (isset($create_canned)) {
				$aci = $ach->create($title, $body);
				if (!$aci) {
					exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_create_canneditem'));
				} 
                } else if (isset($update_canned)) {
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
            } else if (isset($delete_canned)) {
		    if (!$ach->delete($artifact_canned_id)) {
		      exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_delete_canneditem',$artifact_canned_id));
		    }
		    if ($ach->isError()) {
		       exit_error($Language->getText('global','error'), $ach->getErrorMessage());
		    }
		    $feedback .= $Language->getText('tracker_admin_index','deleted_cannedresponse');

		} // End of post_changes
		// Display the UI Form
            if (isset($update_canned) && !isset($post_changes)) {
		  $ath->adminHeader(array ('title'=>$Language->getText('tracker_admin_index','modify_cannedresponse'),
					   'help' => 'TrackerAdministration.html#TrackerCannedResponses'));
			$aci = $ach->fetchData($artifact_canned_id);
			if (!$aci) {
			  exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','not_found_canneditem',$artifact_canned_id));
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
		if (isset($submit)) {
		  $res_new = true;
		  if ($ath->userIsAdmin()) {
			$res_new = $ath->updateNotificationSettings($send_all_artifacts, ($new_artifact_address?$new_artifact_address : ''), user_getid(), $watchees,$feedb);
		  }

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
	
	case 'date_field_notification':
		if ( !user_isloggedin() ) {
		    exit_not_logged_in();
		    return;
		}
		
	        if ( !$ath->userIsAdmin() ) {
		    exit_permission_denied();
		    return;
		}
		
		//check if  field_id exist
		$sql = "SELECT field_id FROM artifact_field WHERE group_artifact_id=".$ath->getID()." AND field_id=".$field_id;
		$result = db_query($sql);
		if (db_numrows($result) < 1) {		    
		    exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','wrong_field',array($field_id)));
		} else {
		    $field = $art_field_fact->getFieldFromId($field_id);  
		    if (! $field->getNotificationStatus()) {
		        exit_error($Language->getText('global','error'),$Language->getText('tracker_admin_index','wrong_field',array($field_id)));
		    }
		}    
		
		$ath->adminHeader(array ('title'=>$Language->getText('tracker_admin_index','admin_date_field_notif'),
		   'help' => 'TrackerAdministration.html#TrackerEmailNotificationSettings'));
		
		if (isset($submit_notif_settings)) {
		    $res = $ath->updateDateFieldReminderSettings($group_id,$field_id,$ath->getID(),$start,$notif_type,$frequency,$recurse,$submitter,$assignee,$cc,$commenter);
		}
		
		$ath->displayDateFieldNotificationSettings($field_id);   
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
		
            if ( isset($update) ) {
                    $name        = $sanitizer->sanitize($name);
                    $description = $sanitizer->sanitize($description);
		    if ( !$ath->update($name,$description,$itemname,$allow_copy,
                                           $submit_instructions,$browse_instructions,$instantiate_for_new_projects) ) {
				exit_error($Language->getText('global','error'),$ath->getErrorMessage());
			} else {
				$succeed = true;
			}
		}
	
		$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_index','options'),'help' => 'TrackerAdministration.html#TrackerGeneralSettings'));
		if ( isset($succeed) ) {
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
                require_once('common/tracker/ArtifactRulesManager.class');
                $arm =& new ArtifactRulesManager();
                $arm->deleteRulesByFieldId($atid, $field_id);
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
            // For date fields, it is possible to give a computed default value (current date)
            if ($field->isDateField() && $default_date_type=='current_date') {
                $computed_value = 'current_date';
            } else {
                $computed_value = false;
            }
            if ( !$field->updateDefaultValue($atid,$default_value, $computed_value) ) {
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
                    $value       = $sanitizer->sanitize($value);
                    $description = $sanitizer->sanitize($description);
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
                    $value       = $sanitizer->sanitize($value);
                    $description = $sanitizer->sanitize($description);
			if ( !$field->updateValueList($atid,$value_id,$value,$description,$order_id,$status) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
                if ($status == $ath->FIELD_VALUE_STATUS_HIDDEN) {
                    require_once('common/tracker/ArtifactRulesManager.class');
                    $arm =& new ArtifactRulesManager();
                    $arm->deleteRulesByValueId($atid, $field_id, $value_id);
                }
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
                require_once('common/tracker/ArtifactRulesManager.class');
                $arm =& new ArtifactRulesManager();
                $arm->deleteRulesByValueId($atid, $field_id, $value_id);
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
		$label       = $sanitizer->sanitize($label);
                $description = $sanitizer->sanitize($description);
		if ( !$art_field_fact->createField($description,$label,$data_type,$display_type,
						 $display_size,$rank_on_screen,
						 (isset($empty_ok)?$empty_ok:0),(isset($keep_history)?$keep_history:0),(isset($enable_notification)?$enable_notification:0),$special,$use_it,$field_set_id) ) {
			exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
		} else {
            // Reload the field factory
            $art_field_fact = new ArtifactFieldFactory($ath);
            // Reload the fieldset factory
            $art_fieldset_fact = new ArtifactFieldSetFactory($ath);
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
                     $label       = $sanitizer->sanitize($label);
                     $description = $sanitizer->sanitize($description);
			if ( !$field->update($atid,$field_name,$description,$label,$data_type,$display_type,
							 ($display_size=="N/A"?"":$display_size),$rank_on_screen,
							 $empty_ok,$keep_history,$enable_notification,$special,$use_it,$field_set_id) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
                if (!(isset($use_it) && $use_it)) {
                    require_once('common/tracker/ArtifactRulesManager.class');
                    $arm =& new ArtifactRulesManager();
                    $arm->deleteRulesByFieldId($atid, $field_id);
                }
				// Reload the field factory
				$art_field_fact = new ArtifactFieldFactory($ath);
                // Reload the fieldset factory
				$art_fieldset_fact = new ArtifactFieldSetFactory($ath);
                
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
            
            //clear permissions
            permission_clear_all_fields_tracker($group_id, $atid, $field->getID());
            
			if ( !$field->delete($atid) ) {
				exit_error($Language->getText('global','error'),$field->getErrorMessage());
			} else {
                require_once('common/tracker/ArtifactRulesManager.class');
                $arm =& new ArtifactRulesManager();
                $arm->deleteRulesByFieldId($atid, $field_id);
                
				// Reload the field factory
				$art_field_fact = new ArtifactFieldFactory($ath);
				// Reload the fieldset factory
				$art_fieldset_fact = new ArtifactFieldSetFactory($ath);
                
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
			  $Language->getText('tracker_admin_index','modify_usage_for',$field->getLabel())."</H2>";
			$ath->displayFieldUsageForm("field_update",$field->getID(),
						    $field->getName(),$field->getDescription(),$field->getLabel(),
						    $field->getDataType(),$field->getDefaultValue(),$field->getDisplayType(),
						    $field->getDisplaySize(),$field->getPlace(),
						    $field->getEmptyOk(),$field->getKeepHistory(),$field->getNotificationStatus(),$field->isSpecial(),$field->getUseIt(),true,$field->getFieldSetID());
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
                require_once('common/tracker/ArtifactRulesManager.class');
                $arm =& new ArtifactRulesManager();
                $arm->deleteRulesByArtifactType($atid);
                  // Delete related reference if it exists
                  // NOTE: there is no way to know if the reference is actually related to this tracker.
                  $reference_manager =& ReferenceManager::instance();
                  $ref =& $reference_manager->loadReferenceFromKeywordAndNumArgs(strtolower($ath->getItemName()),$group_id,1);
                  if ($ref) {
                      if ($reference_manager->deleteReference($ref)) {
                          $feedback .= " - ".$Language->getText('project_reference','t_r_deleted');
                      }
                  }
		} 
		$ath->footer(array());
	  break;
	case 'permissions':
            require('./tracker_permissions.php');
            break;
    case 'field_dependencies':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
	
	    require_once('../include/ArtifactRulesManagerHtml.class');
        $armh =& new ArtifactRulesManagerHtml($ath, '?group_id='. $ath->getGroupID() .'&atid='. $ath->getID() .'&func=field_dependencies');
        $request =& HTTPRequest::instance();
        if ($request->get('save') === 'save' ) {
            if (is_numeric($request->get('source_field')) && is_numeric($request->get('target_field'))) {
                $armh->saveFromRequest($request);
            } else {
                $armh->badRequest();
            }
        } else {
            $armh->displayRules();
        }
        break;
    case 'fieldsets':
		require('./field_sets.php');
		break;
    case 'fieldset_create':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		$name = $sanitizer->sanitize($name);
        $description = $sanitizer->sanitize($description);
		if ( !$art_fieldset_fact->createFieldSet($name, $description, $rank) ) {
			exit_error($Language->getText('global','error'),$art_fieldset_fact->getErrorMessage());
		} else {
		  $feedback = $Language->getText('tracker_admin_index','fieldset_created');
		}
		require('./field_sets.php');
		break;
    case 'display_fieldset_update':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$fieldset = $art_fieldset_fact->getFieldSetById($fieldset_id);
        
		if ( $fieldset ) {
			$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_fieldset','tracker_admin').$Language->getText('tracker_admin_index','modify_fieldset'),
						'help' => 'TrackerAdministration.html#CreationandModificationofaTrackerFieldSet'));
			echo "<H2>".$Language->getText('tracker_import_admin','tracker').
			  " '<a href=\"/tracker/admin/?group_id=".$group_id."&atid=".$atid."\">".$ath->getName()."</a>' ".
			  $Language->getText('tracker_admin_index','modify_fieldset_for',$fieldset->getLabel())."</H2>";
			$ath->displayFieldSetCreateForm("fieldset_update",$fieldset->getID(),
						    $fieldset->getLabel(),$fieldset->getDescriptionText(),$fieldset->getRank());
			$ath->footer(array());
		}
		break;
    case 'fieldset_update':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$fieldset = $art_fieldset_fact->getFieldSetById($fieldset_id);
		if ( $fieldset ) {
            $name = $sanitizer->sanitize($name);
            $description = $sanitizer->sanitize($description);
            
            // We check if there is a change with the name and description
            // If there is no changes, we keep the internationalized key, because in the interface, 
            // the user don't see the i18n key, but the associated value (the l10n value).
            if ($name == $fieldset->getLabel()) {
                // getName returns the key, getLabel returns the value (internationalized if so, same as name if not)
                $name = $fieldset->getName();
            }
            if ($description == $fieldset->getDescriptionText()) {
                // getDescription returns the key, getDescriptionText returns the value (internationalized if so, same as description if not)
                $description = $fieldset->getDescription();
            }
            
			if ( !$fieldset->update($name,$description,$rank) ) {
				exit_error($Language->getText('global','error'),$fieldset->getErrorMessage());
			} else {
                // Reload the field factory
				$art_fieldset_fact = new ArtifactFieldSetFactory($ath);

				$feedback = $Language->getText('tracker_admin_index','fieldset_updated');
			}
		}
		require('./field_sets.php');
		break;
    case 'fieldset_delete':
		if ( !user_isloggedin() ) {
			exit_not_logged_in();
			return;
		}
		
	    if ( !$ath->userIsAdmin() ) {
			exit_permission_denied();
			return;
		}
		
		$fieldset = $art_fieldset_fact->getFieldSetById($fieldset_id);
		if ( $fieldset ) {
            
            if ( !$art_fieldset_fact->deleteFieldSet($fieldset_id) ) {
				exit_error($Language->getText('global','error'),$art_fieldset_fact->getErrorMessage());
			} else {
                // Reload the fieldset factory
				$art_fieldset_fact = new ArtifactFieldSetFactory($ath);
				
				$feedback = $Language->getText('tracker_admin_index','fieldset_deleted');
			}
		}
		require('./field_sets.php');
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
