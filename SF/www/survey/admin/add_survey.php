<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../survey_data.php');
require('../survey_utils.php');

$is_admin_page='y';
survey_header(array('title'=>'Add A Survey'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

if ($post_changes) {
    survey_data_survey_create($group_id,$survey_title,$survey_questions,
			      $is_active, $is_anonymous);
}

?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var timerID2 = null;

function show_questions() {
        newWindow = open("","occursDialog","height=600,width=700,scrollbars=yes,resizable=yes");
        newWindow.location=('show_questions.php?group_id=<?php echo $group_id; ?>');
}

// -->
</script>

<H2>Add a Survey</H2><P>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">

<B>Name of Survey:</B>
<BR>
<INPUT TYPE="TEXT" NAME="survey_title" VALUE="" LENGTH="60" MAXLENGTH="150"><P>
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
List question numbers, in desired order, separated by commas. <B>Refer to your list of questions</B> so you can view
the question id's. Do <B>not</B> include spaces or end your list with a comma.
<BR>
Ex: 1,2,3,4,5,6,7
<BR><INPUT TYPE="TEXT" NAME="survey_questions" VALUE="" LENGTH="90" MAXLENGTH="1500">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<INPUT TYPE="BUTTON" NAME="none" VALUE="Show Existing Questions" ONCLICK="show_questions()">
<p>
<table border="0">
<tr><td><B>Is Active?</B></td>
<td><INPUT TYPE="RADIO" NAME="is_active" VALUE="1" CHECKED> Yes</td>
<td><INPUT TYPE="RADIO" NAME="is_active" VALUE="0"> No</td>
<tr>
<tr><td><B>Anonymous answer ok?</B></td>
<td><INPUT TYPE="RADIO" NAME="is_anonymous" VALUE="1"> Yes</td>
<td><INPUT TYPE="RADIO" NAME="is_anonymous" VALUE="0" CHECKED> No</td>
</tr>
</table>
<P>
<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add This Survey">
</FORM>  

<?php

/*
	Select all surveys from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>

<P>
<H3>Existing Surveys</H3>
<?php

survey_utils_show_surveys($result);

survey_footer(array());
?>
