<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../bug_data.php');
require('../bug_utils.php');
$is_admin_page='y';

if ($group_id && (user_ismember($group_id,'B2') || user_ismember($group_id,'A'))) {

    // Initialize global bug structures
    bug_init($group_id);

 
    /*
      Show main page
    */
    
    bug_header_admin(array ('title'=>'Bug Administration'));
    
    echo '<H2>Bug Administration</H2>';
    echo '<H3><a href="/bugs/admin/field_usage.php?group_id='.$group_id.'">Manage Field Usage</a></H3>';
    echo 'Define what bug fields you want to use in the bug tracking system of this project. (remark: some of the fields like status, assignee,priority... are mandatory and cannot be removed).<P>';
    echo '<H3><a href="/bugs/admin/field_values.php?group_id='.$group_id.'">Manage Field Values</a></H3>';
    echo 'Define the set of values for the bug fields you have decided to use in your bug tracking system for this specific project. <P>';
        
    bug_footer(array());

} else {

    //browse for group first message

    if (!$group_id) {
	exit_no_group();
    } else {
	exit_permission_denied();
    }

}
?>
