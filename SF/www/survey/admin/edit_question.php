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
survey_header(array('title'=>'Edit A Question'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

if ($post_changes) {
	$sql="UPDATE survey_questions SET question='".htmlspecialchars($question)."', question_type='$question_type' where question_id='$question_id' AND group_id='$group_id'";
	$result=db_query($sql);
        if (db_affected_rows($result) < 1) {
                $feedback .= ' UPDATE FAILED ';
        } else {
                $feedback .= ' UPDATE SUCCESSFUL ';
        }
}

$sql="SELECT * FROM survey_questions WHERE question_id='$question_id' AND group_id='$group_id'";
$result=db_query($sql);

if ($result) {
	$question=db_result($result, 0, "question");
	$question_type=db_result($result, 0, "question_type");
} else {
	$feedback .= " Error finding question ";
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

<H2>Edit a Question #<?php echo $question_id; ?></H2>

<H3><FONT COLOR="RED">WARNING! It is a bad idea to change a question after responses to it have been submitted</FONT></H2> 
<P>
If you change a question after responses have been posted, your results pages may be misleading.
<P>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="Y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="question_id" VALUE="<?php echo $question_id; ?>">

Question:
<BR>
<INPUT TYPE="TEXT" NAME="question" VALUE="<?php echo $question; ?>" SIZE="60" MAXLENGTH="150">

<P>
Question Type:
<BR>
<?php

$sql="SELECT * FROM survey_question_types";
$result=db_query($sql);
echo html_build_select_box($result,'question_type',$question_type,false);

?>
<P>

<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
</FORM>  

<P>
<FORM>
<INPUT TYPE="BUTTON" NAME="none" VALUE="Show Existing Questions" ONCLICK="show_questions()">
</FORM>

<?php

survey_footer(array());

?>
