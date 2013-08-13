<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 


survey_header(array('title'=>$Language->getText('survey_admin_browse_question','edit_q'),
		    'help'=>'survey.html#creating-or-editing-questions'));


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
