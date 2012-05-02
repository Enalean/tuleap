<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
//  
//


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
    $hp = Codendi_HTMLPurifier::instance();
    // Executions will continue until morale improves.
    $res = db_query( $sql );

    print '<p><u><b>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($title,$span));
    if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {
        $row = db_fetch_array($res);
        print ' - '.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','in_total',$nb_downloads).'</u></b>';

        print '<table width="100%" cellpadding="2" cellspacing="0" border="0">'."\n"
            . '<tr valign="top">'."\n"
            . ' <th>'.$GLOBALS['Language']->getText('project_admin_utils','date').'</th>'."\n";
        
        if (isset($row['type'])){
            print ' <th>'.$GLOBALS['Language']->getText('project_admin_utils','action').'</th>'."\n";
        }
        print ' <th>'.$GLOBALS['Language']->getText('project_export_utils','user').'</th>'."\n"
            . ' <th>'.$GLOBALS['Language']->getText('project_export_artifact_history_export','email').'</th>'."\n"
            . ' <th>'.$field.'</th>'."\n"
            . ' <th align="right">'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','time').'</th>'."\n"
            . '</tr>'."\n";
        $i = 0;
        do {
            print '<tr class="'. util_get_alt_row_color($i++). '">'
            .' <td>'.strftime("%e %b %Y", $row['time'] ).'</td>';
            if (isset($row['type'])){
                print' <td>'.$row['type'].'</td>';
            }

            print ' <td> <a href="/users/'.$row["user_name"].'/">'.$row["user_name"].'</a> ('. $hp->purify($row["realname"], CODENDI_PURIFIER_CONVERT_HTML) .')</td>'
                .' <td>'.$row["email"].'</td>';
            print ' <td>';
            print $hp->purify($row["title"], CODENDI_PURIFIER_CONVERT_HTML) .'</td>'
                .' <td align="right">'.strftime("%H:%M", $row["time"]).'</td>'
                .'</tr>'."\n";
        } while ($row = db_fetch_array($res));

        print '</table>';

    } else {
        echo "</u></b>
        <p>".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','no_access')."</p>";
    }
}

