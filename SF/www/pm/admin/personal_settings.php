<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 20012 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/bugs/bug_data.php'); // needed by pm_data
require('../pm_data.php');
require('../pm_utils.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

$is_admin_page='y';

/* Update Form submitted */

if ($submit) {
    if (user_set_preference('pm_pref_date'.$group_id, $pref_date)) {
	$feedback .= ' SUCCESSFUL UPDATE';
    } else {
	$feedback .= ' UPDATE FAILED! '.db_error();
    }
}

/*      Show main page    */

pm_header_admin(array ('title'=>'Configure Global Settings',
		       'help'=>'TaskManagerAdministration.html#TaskManagerPersonalConfigurationSettings'));

$current_date = user_get_preference('pm_pref_date'.$group_id);

echo '<H2>Personal Configuration Settings</H2>';

echo '
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<h3>Task Creation Default Date</h3>
<P>When creating a new task I want the default start and end dates to be:&nbsp;';

$date_vals = array ( 0 => 'Current Date', 'No Date');
echo html_build_select_box_from_array ($date_vals, 'pref_date', $current_date);

echo '
<HR>
<P><INPUT type="submit" name="submit" value="Submit">
</FORM>';

pm_footer(array());
?>

	
