<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 20012 All Rights Reserved
// http://codex.xerox.com
//
// 

require_once('pre.php');
require_once('www/bugs/bug_data.php'); // needed by pm_data
require('../pm_data.php');
require('../pm_utils.php');
require_once('www/project/admin/project_admin_utils.php');

$is_admin_page='y';

if ($submit) {

    group_add_history ('changed_task_mgr_other_settings','',$group_id);
    
    $result=db_query('UPDATE groups SET '
		     ."pm_preamble='".htmlspecialchars($form_preamble)."' "
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

pm_header_admin(array ('title'=>'Configure Global Settings',
		       'help'=>'TaskManagerAdministration.html#TaskManagerOtherConfigurationSettings'));

$res_grp = db_query("SELECT pm_preamble FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
    exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

echo '<H2>Other Configuration Settings</H2>';

echo '
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<h3>Submission Form Preamble</h3>
<P><b>Introductory message showing at the top of  the Task creation form :</b>
<br>(HTML tags allowed)<br>
<BR><TEXTAREA cols="70" rows="8" wrap="virtual" name="form_preamble">'.
$row_grp['pm_preamble'].'</TEXTAREA>';


echo '
<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';

pm_footer(array());
?>

	
