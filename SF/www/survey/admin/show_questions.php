<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require($DOCUMENT_ROOT.'/survey/survey_utils.php');
$is_admin_page='y';

$HTML->header(array('title'=>'Survey Questions'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

?>

<H2>Existing Questions</H2>
<P>
You may use any of these questions on your surveys.
<P>
<B><FONT COLOR="RED">NOTE: use these question_id's when you create a new survey.</FONT></B>
<P> 
<?php


/*
	Select this survey from the database
*/

$sql="SELECT survey_questions.question_id,survey_questions.question,survey_question_types.type AS question_type ".
	"FROM survey_questions,survey_question_types ".
	"WHERE survey_question_types.id=survey_questions.question_type AND survey_questions.group_id='$group_id' ".
"ORDER BY survey_questions.question_id DESC";

$result=db_query($sql);

survey_utils_show_questions($result, false);

$HTML->footer(array());

?>
