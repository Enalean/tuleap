<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('vote_function.php');
require('../survey/survey_utils.php');

survey_header(array('title'=>'Survey'));

if (!$group_id) {
	echo "<H1>For some reason, the Group ID or Survey ID did not make it to this page</H1>";
}

Function  ShowResultsGroupSurveys($result) {
	global $group_id;
	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);

	$title_arr=array();
	$title_arr[]='Survey ID';
	$title_arr[]='Survey Title';

	echo html_build_list_table_top ($title_arr);

	for($j=0; $j<$rows; $j++)  {

		echo "<tr BGCOLOR=\"". html_get_alt_row_color($j) ."\">\n";

		echo "<TD><A HREF=\"survey.php?group_id=$group_id&survey_id=".db_result($result,$j,"survey_id")."\">".
			db_result($result,$j,"survey_id")."</TD>";

		for ($i=1; $i<$cols; $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>");
}

$sql="SELECT survey_id,survey_title FROM surveys WHERE group_id='$group_id' AND is_active='1'";

$result=db_query($sql);

if (!$result || db_numrows($result) < 1) {
	echo "<H2>This Group Has No Active Surveys</H2>";
	echo db_error();
} else {
	echo "<H2>Surveys for ".group_getname($group_id)."</H2>";
	ShowResultsGroupSurveys($result);
}

survey_footer(array());

?>
