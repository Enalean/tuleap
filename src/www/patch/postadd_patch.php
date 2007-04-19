<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: postadd_patch.php 793 2004-01-27 17:30:44Z guerin $

if (!$patch_category_id) {
	$patch_category_id=100;
}


if ($uploaded_data) {
	$code = addslashes(fread( fopen($uploaded_data, 'r'), filesize($uploaded_data)));
	if ((strlen($code) > 0) && (strlen($code) < $sys_max_size_upload)) {
		//size is fine
		$feedback .= ' Patch Uploaded ';
	} else {
		//too big or small
	        $feedback .= ' ERROR - patch must be non null and < '.$sys_max_size_upload.' bytes in length ';
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

$sql="INSERT INTO patch (close_date,group_id,patch_status_id,patch_category_id,submitted_by,assigned_to,open_date,summary,code, filename,filesize,filetype) ".
"VALUES ('0','$group_id','1','$patch_category_id','$user','100','".time()."','".
htmlspecialchars($summary)."','".
($uploaded_data ? $code : htmlspecialchars($code))."',".
"'$uploaded_data_name','$uploaded_data_size','$uploaded_data_type')";


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
