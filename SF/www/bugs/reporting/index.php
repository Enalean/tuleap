<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../bug_utils.php');

if ($group_id && user_ismember($group_id,"B2")) {

	include ($DOCUMENT_ROOT.'/include/HTML_Graphs.php');

	if ($run_report) {
		/*
			Update the database
		*/

		if ($aging) {

			bug_header(array ("title"=>"Aging Report"));
			echo "\n<H1>Aging Report</H1>";

			$time_now=time();
//			echo $time_now."<P>";

			for ($counter=1; $counter<=8; $counter++) {

				$start=($time_now-($counter*604800));
				$end=($time_now-(($counter-1)*604800));

				$sql="SELECT avg((close_date-date)/86400) FROM bug WHERE close_date > 0 AND (date >= $start AND date <= $end) AND resolution_id <> '2' AND group_id='$group_id'";

				$result = db_query($sql);

				$names[$counter-1]=date("m/d/y",($start))." to ".date("m/d/y",($end));
				$values[$counter-1]=db_result($result, 0,0);
			}

			GraphIt($names, $values, "Average Turnaround Time For Closed Bugs");

			echo "<P>";

			for ($counter=1; $counter<=8; $counter++) {

				$start=($time_now-($counter*604800));
				$end=($time_now-(($counter-1)*604800));

				$sql="SELECT count(*) FROM bug WHERE date >= $start AND date <= $end AND resolution_id <> '2' AND group_id='$group_id'";

				$result = db_query($sql);

				$names[$counter-1]=date("m/d/y",($start))." to ".date("m/d/y",($end));
				$values[$counter-1]=db_result($result, 0,0);
			}

			GraphIt($names, $values, "Number of Bugs Opened");

			echo "<P>";

			for ($counter=1; $counter<=8; $counter++) {

				$start=($time_now-($counter*604800));
				$end=($time_now-(($counter-1)*604800));

				$sql="SELECT count(*) FROM bug WHERE date <= $end AND (close_date >= $end OR close_date < 1 OR close_date is null) AND resolution_id <> '2' AND group_id='$group_id'";

				$result = db_query($sql);

				$names[$counter-1]=date("m/d/y",($end));
				$values[$counter-1]=db_result($result, 0,0);
			}

			GraphIt($names, $values, "Number of Bugs Still Open");

			echo "<P>";

			bug_footer(array());

		} else if ($category) {

			bug_header(array ("title"=>"Bugs By Category"));
			echo "\n<H1>Bugs by Category</H1>";

			$sql="SELECT bug_category.category_name AS Category, count(*) AS Count FROM bug_category,bug ".
				"WHERE bug_category.bug_category_id=bug.category_id AND bug.status_id <> '3' AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Category";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"Open Bugs By Category");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			echo "<P>";

			$sql="SELECT bug_category.category_name AS Category, count(*) AS Count FROM bug_category,bug ".
				"WHERE bug_category.bug_category_id=bug.category_id AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Category";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"All Bugs By Category");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			bug_footer(array());

		} else if ($tech) {

			bug_header(array ("title"=>"Bugs By Technician"));
			echo "\n<H1>Bugs By Technician</H1>";

			$sql="SELECT user.user_name AS Technician, count(*) AS Count FROM user,bug ".
				"WHERE user.user_id=bug.assigned_to AND bug.status_id <> '3' AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Technician";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"Open Bugs By Technician");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			echo "<P>";

			$sql="SELECT user.user_name AS Technician, count(*) AS Count FROM user,bug ".
				"WHERE user.user_id=bug.assigned_to AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Technician";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"All Bugs By Technician");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			bug_footer(array());

		} else if ($bug_group) {

			bug_header(array ("title"=>"Bugs By Bug Group"));
			echo "\n<H1>Bugs By Bug Group</H1>";

			$sql="SELECT bug_group.group_name AS Bug_Group_Name, count(*) AS Count FROM bug_group,bug ".
				"WHERE bug_group.bug_group_id=bug.bug_group_id AND bug.status_id <> '3' AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Bug_Group_Name";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"Open Bugs By Bug Group");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			echo "<P>";

			$sql="SELECT bug_group.group_name AS Bug_Group_Name, count(*) AS Count FROM bug_group,bug ".
				"WHERE bug_group.bug_group_id=bug.bug_group_id AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Bug_Group_Name";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"All Bugs By Bug Group");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			bug_footer(array());

		} else if ($resolution) {

			bug_header(array ("title"=>"Bugs By Resolution"));
			echo "\n<H1>Bugs By Resolution</H1>";

			$sql="SELECT bug_resolution.resolution_name AS Resolution, count(*) AS Count FROM bug_resolution,bug ".
				"WHERE bug_resolution.resolution_id=bug.resolution_id AND bug.status_id <> '3' AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Resolution";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"Open Bugs By Resolution");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			echo "<P>";

			$sql="SELECT bug_resolution.resolution_name AS Resolution, count(*) AS Count FROM bug_resolution,bug ".
				"WHERE bug_resolution.resolution_id=bug.resolution_id AND bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
				"GROUP BY Resolution";

			$result=db_query($sql);
			if ($result && db_numrows($result) > 0) {
				GraphResult($result,"All Bugs By Resolution");
			} else {
				echo "<H2>No data found to report</H2>";
			}

			bug_footer(array());

		}

	} else {
		/*
			Show main page
		*/
		bug_header(array ("title"=>"Bug Reporting System"));

		echo "\n<H1>Bug Reporting System</H1>";
		echo "\n<P>";
		echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&run_report=1&aging=1\">Aging Report</A><BR>";
		echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&run_report=1&tech=1\">Bugs by Technician</A><BR>";
		echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&run_report=1&category=1\">Bugs by Category</A><BR>";
		echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&run_report=1&bug_group=1\">Bugs by Bug Group</A><BR>";
		echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&run_report=1&resolution=1\">Bugs by Resolution</A>";

		bug_footer(array());

	}

} else {

	//browse for group first message

	if (!$group_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}

}
?>
