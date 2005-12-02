<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$ 
//

$Language->loadLanguageMsg('project/project');

/**
 * Prepare SQL query for given date and given person
 */
function logs_cond($project, $span, $who) {
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

  $whereclause = "log.user_id=user.user_id ".$cond
    ." AND log.time >= $begin_date ";
	
  return $whereclause;
}

/**
 * Process SQL query and display corresponding result
 */
function logs_display($sql, $span, $field, $title='') {
  global $Language;
  // Executions will continue until morale improves.
  $res = db_query( $sql );

  print '<p><u><b>'.$Language->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($title,$span));
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

    print ' - '.$Language->getText('project_stats_source_code_access_utils','in_total',$nb_downloads).'</u></b>';

    print '<table width="100%" cellpadding="2" cellspacing="0" border="0">'."\n"
      . '<tr valign="top">'."\n"
      . ' <th>'.$Language->getText('project_admin_utils','date').'</th>'."\n"
      . ' <th>'.$Language->getText('project_export_utils','user').'</th>'."\n"
      . ' <th>'.$Language->getText('project_export_artifact_history_export','email').'</th>'."\n"
      . ' <th>'.$field.'</th>'."\n"
      . ' <th align="right">'.$Language->getText('project_stats_source_code_access_utils','time').'</th>'."\n"
      . '</tr>'."\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
 
      print '<tr class="'. util_get_alt_row_color($i). '">'
	.' <td>'.strftime("%e %b %Y", $row["time"] ).'</td>'
	.' <td>'.$row["realname"].' ('.util_user_link($row["user_name"]).')</td>'
	.' <td>'.$row["email"].'</td>'
	.' <td>'.$row["title"].'</td>'
	.' <td align="right">'.strftime("%H:%M", $row["time"]).'</td>'
	.'</tr>'."\n";
		}

    print '</table>';

	}
  else {
    echo "</u></b>
<p>".$Language->getText('project_stats_source_code_access_utils','no_access')."</p>";
  }
}

// filedownload_logs_daily
function filedownload_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;
	// check first if service is used by this project
        // if service not used return immediately
	if (!$project->usesFile()) {
		print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','file_download')).'</U></B>';
		return;
	}


	$sql  = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_file.filename AS title "
	."FROM filedownload_log AS log, user, frs_file, frs_release, frs_package "
	."WHERE ".logs_cond($project, $span, $who)
	."AND frs_package.group_id=".$project->getGroupId()." "
        ."AND log.filerelease_id=frs_file.file_id "
        ."AND frs_release.release_id=frs_file.release_id "
        ."AND frs_package.package_id=frs_release.package_id "
	."ORDER BY time DESC";
	
	logs_display($sql, $span, $Language->getText('project_stats_source_code_access_utils','files'),
		     $Language->getText('project_stats_source_code_access_utils','file_download'));
}



function cvsaccess_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;

	// check first if service is used by this project
        // if service not used return immediately
        if (!$project->usesCVS()) {
                print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_index','cvs')).'</U></B>';
		return;
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
	."ORDER BY day DESC";
	
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
        if (! $project->usesSVN()) {
                print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','subversion')).'</U></B>';
		return;
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
	."ORDER BY day DESC";
	
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

/**
 * Display Document pages access log
 */
function doc_logs_daily($project, $span = 7, $who="allusers") {
  global $Language;
	// check first if service is used by this project
        // if service not used return immediately
  if(!$project->usesDocman()) {
    print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','docs')).'</U></B>';
		return;
	}


  $sql  = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, doc_data.title AS title "
    ."FROM doc_log AS log, user, doc_data, doc_groups "
    ."WHERE ".logs_cond($project, $span, $who)
	    ."AND doc_groups.group_id=".$project->getGroupId()." "
	    ."AND doc_groups.doc_group = doc_data.doc_group "
    ."AND doc_data.docid = log.docid "
	    ."ORDER BY time DESC";
	
  logs_display($sql, $span, $Language->getText('project_stats_source_code_access_utils','docs'),
	       $Language->getText('project_stats_source_code_access_utils','doc_download'));
}
		
/**
 * Display Wiki pages access log
 */
function wiki_logs_daily($project, $span = 7, $who="allusers") {
  // check first if service is used by this project
  // if service not used return immediately
  global $Language;
  if(!$project->usesWiki()) {
      print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','wiki')).'</U></B>';
     return;
		}

  $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, log.pagename AS title"
    ." FROM wiki_log AS log, user"
    ." WHERE ".logs_cond($project, $span, $who)
    ." AND log.group_id=".$project->getGroupId()
    ." ORDER BY time DESC";

  logs_display($sql, $span, $Language->getText('project_stats_source_code_access_utils','wiki_page'),
	       $Language->getText('project_stats_source_code_access_utils','wiki_access'));
}

/**
 * Display Wiki Attachments access log
 */
function wiki_attachments_logs_daily($project, $span = 7, $who="allusers") {
    // check first if service is used by this project
    // if service not used return immediately
    global $Language;
    if(!$project->usesWiki()) {
        print '<P><B><U>'.$Language->getText('project_stats_source_code_access_utils','service_disabled',$Language->getText('project_stats_source_code_access_utils','wiki_attachments')).'</U></B>';
        return;
    }
    
    $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, wa.name AS title"
        ." FROM wiki_attachment_log AS log, user, wiki_attachment AS wa"
        ." WHERE ".logs_cond($project, $span, $who)
        ." AND log.group_id=".$project->getGroupId()
        ." AND wa.id=log.wiki_attachment_id"
        ." ORDER BY time DESC";
    
    logs_display($sql, $span, $Language->getText('project_stats_source_code_access_utils','wiki_attachment_title'),
                 $Language->getText('project_stats_source_code_access_utils','wiki_attachment_access'));
}

?>
