<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$ 
//

$Language->loadLanguageMsg('project/project');

// filedownload_logs_daily
function filedownload_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;

	// check first if service is used by this project
	// if service not used return immediately
	$q = "SELECT is_used FROM service WHERE short_name='file' AND group_id=".$project->getGroupId();
	$res = db_query($q);
	if (db_result($res,0,0) == 0) {
		print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','file_download')).'</U></B>';
		return;
	}

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

	if ($who == "allusers") {
	    $cond = "";
	} else {
	    $users = implode(',',$project->getMembersId());
	    if ($who == "members") {
		$cond = " AND user.user_id IN ($users) ";
	    } else {
		$cond = " AND user.user_id NOT IN ($users) ";
	    }
	}

	$sql  = "SELECT filedownload_log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_file.filename AS filename "
	."FROM filedownload_log, user, frs_file, frs_release, frs_package "
	."WHERE filedownload_log.user_id=user.user_id ".$cond
	."AND frs_package.group_id=".$project->getGroupId()." "
        ."AND filedownload_log.filerelease_id=frs_file.file_id "
        ."AND frs_release.release_id=frs_file.release_id "
        ."AND frs_package.package_id=frs_release.package_id "
	."AND filedownload_log.time >= $begin_date "
	."ORDER BY time ASC";
	
	// Executions will continue until morale improves.
	$res = db_query( $sql);

	print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($Language->getText('project_stats_source_code_access_utils','file_download'),$span)).'</U></B>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

	print '<B><U> - '. $Language->getText('project_stats_source_code_access_utils','in_total',$nb_downloads).'</U></B>';

		print	'<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$Language->getText('project_admin_utils','date').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_utils','user').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_artifact_history_export','email').'</B></TD>'
			. '<TD align><B>'.$Language->getText('project_stats_source_code_access_utils','file').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('project_stats_source_code_access_utils','time_gmt').'</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%e %b %Y", $row["time"] ) . '</TD>'
			    . '<TD>' . $row["realname"] .' ('.util_user_link($row["user_name"]).')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["filename"] . '</TD>'				. '<TD align="right">' . gmstrftime("%H:%M", $row["time"]). '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
	  echo '<P>'.$Language->getText('project_stats_source_code_access_utils','no_access',$Language->getText('project_stats_source_code_access_utils','file_download'));
	}

}

function cvsaccess_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;

	// check first if service is used by this project
        // if service not used return immediately
        $q = "SELECT is_used FROM service WHERE short_name='cvs' AND group_id=".$project->getGroupId();
        $res = db_query($q);
        if (db_result($res,0,0) == 0) {
                print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_index','cvs')).'</U></B>';
		return;
	}


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

	if ($who == "allusers") {
	    $cond = "";
	} else {
	    $users = implode(',',$project->getMembersId());
	    if ($who == "members") {
		$cond = " AND user.user_id IN ($users) ";
	    } else {
		$cond = " AND user.user_id NOT IN ($users) ";
	    }
	}

	$sql  = "SELECT history.day, user.user_name, user.realname, user.email, history.cvs_checkouts, history.cvs_browse "
	."FROM group_cvs_full_history as history, user "
	."WHERE history.user_id=user.user_id ".$cond
	."AND history.group_id=".$project->getGroupId()." "
	."AND history.day >= $begin_day "
	."AND (history.cvs_checkouts != 0 OR history.cvs_browse != 0)"
	."ORDER BY day ASC";
	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($Language->getText('project_stats_source_code_access_utils','cvs_co_upd'),$span)).'</U></B></P>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

		print	'<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$Language->getText('project_admin_utils','date').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_utils','user').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_artifact_history_export','email').'</B></TD>'
			. '<TD align><B>'.$Language->getText('project_stats_source_code_access_utils','co_upd').'</B></TD>'
			. '<TD align><B>'.$Language->getText('project_stats_source_code_access_utils','browsing').'</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . substr($row["day"],6,2) .' '. $month_name[substr($row["day"],4,2) - 1] .' '. substr($row["day"],0,4) .'</TD>'
			    . '<TD>' . $row["realname"] .' ('.util_user_link($row["user_name"]).')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["cvs_checkouts"] . '</TD>'
				. '<TD>' . $row["cvs_browse"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo '<P>'.$Language->getText('project_stats_source_code_access_utils','no_access',$Language->getText('project_stats_source_code_access_utils','cvs_access'));
	}



}

