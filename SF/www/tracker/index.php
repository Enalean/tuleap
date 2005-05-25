<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//      Originally by to the SourceForge Team,1999-2000
//
//  Written for CodeX by Stephane Bouhet
//


require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/tracker/Artifact.class');
require_once('common/tracker/ArtifactFile.class');
require('./include/ArtifactFileHtml.class');
require_once('common/tracker/ArtifactType.class');
require('./include/ArtifactTypeHtml.class');
require('./include/ArtifactHtml.class');
require_once('common/tracker/ArtifactGroup.class');
require_once('common/tracker/ArtifactCanned.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('common/tracker/ArtifactField.class');
require_once('common/tracker/ArtifactFieldFactory.class');
require_once('common/tracker/ArtifactReportFactory.class');
require_once('common/tracker/ArtifactReport.class');
require_once('common/tracker/ArtifactReportField.class');
require('./include/ArtifactFieldHtml.class');
require('./include/ArtifactReportHtml.class');

require_once('common/include/SimpleSanitizer.class');

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

        switch ($func) {
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
                        if (!$ath->userIsTech() && !$ath->isPublic() ) {
                                exit_permission_denied();
                        }

                        // First check parameters
                        
                        // CC
			if ($add_cc && !util_validateCCList(util_split_emails($add_cc), $message)) {
                        exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
			}
			// Files
                        if ($add_file && !util_check_fileupload($input_file)) {
                                exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename'));
                        }

                        // Artifact creation                
                        if (!$ah->create()) {
                                exit_error($Language->getText('global','error'),$ah->getErrorMessage());
                        } else {
                                //
                                //      Attach file to this Artifact.
                                //
                                if ($add_file) {
                                        $afh=new ArtifactFileHtml($ah);
                                        if (!$afh || !is_object($afh)) {
                                                $feedback .= $Language->getText('tracker_index','not_create_file');
                                        } elseif ($afh->isError()) {
                                                $feedback .= $afh->getErrorMessage();
                                            exit_error($Language->getText('global','error'),$feedback);
                                        } else {
                                                if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
                                                        $feedback .= ' '.$Language->getText('tracker_index','not_attach_file',$afh->getErrorMessage());
                                                    exit_error($Language->getText('global','error'),$feedback);
                                                }
                                        }
                                }

                                // Add new cc if any
                                if ($add_cc) {
                                    $ah->addCC($add_cc,$cc_comment,$changes);
                                }

                                // send an email to notify the user of the artifact update
                                $ah->mailFollowup($ath->getEmailAddress(),$null);
                                $feedback .= $Language->getText('tracker_index','create_success',$ah->getID());
                            require('./browse.php');
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
                        // (a) current user is a artifact tech
                        // (b) then CC name is the current user 
                        // (c) the CC email address matches the one of the current user
                        // (d) the current user is the person who added a gieven name in CC list
                        if ( $ath->userIsTech() ||
                        (user_getname(user_getid()) == $cc_array['email']) ||  
                        (user_getemail(user_getid()) == $cc_array['email']) ||
                        (user_getname(user_getid()) == $cc_array['user_name'] )) {

                                $changed = $ah->deleteCC($artifact_cc_id,$changes);
                                if ($changed) {
                                    //
                                    //  see if we're supposed to send all modifications to an address
                                    //
                                    if ($ath->emailAll()) {
                                                $address=$ath->getEmailAddress();
                                    }
                                    
                                    //
                                    //  now send the email
                                    //  it's no longer optional due to the group-level notification address
                                    //
                                    $ah->mailFollowup($address,$changes);
                                }
        
                                // unsent artifact_id var to make sure that it doesn;t
                                // impact the next artifact query.
                                unset($aid);
                                unset($HTTP_GET_VARS['aid']);
                                require('./browse.php');
                        
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
                
            if ( !$ath->userIsTech() ) {
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
                            //
                            //  see if we're supposed to send all modifications to an address
                            //
                            if ($ath->emailAll()) {
                                        $address=$ath->getEmailAddress();
                            }
                            
                            //
                            //  now send the email
                            //  it's no longer optional due to the group-level notification address
                            //
                            $ah->mailFollowup($address,$changes);
                        }

                        // unsent artifact_id var to make sure that it doesn;t
                        // impact the next artifact query.
                        unset($aid);
                        unset($HTTP_GET_VARS['aid']);
                        require('./browse.php');
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
            if ( $ath->userIsTech() ||
                (user_getname(user_getid()) == $file_array['user_name'] )) {

                        $afh=new ArtifactFileHtml($ah,$id);
                        if (!$afh || !is_object($afh)) {
                                $feedback .= $Language->getText('tracker_index','not_create_file_obj',$afh->getName());
                        } elseif ($afh->isError()) {
                                $feedback .= $afh->getErrorMessage().'::'.$afh->getName();
                        } else {
                                if (!$afh->delete()) {
                                        $feedback .= ' <br>'.$Language->getText('tracker_index','file_delete',$afh->getErrorMessage());
                                } else {
                                        $feedback .= ' <br>'.$Language->getText('tracker_index','file_delete_success');
                                }
                        }
                        // unsent artifact_id var to make sure that it doesn;t
                        // impact the next artifact query.
                        unset($aid);
                        unset($HTTP_GET_VARS['aid']);
                        require('./browse.php');

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
                        if ( $ath->allowsAnon() == 0 && !user_isloggedin() ) {
                            exit_not_logged_in();
                        }
                        
                        if ( !$ah->ArtifactType->userIsTech() ) {
                                exit_permission_denied();
                                return;
                        }

                        // First check parameters
                        
                        // CC
                    if ($add_cc && !util_validateCCList(util_split_emails($add_cc), $message)) {
                        exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
                    }
                    // Files
                        if ($add_file && !util_check_fileupload($input_file)) {
                                exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename'));
                        }

                        //data control layer
                        $changed = $ah->handleUpdate($artifact_id_dependent,$canned_response,$changes);
                        if (!$changed) {
                                require('./browse.php');
                        }
                
                        //
                        //  Attach file to this Artifact.
                        //
                        if ($add_file) {
                                $afh=new ArtifactFileHtml($ah);
                                if (!$afh || !is_object($afh)) {
                                        $feedback .= $Language->getText('tracker_index','not_create_file');
                                        } elseif ($afh->isError()) {
                                                $feedback .= $afh->getErrorMessage();
                                } else {
                                        if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
					  $feedback .= ' <br>'.$Language->getText('tracker_index','file_upload_err',$afh->getErrorMessage());
                                                $was_error=true;
                                        } else {
                                                $feedback .= ' <br>'.$Language->getText('tracker_index','file_upload_success');
                                        }
                                }
                        }

                        // Add new cc if any
                        if ($add_cc) {
                            $changed |= $ah->addCC($add_cc,$cc_comment,$changes);
                        }
                
                        if ($changed) {
                            //
                            //  see if we're supposed to send all modifications to an address
                            //
                            if ($ath->emailAll()) {
                                        $address=$ath->getEmailAddress();
                            }
                            
                            //
                            //  now send the email
                            //  it's no longer optional due to the group-level notification address
                            //
			    if ($changes)
			      $ah->mailFollowup($address,$changes);
                        }

        
                        //
                        //      Show just one feedback entry if no errors
                        //
                        if (!$was_error) {
                                $feedback = $Language->getText('tracker_index','update_success');
                        }
                        require('./browse.php');
                }
                break;
        }
        
        case 'postmasschange' : {
                //
                //      Modify several Artifacts
                //
	        // Check if users can update anonymously
                if ( $ath->allowsAnon() == 0 && !user_isloggedin() ) {
		  exit_not_logged_in();
                }
                        
                if ( !$ath->userIsAdmin() ) {
		  exit_permission_denied();
		  return;
                }

		// First check parameters
                        
                // CC
		if ($add_cc && !util_validateCCList(util_split_emails($add_cc), $message)) {
		  exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
		}
		// Files
                if ($add_file && !util_check_fileupload($input_file)) {
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
		    if ($add_file) {
		      $afh=new ArtifactFileHtml($ah);
		      if (!$afh || !is_object($afh)) {
			$feedback .= $Language->getText('tracker_index','not_create_file');
		      } elseif ($afh->isError()) {
			$feedback .= $afh->getErrorMessage();
		      } else {
			if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
			  $feedback .= ' <br>'.$Language->getText('tracker_index','file_upload_err',$afh->getErrorMessage());
			  $was_error=true;
			}
		      }
		    }
		    
		    // Add new cc if any
		    if ($add_cc) {
		      $changed |= $ah->addCC($add_cc,$cc_comment,$changes,true);
		    }

                
		  }
		}

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
		  $feedback .= ' - '.$Language->getText('tracker_index','update_success');
		}
		require('./masschange.php');
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

            if ($details) {
                if (!$ah->addComment($details,$email,$changes)) {
                    exit_error($Language->getText('global','error'), $Language->getText('tracker_index','not_saved_comment'));
                }
            }

            //
            //  Attach file to this Artifact.
            //
            if ($add_file) {
                
                if (!util_check_fileupload($input_file)) {
                    exit_error($Language->getText('global','error'),$Language->getText('tracker_index','invalid_filename_attach'));
                }
                
                $afh=new ArtifactFileHtml($ah);
                if (!$afh || !is_object($afh)) {
                    $feedback .= $Language->getText('tracker_index','not_create_file');
                } elseif ($afh->isError()) {
                    $feedback .= $afh->getErrorMessage();
                    exit_error($Language->getText('global','error'),$feedback);
                } else {
                    if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
                        $feedback .= ' '.$Language->getText('tracker_index', 'not_attach_file',$afh->getErrorMessage());
                        exit_error($Language->getText('global','error'),$feedback);
                    }
                }
            }
            
            // send an email to notify the user of the bug update
            $ah->mailFollowup($ath->getEmailAddress(),$changes);
            require('./browse.php');
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
	   require('./import.php');
	   break;
        }
        
        case 'browse' : {
                require('./browse.php');
                break;
        }
        
        case 'masschange' : {
                require('./masschange.php');
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
                        if ( $ath->allowsAnon() == 0 && !user_isloggedin() ) {
                            exit_not_logged_in();
                        }
                        
                        if (browser_is_netscape4()) {
			  $feedback .= $Language->getText('tracker_index','browser_not_supported',$Language->getText('tracker_index','an_artif'));
                        }
                        if ( $ah->ArtifactType->userIsTech() ) {
                                require('./mod.php');
                        } else {
                                require('./detail.php');
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

            if ($field) {
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
        $params['title']=$Language->getText('tracker_index','trackers_for');
        $params['sectionvals']=array($group->getPublicName());
        $params['help']='TrackerService.html';
        $params['pv']  = isset($pv)?$pv:'';

        echo site_project_header($params);
        echo '<strong>'
                 .'<a href="/tracker/admin/?group_id='.$group_id.'">'.$Language->getText('tracker_index','admin_all_trackers').'</a>';
        echo ' | <a href="/tracker/admin/?group_id='.$group_id.'&func=create">'.$Language->getText('tracker_index','create_new_tracker').'</a>';
        if ($params['help']) {
            echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
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
                    echo '
                        <a href="/tracker/?atid='. $at_arr[$j]->getID() .
                        '&group_id='.$group_id.'&func=browse">' .
                        html_image("ic/tracker20w.png",array("border"=>"0","width"=>"20","height"=>"20"),0) .
                        '&nbsp;'.
                        $at_arr[$j]->getName() .'</a> 
                        ( <strong>'. $at_arr[$j]->getOpenCount() .' '.$Language->getText('tracker_index','open').' / '. $at_arr[$j]->getTotalCount() .' '.$Language->getText('tracker_index','total').'</strong> )<br />'.
                        $at_arr[$j]->getDescription() .'<p>';
                }
        }
        echo site_project_footer($params);
} else {
    exit_no_group();
}
?>
