<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../survey_data.php');
require('../survey_utils.php');

$is_admin_page='y';

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

switch ($func) {

 case 'browse' :
     include 'browse_survey.php';
     break;

 case 'delete_survey' :
     survey_data_survey_delete($group_id,$survey_id);
     include 'browse_survey.php';
     break;

 case 'update_survey':
     if ($post_changes) {
	 // Update the survey
	 survey_data_survey_update($group_id,$survey_id,$survey_title,$survey_questions,$is_active,$is_anonymous);
	 // Display the list after the update
	 include 'browse_survey.php';
     } else {
	 // Show the form to update the survey
	 include 'update_survey.php';
     }
    break;
    
 default :
     include 'browse_survey.php';
     break;
}
?>
