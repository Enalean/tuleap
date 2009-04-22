<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
require_once('pre.php');

$hp = Codendi_HTMLPurifier::instance();
// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$currentproject= new project($group_id);

site_project_header(array('title'=>$Language->getText('project_showdetails','proj_details'),'group'=>$group_id,'toptab'=>'summary'));

print '<P><h3>'.$Language->getText('project_showdetails','proj_details').'</h3>';

// Now fetch the project details

$result=db_query("SELECT license_other ".
		"FROM groups ".
		"WHERE group_id=".db_ei($group_id));

if (!$result || db_numrows($result) < 1) {
	echo db_error();
	exit_error($Language->getText('project_showdetails','proj_not_found'),$Language->getText('project_showdetails','no_detail'));
}

$license_other = db_result($result,0,'license_other');

$currentproject->displayProjectsDescFieldsValue();	
	
?>

<?php

if ($license_other != '') {
	print '<P>';
	print '<b><u>'.$Language->getText('project_admin_editgroupinfo','license_comment').'</u></b>';
	print '<P>'.$hp->purify(util_unconvert_htmlspecialchars($license_other), CODENDI_PURIFIER_BASIC, $group_id);
}

print '<P><a href="/project/?group_id='.$group_id .'"> '.$Language->getText('project_showdetails','back_main').' </a>';

site_project_footer(array());

?>
