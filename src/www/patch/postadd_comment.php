<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

if ($details != '') { 
	patch_history_create('details',htmlspecialchars($details),$patch_id);  
	$feedback .= ' Comment added to patch ';
	$project=project_get_object($group_id);
	if ($project->sendAllPatchUpdates()) {
		$address=$project->getNewPatchAddress();
	}
	mail_followup($patch_id,$address);
}

//user is uploading a new version of the patch
if ($upload_new && user_isloggedin()) {

	//see if this user submitted this patch
	$result=db_query("SELECT * FROM patch WHERE submitted_by='".user_getid()."' AND patch_id='$patch_id'");
	if (!$result || db_numrows($result) < 1) {
		exit_error('ERROR','Only the original submittor of a patch can upload a new version.
			If you submitted your patch anonymously, contact the admin of this project for instructions.');
		echo db_error();
	} else {
		//patch for this user was found, so update it now

		$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
		if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {

			$result=db_query("UPDATE patch SET code='".htmlspecialchars($code)."' WHERE submitted_by='".user_getid()."' AND patch_id='$patch_id'");

			//see if the update actually worked
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' Patch not changed - error ';
				echo db_error();
			} else {
				patch_history_create('Patch Code','Modified - New Version',$patch_id);
				$feedback .= ' Patch Code Updated ';
			}
		} else {
			exit_error('ERROR','Patch not changed - patch must be non null and < '.$sys_max_size_upload.' chars in length');
		}
	}
} else if ($upload_new) {
	exit_not_logged_in();
}

?>
