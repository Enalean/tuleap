<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$sql="SELECT * FROM patch WHERE patch_id='$patch_id'";

$result=db_query($sql);

$group_id=db_result($result,0,'group_id');

if ((db_numrows($result) > 0) && (user_ismember($group_id,'C2'))) {

	//user is uploading a new version of the patch

	if ($uploaded_data) {
		$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
		if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
			$codesql=", code='$code', filename='$uploaded_data_name',".
			    "filesize='$uploaded_data_size',".
			    "filetype='$uploaded_data_type'";
			 patch_history_create('Patch Code','Modified - New Version',$patch_id);
		} else {
			$feedback .= ' Patch not changed - patch must be non null and < '.$sys_max_size_upload.' chars in length ';
			$codesql='';
		}
	} else {
		$codesql='';
	}

	/*
		See which fields changed during the modification
	*/
	if (db_result($result,0,'patch_status_id') != $patch_status_id) { patch_history_create('patch_status_id',db_result($result,0,'patch_status_id'),$patch_id);  }
	if (db_result($result,0,'patch_category_id') != $patch_category_id) { patch_history_create('patch_category_id',db_result($result,0,'patch_category_id'),$patch_id);  }
	if (db_result($result,0,'assigned_to') != $assigned_to) { 
		patch_history_create('assigned_to',db_result($result,0,'assigned_to'),$patch_id);  

//////////////add notification of former assignee

	}
	if (db_result($result,0,'summary') != stripslashes(htmlspecialchars($summary))) 
		{ patch_history_create('summary',htmlspecialchars(addslashes(db_result($result,0,'summary'))),$patch_id);  }

	/*
		Details field is handled a little differently
	*/
	if ($details != '') { patch_history_create('details',htmlspecialchars($details),$patch_id);  }

	/*
		Enter the timestamp if we are changing to closed
	*/
	if ($patch_status_id == "2" || $patch_status_id == "4") {
		$now=time();
		$close_date=", close_date='$now' ";
		patch_history_create('close_date',db_result($result,0,'close_date'),$patch_id);
	} else {
		$close_date='';
	}

	/*
		Finally, update the patch itself
	*/
	$sql="UPDATE patch SET patch_status_id='$patch_status_id'$close_date $codesql, patch_category_id='$patch_category_id', ".
		"assigned_to='$assigned_to', summary='".htmlspecialchars($summary)."' ".
		"WHERE patch_id='$patch_id'";

	$result=db_query($sql);

	if (!$result) {
		patch_header(array ('title'=>'Patch Modification Failed'));
		echo '
			<H1>Error - update failed!</H1>';
		echo db_error();
		echo $sql;
		patch_footer(array());
		exit;
	} else {
		$feedback .= " Successfully Modified Patch ";
	}

	/*
		see if we're supposed to send all modifications to an address
	*/
	$project=project_get_object($group_id);
	if ($project->sendAllPatchUpdates()) {
		$address=$project->getNewPatchAddress();
	}       

	/*
		now send the email
		it's no longer optional due to the group-level notification address
	*/
	mail_followup($patch_id,$address);

} else {

	exit_permission_denied();

}

?>
