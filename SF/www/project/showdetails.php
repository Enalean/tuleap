<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require_once('pre.php');

$Language->loadLanguageMsg('project/project');

// Check if group_id is valid
if (!$group_id) {
	exit_error($Language->getText('project_index','g_missed'),$Language->getText('project_index','must_spec_g'));
}

site_project_header(array('title'=>$Language->getText('project_showdetails','proj_details'),'group'=>$group_id,'toptab'=>'summary'));

print '<P><h3>'.$Language->getText('project_showdetails','proj_details').'</h3>';

// Now fetch the project details

$result=db_query("SELECT register_purpose,patents_ips,required_software,other_comments, license_other ".
		"FROM groups ".
		"WHERE group_id='$group_id'");

if (!$result || db_numrows($result) < 1) {
	echo db_error();
	exit_error($Language->getText('project_showdetails','proj_not_found'),$Language->getText('project_showdetails','no_detail'));
}

	$register_purpose = db_result($result,0,'register_purpose');
	$patents_ips = db_result($result,0,'patents_ips');
	$required_software = db_result($result,0,'required_software');
	$other_comments = db_result($result,0,'other_comments');
	$license_other = db_result($result,0,'license_other');
?>

<P>
<b><u><?php echo $Language->getText('project_showdetails','proj_desc'); ?></u></b>
<P><?php echo ($register_purpose == '') ? $Language->getText('global','none').'.' : util_make_links( nl2br ( $register_purpose)) ; ?>

<P>
<b><u><?php echo $Language->getText('project_showdetails','ip_patents'); ?></u></b>
<P><?php echo ($patents_ips == '') ? $Language->getText('global','none').'.' : util_make_links( nl2br ($patents_ips)) ; ?>

<P>
<b><u><?php echo $Language->getText('project_showdetails','soft_required'); ?></u></b>
<P><?php echo ($required_software == '') ? $Language->getText('global','none').'.' : util_make_links( nl2br ($required_software)) ; ?>

<P>
<b><u><?php echo $Language->getText('project_showdetails','misc_comments'); ?></u></b>
<P><?php echo ($other_comments == '') ? $Language->getText('global','none').'.' : util_make_links( nl2br ($other_comments)) ; ?>

<?php

if ($license_other != '') {
	print '<P>';
	print '<b><u>'.$Language->getText('project_showdetails','license_comment').'</u></b>';
	print '<P>'.util_make_links( nl2br ($license_other));
}

print '<P><a href="/project/?group_id='.$group_id .'"> '.$Language->getText('project_showdetails','back_main').' </a>';

site_project_footer(array());

?>
