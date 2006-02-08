<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../survey_data.php');
require('../survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

if ($post_changes) {
   survey_data_question_create($group_id,htmlspecialchars($question),$question_type);  

   $quest = htmlspecialchars($question);
   $qry = "SELECT * FROM survey_questions WHERE group_id='$group_id' AND question_type='$question_type' AND question='$quest'";
   $res = db_query($qry);   
   $quest_id = db_result($res,0,'question_id');

   // if radio-type question is created, redirect to Edit A Question page
   if ($question_type=="6") {
       session_redirect("/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$quest_id");       
   }
}

$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_add_question','add_q'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

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

$sql="SELECT * from survey_question_types";
$result=db_query($sql);
echo html_build_select_box($result,'question_type','xzxz',false);

?>
<P>

<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE=" <?php echo $Language->getText('survey_admin_add_question','add_this_q'); ?>">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<INPUT TYPE="BUTTON" NAME="none" VALUE="<?php echo $Language->getText('survey_admin_add_question','show_q'); ?>" ONCLICK="show_questions()">
</FORM>

<?php

survey_footer(array());

?>
