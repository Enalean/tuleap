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
survey_header(array('title'=>'Add A Survey'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

if ($post_changes) {
	//$survey_questions=trim(ltrim($survey_questions));
	$sql="insert into surveys (survey_title,group_id,survey_questions) values ('$survey_title','$group_id','$survey_questions')";
	$result=db_query($sql);
	if ($result) {
		$feedback .= " Survey Inserted ";
	} else {
		$feedback .= " Error in Survey Insert ";
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
<BR><INPUT TYPE="TEXT" NAME="survey_questions" VALUE="" LENGTH="90" MAXLENGTH="1500"><P>
<B>Is Active</B>
<BR><INPUT TYPE="RADIO" NAME="is_active" VALUE="1" CHECKED> Yes
<BR><INPUT TYPE="RADIO" NAME="is_active" VALUE="0"> No
<P>
<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add This Survey">
</FORM>  

<?php

Function  ShowResultsEditSurvey($result) {
	global $group_id;
	$rows  =  db_NumRows($result);
	$cols  =  db_NumFields($result);
	echo "<h3>$rows Found</h3>";

	echo /*"<TABLE BGCOLOR=\"NAVY\"><TR><TD BGCOLOR=\"NAVY\">*/ "<table border=0>\n";

	/*  Create  the  headers  */
	echo "<tr BGCOLOR=\"$GLOBALS[COLOR_MENUBARBACK]\">\n";
	for ($i  =  0;  $i  <  $cols;  $i++)  {
		printf( "<th><FONT COLOR=\"WHITE\"><B>%s</th>\n",  db_fieldname($result,$i));
	}
	echo "</tr>";
	for($j  =  0;  $j  <  $rows;  $j++)  {

		if ($j%2==0) {
			$row_bg="#FFFFFF";
		} else {
			$row_bg="$GLOBALS[COLOR_LTBACK1]";
		}

		echo "<tr BGCOLOR=\"$row_bg\">\n";
		echo "<TD><A HREF=\"edit_survey.php?group_id=$group_id&survey_id=".db_result($result,$j,0)."\">".db_result($result,$j,0)."</A></TD>";
		for ($i = 1; $i < $cols; $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>";
}

/*
	Select this survey from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>
<FORM>
<INPUT TYPE="BUTTON" NAME="none" VALUE="Show Existing Questions" ONCLICK="show_questions()">
</FORM>

<P>
<H2>Existing Surveys</H2>
<P>
<?php
ShowResultsEditSurvey($result);

survey_footer(array());
?>
