<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$ 

   // filedownload_logs_daily
function filedownload_logs_daily( $group_id, $span = 7 ) {

	if (! $span ) { 
		$span = 7;
	}

	// Get information about the date $span days ago 
	// Start at midnight $span days ago
	$time_back = localtime( (time() - ($span * 86400)), 1);

	// This for debug
	// print "time_back= ". $time_back['tm_hour'].":".$time_back['tm_min'].":".$time_back['tm_sec']." on ".$time_back['tm_mday']." ".$time_back['tm_mon']." ".$time_back['tm_year']."<BR>";

	// Adjust to midnight this day
	$time_back["tm_sec"] = $time_back["tm_min"] = $time_back["tm_hour"] = 0;
	$begin_date = mktime($time_back["tm_hour"], $time_back["tm_min"], $time_back["tm_sec"], $time_back["tm_mon"]+1, $time_back["tm_mday"], $time_back["tm_year"]+1900);


	// For Debug
	// print join(" ",localtime($begin_date,0))."<BR>";
	// print "begin_date: $begin_date<BR>";

	$sql  = "SELECT filedownload_log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_file.filename AS filename "
	."FROM filedownload_log, user, frs_file, frs_release, frs_package "
	."WHERE filedownload_log.user_id=user.user_id "
	."AND frs_package.group_id=$group_id "
        ."AND filedownload_log.filerelease_id=frs_file.file_id "
        ."AND frs_release.release_id=frs_file.release_id "
        ."AND frs_package.package_id=frs_release.package_id "
	."AND filedownload_log.time >= $begin_date "
	."ORDER BY time ASC";
	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><B><U>File Download for the past ' . $span. ' days</U>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

	print '<B><U> - '. $nb_downloads .' in total</U></B>';

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>Date</B></TD>'
			. '<TD><B>User</B></TD>'
			. '<TD><B>E-mail</B></TD>'
			. '<TD align><B>File</B></TD>'
			. '<TD align="right"><B>Time (local)</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%e %b %Y", $row["time"] ) . '</TD>'
				. '<TD>' . $row["realname"] .' ('.$row["user_name"].')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["filename"] . '</TD>'				. '<TD align="right">' . gmstrftime("%H:%M", $row["time"]). '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo "<P>No file download for this period.";
	}

}

function cvsaccess_logs_daily( $group_id, $span = 7 ) {

	if (! $span ) { 
		$span = 7;
	}

	$month_name = array('Jan','Feb','Mar','Apr','May','June','Jul','Aug', 'Sep','Oct','Nov','Dec');

	// Get information about the date $span days ago 
	// Start at midnight $span days ago
	$time_back = localtime( (time() - ($span * 86400)), 1);

	// This for debug
	// print "time_back= ". $time_back['tm_hour'].":".$time_back['tm_min'].":".$time_back['tm_sec']." on ".$time_back['tm_mday']." ".$time_back['tm_mon']." ".$time_back['tm_year']."<BR>";

	// Adjust to midnight this day
	$time_back["tm_sec"] = $time_back["tm_min"] = $time_back["tm_hour"] = 0;
	$begin_date = mktime($time_back["tm_hour"], $time_back["tm_min"], $time_back["tm_sec"], $time_back["tm_mon"]+1, $time_back["tm_mday"], $time_back["tm_year"]+1900);

	$begin_day = strftime("%Y%m%d", $begin_date);

	// For Debug
	// print join(" ",localtime($begin_date,0))."<BR>";
	// print "begin_day: $begin_day<BR>";

	$sql  = "SELECT group_cvs_full_history.day AS day, user.user_name AS user_name, user.realname AS realname, user.email AS email, group_cvs_full_history.cvs_checkouts AS cvs_checkouts "
	."FROM group_cvs_full_history, user "
	."WHERE group_cvs_full_history.user_id=user.user_id "
	."AND group_cvs_full_history.group_id=$group_id "
	."AND group_cvs_full_history.day >= $begin_day "
	."AND group_cvs_full_history.cvs_checkouts != 0 "
	."ORDER BY day ASC";
	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><U><B>CVS checkouts/updates for the past ' . $span. ' days </B></U></P>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

		print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>Date</B></TD>'
			. '<TD><B>User</B></TD>'
			. '<TD><B>E-mail</B></TD>'
			. '<TD align><B>Checkouts/Update</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . substr($row["day"],6,2) .' '. $month_name[substr($row["day"],4,2) - 1] .' '. substr($row["day"],0,4) .'</TD>'
				. '<TD>' . $row["realname"] .' ('.$row["user_name"].')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["cvs_checkouts"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo "<P>No CVS access for this period.";
	}



}
?>
