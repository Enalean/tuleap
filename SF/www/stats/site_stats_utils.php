<?php
//
// $Id$
//

$Language->loadLanguageMsg('stats/stats');

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


function stats_util_sum_array( $sum, $add ) {
	while( list( $key, $val ) = each( $add ) ) {
		$sum[$key] += $val;
	}
	return $sum;
}


function stats_generate_trove_pulldown( $selected_id = 0 ) {
  global $Language;

	$sql = "SELECT trove_cat_id,fullpath FROM trove_cat ORDER BY fullpath";
	$res = db_query( $sql );
	
	print '<SELECT name="trovecatid">' . "\n";
	print "\t<OPTION value=\"0\"> ".$Language->getText('stats_site_stats_utils','all_proj')." </OPTION>\n";
	print "\t<OPTION value=\"-1\"" . ( $selected_id == -1 ? " SELECTED" : "" ) . "> ".$Language->getText('stats_site_stats_utils','special_list')." </OPTION>\n";
	while ( $row = db_fetch_array($res) ) {
		print	"\t<OPTION value=\"" . $row["trove_cat_id"] . "\""
			. ( $selected_id == $row["trove_cat_id"] ? " SELECTED" : "" )
			. ">" . $row["fullpath"] . "</OPTION>\n";
	}
	print '</SELECT>' . "\n";
}


function stats_trove_cat_to_name( $trovecatid ) {
  global $Language;

	$sql = "SELECT fullpath FROM trove_cat WHERE trove_cat_id = $trovecatid";
	$res = db_query( $sql );
	if ( $row = db_fetch_array($res) ) {
		return $row["fullpath"];
	} else { 
		return " ".$Language->getText('stats_site_stats_utils','no_cat',$trovecatid)." ";
	}
}


function stats_generate_trove_grouplist( $trovecatid ) {
	
	$results = array();

	$sql = "SELECT * FROM trove_group_link WHERE trove_cat_id = $trovecatid";
	$res = db_query( $sql );
	print db_error( $res );

	$i = 0;
	while ( $row = db_fetch_array($res) ) {
		$results[$i++] = $row["group_id"];
	}

	return $results;
}


function stats_site_projects_form( $span = 21, $orderby = "downloads", $offset = 0, $projects = 0, $trovecat = 0 ) {
  global $Language;

	print '<FORM action="projects.php" method="get">' . "\n";
	print '<table width="100%" cellpadding="0" cellspacing="0" class="boxitem">' . "\n";

	print '<tr><td><b>'.$Language->getText('stats_site_stats_utils','proj_type').' </b></td><td>';
	stats_generate_trove_pulldown( $trovecat );
	print '</td></tr>';

	print '<tr><td><b>'.$Language->getText('stats_site_stats_utils','special_proj_list').' </b></td>';
	print '<td> <INPUT type="text" width="100" name="projects" value="' . ($projects ? $projects : "") . '">';
	print '  '.$Language->getText('stats_site_stats_utils','space_sep_gid').' </td></tr>';

	print '<tr><td><b>'.$Language->getText('stats_site_stats_utils','days_spanned').' </b></td><td>';
	$span_vals = array(7,14,21,30,60,90,120,180,$Language->getText('stats_site_stats_utils','all'));
	print html_build_select_box_from_array( $span_vals, "span", $span, 1 );
	print ' days </td></tr>';

	print '<tr><td><b>'.$Language->getText('stats_site_stats_utils','view_by').' </b></td><td>';
	$orderby_vals = array(	"ranking",
				"downloads",
				"site_views",
				"subdomain_views",
				"msg_posted",
				"bugs_opened",
				"bugs_closed",
				"support_opened",
				"support_closed",
				"patches_opened",
				"patches_closed",
				"tasks_opened",
				"tasks_closed",
				"cvs_checkouts",
				"cvs_commits",
				"cvs_adds",
				"svn_access_count");
	print html_build_select_box_from_array( $orderby_vals, "orderby", $orderby, 1 );
	print '</td></tr>';

	print '<tr><td colspan="2" align="center"> <INPUT type="submit" value="'.$Language->getText('stats_site_stats_utils','generate_report').'"> </td></tr>';

	print '</table>' . "\n";
	print '</FORM>' . "\n";

}


   // stats_site_projects
