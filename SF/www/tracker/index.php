<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//	Originally by to the SourceForge Team,1999-2000
//
//  Written for CodeX by Stephane Bouhet
//

require('pre.php');
require($DOCUMENT_ROOT.'/../common/tracker/Artifact.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFile.class');
require('./include/ArtifactFileHtml.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require('./include/ArtifactTypeHtml.class');
require('./include/ArtifactHtml.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactGroup.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactCanned.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReportFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReport.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReportField.class');
require('./include/ArtifactFieldHtml.class');
require('./include/ArtifactReportHtml.class');

if ($group_id && $atid) {

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
	// Check if this tracker is valid (not deleted)
	if ( !$ath->isValid() ) {
		exit_error('Error',"This tracker is no longer valid.");
	}

	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($ath);

	switch ($func) {
	case 'add' : {
		$ah=new ArtifactHtml($ath);
		if (!$ah || !is_object($ah)) {
			exit_error('ERROR','Artifact Could Not Be Created');
		} else {
			include './add.php';
		}
		break;
	}
	
	case 'postadd' : {
		//
		//		Create a new Artifact
		//	

		$ah=new ArtifactHtml($ath);
		if (!$ah || !is_object($ah)) {
			exit_error('ERROR','Artifact Could Not Be Created');
		} else {
			// Check if a user can submit a new without loggin
			if ( !user_isloggedin() && !$ath->allowsAnon() ) {
				exit_not_logged_in();
				return;
			}
			
			//
			//  make sure this person has permission to add artifacts
			//
			if (!$ath->userIsTech() && !$ath->allowsAnon() ) {
				exit_permission_denied();
			}

			// First check parameters
			
			// CC
		    if ($add_cc && !$ah->validateCCList(util_split_emails($add_cc), $message)) {
		        exit_error("Error - The CC list is invalid", $message);
		    }
		    // Files
			if ($add_file && !util_check_fileupload($input_file)) {
				exit_error("Error","Invalid filename");
			}

			// Artifact creation		    
			if (!$ah->create()) {
				exit_error('ERROR',$ah->getErrorMessage());
			} else {
				//
				//	Attach file to this Artifact.
				//
				if ($add_file) {
					$afh=new ArtifactFileHtml($ah);
					if (!$afh || !is_object($afh)) {
						$feedback .= 'Could Not Create File Object';
					} elseif ($afh->isError()) {
						$feedback .= $afh->getErrorMessage();
					    exit_error('ERROR',$feedback);
					} else {
						if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
							$feedback .= ' Could Not Attach File to Item: '.$afh->getErrorMessage();
						    exit_error('ERROR',$feedback);
						}
					}
				}

				// Add new cc if any
				if ($add_cc) {
				    $ah->addCC($add_cc,$cc_comment,$changes);
				}

				// send an email to notify the user of the artifact update
				$ah->mailFollowup($ath->getEmailAddress(),$null);
				$feedback .= ' Item Successfully Created ';
			    include './browse.php';
			}
		}
		break;
	}
	
    case 'delete_cc' : {
    	
		$ah=new ArtifactHtml($ath,$aid);
		if (!$ah || !is_object($ah)) {
			exit_error('ERROR','Artifact Could Not Be Created');
		} else if ($ah->isError()) {
			exit_error('ERROR',$ah->getErrorMessage());
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
				include './browse.php';
			
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
			exit_error('ERROR','Artifact Could Not Be Created');
		} else if ($ah->isError()) {
			exit_error('ERROR',$ah->getErrorMessage());
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
			include './browse.php';
		}
		break;
	}

	case 'delete_file' : {
		//
		//	Delete a file from this artifact
		//
		
		$ah=new ArtifactHtml($ath,$aid);

		// Check permissions
		$file_array = $ah->getAttachedFile($id);
	    if ( $ath->userIsTech() ||
		(user_getname(user_getid()) == $file_array['user_name'] )) {

			$afh=new ArtifactFileHtml($ah,$id);
			if (!$afh || !is_object($afh)) {
				$feedback .= 'Could Not Create File Object::'.$afh->getName();
			} elseif ($afh->isError()) {
				$feedback .= $afh->getErrorMessage().'::'.$afh->getName();
			} else {
				if (!$afh->delete()) {
					$feedback .= ' <br>File Delete: '.$afh->getErrorMessage();
				} else {
					$feedback .= ' <br>File Delete: Successful ';
				}
			}
			// unsent artifact_id var to make sure that it doesn;t
			// impact the next artifact query.
			unset($aid);
			unset($HTTP_GET_VARS['aid']);
			include './browse.php';

		} else {
			// Invalid permission
			exit_permission_denied();
			return;
		}
	
		break;
	}

	case 'postmod' : {
		//
		//	Modify an Artifact
		//
		$ah=new ArtifactHtml($ath,$aid);
		if (!$ah || !is_object($ah)) {
			exit_error('ERROR','Artifact Could Not Be Created');
		} else if ($ah->isError()) {
			exit_error('ERROR',$ah->getErrorMessage());
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
		    if ($add_cc && !$ah->validateCCList(util_split_emails($add_cc), $message)) {
		        exit_error("Error - The CC list is invalid", $message);
		    }
		    // Files
			if ($add_file && !util_check_fileupload($input_file)) {
				exit_error("Error","Invalid filename");
			}

			//data control layer
			$changed = $ah->handleUpdate($artifact_id_dependent,$canned_response,$changes);
			if (!$changed) {
				include './browse.php';
			}
		
			//
			//  Attach file to this Artifact.
			//
			if ($add_file) {
				$afh=new ArtifactFileHtml($ah);
				if (!$afh || !is_object($afh)) {
					$feedback .= 'Could Not Create File Object';
					} elseif ($afh->isError()) {
						$feedback .= $afh->getErrorMessage();
				} else {
					if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
						$feedback .= ' <br>File Upload: '.$afh->getErrorMessage();
						$was_error=true;
					} else {
						$feedback .= ' <br>File Upload: Successful ';
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
			    $ah->mailFollowup($address,$changes);
			}

	
			//
			//	Show just one feedback entry if no errors
			//
			if (!$was_error) {
				$feedback = 'Successfully Updated';
			}
			include './browse.php';
		}
		break;
	}
	
	case 'postaddcomment' : {
		//
		//	Attach a comment to an artifact
		//	Used by non-admins
		//
		$ah=new ArtifactHtml($ath,$aid);
		if (!$ah || !is_object($ah)) {
			exit_error('ERROR','Artifact Could Not Be Created');
		} else if ($ah->isError()) {
			exit_error('ERROR',$ah->getErrorMessage());
		} else {
			if ($ah->addComment($details,$email,$changes)) {
				//
				//	Attach file to this Artifact.
				//
				if ($add_file) {
					$afh=new ArtifactFileHtml($ah);
					if (!$afh || !is_object($afh)) {
						$feedback .= 'Could Not Create File Object';
					} elseif ($afh->isError()) {
						$feedback .= $afh->getErrorMessage();
					    exit_error('ERROR',$feedback);
					} else {
						if (!$afh->upload($input_file,$input_file_name,$input_file_type,$file_description,$changes)) {
							$feedback .= ' Could Not Attach File to Item: '.$afh->getErrorMessage();
						    exit_error('ERROR',$feedback);
						}
					}
				}

				// send an email to notify the user of the bug update
				$ah->mailFollowup($ath->getEmailAddress(),$changes);
			    include './browse.php';
			} else {
				//some kind of error in creation
				exit_error('ERROR',$feedback);
			}
		}
		break;
	}
	
	case 'browse' : {
		include './browse.php';
		break;
	}
	
	case 'detail' : {
		//
		//	users can modify their own tickets if they submitted them
		//	even if they are not artifact admins
		//
		$ah=new ArtifactHtml($ath,$aid);
		if (!$ah || !is_object($ah)) {
			exit_error('ERROR','Artifact Could Not Be Created');
		} else if ($ah->isError()) {
			exit_error('ERROR',$ah->getErrorMessage());
		} else {
			
			// Check if users can browse anonymously
			if ( $ath->allowsAnon() == 0 && !user_isloggedin() ) {
			    exit_not_logged_in();
			}
			
			if ( $ah->ArtifactType->userIsTech() ) {
				include './mod.php';
			} else {
				include './detail.php';
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
	
	default : {
		include './browse.php';
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
		exit_error('Error','Could Not Get ArtifactTypeFactory');
	}
	
	// Get the artfact type list
	$at_arr = $atf->getArtifactTypes();
	
	//required params for site_project_header();
	$params['group']=$group_id;
	$params['toptab']='trackers';
	$params['pagename']='trackers';
	$params['title']='Trackers';
	$params['sectionvals']=array($group->getPublicName());
	$params['help']='TrackerService.html';
	
	echo site_project_header($params);
	echo '<strong>'
		 .'<a href="/tracker/admin/?group_id='.$group_id.'">Admin All Trackers</a>';
	echo ' | <a href="/tracker/admin/?group_id='.$group_id.'&func=create">Create a New Tracker</a>';
	if ($params['help']) {
	    echo ' | '.help_button($params['help'],false,'Help');
	}
	echo "</strong><p>";
	
	if (!$at_arr || count($at_arr) < 1) {
		echo "<h1>No Accessible Trackers Found</h1>";
		echo "<p>
			<strong>No trackers have been set up, or you cannot view them.<p><FONT COLOR=RED>The Admin for this project ".
			"will have to set up data types using the <a href=\"/tracker/admin/?group_id=$group_id\">admin page</a></FONT></strong>";
	} else {
		echo '<p>Choose a tracker and you can browse/edit/add items to it.<p>';
		//
		// Put the result set (list of trackers for this group) into a column with folders
		//
		for ($j = 0; $j < count($at_arr); $j++) {
			echo '
			<a href="/tracker/?atid='. $at_arr[$j]->getID() .
			'&group_id='.$group_id.'&func=browse">' .
			html_image("ic/tracker20w.png",array("border"=>"0","width"=>"20","height"=>"20")) . ' &nbsp;'.
			$at_arr[$j]->getName() .'</a> 
			( <strong>'. $at_arr[$j]->getOpenCount() .' open / '. $at_arr[$j]->getTotalCount() .' total</strong> )<br />'.
			$at_arr[$j]->getDescription() .'<p>';
		}
	}
	echo site_project_footer(array());
} else {
	
	if ( $func == 'gotoid' ) {
		// Direct access to an artifact

		// Retrieve with the aid the group_artifact_id and the group_id
		if ( !util_get_ids_from_aid($aid,$group_id,$atid) ) {
			exit_error('ERROR','Invalid Artifact ID');
		} else {
		    if ($HTTPS == 'on'|| $GLOBALS['sys_force_ssl'] == 1) {
				$location = "Location: https://".$GLOBALS['sys_https_host'];
		    } else {
				$location = "Location: http://".$GLOBALS['sys_default_domain'];
			}
			$location .= "/tracker/?func=detail&aid=".$aid."&group_id=".$group_id."&atid=".$atid;
			header($location);
			exit;
		}
		
	} else {
		exit_no_group();
	}
}
?>
