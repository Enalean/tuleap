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
	$sql="insert into surveys (survey_title,group_id,survey_questions,is_active,is_anonymous) values ('$survey_title','$group_id','$survey_questions','$is_active','$is_anonymous')";
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
<BR><INPUT TYPE="TEXT" NAME="survey_questions" VALUE="" LENGTH="90" MAXLENGTH="1500">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<INPUT TYPE="BUTTON" NAME="none" VALUE="Show Existing Questions" ONCLICK="show_questions()">
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

<INPUT TYPE="BUTTON" NAME="none" VALUE="Show Existing Questions" ONCLICK="show_questions()">
<?php


//' comment for correct syntax highlighting

Function  ShowResultsEditSurvey($result) {
	global $group_id,$PHP_SELF;
	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);
	echo "<h3>$rows Found</h3>";

	/*  Create  the  headers  */
	for ($i = 0; $i < $cols; $i++)  {
	    $title_arr[] = db_fieldname($result,$i);
	}

	echo html_build_list_table_top ($title_arr);

	for ($j=0; $j<$rows; $j++)  {

		echo '<tr BGCOLOR="'.html_get_alt_row_color($j).'">';

		echo "<TD><A HREF=\"$PHP_SELF?group_id=$group_id&survey_id=".
			db_result($result,$j,0)."\">".db_result($result,$j,0)."</A></TD>";
		for ($i = 1; $i < $cols; $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>";

}

/*
	Select this survey from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>

<P>
<H2>Existing Surveys</H2>
<P>
<?php
ShowResultsEditSurvey($result);

survey_footer(array());
?>
