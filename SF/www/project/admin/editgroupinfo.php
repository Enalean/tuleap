<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('vars.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// If this was a submission, make updates

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

if ($Update) {

	group_add_history ('Changed Public Info','',$group_id);

	// in the database, these all default to '1', 
	// so we have to explicity set 0
	if (!$use_bugs) {
		$use_bugs=0;
	}
	if (!$use_mail) {
		$use_mail=0;
	}
	if (!$use_survey) {
		$use_survey=0;
	}
	if (!$use_patch) {
		$use_patch=0;
	}
	if (!$use_forum) {
		$use_forum=0;
	}
	if (!$use_pm) {
		$use_pm=0;
	}
	if (!$use_cvs) {
		$use_cvs=0;
	}
	if (!$use_news) {
		$use_news=0;
	}
	if (!$use_support) {
		$use_support=0;
	}
	if (!$use_docman) {
		$use_docman=0;
	}

	$sql = 'UPDATE groups SET '
		."group_name='$form_group_name',"
		."homepage='$form_homepage',"
		."short_description='$form_shortdesc',"
		."register_purpose='".htmlspecialchars($form_purpose)."', "
		."required_software='".htmlspecialchars($form_required_sw)."', "
		."patents_ips='".htmlspecialchars($form_patents)."', "
		."other_comments='".htmlspecialchars($form_comments)."', "
		."use_mail='$use_mail',"
		."use_survey='$use_survey',"
		."use_patch='$use_patch',"
		."use_forum='$use_forum',"
		."use_cvs='$use_cvs',"
		."use_news='$use_news',"
		."use_docman='$use_docman' ";
		
	if ( $row_grp['activate_old_bug'] ) {
		$sql .= ",use_bugs='$use_bugs'";
	}
	if ( $row_grp['activate_old_task'] ) {
		$sql .= ",use_pm='$use_pm'";
	}
	if ( $row_grp['activate_old_sr'] ) {
		$sql .= ",use_support='$use_support'";
	}	
	if ( $sys_activate_tracker ) {
		$sql .= ",use_trackers='$use_trackers'";
	}	
	$sql .= " WHERE group_id=$group_id";

	//echo $sql;
	$result=db_query($sql);
	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' UPDATE FAILED OR NO DATA CHANGED! '.db_error();
	} else {
		$feedback .= ' UPDATE SUCCESSFUL ';
	}
}

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

project_admin_header(array('title'=>'Editing Group Info','group'=>$group_id,
			   'help' => 'ProjectPublicInformation.html'));

print '<P><h3>Editing group info for: <B>'.$row_grp['group_name'].'</B></h3>';

print '
<P>
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">

<P>Descriptive Group Name:
<BR><INPUT type="text" size="40" maxlen="40" name="form_group_name" value="'.$row_grp['group_name'].'">

<P>Short Description (255 Character Max):
<BR><TEXTAREA cols="70" rows="3" wrap="virtual" name="form_shortdesc">
'.$row_grp['short_description'].'</TEXTAREA>

<P>Homepage Link:
<BR>http://<INPUT type="text" name="form_homepage" size="40" value="'.$row_grp['homepage'].'">

<P>Long Description:
<BR><TEXTAREA cols="70" rows="10" wrap="virtual" name="form_purpose">
'.$row_grp['register_purpose'].'</TEXTAREA>

<P>Patents and Invention Proposals (IPs)
<BR><TEXTAREA cols="70" rows="6" wrap="virtual" name="form_patents">
'.$row_grp['patents_ips'].'</TEXTAREA>

<P>Other Software Required
<BR><TEXTAREA cols="70" rows="6"wrap="virtual" name="form_required_sw">
'.$row_grp['required_software'].'</TEXTAREA>

<P>Other Comments:<BR>
<TEXTAREA name="form_comments" wrap="virtual" cols="70" rows="4">'.$row_grp['other_comments'].'</TEXTAREA>
<HR>

<H3>Active Services:</H3>
<P>
';
/*
	Show the options that this project is using
*/

if ( $row_grp['activate_old_bug'] ) {
	echo '<B>Use Bug Tracker:</B> <INPUT TYPE="CHECKBOX" NAME="use_bugs" VALUE="1"'.( ($row_grp['use_bugs']==1) ? ' CHECKED' : '' ).'><BR>';
}
echo '
	<B>Use Mailing Lists:</B> <INPUT TYPE="CHECKBOX" NAME="use_mail" VALUE="1"'.( ($row_grp['use_mail']==1) ? ' CHECKED' : '' ).'><BR>
	<B>Use Surveys:</B> <INPUT TYPE="CHECKBOX" NAME="use_survey" VALUE="1"'.( ($row_grp['use_survey']==1) ? ' CHECKED' : '' ).'><BR>
	<B>Use Patch Manager:</B> <INPUT TYPE="CHECKBOX" NAME="use_patch" VALUE="1"'.( ($row_grp['use_patch']==1) ? ' CHECKED' : '' ).'><BR>
	<B>Use Forums:</B> <INPUT TYPE="CHECKBOX" NAME="use_forum" VALUE="1"'.( ($row_grp['use_forum']==1) ? ' CHECKED' : '' ).'><BR>';
if ( $row_grp['activate_old_task'] ) {
	echo '<B>Use Project/Task Manager:</B> <INPUT TYPE="CHECKBOX" NAME="use_pm" VALUE="1"'.( ($row_grp['use_pm']==1) ? ' CHECKED' : '' ).'><BR>';
}
echo '
	<B>Use CVS:</B> <INPUT TYPE="CHECKBOX" NAME="use_cvs" VALUE="1"'.( ($row_grp['use_cvs']==1) ? ' CHECKED' : '' ).'><BR>
	<B>Use News:</B> <INPUT TYPE="CHECKBOX" NAME="use_news" VALUE="1"'.( ($row_grp['use_news']==1) ? ' CHECKED' : '' ).'><BR>
	<B>Use Doc Mgr:</B> <INPUT TYPE="CHECKBOX" NAME="use_docman" VALUE="1"'.( ($row_grp['use_docman']==1) ? ' CHECKED' : '' ).'><BR>';
if ( $row_grp['activate_old_sr'] ) {
	echo '<B>Use Support:</B> <INPUT TYPE="CHECKBOX" NAME="use_support" VALUE="1"'.( ($row_grp['use_support']==1) ? ' CHECKED' : '' ).'><BR>';
}	
if ( $sys_activate_tracker ) {
	echo '<B>Use Trackers:</B> <INPUT TYPE="CHECKBOX" NAME="use_trackers" VALUE="1"'.( ($row_grp['use_trackers']==1) ? ' CHECKED' : '' ).'><BR>';
}
echo '
<HR>
<P><INPUT type="submit" name="Update" value="Update">
</FORM>
';

project_admin_footer(array());

?>
