<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

$changes = array();
$changed = false;

// Add a new comment if there is one
if ($details != '') {

    // For none project members force the comment type to None (100)
    bug_data_add_history ('details',htmlspecialchars($details),$bug_id,100);  
    $changes['details']['add'] = stripslashes($details);
    $changes['details']['type'] = 'None';
    $changed = true;

    $feedback .= ' Comment added to bug ';
    
}

// Add a new cc if any
if ($add_cc) {
    $changed |= bug_add_cc($bug_id,$group_id,$add_cc,$cc_comment,$changes);
}

// Attach new file if there is one
if ($add_file && $input_file) {
    $changed |= bug_attach_file($bug_id,$group_id,$input_file,
				$input_file_name,$input_file_type,
				$input_file_size,$file_description,
				$changes);
}

if (!$changed) {
    $feedback .= ' Nothing Done ';
}

?>