function stats_site_projects( $span = 7, $orderby = "ranking", $offset = 0, $projects = 0, $trove_cat = 0 ) {
  global $Language;

	$sql	= "SELECT s.month AS month, s.week AS week, s.day AS day, s.group_id AS group_id, "
		. "g.group_name AS group_name, m.ranking AS ranking, m.percentile AS percentile, SUM(s.downloads) AS downloads, "
		. "SUM(s.site_views) AS site_views, SUM(s.subdomain_views) AS subdomain_views, "
		. "SUM(s.msg_posted) AS msg_posted, SUM(s.bugs_opened) AS bugs_opened, "
		. "SUM(s.bugs_closed) AS bugs_closed, SUM(s.support_opened) AS support_opened, "
		. "SUM(s.support_closed) AS support_closed, SUM(s.patches_opened) AS patches_opened, "
		. "SUM(s.patches_closed) AS patches_closed, SUM(s.tasks_opened) AS tasks_opened, "
		. "SUM(s.tasks_closed) AS tasks_closed, SUM(s.cvs_checkouts) AS cvs_checkouts, "
		. "SUM(s.cvs_commits) AS cvs_commits, SUM(s.cvs_adds) AS cvs_adds, "
		. "SUM(s.svn_access_count) AS svn_access_count "
		. "FROM stats_project AS s,groups AS g,project_metric AS m ";

	   // Get information about the date $span days ago 
	$begin_date = localtime( (time() - ($span * 86400)), 1);
	$year = $begin_date["tm_year"] + 1900;
	$month = sprintf("%02d", $begin_date["tm_mon"] + 1);
	$day = $begin_date["tm_mday"];


	$sql .= "WHERE ( ";

	if ( $span != "All" ) {
		$sql .= "( ( month = " . $year . $month . " AND day >= " . $day . " ) OR ( month > " . $year . $month . " ) )";
	}

	$sql .= "AND ( s.group_id = g.group_id ) ";
	$sql .= "AND ( s.group_id = m.group_id ) ";

	if ( is_array( $projects ) ) {
		$sql .= "AND ( s.group_id IN (" . implode(",", $projects ) . ") ) ";
	} 

	$sql .= " ) ";
	$sql .= "GROUP BY s.group_id ";

	if ( $orderby == "ranking" ) {
		$sql .= "ORDER BY $orderby ASC ";
	} else {
		$sql .= "ORDER BY $orderby DESC ";
	}

	if ( $offset > 0 ) {
		$sql .= "LIMIT $offset,50";
	} else {
		$sql .= "LIMIT 50";
	}

	//print "\n\n<BR><HR><BR> $sql <BR><HR><BR>\n\n";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );

	   // if there are any rows, we have valid data (or close enough).
	if ( ($valid_days = db_numrows( $res )) > 1 ) {

		print "<P><B>".$Language->getText('stats_site_stats_utils','proj_stats_for')." ";
		if ( $span == $Language->getText('stats_site_stats_utils','all')) {
			print $Language->getText('stats_site_stats_utils','all_time');
		} else {
			print $Language->getText('stats_site_stats_utils','past_x_days',$span);
		}
		print " ".$Language->getText('stats_site_stats_utils','sorted_by',$orderby);
		if ( $trove_cat > 0 ) {
			print " ".$Language->getText('stats_site_stats_utils','within_cat',stats_trove_cat_to_name( $trove_cat ));
		}
		if ( is_array($projects) && $trove_cat <= 0 ) {
			print "<br> ".$Language->getText('stats_site_stats_utils','for_group',implode( ", ", $projects ));
		}
		print ". </B></P><BR>";

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>';

		print	'<TR valign="top" class="boxitem">'
			. '<TD><B>'.$Language->getText('stats_site_stats_utils','proj_name').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','ranking').'</B></TD>'
			. '<TD align="right" COLSPAN="2"><B>'.$Language->getText('stats_site_stats_utils','page_views').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','downl').'</B></TD>'
			. '<TD align="center" COLSPAN="2"><B>'.$Language->getText('stats_site_stats_utils','bugs').'</B></TD>'
			. '<TD align="center" COLSPAN="2"><B>'.$Language->getText('stats_site_stats_utils','support').'</B></TD>'
			. '<TD align="center" COLSPAN="2"><B>'.$Language->getText('stats_site_stats_utils','patches').'</B></TD>'
			. '<TD align="center" COLSPAN="2"><B>'.$Language->getText('stats_site_stats_utils','tasks').'</B></TD>'
			. '<TD align="center" COLSPAN="3"><B>'.$Language->getText('stats_site_stats_utils','cvs').'</B></TD>'
			. '<TD align="center" COLSPAN="3"><B>'.$Language->getText('stats_site_stats_utils','svn').'</B></TD>'
			. '</TR>' . "\n";

		   // Build the query string to resort results.
		$uri_string = "projects.php?span=" . $span;
		if ( $trove_cat > 0 ) {
			$uri_string .= "&trovecatid=" . $trove_cat;
		}
		if ( $trove_cat == -1 ) { 
			$uri_string .= "&projects=" . urlencode( implode( " ", $projects) );
		}
		$uri_string .= "&orderby=";

		print	'<TR valign="top" class="boxitem">'
			. '<TD align="right">&nbsp;</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'ranking">'.$Language->getText('stats_site_stats_utils','rank').'</A></TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'site_views">'.$Language->getText('stats_site_stats_utils','site').'</A></TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'subdomain_views">'.$Language->getText('stats_site_stats_utils','subdomain').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'downloads">'.$Language->getText('stats_site_stats_utils','total').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'bugs_opened">'.$Language->getText('stats_site_stats_utils','opn').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'bugs_closed">'.$Language->getText('stats_site_stats_utils','cls').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'support_opened">'.$Language->getText('stats_site_stats_utils','opn').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'support_closed">'.$Language->getText('stats_site_stats_utils','cls').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'patches_opened">'.$Language->getText('stats_site_stats_utils','opn').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'patches_closed">'.$Language->getText('stats_site_stats_utils','cls').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'tasks_opened">'.$Language->getText('stats_site_stats_utils','opn').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'tasks_closed">'.$Language->getText('stats_site_stats_utils','cls').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'cvs_checkouts">'.$Language->getText('stats_site_stats_utils','co').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'cvs_commits">'.$Language->getText('stats_site_stats_utils','comms').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'cvs_adds">'.$Language->getText('stats_site_stats_utils','adds').'</TD>'
			. '<TD align="right"><A HREF="' . $uri_string .'svn_access_count">'.$Language->getText('stats_site_stats_utils','access_cnt').'</TD>'
			. '</TR>' . "\n";
	
		$i = $offset;	
		while ( $row = db_fetch_array($res) ) {
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . ($i + 1) . '. <A HREF="/project/stats/?group_id=' . $row["group_id"] . '">' . $row["group_name"] . '</A></TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["ranking"] ) . ' (' . $row["percentile"] . '%) </TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["site_views"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["subdomain_views"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["downloads"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["bugs_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["bugs_closed"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["support_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["support_closed"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["patches_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["patches_closed"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["tasks_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["tasks_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["cvs_checkouts"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["cvs_commits"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["cvs_adds"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $row["svn_access_count"] ) . '</TD>'
				. '</TR>' . "\n";
			$i++;
			$sum = stats_util_sum_array( $sum, $row );
		}

		if ( $trove_cat == -1 ) {
			print '<TR><TD COLSPAN="16">&nbsp;</TD></TR>' . "\n";
			print '<TR><TD COLSPAN="16" align="center"></TD></TR>' . "\n";
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD><B>'.$Language->getText('stats_site_stats_utils','totals').'</B></TD>'
				. '<TD>&nbsp;</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["site_views"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["subdomain_views"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["downloads"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["bugs_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["bugs_closed"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["support_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["support_closed"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["patches_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["patches_closed"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["tasks_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["tasks_opened"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["cvs_checkouts"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["cvs_commits"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["cvs_adds"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . number_format( $sum["svn_access_count"] ) . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo $Language->getText('stats_site_stats_utils','no_valid_data')."\n";
		echo "<BR><HR><BR>\n $sql \n<BR><HR><BR>\n\n";
	}

}


   // stats_site_projects_daily
function stats_site_projects_daily( $span = 14 ) {
  global $Language;

	if (! $span ) { 
		$span = 14;
	}

	   // Get information about the date $span days ago 
	$begin_date = localtime( (time() - ($span * 86400)), 1);
	$year = $begin_date["tm_year"] + 1900;
	$month = sprintf("%02d", $begin_date["tm_mon"] + 1);
	$day = $begin_date["tm_mday"];

	$sql  = "SELECT s.month AS month, s.day AS day, s.downloads AS downloads, "
		. "s.site_views AS site_views, s.subdomain_views AS subdomain_views, "
		. "SUM(p.msg_posted) AS msg_posted, SUM(p.bugs_opened) AS bugs_opened, "
		. "SUM(p.bugs_closed) AS bugs_closed, SUM(p.support_opened) AS support_opened, "
		. "SUM(p.support_closed) AS support_closed, SUM(p.patches_opened) AS patches_opened, "
		. "SUM(p.patches_closed) AS patches_closed, SUM(p.tasks_opened) AS tasks_opened, "
		. "SUM(p.tasks_closed) AS tasks_closed, SUM(p.cvs_checkouts) AS cvs_checkouts, "
		. "SUM(p.cvs_commits) AS cvs_commits, SUM(p.cvs_adds) AS cvs_adds, "
		. "SUM(p.svn_access_count) AS svn_access_count "
	        . "FROM stats_project AS p, stats_site AS s "
		. "WHERE ( ( s.month = p.month AND s.day = p.day ) AND "
		. "( ( p.month = " . $year . $month . " AND p.day >= " . $day . " ) OR "
		. "( p.month > " . $year . $month . " ) ) ) "
		. "GROUP BY month,day ORDER BY month DESC, day DESC";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );
	print db_error( $res );

	   // if there are any weeks, we have valid data.
	if ( ($valid_days = db_numrows( $res )) > 1 ) {

		print '<P><B>'.$Language->getText('stats_site_stats_utils','stats_for_past_x_days',$valid_days).'</B></P>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$Language->getText('stats_site_stats_utils','day').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','site_views').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','subdomain_views').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','downl').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','bugs').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','support').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','patches').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','tasks').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','cvs').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','svn').'</B></TD>'
			. '</TR>' . "\n";

		while ( $row = db_fetch_array($res) ) {
			$i++;

			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%d %b %Y", mktime(0,0,1,substr($row["month"],4,2),$row["day"],substr($row["month"],0,4)) ) . '</TD>'
				. '<TD align="right">' . number_format( $row["site_views"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["subdomain_views"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["downloads"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["bugs_opened"] . " ( " . $row["bugs_closed"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["support_opened"] . " ( " . $row["support_closed"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["patches_opened"] . " ( " . $row["patches_closed"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["tasks_opened"] . " ( " . $row["tasks_closed"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["cvs_checkouts"] . " ( " . $row["cvs_commits"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["svn_access_count"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo $Language->getText('stats_site_stats_utils','proj_not_exist');
	}
}


   // stats_site_projects_weeky
function stats_site_projects_weekly( $span = 14 ) {
  global $Language;

	if (! $span ) { 
		$span = 14;
	}

	   // Get information about the date $span days ago 
	$begin_date = localtime( (time() - ($span * 86400)), 1);
	$year = $begin_date["tm_year"] + 1900;
	$month = sprintf("%02d", $begin_date["tm_mon"] + 1);
	$day = $begin_date["tm_mday"];


	$sql  = "SELECT month,day,AVG(group_ranking),AVG(group_metric),SUM(downloads),SUM(site_views),SUM(subdomain_views),";
	$sql .= "SUM(msg_posted),SUM(bugs_opened),SUM(bugs_closed),SUM(support_opened),";
	$sql .= "SUM(support_closed),SUM(patches_opened),SUM(patches_closed),SUM(tasks_opened),";
	$sql .= "SUM(tasks_closed),SUM(cvs_commits),SUM(cvs_adds),SUM(svn_access_count)";
	$sql .= "FROM stats_project ";
	$sql .= "WHERE ( ( month = " . $year . $month . " AND day >= " . $day . " ) OR ";
	$sql .= "( month > " . $year . $month . " ) ) ";
	$sql .= "GROUP BY month,day ORDER BY month DESC, day DESC";

	   // Executions will continue until morale improves.
	$res = db_query( $sql );
	print db_error( $res );

	   // if there are any weeks, we have valid data.
	if ( ($valid_days = db_numrows( $res )) > 1 ) {

		print '<P><B>'.$Language->getText('stats_site_stats_utils','stats_for_past_x_days',$valid_days).'</B></P>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$Language->getText('stats_site_stats_utils','day').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','site_views').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','subdomain_views').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','downl').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','bugs').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','support').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','patches').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','tasks').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('stats_site_stats_utils','cvs').'</B></TD>'
			. '</TR>' . "\n";

		while ( $row = db_fetch_array($res) ) {
			$i++;

			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%d %b %Y", mktime(0,0,1,substr($row["month"],4,2),$row["day"],substr($row["month"],0,4)) ) . '</TD>'
				. '<TD align="right">' . number_format( $row["SUM(site_views)"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["SUM(subdomain_views)"] ) . '</TD>'
				. '<TD align="right">' . number_format( $row["SUM(downloads)"] ) . '</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(bugs_opened)"] . " ( " . $row["SUM(bugs_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(support_opened)"] . " ( " . $row["SUM(support_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(patches_opened)"] . " ( " . $row["SUM(patches_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(tasks_opened)"] . " ( " . $row["SUM(tasks_closed)"] . ' )</TD>'
				. '<TD align="right">&nbsp;&nbsp;' . $row["SUM(cvs_checkouts)"] . " ( " . $row["SUM(cvs_commits)"] . ' )</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo $Language->getText('stats_site_stats_utils','proj_not_exist');
	}
}


   // stats_site_agregate
function stats_site_agregate( $group_id ) {
  global $Language;

	$sql	= "SELECT COUNT(day) AS days,SUM(site_views) AS site_views,"
		. "SUM(subdomain_views) AS subdomain_views,SUM(downloads) AS downloads "
		. "FROM stats_site";
	$res = db_query( $sql );
	$site_totals = db_fetch_array($res);

	$sql	= "SELECT COUNT(*) AS count FROM groups WHERE status='A'";
	$res = db_query( $sql );
	$groups = db_fetch_array($res);

	$sql	= "SELECT COUNT(*) AS count FROM user WHERE status='A' or status='R'";
	$res = db_query( $sql );
	$users = db_fetch_array($res);
	

	print "\n\n";
	print '<P><B>'.$Language->getText('stats_site_stats_utils','curr_agg_stats').'</B></P>' . "\n";

	print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>' . "\n";
	print	'<TR valign="top">'
		. '<TD><B>'.$Language->getText('stats_site_stats_utils','lifespan').'</B></TD>'
		. '<TD><B>'.$Language->getText('stats_site_stats_utils','site_views').'</B></TD>'
		. '<TD><B>'.$Language->getText('stats_site_stats_utils','subdomain_views').'</B></TD>'
		. '<TD><B>'.$Language->getText('stats_site_stats_utils','downl').'</B></TD>'
		. '<TD><B>'.$Language->getText('stats_site_stats_utils','developers').'</B></TD>'
		. '<TD><B>'.$Language->getText('stats_site_stats_utils','projs').'</B></TD>'
		. '</TR>' . "\n";

	print	'<TR>'
		. '<TD>' . $site_totals["days"] . ' '.$Language->getText('stats_site_stats_utils','days').' </TD>'
		. '<TD>' . number_format( $site_totals["site_views"] ) . '</TD>'
		. '<TD>' . number_format( $site_totals["subdomain_views"] ) . '</TD>'
		. '<TD>' . number_format( $site_totals["downloads"] ) . '</TD>'
		. '<TD>' . number_format( $users["count"] ) . '</TD>'
		. '<TD>' . number_format( $groups["count"] ) . '</TD>'
		. '</TR>' . "\n";

	print '</TABLE>';
}


?>
