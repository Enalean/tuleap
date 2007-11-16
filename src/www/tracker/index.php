<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//
//      Originally by to the SourceForge Team,1999-2000
//
//  Written for CodeX by Stephane Bouhet
//


require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/tracker/Artifact.class.php');
require_once('common/tracker/ArtifactFile.class.php');
require('./include/ArtifactFileHtml.class.php');
require_once('common/tracker/ArtifactType.class.php');
require('./include/ArtifactTypeHtml.class.php');
require('./include/ArtifactHtml.class.php');
require_once('common/tracker/ArtifactCanned.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactFieldSet.class.php');
require_once('common/tracker/ArtifactFieldSetFactory.class.php');
require_once('common/tracker/ArtifactField.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('common/tracker/ArtifactReportFactory.class.php');
require_once('common/tracker/ArtifactReport.class.php');
require_once('common/tracker/ArtifactReportField.class.php');
require('./include/ArtifactFieldHtml.class.php');
require('./include/ArtifactReportHtml.class.php');
require('./include/ArtifactImportHtml.class.php');
require_once('www/project/admin/permissions.php');
require_once('common/include/SimpleSanitizer.class.php');

$Language->loadLanguageMsg('tracker/tracker');


//Sanitize some fields
$strings_to_sanitize = array('cc_comment', 'file_description');
$sanitizer           = new SimpleSanitizer();
foreach($strings_to_sanitize as $str) {
    if (isset($_REQUEST[$str])) {
        //var_dump($_REQUEST);
        $_REQUEST[$str] = $sanitizer->sanitize($_REQUEST[$str]);
        $$str = $_REQUEST[$str];
        //var_dump($_REQUEST);
    }
}

if (isset($aid) && !isset($atid)) {
    // We have the artifact id, but not the tracker id
    $sql="SELECT group_artifact_id FROM artifact WHERE artifact_id=$aid";
    $result = db_query($sql);
    if (db_numrows($result)>0) {
        $row = db_fetch_array($result);
        $atid = $row['group_artifact_id'];
    }
 }
		    
if (isset($atid) && !isset($group_id)) {
    // We have the artifact group id, but not the group id
    $sql="SELECT group_id FROM artifact_group_list WHERE group_artifact_id=$atid";
    $result = db_query($sql);
    if (db_numrows($result)>0) {
        $row = db_fetch_array($result);
        $group_id = $row['group_id'];
    }
 }


//define undefined variables
if (!isset($func)) {
    $func = '';
}
if ( $func == 'gotoid' ) {
    // Direct access to an artifact
    if (!isset($aid) || !$aid) {
        exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'art_id_necessary'));
    } else {
        require('./gotoid.php');
    }
 } else if ($group_id && isset($atid) && $atid) {
        //
        //      get the Group object
        //
        $group = group_get_object($group_id);
        if (!$group || !is_object($group) || $group->isError()) {
                exit_no_group();
        }
        //
        //      Create the ArtifactType object
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

        // Create field factory
        $art_field_fact = new ArtifactFieldFactory($ath);
        $art_fieldset_fact = new ArtifactFieldSetFactory($ath);

        switch ($func) {
        case 'rss':
            if ($aid) {
                $ah=new ArtifactHtml($ath,$aid);
                if (!$ah || !is_object($ah)) {
                    exit_error($Language->getText('global','error'),$Language->getText('tracker_index','not_create_art'));
                } else {
                    $ah->displayRSS();
                }
            } else {
               require('./browse.php');
            }
            break;
        case 'add' : {
            if (browser_is_netscape4()) {
	      exit_error($Language->getText('global','error'),$Language->getText('tracker_index','browser_not_supported',$Language->getText('tracker_index','an_artif')));
                return;
            }
                $ah=new ArtifactHtml($ath);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index','not_create_art'));
                } else {
                        require('./add.php');
                }
                break;
        }
        
        case 'postadd' : {
            
                //
                //              Create a new Artifact
                //      

                $ah=new ArtifactHtml($ath);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index','not_create_art'));
                } else {
                        // Check if a user can submit a new without loggin
                        if ( !user_isloggedin() && !$ath->allowsAnon() ) {
                                exit_not_logged_in();
                                return;
                        }
                        
                        //
                        //  make sure this person has permission to add artifacts
                        //
                        if (!$ath->userCanSubmit()) {
                                exit_permission_denied();
                        }

                        // First check parameters
                        
                        // CC
                        $array_add_cc = split('[,;]', $add_cc);
			if ($add_cc && !util_validateCCList($array_add_cc, $message)) {
                        exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
			}
			// Files
                        if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && !util_check_fileupload($input_file)) {
                                exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename'));
                        }
                        
                        //Check Field Dependencies
                        require_once('common/tracker/ArtifactRulesManager.class.php');
                        $arm =& new ArtifactRulesManager();
                        if (!$arm->validate($atid, $art_field_fact->extractFieldList(), $art_field_fact)) {
                            exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_field_dependency'));
                        }
                        
                        // Artifact creation                
                        if (!$ah->create()) {
                                exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                        } else {
                                //
                                //      Attach file to this Artifact.
                                //
                                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                                        $afh=new ArtifactFileHtml($ah);
                                        if (!$afh || !is_object($afh)) {
                                                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_create_file'));
                                        } elseif ($afh->isError()) {
                                                $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                                        } else {
                                                if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
                                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_attach_file',$afh->getErrorMessage()));
                                                }
                                        }
                                }

                                // Add new cc if any
                                if ($add_cc) {
                                    $ah->addCC($add_cc,$cc_comment,$changes);
                                }

                                // send an email to notify the user of the artifact add
                                $agnf =& new ArtifactGlobalNotificationFactory();
                                $addresses = $agnf->getAllAddresses($ath->getID());
                                $ah->mailFollowupWithPermissions($addresses);
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','create_success',$ah->getID()));
                            $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&func=browse');
                        }
                }
                break;
        }
        case 'postcopy' : {
                //
                //              Create a new Artifact
                //      

                $ah=new ArtifactHtml($ath);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index','not_create_art'));
                } else {
                        // Check if a user can submit a new without loggin
                        if ( !user_isloggedin() && !$ath->allowsAnon() ) {
                                exit_not_logged_in();
                                return;
                        }
                        
                        //
                        //  make sure this person has permission to copy artifacts
                        //  !!!! verify with new permission scheme !!!!
                        if (!$ath->userCanSubmit()) {
                                exit_permission_denied();
                        }

                        // First check parameters
                        
                        // CC
			// 			
                        $array_add_cc = split('[,;]', $add_cc);
			if ($add_cc && !util_validateCCList($array_add_cc, $message)) {
                        exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
			}

			// Files
			// 
                        if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && !util_check_fileupload($input_file)) {
                                exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename'));
                        }

                        // Artifact creation                
                        if (!$ah->create()) {
                                exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                        } else {
                                //
                                //      Attach file to this Artifact.
                                //
                                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                                        $afh=new ArtifactFileHtml($ah);
                                        if (!$afh || !is_object($afh)) {
                                                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_create_file'));
                                        } elseif ($afh->isError()) {
                                               $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                                        } else {
                                                if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
                                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_attach_file',$afh->getErrorMessage()));
                                                }
                                        }
                                }

                                // Add new cc if any
                                if ($add_cc) {
                                    $ah->addCC($add_cc,$cc_comment,$changes);
                                }

				// Add new dependencies if any
				if ($artifact_id_dependent) {
				  $ah->addDependencies($artifact_id_dependent,&$changes,false);
				}

				// Add follow-up comments if any
				$ah->addFollowUpComment($follow_up_comment,$comment_type_id,$canned_response,$changes,$feedback);

                                // send an email to notify the user of the artifact update
                                    $agnf =& new ArtifactGlobalNotificationFactory();
                                    $addresses = $agnf->getAllAddresses($ath->getID());
                                    $ah->mailFollowupWithPermissions($addresses);
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','create_success',$ah->getID()));
                            $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&func=browse');
                        }
                }
                break;
        }
        
    case 'delete_cc' : {
        
                $ah=new ArtifactHtml($ath,$aid);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
                } else if ($ah->isError()) {
                        exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                } else {
                        $cc_array = $ah->getCC($artifact_cc_id);
                        // Perform CC deletion if one of the condition is met:
                        // (a) current user is a artifact admin
                        // (b) then CC name is the current user 
                        // (c) the CC email address matches the one of the current user
                        // (d) the current user is the person who added a gieven name in CC list
                        if ( user_ismember($group_id) ||
                        (user_getname(user_getid()) == $cc_array['email']) ||  
                        (user_getemail(user_getid()) == $cc_array['email']) ||
                        (user_getname(user_getid()) == $cc_array['user_name'] )) {

                                $changed = $ah->deleteCC($artifact_cc_id,$changes);
                                if ($changed) {
                                    $agnf =& new ArtifactGlobalNotificationFactory();
                                    $addresses = $agnf->getAllAddresses($ath->getID(), true);
                                    $ah->mailFollowupWithPermissions($addresses, $changes);
                                }
        
                                $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&aid='. $aid .'&func=detail');
                        
                        } else {
                                // Invalid permission
                                exit_permission_denied();
                                return;
                        }
        
                }
                break;
        }

    case 'delete_comment' : {
		
		if ( !user_isloggedin() ) {
		    exit_not_logged_in();
                    return;
                }
                
		if ( !user_ismember($group_id) ) {
                    exit_permission_denied();
                    return;
                }
                
                $ah=new ArtifactHtml($ath,$_REQUEST['aid']);
                if (!$ah || !is_object($ah)) {
                    exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
                } else if ($ah->isError()) {
                    exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                } else {		    
		    if ($ah->userCanEditFollowupComment($_REQUEST['artifact_history_id'])) {    
			$ah->deleteFollowupComment($_REQUEST['aid'],$_REQUEST['artifact_history_id']);

                        $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&aid='. $aid .'&func=detail');
		    } else {
                        // Invalid permission
                        exit_permission_denied();
                        return;		    
		    }
		}		
	        break;
    }
        
    case 'delete_dependent' : {
        
                if ( !user_isloggedin() ) {
                        exit_not_logged_in();
                        return;
                }
                
		if ( !user_ismember($group_id) ) {
                        exit_permission_denied();
                        return;
                }
                
                $ah=new ArtifactHtml($ath,$aid);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
                } else if ($ah->isError()) {
                        exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                } else {
                        $changed = $ah->deleteDependency($dependent_on_artifact_id,$changes);
                        if ($changed) {
                            $agnf =& new ArtifactGlobalNotificationFactory();
                            $addresses = $agnf->getAllAddresses($ath->getID(), true);
                            $ah->mailFollowupWithPermissions($addresses, $changes);
                        }

                        $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&aid='. $aid .'&func=detail');
                }
                break;
        }

        case 'delete_file' : {
                //
                //      Delete a file from this artifact
                //
                
                $ah=new ArtifactHtml($ath,$aid);

                // Check permissions
                $file_array = $ah->getAttachedFile($id);
		if ( user_ismember($group_id) ||
                (user_getname(user_getid()) == $file_array['user_name'] )) {

                        $afh=new ArtifactFileHtml($ah,$id);
                        if (!$afh || !is_object($afh)) {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_create_file_obj',$afh->getName()));
                        } elseif ($afh->isError()) {
                                $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage().'::'.$afh->getName());
                        } else {
                                if (!$afh->delete()) {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','file_delete',$afh->getErrorMessage()));
                                } else {
                                        $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','file_delete_success'));
                                }
                        }
                        $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&aid='. $aid .'&func=detail');

                } else {
                        // Invalid permission
                        exit_permission_denied();
                        return;
                }
        
                break;
        }


        case 'postmod' : {
                //
                //      Modify an Artifact
                //
                $ah=new ArtifactHtml($ath,$aid);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
                } else if ($ah->isError()) {
                        exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                } else {

                        // Check if users can update anonymously
                        if ( !user_isloggedin() && !$ath->allowsAnon() ) {
                            exit_not_logged_in();
                        }

                        // Check timestamp
                        if ( isset($_REQUEST['artifact_timestamp']) &&
                             ($ah->getLastUpdateDate()>$_REQUEST['artifact_timestamp']) ) {
                            // Artifact was updated between the time it was sent to the user, and the time it was submitted
                            exit_error($Language->getText('tracker_index','artifact_has_changed_title'),$Language->getText('tracker_index','artifact_has_changed',"/tracker/?func=detail&aid=$aid&atid=$atid&group_id=$group_id"));
                       }

                        // First check parameters
                        
                        // CC
                        $array_add_cc = split('[,;]', $add_cc);
                    if ($add_cc && !util_validateCCList($array_add_cc, $message)) {
                        exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
                    }
                    // Files
                        if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && !util_check_fileupload($input_file)) {
                                exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename'));
                        }

                        //Check Field Dependencies
                        require_once('common/tracker/ArtifactRulesManager.class.php');
                        $arm =& new ArtifactRulesManager();
                        if (!$arm->validate($atid, $art_field_fact->extractFieldList(), $art_field_fact)) {
                            exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_field_dependency'));
                        }
                        
                        //data control layer
                        $changed = $ah->handleUpdate($artifact_id_dependent,$canned_response,$changes);
                        if (!$changed) {
                                $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&func=browse');
                                exit();
                        }
                
                        //
                        //  Attach file to this Artifact.
                        //
                        if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                                $afh=new ArtifactFileHtml($ah);
                                if (!$afh || !is_object($afh)) {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_create_file'));
                                        } elseif ($afh->isError()) {
                                                $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                                } else {
                                        if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
                                            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','file_upload_err',$afh->getErrorMessage()));
                                                $was_error=true;
                                        } else {
                                                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','file_upload_success'));
                                        }
                                }
                        }

                        // Add new cc if any
                        if ($add_cc) {
                            $changed |= $ah->addCC($add_cc,$cc_comment,$changes);
                        }
                        if ($changed && $changes) {
                            $agnf =& new ArtifactGlobalNotificationFactory();
                            $addresses = $agnf->getAllAddresses($ath->getID(), true);
                            $ah->mailFollowupWithPermissions($addresses, $changes);
                        }

                        // Update the 'last_update_date' artifact field
                        $res_last_up = $ah->update_last_update_date();
    
                        //
                        //      Show just one feedback entry if no errors
                        //
                        if (!isset($was_error) || !$was_error) {
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','update_success'));
                        }
                        $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&func=browse');
                }
                break;
        }

	case 'postmasschange' : {
                //
                //      Modify several Artifacts
                //
	        // Check if users can update anonymously
                if ( !user_isloggedin() && !$ath->allowsAnon() ) {
		  exit_not_logged_in();
                }
                        
                if ( !$ath->userIsAdmin() ) {
		  exit_permission_denied();
		  return;
                }

		// First check parameters
                        
                // CC
                $array_add_cc = split('[,;]', $add_cc);
		if ($add_cc && !util_validateCCList($array_add_cc, $message)) {
		  exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
		}
		// Files
                if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE && !util_check_fileupload($input_file)) {
		  exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename'));
                }

		if ($report_id) {
		  // Create factories
		  $report_fact = new ArtifactReportFactory();
		  // Create the HTML report object
		  $art_report_html = $report_fact->getArtifactReportHtml($report_id,$atid);
		  $query = $art_field_fact->extractFieldList(true,'query_');
		  $art_report_html->getQueryElements($query,$advsrch,$from,$where);
		  $sql = "select distinct a.artifact_id ".$from." ".$where;

		  $result = db_query($sql);
		  $number_aid = db_numrows($result);
		} else {
		  reset($mass_change_ids);
		  $number_aid = count($mass_change_ids);
		}
		
		$feedback = '';
		for ($i = 0; $i<$number_aid; $i++) {
		  if ($report_id) {
		    $row = db_fetch_array($result);
		    $aid = $row['artifact_id'];
		    
		  } else {
		    $aid = $mass_change_ids[$i];
		  }

		  $ah=new ArtifactHtml($ath,$aid);
		  if (!$ah || !is_object($ah)) {
		    exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
		  } else if ($ah->isError()) {
		    exit_error($Language->getText('global','error'),$ah->getErrorMessage());
		  } else {
                        
                    //data control layer
		    $changed = $ah->handleUpdate($artifact_id_dependent,$canned_response,$changes,true);
		    if ($changed) {
		      if ($i > 0) $feedback .= ",";
		      if ($i == 0) $feedback .= $Language->getText('tracker_index','updated_aid');
		      $feedback .= " $aid";
				    
		    }
		    //
		    //  Attach file to this Artifact.
		    //
		    if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
		      $afh=new ArtifactFileHtml($ah);
		      if (!$afh || !is_object($afh)) {
			$GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_create_file'));
		      } elseif ($afh->isError()) {
			$GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
		      } else {
			if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
			  $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','file_upload_err',$afh->getErrorMessage()));
			  $was_error=true;
			}
		      }
		    }
		    
		    // Add new cc if any
		    if ($add_cc) {
		      $changed |= $ah->addCC($add_cc,$cc_comment,$changes,true);
		    }

                    // Update the 'last_update_date' artifact field
                    // Should check that the artifact was really modified?
                    $res_last_up = $ah->update_last_update_date();

                
		  }
		}
        $GLOBALS['Response']->addFeedback('info', $feedback);
        
		//Delete cc if any
		if ($delete_cc) {
		  $ath->deleteCC($delete_cc);
		}

		//Delete attached files
		if ($delete_attached) {
		    $ath->deleteAttachedFiles($delete_attached);
		}

		//Delete dependencies if any
		if ($delete_depend) {
		  $ath->deleteDependencies($delete_depend);
		}


		//update group history
		$old_value = $ath->getName();
		group_add_history('mass_change',$old_value,$group_id);

		//
		//      Show just one feedback entry if no errors
		//
		if (!$was_error) {
		  $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_index','update_success'));
		}
		require('./browse.php');
                break;
        }
        
        case 'postaddcomment' : {
            //
            //  Attach a comment to an artifact
            //  Used by non-admins
            //
            $ah=new ArtifactHtml($ath,$aid);
            if (!$ah || !is_object($ah)) {
                exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
            } else if ($ah->isError()) {
                exit_error($Language->getText('global','error'),$ah->getErrorMessage());
            }

            if ($comment) {
                if (!$ah->addComment($comment,$email,$changes)) {
                    exit_error($Language->getText('global','error'), $Language->getText('tracker_index','not_saved_comment'));
                }
            }
            
            //
            // Add CC
            //
            if (isset($_REQUEST['add_cc'])) {
                $add_cc = trim($_REQUEST['add_cc']);
                if ($add_cc !== "") {
                    $ah->addCC($add_cc,$cc_comment,$changes);
                }
            }
            
            
            //
            //  Attach file to this Artifact.
            //
            if (isset($_FILES['input_file']['error']) && $_FILES['input_file']['error'] != UPLOAD_ERR_NO_FILE) {
                
                if (!util_check_fileupload($input_file)) {
                    exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename_attach'));
                }
                
                $afh=new ArtifactFileHtml($ah);
                if (!$afh || !is_object($afh)) {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index','not_create_file'));
                } elseif ($afh->isError()) {
                    $GLOBALS['Response']->addFeedback('error', $afh->getErrorMessage());
                } else {
                    if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_index', 'not_attach_file',$afh->getErrorMessage()));
                    }
                }
            }
            
            // send an email to notify the user of the bug update
            $agnf =& new ArtifactGlobalNotificationFactory();
            $addresses = $agnf->getAllAddresses($ath->getID(), true);
            $ah->mailFollowupWithPermissions($addresses, $changes);
            $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&func=browse');
            break;
        }

	case 'editcomment' : {
	   
	    if ( !user_isloggedin()) {
	        exit_not_logged_in();
	        return;
	    }
	    $ah=new ArtifactHtml($ath,$aid);
	    if (!$ah || !is_object($ah)) {
                exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
            } else {
		require('./edit_comment.php');
	    }
	    break;
	
	}
        
        case 'import' : {
	   if ( !user_isloggedin()) {
	     exit_not_logged_in();
	     return;
	   }
                        
	   //
	   //  make sure this person has permission to import artifacts
	   //
	   if (!$ath->userIsAdmin()) {
	     exit_permission_denied();
	   }
	   $user_id = user_getid();
	   
	   

	   if($group_id && $atid && $user_id) {

	     $import = new ArtifactImportHtml($ath,$art_field_fact,$group);
	     if (isset($mode) && $mode == "parse") {
	       $import->displayParse($csv_filename);
	     } else if (isset($mode) && $mode == "import") {
	       for ($i=0; $i < $count_artifacts; $i++) {
		 for ($c=0; $c < count($parsed_labels); $c++) {
		   $label = $parsed_labels[$c];
		   $var_name = "artifacts_data_".$i."_".$c;
		   $data[$label] = $$var_name;
		   //echo "insert $label,".$$var_name." into data<br>";
		 }
		 $artifacts_data[] = $data;
	       }
	       $import->displayImport($parsed_labels,$artifacts_data,$aid_column,$count_artifacts);
	       require('./browse.php');

	     } else if (isset($mode) && $mode == "showformat") {
	       $import->displayShowFormat();
	     } else {
	       $import->displayCSVInput($atid,$user_id);
	     }
	   } else {
	     exit_no_group();
	   }
	   break;
        }
        
        case 'export' : {
	  require('./export.php');
                break;
        }
        case 'updatecomment':
            if (user_isloggedin() && isset($_REQUEST['followup_update'])) {
                $ah = new ArtifactHtml($ath,$_REQUEST['artifact_id']);
                if ($ah->updateFollowupComment($_REQUEST['artifact_history_id'],$_REQUEST['followup_update'],$changes)) {  
                    $GLOBALS['Response']->addFeedback('info',$GLOBALS['Language']->getText('tracker_common_artifact','followup_upd_succ'));		  
                    $agnf =& new ArtifactGlobalNotificationFactory();
                    $addresses = $agnf->getAllAddresses($ath->getID());
                    $ah->mailFollowupWithPermissions($addresses,$changes);
                } else {
                    $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('tracker_common_artifact','followup_upd_fail'));
                }
            }
            $GLOBALS['Response']->redirect('?group_id='. $group_id .'&atid='. $atid .'&aid='. $_REQUEST['artifact_id'] .'&func=detail');
            break;
        case 'browse' : {
                $masschange = false;
                require('./browse.php');
                break;
        }
        
        case 'masschange' : {
	  $masschange = true;
	  $export = false;
                require('./browse.php');
                break;
        }
        
        case 'masschange_detail' : {
	        $ah=new ArtifactHtml($ath);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
                } else {
		        require('./masschange_detail.php');
		}
                break;
        }
        
        
        case 'detail' : {
                //
                //      users can modify their own tickets if they submitted them
                //      even if they are not artifact admins
                //
                $ah=new ArtifactHtml($ath,$aid);
                if (!$ah || !is_object($ah)) {
                        exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
                } else if ($ah->isError()) {
                        exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                } else {
                        
                        // Check if users can browse anonymously
                        if ( !user_isloggedin() && !$ath->userCanView($GLOBALS['UGROUP_ANONYMOUS']) ) {
                            exit_not_logged_in();
                        }
                        
                        if (browser_is_netscape4()) {
                            $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_index','browser_not_supported',$Language->getText('tracker_index','an_artif')));
                        }
                        if ( user_ismember($group_id) ) {
                                require('./mod.php');
                        } else {
                                require('./detail.php');
                        }
                }
                break;
        }

	case 'copy': {
	  $ah=new ArtifactHtml($ath,$aid);
      if (!$ah || !is_object($ah)) {
	    exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));
	  } else if ($ah->isError()) {
	    exit_error($Language->getText('global','error'),$ah->getErrorMessage());
	  } else {

	    // Check if users can browse anonymously
                        if ( !user_isloggedin() && !$ath->allowsAnon() ) {
                            exit_not_logged_in();
                        }
                        
                        if (browser_is_netscape4()) {
                            $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_index','browser_not_supported',$Language->getText('tracker_index','an_artif')));
                        }

			// !!!! need to specify here for which users we allow to copy artifacts !!!!
                        if ( user_ismember($group_id) ) {
                                require('./copy.php');
                        } else {
                             exit_error($Language->getText('global','error'),$Language->getText('tracker_index', 'not_create_art'));   
                        }

	  }
	  break;
	}

        case 'reporting': {

                if ( !user_isloggedin() ) {
                        exit_not_logged_in();
                        return;
                }
                
            if ( !$ath->userIsAdmin() ) {
                        exit_permission_denied();
                        return;
                }

            if (isset($field) && $field) {
              if ($field == 'aging') {
                        $ath->reportingByAge();
              } else {
                        // It's any of the select box field. 
                        $ath->reportingByField($field);
              }
                
            } else {
              $ath->reportingMainPage();
            }
            break;
        }
        default: {
                require('./browse.php');
                break;
        }
        
        } // switch
} elseif ($group_id) {
        //        
        //  get the Group object
        //        
        $group = group_get_object($group_id);
        if (!$group || !is_object($group) || $group->isError()) {
                exit_no_group();
        }                  
        $atf = new ArtifactTypeFactory($group);
        if (!$group || !is_object($group) || $group->isError()) {
	  exit_error($Language->getText('global','error'), $Language->getText('tracker_import_admin', 'not_get_atf'));
        }
        
        // Get the artfact type list
        $at_arr = $atf->getArtifactTypes();
        
        //required params for site_project_header();
        $params['group']=$group_id;
        $params['toptab']='tracker';
        $params['pagename']='trackers';
        $params['title']=$Language->getText('tracker_index','trackers_for',$group->getPublicName());
        $params['sectionvals']=array($group->getPublicName());
        $params['help']='TrackerService.html';
        $params['pv']  = isset($pv)?$pv:'';

        echo site_project_header($params);
        echo '<strong>';
        // Admin link and create link are only displayed if the user is a project administrator
        if (user_ismember($group_id, 'A')) {
            echo '<a href="/tracker/admin/?group_id='.$group_id.'">'.$Language->getText('tracker_index','admin_all_trackers').'</a>';
            echo ' | <a href="/tracker/admin/?group_id='.$group_id.'&func=create">'.$Language->getText('tracker_index','create_new_tracker').'</a>';
            if ($params['help']) {
                echo ' | ';
            }
        }
        if ($params['help']) {
            echo help_button($params['help'],false,$Language->getText('global','help'));
        }
        echo "</strong><p>";
        
        if (!$at_arr || count($at_arr) < 1) {
	  echo '<h2>'.$Language->getText('tracker_index','no_accessible_trackers_hdr').'</h2>';
	  echo '<p>'.$Language->getText('tracker_index','no_accessible_trackers_msg').'</p>';
        } else {
            echo "<p>".$Language->getText('tracker_index','choose_tracker');
            if (!isset($pv) || !$pv) {
                echo " ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> )";
            }
            echo "<p>";

            //
            // Put the result set (list of trackers for this group) into a column with folders
            //
            for ($j = 0; $j < count($at_arr); $j++) {
                if ($at_arr[$j]->userCanView()) {
                    echo '
                        <a href="/tracker/?atid='. $at_arr[$j]->getID() .
                        '&group_id='.$group_id.'&func=browse">' .
                        html_image("ic/tracker20w.png",array("border"=>"0","width"=>"20","height"=>"20"),0) .
                        '&nbsp;'.
                        $at_arr[$j]->getName() .'</a> ';
                    // Only show number of artifacts if the user has full access on the tracker.
                    if ($at_arr[$j]->userHasFullAccess()) {
                        echo '( <strong>'. $at_arr[$j]->getOpenCount() .' '.$Language->getText('tracker_index','open').' / '. $at_arr[$j]->getTotalCount() .' '.$Language->getText('tracker_index','total').'</strong> )';
                    }
                    echo '<br />'.$at_arr[$j]->getDescription() .'<p>';
                }
            }
        }
        echo site_project_footer($params);
} else {
    exit_no_group();
}
?>
