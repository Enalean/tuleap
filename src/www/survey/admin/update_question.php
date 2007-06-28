<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 20012 All Rights Reserved
// http://codex.xerox.com
//
// 

require_once('common/survey/SurveySingleton.class.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_update_question','edit_a_q'), 'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

// Fetch the question from the DB
$sql="SELECT * FROM survey_questions WHERE question_id='$question_id' AND group_id='$group_id'";
$result=db_query($sql);

if ($result) {
	$question=db_result($result, 0, "question");
	$question_type=db_result($result, 0, "question_type");
} else {
	$feedback .= " Error finding question #".$question_id;
}

?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var timerID2 = null;

function show_questions() {
	newWindow = open("","occursDialog","height=600,width=700,scrollbars=yes,resizable=yes");
	newWindow.location=('show_questions.php?group_id=<?php echo $group_id; ?>&pv=1');
}

// -->
</script>

<H2><?php echo $Language->getText('survey_admin_update_question','edit_a_q'); ?></H2>

<H3>
<?php

// check if question is associated to an existing survey. If it is the case, display a warning.
$sql="SELECT * FROM surveys WHERE group_id=".$group_id;
$res=db_query($sql);
$warn=false;
$i=0;
if (db_numrows($res) > 0) {
    while (($i < db_numrows($res)) && (! $warn)) {
        $question_list=db_result($res,$i,'survey_questions');
        $question_array=explode(',', $question_list);
        if (in_array($question_id,$question_array)) {
            $warn=true;
        }
        $i++;
    }
}

if ($warn) { 
    echo $Language->getText('survey_admin_update_question','warn'); 
} 

?></H3><P>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_question">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="Y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="question_id" VALUE="<?php echo $question_id; ?>">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">

<?php echo $Language->getText('survey_admin_update_question','q'); ?>
<BR>
<TEXTAREA NAME="question" COLS="80" ROWS="8" WRAP="SOFT"><?php echo $question; ?></TEXTAREA>

<P>
<?php echo $Language->getText('survey_admin_add_question','q_type'); ?>
<BR>
<?php

$survey =& SurveySingleton::instance();
echo $survey->showTypeBox('question_type',$question_type);


// see if the question is a radio-button type
$qry1="SELECT * FROM survey_questions WHERE group_id='$group_id' AND question_id='$question_id'";
$res1=db_query($qry1);
$question_type=db_result($res1,0,'question_type');

?>
<P>

<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="<?php echo $Language->getText('survey_admin_update_question','subm_changes'); ?>">


&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

<INPUT TYPE="BUTTON" NAME="none" VALUE="<?php echo $Language->getText('survey_admin_add_question','show_q'); ?>" ONCLICK="show_questions()">
</FORM>

<?php

// for radio-button questions, display buttons list and form
if ($question_type=="6" || $question_type=="7") {   
    
    $sql="SELECT * ".
    "FROM survey_radio_choices ".
    "WHERE question_id='$question_id'".
    "ORDER BY choice_rank";
    $result=db_query($sql);
    
    survey_utils_show_radio_list($result,$question_type);
    survey_utils_show_radio_form($question_id,"",$question_type);
}

survey_footer(array());

?>
