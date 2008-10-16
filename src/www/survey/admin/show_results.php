<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../survey_data.php');
require('../survey_utils.php');



$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_show_r_comments','s_res'),
		    'help'=>'AdministeringSurveys.html#ReviewingSurveyResults'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

echo '<h2>'.$Language->getText('survey_admin_show_r_comments','s_res').'</h2>';

if (!isset($survey_id) || !$survey_id) {

	/*
		Select a list of surveys, so they can click in and view a particular set of responses
	*/

	$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

	$result=db_query($sql);

	echo "\n<p>".$Language->getText('survey_admin_show_r','click_s_id')."\n";
	survey_utils_show_surveys_for_results($result,false);

}

survey_footer(array());

?>
