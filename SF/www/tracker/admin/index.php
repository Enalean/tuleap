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
require('../include/ArtifactTypeHtml.class');

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

	switch ( $func ) {
	case 'permissions':
		include './browse_perm.php';
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
