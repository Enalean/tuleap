<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('www/include/pre.php');
require_once('common/survey/SurveySingleton.class.php');
require_once('www/survey/survey_data.php');
require_once('www/survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	survey_header(array('title'=>$Language->getText('survey_admin_add_question','add_q'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

if (isset($post_changes) && $post_changes) {
   $question_id = survey_data_question_create($group_id,htmlspecialchars($question),$question_type);  

   // if radio-type question is created, redirect to Edit A Question page
   if (isset($question_id) && ($question_type=="6" || $question_type=="7")) {
       session_redirect("/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$question_id");       
   }
}

survey_header(array('title'=>$Language->getText('survey_admin_add_question','add_q'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));


?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var timerID2 = null;

function show_questions() {
	newWindow = open("","occursDialog","height=600,width=500,scrollbars=yes,resizable=yes");
	newWindow.location=('show_questions.php?group_id=<?php echo $group_id; ?>');
}

// -->
</script>

<H2><?php echo $Language->getText('survey_admin_add_question','add_q'); ?></H2>
<P>
<FORM ACTION ="<?php echo $PHP_SELF ; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="Y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<?php echo $Language->getText('survey_admin_add_question','q_allowed'); ?><BR>
<TEXTAREA NAME="question" COLS="60" ROWS="4" WRAP="SOFT"></TEXTAREA>
<P>

<?php echo $Language->getText('survey_admin_add_question','q_type'); ?><BR>
<?php

$survey =& SurveySingleton::instance();
echo $survey->showTypeBox();


?>
<P>

<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE=" <?php echo $Language->getText('survey_admin_add_question','add_this_q'); ?>">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<INPUT TYPE="BUTTON" NAME="none" VALUE="<?php echo $Language->getText('survey_admin_add_question','show_q'); ?>" ONCLICK="show_questions()">
</FORM>

<?php

survey_footer(array());

?>
