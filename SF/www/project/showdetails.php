<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require ('pre.php');

// Check if group_id is valid
if (!$group_id) {
	exit_error("Missing Group Argument","A group must be specified for this page.");
}

site_project_header(array('title'=>"Project Details",'group'=>$group_id,'toptab'=>'home'));

print "<P><h3>Project Details</h3>";

// Now fetch the project details

$result=db_query("SELECT register_purpose,patents_ips,required_software,other_comments, license_other ".
		"FROM groups ".
		"WHERE group_id='$group_id'");

if (!$result || db_numrows($result) < 1) {
	echo db_error();
	exit_error("Error - Project Not Found","No detail available for this project");
}

	$register_purpose = db_result($result,0,'register_purpose');
	$patents_ips = db_result($result,0,'patents_ips');
	$required_software = db_result($result,0,'required_software');
	$other_comments = db_result($result,0,'other_comments');
	$license_other = db_result($result,0,'license_other');
?>

<P>
<b><u>Project Description:</u></b>
<P><?php echo ($register_purpose == '') ? 'None.' : util_make_links( nl2br ( $register_purpose)) ; ?>

<P>
<b><u>Intellectual Property (Patents and IPs):</u></b>
<P><?php echo ($patents_ips == '') ? 'None.' : util_make_links( nl2br ($patents_ips)) ; ?>

<P>
<b><u>Software Required:</u></b>
<P><?php echo ($required_software == '') ? 'None.' : util_make_links( nl2br ($required_software)) ; ?>

<P>
<b><u>Miscellaneous Comments:</u></b>
<P><?php echo ($other_comments == '') ? 'None.' : util_make_links( nl2br ($other_comments)) ; ?>

<?php

if ($license_other != '') {
	print '<P>';
	print '<b><u>Comments about Other License used for this project:</u></b>';
	print '<P>'.util_make_links( nl2br ($license_other));
}

print '<P><a href="/project/?group_id='.$group_id .'"> [Back to Main Page] </a>';

site_project_footer(array());

?>