function frs_logs_extract($project,$span,$who) {
    /*
     * This request is used to obtain FRS actions log such as package, release or file : creation, update or deletion.
     * Each SELECT statement is used to obtain logs related to an FRS element type.
     *    SELECT #1 : Creation, update and deletion of packages.
     *    SELECT #2 : Creation, update and deletion of releases.
     *    SELECT #3 : Creation, update and deletion of files.
     *    SELECT #4 : Restoration of files.
     * Each CASE statement is used to replace log.action_id by text description corresponding to the action.
     * So don't worry if this request seem so big and so hard to understand in fact it's a relatively simple union of selects.
     */
    $sql = "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_package.name AS title,".
           "        CASE ".
           "        WHEN log.action_id = ".FRSPackage::EVT_CREATE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_create_package')."'".
           "        WHEN log.action_id = ".FRSPackage::EVT_UPDATE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_update_package')."'".
           "        WHEN log.action_id = ".FRSPackage::EVT_DELETE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_delete_package')."'".
           "        END as type".
           "    FROM frs_log AS log".
           "        JOIN user USING (user_id)".
           "        JOIN frs_package ON log.item_id=frs_package.package_id".
           "    WHERE log.group_id=".$project->getGroupId().
           "        AND ".logs_cond($project, $span, $who).
           "        AND ( log.action_id=".FRSPackage::EVT_CREATE." OR log.action_id=".FRSPackage::EVT_UPDATE." OR log.action_id=".FRSPackage::EVT_DELETE." )".
           " UNION".
           "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(frs_package.name, '/', frs_release.name) AS title,".
           "        CASE ". 
           "        WHEN log.action_id = ".FRSRelease::EVT_CREATE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_create_release')."'".
           "        WHEN log.action_id = ".FRSRelease::EVT_UPDATE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_update_release')."'".
           "        WHEN log.action_id = ".FRSRelease::EVT_DELETE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_delete_release')."'".
           "        END as type".
           "    FROM frs_log AS log".
           "        JOIN user using (user_id)".
           "        JOIN frs_release ON log.item_id=frs_release.release_id ".
           "        JOIN frs_package using (package_id)". 
           "    WHERE ".logs_cond($project, $span, $who).
           "        AND ( log.action_id=".FRSRelease::EVT_CREATE." OR log.action_id=".FRSRelease::EVT_UPDATE." OR log.action_id=".FRSRelease::EVT_DELETE." ) ".
           "        AND log.group_id=".$project->getGroupId()." ".
           " UNION".
           "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(frs_package.name, '/', frs_release.name, '/', SUBSTRING_INDEX(frs_file.filename, '/', -1)) AS title,".
           "        CASE ".
           "        WHEN log.action_id = ".FRSFile::EVT_CREATE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_create_file')."'".
           "        WHEN log.action_id = ".FRSFile::EVT_UPDATE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_update_file')."'".
           "        WHEN log.action_id = ".FRSFile::EVT_DELETE." THEN '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_delete_file')."'".
           "        END as type".
           "    FROM frs_log AS log".
           "        JOIN user using (user_id)".
           "        JOIN frs_file ON log.item_id=frs_file.file_id".
           "        JOIN frs_release using (release_id) ".
           "        JOIN frs_package using (package_id) ".
           "    WHERE ".logs_cond($project, $span, $who).
           "        AND ( log.action_id=".FRSFile::EVT_CREATE." OR log.action_id=".FRSFile::EVT_UPDATE." OR log.action_id=".FRSFile::EVT_DELETE." )".
           "        AND log.group_id=".$project->getGroupId().
           " UNION".
           "    SELECT log.log_id, log.time AS time, 'N/A' AS user_name, 'N/A' AS realname, 'N/A' AS email, CONCAT(frs_package.name, '/', frs_release.name, '/', SUBSTRING_INDEX(frs_file.filename, '/', -1)) AS title, '".$GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_restore')."' AS type".
           "    FROM frs_log AS log".
           "        JOIN frs_file ON log.item_id=frs_file.file_id".
           "        JOIN frs_release using (release_id) ".
           "        JOIN frs_package using (package_id) ".
           "    WHERE log.action_id=".FRSFile::EVT_RESTORE.
           "        AND log.group_id=".$project->getGroupId().
           " ORDER BY log_id DESC";
    return $sql;
}

function filedownload_logs_extract($project,$span,$who) {

	$sql  = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_file.filename AS title "
	."FROM filedownload_log AS log, user, frs_file, frs_release, frs_package "
	."WHERE ".logs_cond($project, $span, $who)
	."AND frs_package.group_id=".$project->getGroupId()." "
        ."AND log.filerelease_id=frs_file.file_id "
        ."AND frs_release.release_id=frs_file.release_id "
        ."AND frs_package.package_id=frs_release.package_id "
	."ORDER BY time DESC";
	
	return $sql;

}

// filedownload_logs_daily
function filedownload_logs_daily($project, $span = 7, $who="allusers") {
  
	// check first if service is used by this project
        // if service not used return immediately
	if (!$project->usesFile()) {
		print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','service_disabled',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','file_download')).'</U></B>';
		return;
	}

    $sql = filedownload_logs_extract($project,$span,$who);
    	
	logs_display($sql, $span, $GLOBALS['Language']->getText('project_stats_source_code_access_utils','files'),
		     $GLOBALS['Language']->getText('project_stats_source_code_access_utils','file_download'));

    $sql = frs_logs_extract($project,$span,$who);
    logs_display($sql, $span, $GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_elements'),
                 $GLOBALS['Language']->getText('project_stats_source_code_access_utils','frs_actions'));
}

