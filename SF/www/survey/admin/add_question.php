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
survey_header(array('title'=>'Add A Question'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

if ($post_changes) {
	$sql="INSERT INTO survey_questions (group_id,question,question_type) VALUES ('$group_id','".htmlspecialchars($question)."','$question_type')";
	$result=db_query($sql);
	if ($result) {
		$feedback .= "Question Added";
	} else {
		$feedback .= "Error inserting question";
	}
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

<H2>Add a Question</H2>
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="Y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
Question:<BR>
<INPUT TYPE="TEXT" NAME="question" VALUE="" SIZE="60" MAXLENGTH="150">
<P>

Question Type:<BR>
<?php

$sql="SELECT * from survey_question_types";
$result=db_query($sql);
echo html_build_select_box($result,'question_type','xzxz',false);

?>
<P>

<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add This Question">
</FORM>  

<P>
<FORM>
<INPUT TYPE="BUTTON" NAME="none" VALUE="Show Existing Questions" ONCLICK="show_questions()">
</FORM>

<?php

survey_footer(array());

?>
