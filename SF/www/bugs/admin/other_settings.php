<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 20012 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

require ('pre.php');
require('../bug_data.php');
require('../bug_utils.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

$is_admin_page='y';


if ($submit) {

	group_add_history ('Changed Bug Tracking System Settings','',$group_id);
	//blank out any invalid email addresses
	if ($email_addresses && !validate_emails($email_addresses) ) {
		$email_addresses='';
		$feedback .= ' Email Address Appeared Invalid ';
	}

	// Update the Bug table now
	$result=db_query('UPDATE groups SET '
	     ."send_all_bugs='$send_all_bugs', "
	     .($email_addresses? "new_bug_address='$email_addresses', " : "")
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
    
bug_header_admin(array ('title'=>'Bug Administration - Other Configuration Settings'));

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


echo '<h3>Email Notification Rules</h3>
              <P><B>If you wish, you can provide email addresses (separated by a comma) to which new Bug submissions will be sent .</B><BR>
              (Remark: Bug submission and updates are always sent to the Bug submitter and the bug assignee)<br>
	<BR><INPUT TYPE="TEXT" NAME="email_addresses" VALUE="'.$row_grp['new_bug_address'].'" SIZE="55" MAXLENGTH="255"> 
	&nbsp;&nbsp;&nbsp;(send on all updates) <INPUT TYPE="CHECKBOX" NAME="send_all_bugs" VALUE="1" '. (($row_grp['send_all_bugs'])?'CHECKED':'') .'><BR>';

echo '
<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';

bug_footer(array());



?>
