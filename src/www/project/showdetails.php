<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// 
require_once('pre.php');

$Language->loadLanguageMsg('project/project');

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

site_project_header(array('title'=>$Language->getText('project_showdetails','proj_details'),'group'=>$group_id,'toptab'=>'summary'));

print '<P><h3>'.$Language->getText('project_showdetails','proj_details').'</h3>';

// Now fetch the project details

$result=db_query("SELECT register_purpose,patents_ips,required_software,other_comments, license_other ".
		"FROM groups ".
		"WHERE group_id=".db_ei($group_id));

if (!$result || db_numrows($result) < 1) {
	echo db_error();
	exit_error($Language->getText('project_showdetails','proj_not_found'),$Language->getText('project_showdetails','no_detail'));
}
$hp = CodeX_HTMLPurifier::instance();
	$register_purpose = db_result($result,0,'register_purpose');
	$patents_ips = db_result($result,0,'patents_ips');
	$required_software = db_result($result,0,'required_software');
	$other_comments = db_result($result,0,'other_comments');
	$license_other = db_result($result,0,'license_other');
?>

<P>
<b><u><?php echo $Language->getText('project_admin_editgroupinfo','long_desc'); ?></u></b>
<P><?php echo ($register_purpose == '') ? $Language->getText('global','none').'.' :  $hp->purify(util_unconvert_htmlspecialchars($register_purpose), CODEX_PURIFIER_LIGHT, $group_id)  ; ?>

<P>
<b><u><?php echo $Language->getText('project_admin_editgroupinfo','patents'); ?></u></b>
<P><?php echo ($patents_ips == '') ? $Language->getText('global','none').'.' :  $hp->purify(util_unconvert_htmlspecialchars($patents_ips), CODEX_PURIFIER_BASIC, $group_id)  ; ?>

<P>
<b><u><?php echo $Language->getText('project_admin_editgroupinfo','soft_required'); ?></u></b>
<P><?php echo ($required_software == '') ? $Language->getText('global','none').'.' :  $hp->purify(util_unconvert_htmlspecialchars($required_software), CODEX_PURIFIER_BASIC, $group_id)  ; ?>

<P>
<b><u><?php echo $Language->getText('project_admin_editgroupinfo','comments'); ?></u></b>
<P><?php echo ($other_comments == '') ? $Language->getText('global','none').'.' :  $hp->purify(util_unconvert_htmlspecialchars($other_comments), CODEX_PURIFIER_BASIC, $group_id)  ; ?>

<?php

if ($license_other != '') {
	print '<P>';
	print '<b><u>'.$Language->getText('project_admin_editgroupinfo','license_comment').'</u></b>';
	print '<P>'.$hp->purify(util_unconvert_htmlspecialchars($license_other), CODEX_PURIFIER_BASIC, $group_id);
}

print '<P><a href="/project/?group_id='.$group_id .'"> '.$Language->getText('project_showdetails','back_main').' </a>';

site_project_footer(array());

?>
