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
survey_header(array('title'=>'Survey Results',
		    'help'=>'AdministeringSurveys.html#ReviewingSurveyResults'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

echo "<H2>Survey Results</H2>";

if (!$survey_id) {

	/*
		Select a list of surveys, so they can click in and view a particular set of responses
	*/

	$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

	$result=db_query($sql);

	echo "\n<p>Click  on a Survey ID to View Aggregate Responses\n";
	survey_utils_show_surveys_for_results($result,false);

}

survey_footer(array());

?>
