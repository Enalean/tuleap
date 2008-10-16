<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 20012 All Rights Reserved
// http://codex.xerox.com
//
// 


survey_header(array('title'=>$Language->getText('survey_admin_browse_question','edit_q'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));


/*
	Select all questions from the database
*/

$sql="SELECT survey_questions.question_id,survey_questions.question, survey_questions.question_type AS question_type_id,survey_question_types.type AS question_type ".
    "FROM survey_questions,survey_question_types ".
    "WHERE survey_question_types.id=survey_questions.question_type AND survey_questions.group_id='$group_id' ".
    "ORDER BY survey_questions.question_id DESC";
$result=db_query($sql);

?>

<P>
<H2><?php echo $Language->getText('survey_admin_browse_question','edit_q'); ?></H2>
<?php echo $Language->getText('survey_admin_browse_question','edit_q_msg'); ?>
<?php

survey_utils_show_questions($result);

survey_footer(array());
?>