function cvsaccess_logs_extract($project,$span,$who) {

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
		."AND day >= $begin_day "
		."AND (cvs_checkouts != 0 OR cvs_browse != 0) "
		."ORDER BY day DESC";

	return $sql;
}

function cvsaccess_logs_daily($project, $span = 7, $who="allusers") {  
    $hp = Codendi_HTMLPurifier::instance();
	// check first if service is used by this project
        // if service not used return immediately
        if (!$project->usesCVS()) {
                print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','service_disabled',$GLOBALS['Language']->getText('project_stats_index','cvs')).'</U></B>';
		return;
	}

	$month_name = array('Jan','Feb','Mar','Apr','May','June','Jul','Aug', 'Sep','Oct','Nov','Dec');

    $sql = cvsaccess_logs_extract($project, $span, $who);
    	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($GLOBALS['Language']->getText('project_stats_source_code_access_utils','cvs_co_upd'),$span)).'</U></B></P>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

		print	'<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_admin_utils','date').'</B></TD>'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_export_utils','user').'</B></TD>'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_export_artifact_history_export','email').'</B></TD>'
			. '<TD align><B>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','co_upd').'</B></TD>'
			. '<TD align><B>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','browsing').'</B></TD>'
			. '</TR>' . "\n";
		
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . substr($row["day"],6,2) .' '. $month_name[substr($row["day"],4,2) - 1] .' '. substr($row["day"],0,4) .'</TD>'
			    . '<TD> <a href="/users/'.$row["user_name"].'/">' . $row["user_name"] .'</a> ('. $hp->purify($row["realname"], CODENDI_PURIFIER_CONVERT_HTML) .')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["cvs_checkouts"] . '</TD>'
				. '<TD>' . $row["cvs_browse"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo '<P>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','no_access',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','cvs_access'));
	}



}

function svnaccess_logs_extract($project, $span, $who) {

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
	
	return $sql;

}

function svnaccess_logs_daily($project, $span = 7, $who="allusers") {
    $hp = Codendi_HTMLPurifier::instance();
	// check first if service is used by this project
        // if service not used return immediately
        if (! $project->usesSVN()) {
                print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','service_disabled',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','subversion')).'</U></B>';
		return;
	}

	$month_name = array('Jan','Feb','Mar','Apr','May','June','Jul','Aug', 'Sep','Oct','Nov','Dec');

    $sql = svnaccess_logs_extract($project, $span, $who);
    	
	// Executions will continue until morale improves.
	$res = db_query( $sql );

	print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','access_for_past_x_days',array($GLOBALS['Language']->getText('project_stats_source_code_access_utils','svn_access'),$span)).'</U></B></P>';

	// if there are any days, we have valid data.
	if ( ($nb_downloads = db_numrows( $res )) >= 1 ) {

		print	'<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
			. '<TR valign="top">'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_admin_utils','date').'</B></TD>'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_export_utils','user').'</B></TD>'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_export_artifact_history_export','email').'</B></TD>'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','accesses').'</B></TD>'
			. '<TD><B>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','browsing').'</B></TD>'
			. '</TR>' . "\n";
		$i=0;
		while ( $row = db_fetch_array($res) ) {
			$i++;
			print	'<TR class="' . util_get_alt_row_color($i) . '">'
				. '<TD>' . substr($row["day"],6,2) .' '. $month_name[substr($row["day"],4,2) - 1] .' '. substr($row["day"],0,4) .'</TD>'
			    . '<TD> <a href="/users/'.$row["user_name"].'/">' . $row["user_name"] .'</a> ('. $hp->purify($row["realname"], CODENDI_PURIFIER_CONVERT_HTML) .')</TD>'
				. '<TD>' . $row["email"] . '</TD>'
				. '<TD>' . $row["svn_access_count"] . '</TD>'
				. '<TD>' . $row["svn_browse"] . '</TD>'
				. '</TR>' . "\n";
		}

		print '</TABLE>';

	} else {
		echo '<P>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','no_access',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','svn_access'));
	}



}

