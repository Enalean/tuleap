<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../bug_data.php');
require('../bug_utils.php');

if ($group_id && user_ismember($group_id,"B2")) {

    // Initialize the global data structure before anything else
    bug_init($group_id);

    require($DOCUMENT_ROOT.'/include/HTML_Graphs.php');

    if ($field) {
	if ($field == 'aging') {
	    bug_header(array ("title"=>"Aging Report",
			      'help' => 'BugReporting.html'));
	    echo "\n<H2>Aging Report</H2>";

	    $time_now=time();
	    //			echo $time_now."<P>";

	    for ($counter=1; $counter<=8; $counter++) {

		$start=($time_now-($counter*604800));
		$end=($time_now-(($counter-1)*604800));

		$sql="SELECT avg((close_date-date)/86400) FROM bug WHERE close_date > 0 AND (date >= $start AND date <= $end) AND resolution_id <> '2' AND group_id='$group_id'";

		$result = db_query($sql);

		$names[$counter-1]=format_date("m/d/y",($start))." to ".format_date("m/d/y",($end));
		$values[$counter-1]=db_result($result, 0,0);
	    }

	    GraphIt($names, $values, "Average Turnaround Time For Closed Bugs");

	    echo "<P>";

	    for ($counter=1; $counter<=8; $counter++) {

		$start=($time_now-($counter*604800));
		$end=($time_now-(($counter-1)*604800));

		$sql="SELECT count(*) FROM bug WHERE date >= $start AND date <= $end AND resolution_id <> '2' AND group_id='$group_id'";

		$result = db_query($sql);

		$names[$counter-1]=format_date("m/d/y",($start))." to ".format_date("m/d/y",($end));
		$values[$counter-1]=db_result($result, 0,0);
	    }

	    GraphIt($names, $values, "Number of Bugs Opened");

	    echo "<P>";

	    for ($counter=1; $counter<=8; $counter++) {

		$start=($time_now-($counter*604800));
		$end=($time_now-(($counter-1)*604800));

		$sql="SELECT count(*) FROM bug WHERE date <= $end AND (close_date >= $end OR close_date < 1 OR close_date is null) AND resolution_id <> '2' AND group_id='$group_id'";

		$result = db_query($sql);

		$names[$counter-1]=format_date("m/d/y",($end));
		$values[$counter-1]=db_result($result, 0,0);
	    }

	    GraphIt($names, $values, "Number of Bugs Still Open");

	    echo "<P>";

	    bug_footer(array());

	} else {

	    // It's any of the select box field. 

	    $label = bug_data_get_label($field);
	    bug_header(array ("title"=>"Bugs By $label",
			      'help' => 'BugReporting.html'));

	    // Make sure it is a correct field
	    if (bug_data_is_special($field) || !bug_data_is_used($field) ||
		!bug_data_is_select_box($field) ) {

		echo "<h2>Can't generate report for field $label";

	    } else {

		echo "\n<H2>Bugs by $label</H2>";

		// First graph the bug distribution for Open bugs only	
		// Open means not status is neither closed (3) and nor Declined (7)
		// and resolution is not equal to Invalid (2)
		$sql="SELECT bug.$field, count(*) AS Count FROM bug ".
		    "WHERE bug.status_id <> '3' AND bug.status_id <> '7' AND ".
		    "bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
		    "GROUP BY bug.$field";

		$result=db_query($sql);
		if ($result && db_numrows($result) > 0) {
		    for ($j=0; $j<db_numrows($result); $j++) {
			$names[$j]= bug_data_get_cached_field_value($field, $group_id,db_result($result, $j, 0));
			$values[$j]= db_result($result, $j, 1);
		    }
		    GraphIt($names, $values,"Open Bugs By '$label'");

		} else { 
		    echo "<H3>Open Bugs By $label</H3>";
		    echo "No data found to report - Field probably not used";
		}
		echo "<P>";

		//Second  graph the bug distribution for all bugs only
		$sql="SELECT bug.$field, count(*) AS Count FROM bug ".
		    "WHERE bug.resolution_id <> '2' AND bug.group_id='$group_id' ".
		    "GROUP BY bug.$field";

		$result=db_query($sql);
		if ($result && db_numrows($result) > 0) {
		    for ($j=0; $j<db_numrows($result); $j++) {
			$names[$j]= bug_data_get_cached_field_value($field, $group_id,db_result($result, $j, 0));
			$values[$j]= db_result($result, $j, 1);
		    }
		    GraphIt($names, $values,"All Bugs By '$label'");

		} else {
		    echo "<H3>All Bugs By $label</H3>";
		    echo "No data found to report - Field probably not used";
		}

		bug_footer(array());
	    }
	}

    } else {
	/*
	  Show main page
	*/
	bug_header(array ("title"=>"Bug Reporting System",
			  'help' => 'BugReporting.html'));

	echo "\n<H1>Bug Reporting System</H1>";
	echo "\n<P>";
	echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&field=aging\">Aging Report</A><BR>";

	while ($field = bug_list_all_fields()) {

	    if (bug_data_is_special($field)) { continue;}

	    if (bug_data_is_select_box($field) && bug_data_is_used($field)) {

		echo "\n<A HREF=\"/bugs/reporting/?group_id=$group_id&field=$field\">Bugs by '".bug_data_get_label($field)."'</A><BR>";
	    }
	}

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
