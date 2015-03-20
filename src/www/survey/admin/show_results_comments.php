<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015. All Rights Reserved.
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('HTML_Graphs.php');
require_once('www/survey/survey_utils.php');


$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_show_r_aggregate','agg_res'),
		    'help'=>'survey.html#reviewing-survey-results'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

$sql      = "SELECT question FROM survey_questions WHERE question_id='" . db_ei($question_id) . "'";
$result   = db_query($sql);
$purifier = Codendi_HTMLPurifier::instance();

echo '<h2>'.$Language->getText('survey_admin_show_r_comments','s_res').'</h2>';

echo '<h3>'.$Language->getText('survey_admin_show_r_comments','q_no',array($question_num, $purifier->purify(db_result($result,0,"question")))).'</H3>';
echo "<P>";

$sql="SELECT response, count(*) AS count FROM survey_responses WHERE survey_id='" . db_ei($survey_id) . "' ".
"AND question_id='" . db_ei($question_id) . "' AND group_id='" . db_ei($group_id) . "' ".
"GROUP BY response";
$result=db_query($sql);
survey_utils_show_comments($result);

survey_footer(array());
