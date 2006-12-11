<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../bug_data.php');
require('../bug_utils.php');
$is_admin_page='y';

//must be logged in to access top admin page
session_require(array('isloggedin'=>'1'));

if ($group_id) {

    // Initialize global bug structures
    bug_init($group_id);

 
    /*
      Show main page
    */
    
    bug_header_admin(array ('title'=>'Bug Administration',
			    'help' => 'BTSAdministration.html'));
    
    echo '<H2>Bug Administration</H2>';
    if (user_ismember($group_id,'B2') || user_ismember($group_id,'A')) {
	 echo '<H3><a href="/bugs/admin/field_usage.php?group_id='.$group_id.'">Manage Field Usage</a></H3>';
	 echo 'Define what bug fields you want to use in the bug tracking system of this project. (remark: some of the fields like status, assignee, severity&hellip; are mandatory and cannot be removed).<P>';
	 echo '<H3><a href="/bugs/admin/field_values.php?group_id='.$group_id.'">Manage Field Values</a></H3>';
	 echo 'Define the set of values for the bug fields you have decided to use in your bug tracking system for this specific project. <P>';
    }

    if (user_isloggedin()) {
	echo '<H3><a href="/bugs/admin/reports.php?group_id='.$group_id.'">Manage Bug Reports</a></H3>';
	echo 'Define personal or project-wide bug reports: what search criteria to use and what bug fields to show in the bug report table&hellip;';

	echo '<H3><a href="/bugs/admin/notification_settings.php?group_id='.$group_id.'">Email Notification Settings</a></H3>';
	echo 'Users can define when they want to be notified of a bug update via email. Project
Administrators can also define global email notification rules.<P>';
    }

    if (user_ismember($group_id,'B2') || user_ismember($group_id,'A')) {
	echo '<H3><a href="/bugs/admin/other_settings.php?group_id='.$group_id.'">Other Configuration Settings</a></H3>';
	echo 'Define introductory messages for submission forms&hellip;<P>';
    }
    bug_footer(array());

} else {

    //browse for group first message

    if (!$group_id) {
	exit_no_group();
    }

}
?>
