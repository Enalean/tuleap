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
survey_header(array('title'=>'Survey Results'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo "<H1>Permission Denied</H1>";
	survey_footer(array());
	exit;
}

echo "<P><H3>Survey Results:</H3>";


Function  ShowResultsSurvey($result) {
	global $group_id,$PHP_SELF;
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

		echo "<TD><A HREF=\"$PHP_SELF?group_id=$group_id&survey_id=".db_result($result,$j,"survey_id")."\">".db_result($result,$j,"survey_id")."</A></TD>\n";

		for($i  =  1;  $i  <  $cols;  $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>";
}


Function  ShowResultsAggregate($result) {
	global $group_id;
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

		echo "<TD><A HREF=\"show_results_aggregate.php?group_id=$group_id&survey_id=".db_result($result,$j,"survey_id")."\">".db_result($result,$j,"survey_id")."</A></TD>\n";

		for($i  =  1;  $i  <  $cols;  $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>";
}


Function  ShowResultsCustomer($result) {
	global $survey_id,$group_id;

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

		echo "<TD><A HREF=\"show_results_individual.php?group_id=$group_id&survey_id=$survey_id&customer_id=".db_result($result,$j,"cust_id")."\">".db_result($result,$j,"cust_id")."</A></TD>\n";

		for($i  =  1;  $i  <  $cols;  $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>";
}



if (!$survey_id) {

	/*
		Select a list of surveys, so they can click in and view a particular set of responses
	*/

	$sql="SELECT survey_id,survey_title FROM surveys WHERE group_id='$group_id'";

	$result=db_query($sql);

//	echo "\n<h2>View Individual Responses</h2>\n\n";
//	ShowResultsSurvey($result);

	echo "\n<h2>View Aggregate Responses</h2>\n\n";
	ShowResultsAggregate($result);

} /* else {

	/ *
		Pull up a list of customer IDs for people that responded to this survey
	* /

	$sql="select people.cust_id, people.first_name, people.last_name ".
		"FROM people,responses where responses.customer_id=people.cust_id AND responses.survey_id='$survey_id' ".
		"GROUP BY people.cust_id, people.first_name, people.last_name";

	$result=db_query($sql);

	ShowResultsCustomer($result);

} */

survey_footer(array());

?>
