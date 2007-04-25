<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('www/survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';
$params = array('title'=>$Language->getText('survey_admin_show_questions','s_quest'),'pv'=>'1','group'=>$group_id);

site_project_header($params);

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

?>

<H2><?php echo $Language->getText('survey_admin_show_questions','exist_q'); ?></H2>
<P>
<?php echo $Language->getText('survey_admin_show_questions','exist_q_comm'); ?>
<P>
<B><span class="highlight"><?php echo $Language->getText('survey_admin_show_questions','q_id'); ?></span></B>
<P> 
<?php


/*
	Select this survey from the database
*/

$sql="SELECT survey_questions.question_id,survey_questions.question,survey_question_types.id AS question_type_id ".
	"FROM survey_questions,survey_question_types ".
	"WHERE survey_question_types.id=survey_questions.question_type AND survey_questions.group_id='$group_id' ".
"ORDER BY survey_questions.question_id DESC";

$result=db_query($sql);

survey_utils_show_questions($result, false, false);

site_project_footer($params);

?>
