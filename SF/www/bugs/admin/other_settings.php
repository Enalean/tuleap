<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2002 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

require ('pre.php');
require('../bug_data.php');
require('../bug_utils.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

$is_admin_page='y';

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

if (!user_ismember($group_id,'B2') && !user_ismember($group_id,'A')) {
    // Must be at least Bug Admin or Project Admin
    exit_permission_denied();
}

if ($submit) {

    group_add_history ('Changed BTS bug form message','',$group_id);
    //blank out any invalid email addresses
    if ($email_addresses && !validate_emails($email_addresses) ) {
	$email_addresses='';
	$feedback .= ' Email Address Appeared Invalid ';
    }
    
    // Update the Bug table now
    $result=db_query('UPDATE groups SET '
		     ."bug_preamble='".htmlspecialchars($form_preamble)."' "
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

$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
    exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

echo '<H2>Other Configuration Settings</h2>';

echo '
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<h3>Submission Form Preamble</h3>
<P><b>Introductory message showing at the top of  the Bug submission form :</b>
<br>(HTML tags allowed)<br>
<BR><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.
$row_grp['bug_preamble'].'</TEXTAREA>';

echo '
<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';

bug_footer(array());

?>