function svnaccess_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;

	// check first if service is used by this project
        // if service not used return immediately
        $q = "SELECT is_used FROM service WHERE short_name='svn' AND group_id=".$project->getGroupId();
        $res = db_query($q);
        if (db_result($res,0,0) == 0) {
                print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','subversion')).'</U></B>';
		return;
	}
	
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

	if ($who == "allusers") {
	    $cond = "";
	} else {
	    $users = implode(',',$project->getMembersId());
	    if ($who == "members") {
		$cond = " AND user.user_id IN ($users) ";
	    } else {
		$cond = " AND user.user_id NOT IN ($users) ";
	    }
	}

	// We do not show Co/up/del/add svn counters for now because
	// they are at 0 in the DB 
	$sql  = "SELECT group_svn_full_history.day, user.user_name, user.realname, user.email, svn_access_count, svn_browse "
	."FROM group_svn_full_history, user "
	."WHERE group_svn_full_history.user_id=user.user_id ".$cond
	."AND group_svn_full_history.group_id=".$project->getGroupId()." "
	."AND group_svn_full_history.day >= $begin_day "
	."ORDER BY day ASC";
	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($Language->getText('project_stats_source_code_access_utils','svn_access'),$span)).'</U></B></P>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

		print	'<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$Language->getText('project_admin_utils','date').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_utils','user').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_artifact_history_export','email').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_stats_source_code_access_utils','accesses').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_stats_source_code_access_utils','browsing').'</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . substr($row["day"],6,2) .' '. $month_name[substr($row["day"],4,2) - 1] .' '. substr($row["day"],0,4) .'</TD>'
			        . '<TD>' . $row["realname"] .' ('.util_user_link($row["user_name"]).')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["svn_access_count"] . '</TD>'
				. '<TD>' . $row["svn_browse"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo '<P>'.$Language->getText('project_stats_source_code_access_utils','no_access',$Language->getText('project_stats_source_code_access_utils','svn_access'));
	}



}

// doc_logs_daily
function doc_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;
	// check first if service is used by this project
        // if service not used return immediately
        $q = "SELECT is_used FROM service WHERE short_name='doc' AND group_id=".$project->getGroupId();
        $res = db_query($q);
        if (db_result($res,0,0) == 0) {
                print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','docs')).'</U></B>';
		return;
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

	if ($who == "allusers") {
	    $cond = "";
	} else {
	    $users = implode(',',$project->getMembersId());
	    if ($who == "members") {
		$cond = " AND user.user_id IN ($users) ";
	    } else {
		$cond = " AND user.user_id NOT IN ($users) ";
	    }
	}

	$sql  = "SELECT doc_log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, doc_data.title AS title "
	    ."FROM doc_log, user, doc_data, doc_groups "
	    ."WHERE doc_log.user_id=user.user_id ".$cond
	    ."AND doc_groups.group_id=".$project->getGroupId()." "
	    ."AND doc_groups.doc_group = doc_data.doc_group "
	    ."AND doc_data.docid = doc_log.docid "
	    ."AND doc_log.time >= $begin_date "
	    ."ORDER BY time ASC";
	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($Language->getText('project_stats_source_code_access_utils','doc_download'),$span)).'</B></U>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

	print '<B><U> - '. $Language->getText('project_stats_source_code_access_utils','in_total',$nb_downloads).'</U></B>';

		print	'<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$Language->getText('project_admin_utils','date').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_utils','user').'</B></TD>'
			. '<TD><B>'.$Language->getText('project_export_artifact_history_export','email').'</B></TD>'
			. '<TD align><B>'.$Language->getText('project_stats_source_code_access_utils','doc').'</B></TD>'
			. '<TD align="right"><B>'.$Language->getText('project_stats_source_code_access_utils','time_gmt').'</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . gmstrftime("%e %b %Y", $row["time"] ) . '</TD>'
			    . '<TD>' . $row["realname"] .' ('.util_user_link($row["user_name"]).')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["title"] . '</TD>'				. '<TD align="right">' . gmstrftime("%H:%M", $row["time"]). '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo '<P>'.$Language->getText('project_stats_source_code_access_utils','no_access',$Language->getText('project_stats_source_code_access_utils','doc_download'));
	}

}

?>