function doc_logs_extract($project, $span, $who) {

    $sql  = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, doc_data.title AS title "
	    ."FROM doc_log AS log, user, doc_data, doc_groups "
	    ."WHERE ".logs_cond($project, $span, $who)
	    ."AND doc_groups.group_id=".$project->getGroupId()." "
	    ."AND doc_groups.doc_group = doc_data.doc_group "
	    ."AND doc_data.docid = log.docid "
	    ."ORDER BY time DESC";
	    
    return $sql;

}

/**
 * Display Document pages access log
 */
function doc_logs_daily($project, $span = 7, $who="allusers") {
  
	// check first if service is used by this project
        // if service not used return immediately
  if(!$project->usesDocman()) {
    print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','service_disabled',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','docs')).'</U></B>';
		return;
	}

  $sql = doc_logs_extract($project,$span,$who);
  	
  logs_display($sql, $span, $GLOBALS['Language']->getText('project_stats_source_code_access_utils','docs'),
	       $GLOBALS['Language']->getText('project_stats_source_code_access_utils','doc_download'));
}

function wiki_logs_extract($project, $span, $who) {

  $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, log.pagename AS title"
    ." FROM wiki_log AS log, user"
    ." WHERE ".logs_cond($project, $span, $who)
    ." AND log.group_id=".$project->getGroupId()
    ." ORDER BY time DESC";
    
  return $sql;

}

/**
 * Display Wiki pages access log
 */
function wiki_logs_daily($project, $span = 7, $who="allusers") {

  // check first if service is used by this project
  // if service not used return immediately  
  if(!$project->usesWiki()) {
      print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','service_disabled',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','wiki')).'</U></B>';
     return;
  }

  $sql = wiki_logs_extract($project, $span, $who);
  
  logs_display($sql, $span, $GLOBALS['Language']->getText('project_stats_source_code_access_utils','wiki_page'),
	       $GLOBALS['Language']->getText('project_stats_source_code_access_utils','wiki_access'));
}

function wiki_attachments_logs_extract($project, $span, $who) {

    $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, wa.name AS title"
        ." FROM wiki_attachment_log AS log, user, wiki_attachment AS wa"
        ." WHERE ".logs_cond($project, $span, $who)
        ." AND log.group_id=".$project->getGroupId()
        ." AND wa.id=log.wiki_attachment_id"
        ." ORDER BY time DESC";
	
    return $sql;

}

/**
 * Display Wiki Attachments access log
 */
function wiki_attachments_logs_daily($project, $span = 7, $who="allusers") {

	// check first if service is used by this project
    // if service not used return immediately
    if(!$project->usesWiki()) {
        print '<P><B><U>'.$GLOBALS['Language']->getText('project_stats_source_code_access_utils','service_disabled',$GLOBALS['Language']->getText('project_stats_source_code_access_utils','wiki_attachments')).'</U></B>';
        return;
    }
    
    $sql = wiki_attachments_logs_extract($project, $span, $who);
        
    logs_display($sql, $span, $GLOBALS['Language']->getText('project_stats_source_code_access_utils','wiki_attachment_title'),
                 $GLOBALS['Language']->getText('project_stats_source_code_access_utils','wiki_attachment_access'));
}

function plugins_log_extract($project, $span, $who) {

    $event_manager = EventManager::instance();
    $logs = array();
    $event_manager->processEvent('logs_daily', array(
        'group_id'  => $project->getGroupId(),
        'logs_cond' => logs_cond($project, $span, $who),
        'logs'      => &$logs
    ));
    return $logs;

}

function plugins_logs_daily($project, $span = 7, $who = 'allusers') {

	$logs = plugins_log_extract($project, $span, $who);
	foreach($logs as $log) {
        logs_display($log['sql'], $span, $log['field'], $log['title']);
    }
}


?>
