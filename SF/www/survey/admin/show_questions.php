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

$HTML->header(array('title'=>'Survey Questions'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

?>

<H2>Existing Questions:</H2>
<P>
You may use any of these questions on your surveys.
<P>
<B><FONT COLOR="RED">NOTE: use these question_id's when you create a new survey.</FONT></B>
<P> 
<?php

Function  ShowResultsEditQuestion($result) {
	global $group_id;
	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);
	echo "<h3>$rows Found</h3>";

	echo /*"<TABLE BGCOLOR=\"NAVY\"><TR><TD BGCOLOR=\"NAVY\">*/ "<table border=0>\n";
	/*  Create  the  headers  */
	echo "<tr BGCOLOR=\"$GLOBALS[COLOR_MENUBARBACK]\">\n";
	for($i=0; $i<$cols; $i++)  {
		printf( "<th><FONT COLOR=\"WHITE\"><B>%s</th>\n",  db_fieldname($result,$i));
	}
	echo( "</tr>");
	for($j  =  0;  $j  <  $rows;  $j++)  {

		echo( "<tr BGCOLOR=\"". html_get_alt_row_color($j) ."\">\n");

		echo "<TD><A HREF=\"edit_question.php?group_id=$group_id&question_id=".db_result($result,$j,"question_id")."\">".db_result($result,$j,"question_id")."</A></TD>\n";

		for($i  =  1;  $i  <  $cols;  $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo( "</tr>");
	}
	echo "</table>"; //</TD></TR></TABLE>");
}

/*
	Select this survey from the database
*/

$sql="SELECT survey_questions.question_id,survey_questions.question,survey_question_types.type ".
	"FROM survey_questions,survey_question_types ".
	"WHERE survey_question_types.id=survey_questions.question_type AND survey_questions.group_id='$group_id' ORDER BY survey_questions.question_id DESC";

$result=db_query($sql);

ShowResultsEditQuestion($result);

$HTML->footer(array());

?>
