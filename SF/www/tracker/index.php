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

				// send an email to notify the user of the bug update
				$ah->mailFollowup($ath->getEmailAddress(),$changes);
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
		}
		break;
	}

    case 'delete_dependent' : {
    	
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
			//data control layer
			$changed = $ah->handleUpdate($artifact_id_dependent,$canned_response,$changes);
		
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
					if (!util_check_fileupload($input_file)) {
						exit_error("Error","Invalid filename");
					}
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
		
			// Add new dependency if any
			if ($artifact_id_depend) {
			    $changed |= $ah->addDependencies($artifact_id_dependent,$changes);
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
			if ( $ah->ArtifactType->userIsTech() ) {
				include './mod.php';
			} else {
				include './detail.php';
			}
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
	
	echo site_project_header($params);
	echo '<strong>'
		 .'<a href="/tracker/admin/?group_id='.$group_id.'">Admin Trackers</a>'
		 .'</strong><p>';
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
	exit_no_group();
}
?>
