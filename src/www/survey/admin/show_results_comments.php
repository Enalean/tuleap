<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: show_results_comments.php 1405 2005-03-21 14:41:41Z guerin $

require_once('pre.php');
require_once('HTML_Graphs.php');
require_once('www/survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_show_r_aggregate','agg_res'),
		    'help'=>'AdministeringSurveys.html#ReviewingSurveyResults'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

$sql="SELECT question FROM survey_questions WHERE question_id='$question_id'";
$result=db_query($sql);

echo '<h2>'.$Language->getText('survey_admin_show_r_comments','s_res').'</h2>';

echo '<h3>'.$Language->getText('survey_admin_show_r_comments','q_no',array($question_num,util_unconvert_htmlspecialchars(db_result($result,0,"question")))).'</H3>';
echo "<P>";

$sql="SELECT response, count(*) AS count FROM survey_responses WHERE survey_id='$survey_id' ".
"AND question_id='$question_id' AND group_id='$group_id' ".
"GROUP BY response";
$result=db_query($sql);
survey_utils_show_comments($result);

survey_footer(array());

?>
