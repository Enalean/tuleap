<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if (!$patch_category_id) {
	$patch_category_id=100;
}

if ($upload_instead) {
	$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
	if ((strlen($code) > 20) && (strlen($code) < 512000)) {
		//size is fine
		$feedback .= ' Patch Uploaded ';
	} else {
		//too big or small
		$feedback .= ' ERROR - patch must be > 20 chars and < 512000 chars in length ';
		$code='';
	}
}

if (!user_isloggedin()) {
	$user=100;
} else {
	$user=user_getid();
}

if (!$group_id || !$summary || !$code) {
	exit_error('Missing Info',$feedback.' - Go Back and fill in all the information requested');
}

$sql="INSERT INTO patch (close_date,group_id,patch_status_id,patch_category_id,submitted_by,assigned_to,open_date,summary,code) ".
	"VALUES ('0','$group_id','1','$patch_category_id','$user','100','".time()."','".htmlspecialchars($summary)."','".htmlspecialchars($code)."')";

$result=db_query($sql);

if (!$result) {

	patch_header(array ('title'=>'Patch Submission Failed'));
	echo '
		<H1>Error - Go Back and Try Again!</H1>';
	echo db_error();
	echo $sql;
	patch_footer(array());
	exit;

} else {
	$feedback .= ' Successfully Added Patch ';
}

$project=project_get_object($group_id);

mail_followup(db_insertid($result), $project->getNewPatchAddress());

?>
