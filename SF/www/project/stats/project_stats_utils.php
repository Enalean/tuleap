<?php

   // week_to_dates
function week_to_dates( $week, $year = 0 ) {

	if ( $year == 0 ) {
		$year = gmstrftime("%Y", time() );
	} 

	   // One second into the New Year!
	$beginning = gmmktime(0,0,0,1,1,$year);
	while ( gmstrftime("%U", $beginning) < 1 ) {
		   // 86,400 seconds? That's almost exactly one day!
		$beginning += 86400;
	}
	$beginning += (86400 * 7 * ($week - 1));
	$end = $beginning + (86400 * 6);

	return array( $beginning, $end );
}


   // stats_project_daily
function stats_project_daily( $group_id, $span = 7 ) {

	if (! $span ) { 
		$span = 7;
	}

	   // Get information about the date $span days ago 
	$begin_date = localtime( (time() - ($span * 86400)), 1);
	$year = $begin_date["tm_year"] + 1900;
	$month = sprintf("%02d", $begin_date["tm_mon"] + 1);
	$day = $begin_date["tm_mday"];

	$sql  = "SELECT month,day,AVG(group_ranking),AVG(group_metric),SUM(downloads),SUM(site_views + subdomain_views),";
	$sql .= "SUM(msg_posted),SUM(bugs_opened),SUM(bugs_closed),SUM(support_opened),";
	$sql .= "SUM(support_closed),SUM(patches_opened),SUM(patches_closed),SUM(tasks_opened),";
	$sql .= "SUM(tasks_closed),SUM(cvs_commits),SUM(cvs_adds)";
	$sql .= "FROM stats_project ";
	$sql .= "WHERE ( (( month = " . $year . $month . " AND day >= " . $day . " ) OR ";
	$sql .= "( month > " . $year . $month . " )) AND group_id = " . $group_id . " ) ";
	$sql .= "GROUP BY month,day ORDER BY month DESC, day DESC";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );

	   // if there are any days, we have valid data.
	if ( ($valid_days = db_numrows( $res )) > 1 ) {

		print '<P><B>Statistics for the past ' . $valid_days . ' days</B></P>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>Date</B></TD>'
			. '<TD><B>Rank</B></TD>'
			. '<TD align="right"><B>Page Views</B></TD>'
			. '<TD align="right"><B>Downloads</B></TD>'
			. '<TD align="right"><B>Bugs</B></TD>'
			. '<TD align="right"><B>Support</B></TD>'
			. '<TD align="right"><B>Patches</B></TD>'
			. '<TD align="right"><B>Tasks</B></TD>'
			. '<TD align="right"><B>CVS</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%e %b %Y", gmmktime(0,0,0,substr($row["month"],4,2),$row["day"],substr($row["month"],0,4)) ) . '</TD>'
				//. '<TD>' . $row["month"] . " " . $row["day"] . '</TD>'
				. '<TD>' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>'
				. '<TD align="right">' . number_format( $row["SUM(site_views + subdomain_views)"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["SUM(downloads)"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(bugs_opened)"] . " ( " . $row["SUM(bugs_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(support_opened)"] . " ( " . $row["SUM(support_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(patches_opened)"] . " ( " . $row["SUM(patches_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(tasks_opened)"] . " ( " . $row["SUM(tasks_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(cvs_commits)"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo "Project did not exist on this date.";
	}

}


   // stats_project_weekly
function stats_project_weekly( $group_id, $span = 8 ) {

	if (! $span ) { 
		$span = 8;
	}

	   // Get information about the date $span weeks ago 
	$begin_date = localtime( (time() - ($span * 7 * 86400)), 1);
	$week = gmstrftime("%U", (time() - ($span * 7 * 86400)) );
	$year = $begin_date["tm_year"] + 1900;
	$month = sprintf("%02d", $begin_date["tm_mon"] + 1);
	$day = $begin_date["tm_mday"];

	$sql  = "SELECT month,week,AVG(group_ranking),AVG(group_metric),SUM(downloads),SUM(site_views + subdomain_views),";
	$sql .= "SUM(msg_posted),SUM(bugs_opened),SUM(bugs_closed),SUM(support_opened),";
	$sql .= "SUM(support_closed),SUM(patches_opened),SUM(patches_closed),SUM(tasks_opened),";
	$sql .= "SUM(tasks_closed),SUM(cvs_commits),SUM(cvs_adds)";
	$sql .= "FROM stats_project ";
	$sql .= "WHERE ( (( month > " . $year . "00 AND week > " . $week . " ) OR ( month > " . $year . $month . "))";
	$sql .= "AND group_id = " . $group_id . " ) ";
	$sql .= "GROUP BY week ORDER BY month DESC, week DESC";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );

	   // if there are any weeks, we have valid data.
	if ( ($valid_weeks = db_numrows( $res )) > 1 ) {

		print '<P><B>Statistics for the past ' . ($valid_weeks - 1) . ' weeks, plus the week-in-progress.</B></P>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>Week</B></TD>'
			. '<TD><B>Rank</B></TD>'
			. '<TD align="right"><B>Page Views</B></TD>'
			. '<TD align="right"><B>Downloads</B></TD>'
			. '<TD align="right"><B>Bugs</B></TD>'
			. '<TD align="right"><B>Support</B></TD>'
			. '<TD align="right"><B>Patches</B></TD>'
			. '<TD align="right"><B>Tasks</B></TD>'
			. '<TD align="right"><B>CVS</B></TD>'
			. '</TR>' . "\n";

		$today = time();

		while ( $row = db_fetch_array($res) ) {
			$i++;

			$w_begin = $w_end = 0;
			list($w_begin, $w_end) = week_to_dates($row["week"]);
			//if ( $w_end > $today ) {
			//	$w_end = $today;
			//}

			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . $row["week"] . "&nbsp;(" . gmstrftime("%D", $w_begin) . " -> " . strftime("%D", $w_end) . ') </TD>'
				. '<TD>' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>'
				. '<TD align="right">' . number_format( $row["SUM(site_views + subdomain_views)"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["SUM(downloads)"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(bugs_opened)"] . " ( " . $row["SUM(bugs_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(support_opened)"] . " ( " . $row["SUM(support_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(patches_opened)"] . " ( " . $row["SUM(patches_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(tasks_opened)"] . " ( " . $row["SUM(tasks_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(cvs_commits)"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo "Project did not exist on this date.";
	}

}


   // stats_project_monthly
function stats_project_monthly( $group_id, $span = 4 ) {

	if (! $span ) { 
		$span = 4;
	}

	   // Get information about the date $span months ago 
	$begin_date = localtime( time(), 1 );
	$year = $begin_date["tm_year"] + 1900;
	$month = $begin_date["tm_mon"] + 1 - $span;
	while ( $month < 1 ) {
		$month += 12;
		$year -= 1;
	}

	$sql  = "SELECT month,AVG(group_ranking),AVG(group_metric),SUM(downloads),SUM(site_views + subdomain_views),";
	$sql .= "SUM(msg_posted),SUM(bugs_opened),SUM(bugs_closed),SUM(support_opened),";
	$sql .= "SUM(support_closed),SUM(patches_opened),SUM(patches_closed),SUM(tasks_opened),";
	$sql .= "SUM(tasks_closed),SUM(cvs_commits),SUM(cvs_adds)";
	$sql .= "FROM stats_project ";
	$sql .= "WHERE ( month > " . $year . sprintf("%02d", $month) . " AND group_id = " . $group_id . " ) ";
	$sql .= "GROUP BY month ORDER BY month DESC";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );

	   // if there are any weeks, we have valid data.
	if ( ($valid_months = db_numrows( $res )) > 1 ) {

		print '<P><B>Statistics for the past ' . $valid_months . ' months.</B></P>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>Month</B></TD>'
			. '<TD><B>Rank</B></TD>'
			. '<TD align="right"><B>Page Views</B></TD>'
			. '<TD align="right"><B>Downloads</B></TD>'
			. '<TD align="right"><B>Bugs</B></TD>'
			. '<TD align="right"><B>Support</B></TD>'
			. '<TD align="right"><B>Patches</B></TD>'
			. '<TD align="right"><B>Tasks</B></TD>'
			. '<TD align="right"><B>CVS</B></TD>'
			. '</TR>' . "\n";

		while ( $row = db_fetch_array($res) ) {
			$i++;

			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%B %Y", mktime(0,0,1,substr($row["month"],4,2),1,substr($row["month"],0,4)) ) . '</TD>'
				. '<TD>' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>'
				. '<TD align="right">' . number_format( $row["SUM(site_views + subdomain_views)"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["SUM(downloads)"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(bugs_opened)"] . " ( " . $row["SUM(bugs_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(support_opened)"] . " ( " . $row["SUM(support_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(patches_opened)"] . " ( " . $row["SUM(patches_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(tasks_opened)"] . " ( " . $row["SUM(tasks_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(cvs_commits)"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo "Project did not exist on this date.";
	}
}


   // stats_site_alltime
function stats_site_agregate( $group_id ) {

	$sql  = "SELECT COUNT(day),AVG(group_ranking),AVG(group_metric),SUM(downloads),SUM(site_views + subdomain_views),";
	$sql .= "developers,SUM(msg_posted),SUM(bugs_opened),SUM(bugs_closed),SUM(support_opened),";
	$sql .= "SUM(support_closed),SUM(patches_opened),SUM(patches_closed),SUM(tasks_opened),";
	$sql .= "SUM(tasks_closed),SUM(cvs_commits),SUM(cvs_adds)";
	$sql .= "FROM stats_project ";
	$sql .= "WHERE group_id = " . $group_id . " ";
	$sql .= "GROUP BY group_id ";
	$sql .= "ORDER BY month DESC, day DESC";

//	echo $sql . "<br><br>\n\n";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );
	$row = db_fetch_array($res);

	   // if there are any days, we have valid data.
	if ( 1 ) {

		print '<P><B>Statistics for All Time</B></P>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>Lifespan</B></TD>'
			. '<TD><B>Rank</B></TD>'
			. '<TD align="right"><B>Page Views</B></TD>'
			. '<TD align="right"><B>Downloads</B></TD>'
			. '<TD align="right"><B>Developers</B></TD>'
			. '<TD align="right"><B>Bugs</B></TD>'
			. '<TD align="right"><B>Support</B></TD>'
			. '<TD align="right"><B>Patches</B></TD>'
			. '<TD align="right"><B>Tasks</B></TD>'
			. '<TD align="right"><B>CVS</B></TD>'
			. '</TR>' . "\n";

		print	'<TR class="' . util_get_alt_row_color(1) . '">'
			. '<TD>' . $row["COUNT(day)"] . ' days </TD>'
			. '<TD>' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>'
			. '<TD align="right">' . number_format( $row["SUM(site_views + subdomain_views)"] ) . '</TD>'
			. '<TD align="right">' . number_format( $row["SUM(downloads)"] ) . '</TD>'
			. '<TD align="right">' . $row["developers"] . '</TD>'
			. '<TD align="right">' . $row["SUM(bugs_opened)"] . " ( " . $row["SUM(bugs_closed)"] . ' )</TD>'
			. '<TD align="right">' . $row["SUM(support_opened)"] . " ( " . $row["SUM(support_closed)"] . ' )</TD>'
			. '<TD align="right">' . $row["SUM(patches_opened)"] . " ( " . $row["SUM(patches_closed)"] . ' )</TD>'
			. '<TD align="right">' . $row["SUM(tasks_opened)"] . " ( " . $row["SUM(tasks_closed)"] . ' )</TD>'
			. '<TD align="right">' . $row["SUM(cvs_commits)"] . '</TD>'
			. '</TR>' . "\n";

		print '</TABLE>';

	} else {
		echo "Project does not seem to exist.";
	}
}


?>
