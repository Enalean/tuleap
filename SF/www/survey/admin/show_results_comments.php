<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('HTML_Graphs.php');
require($DOCUMENT_ROOT.'/survey/survey_utils.php');
$is_admin_page='y';
survey_header(array('title'=>'Survey Aggregate Results'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

Function  ShowResultComments($result) {
	global $survey_id;

	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);
	echo "<h3>$rows Found</h3>";

	echo /*"<TABLE BGCOLOR=\"NAVY\"><TR><TD BGCOLOR=\"NAVY\">*/ "<table border=0>\n";
	/*  Create  the  headers  */
	echo "<tr BGCOLOR=\"$GLOBALS[COLOR_MENUBARBACK]\">\n";

	for($i  =  0;  $i  <  $cols;  $i++)  {
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

		for ($i = 0; $i < $cols; $i++) {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>";
}

$sql="SELECT question FROM survey_questions WHERE question_id='$question_id'";
$result=db_query($sql);
echo "<h2>Question: ".db_result($result,0,"question")."</H2>";
echo "<P>";

$sql="SELECT DISTINCT response FROM survey_responses WHERE survey_id='$survey_id' AND question_id='$question_id' AND group_id='$group_id'";
$result=db_query($sql);
ShowResultComments($result);

survey_footer(array());

?>
