<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2002 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../bug_data.php');
require('../bug_utils.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

$is_admin_page='y';

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

if (!user_ismember($group_id,'B2') && !user_ismember($group_id,'A')) {
    // Must be at least Bug Admin or Project Admin
    exit_permission_denied();
}

// Get the data
$sql_grp = "SELECT bug_preamble,bug_allow_anon FROM groups WHERE group_id=$group_id";


if ($submit) {

    // Get current values from the DB
    $res_grp = db_query($sql_grp);
    if (db_numrows($res_grp) < 1) {
	exit_no_group();
    }
    $row_grp = db_fetch_array($res_grp);

    // Post process the form values
    // Make sure bug_allow_anon is zero if not checked
    if (!$form_bug_allow_anon) { $form_bug_allow_anon = 0; }
    
    // Update project change history if need be
    if ($form_preamble != $row_grp['bug_preamble']) {
	group_add_history ('Changed BTS bug form message','',$group_id);
    }
    if ($form_bug_allow_anon != $row_grp['bug_allow_anon']) {
	group_add_history ('Changed BTS bug allow anonymous submission',
			   $row_grp['bug_allow_anon'],$group_id);
    }
    
    // Update the Bug table anyway. The update won't do anything
    // if values are the same
    $result=db_query('UPDATE groups SET '
		     ."bug_preamble='".htmlspecialchars($form_preamble)."', "
		     ."bug_allow_anon='$form_bug_allow_anon' "
		     ."WHERE group_id=$group_id");
    
    if (!$result) {
	$feedback .= ' UPDATE FAILED! '.db_error();
    } else if (db_affected_rows($result) < 1) {
	$feedback .= ' NO DATA CHANGED! ';
    } else {
	$feedback .= ' SUCCESSFUL UPDATE';
    }
    
}


/*      Show main page    */

bug_header_admin(array ('title'=>'Bug Administration - Other Configuration Settings',
			'help' => 'BTSAdministration.html#BugOtherConfigurationSettings'));

// Get the data
$res_grp = db_query($sql_grp);
if (db_numrows($res_grp) < 1) {
    exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

echo '<H2>Other Configuration Settings</h2>';

echo '
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<h3>Submission Form Preamble</h3>
<P>Introductory message showing at the top of  the Bug submission form :
<br>(HTML tags allowed)<br>
<BR><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.
$row_grp['bug_preamble'].'</TEXTAREA>';

echo '
<h3>Bug Submission Access Control</h3>
Users <b>not</b> logged in can submit/update bugs: 
<INPUT TYPE="CHECKBOX" NAME="form_bug_allow_anon" VALUE="1" '.
($row_grp['bug_allow_anon']? ' CHECKED':'').'><p>';

echo '
<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';

bug_footer(array());

?>
