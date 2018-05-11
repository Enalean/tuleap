<?php
  //
  // SourceForge: Breaking Down the Barriers to Open Source Development
  // Copyright 1999-2000 (c) The SourceForge Crew
  // http://sourceforge.net
  //
  // 

require_once('www/project/admin/permissions.php');
require_once('www/new/new_utils.php');

function show_features_boxes() {
    GLOBAL $HTML,$Language;
    $return  = "";
    
    $w = new Widget_Static($GLOBALS['sys_name'].' '.$Language->getText('include_features_boxes','stats'));
    $w->setContent(show_sitestats());
    $w->display();

    $w = new Widget_Static($Language->getText('include_features_boxes','top_download_yesterday'));
    $w->setContent(show_top_downloads());
    $w->display();

    $w = new Widget_Static($Language->getText('include_features_boxes','newest_releases'));
    $w->setContent(show_newest_releases());
    $w->display();

    $w = new Widget_Static($Language->getText('include_features_boxes','newest_projects'));
    $w->setContent(show_newest_projects());
    $w->setRssUrl('/export/rss_sfprojects.php?type=rss&option=newest');
    $w->display();

    $w = new Widget_Static($Language->getText('include_features_boxes','most_active_week'));
    $w->setContent(show_highest_ranked_projects());
    $w->display();
}

function show_top_downloads() {
    global $Language;
    $return  = "";
    $hp = Codendi_HTMLPurifier::instance();
    // Get time for today and yesterday at midnight
    $end_time = mktime(0,0,0);
    $start_time = $end_time - 86400;

    // Get the top downloads for this time window
    $res_topdown = db_query("SELECT groups.group_id,groups.group_name, "
                            ."groups.unix_group_name, "
                            ."COUNT(filedownload_log.filerelease_id) as downloads "
                            ."FROM groups,filedownload_log,frs_file,frs_release,frs_package "
                            ."WHERE frs_file.file_id = filedownload_log.filerelease_id "
                            ."AND frs_file.release_id = frs_release.release_id "
                            ."AND frs_package.package_id = frs_release.package_id "
                            ."AND frs_package.group_id = groups.group_id "
                            ."AND groups.type = 1 "
                            ."AND filedownload_log.time > ".$start_time." "
                            ."AND filedownload_log.time < ".$end_time." "
                            ."GROUP BY groups.group_id "
                            ."ORDER BY downloads DESC LIMIT 10");
	
    // print each one
    while ($row_topdown = db_fetch_array($res_topdown)) {
        if ($row_topdown['downloads'] > 0) 
            $return .= '('. $row_topdown['downloads'] .') <A href="/projects/'. $row_topdown['unix_group_name'] .'/">'
                .  $hp->purify(util_unconvert_htmlspecialchars($row_topdown['group_name']), CODENDI_PURIFIER_CONVERT_HTML)  ."</A><BR>\n";
    }

    return $return;

}

function show_newest_releases() {
    global $Language;
    $return  = "";
    $hp = Codendi_HTMLPurifier::instance();
    // Fetch releases that are no more than 3 months old
    $start_time = strval(time() - 3*30*24*3600);
    $query = new_utils_get_new_releases_short($start_time);

    $res_newrel = db_query($query);

    // print each one but only show one release per project
    $count = 0;
    $DONE  = array();
    $frspf = new FRSPackageFactory();
    $frsrf = new FRSReleaseFactory();
    while ( ($row_newrel = db_fetch_array($res_newrel)) && ($count < 10)) {
        
        if ( !isset($DONE[$row_newrel['group_id']])) { 
            //if ((!permission_exist("PACKAGE_READ",$row_newrel['package_id'] ))&&
            //    (!permission_exist("RELEASE_READ",$row_newrel['release_id'] ))) {
            if ($frspf->userCanRead($row_newrel['group_id'], $row_newrel['package_id'], 100) &&
                $frsrf->userCanRead($row_newrel['group_id'], $row_newrel['package_id'], $row_newrel['release_id'], 100)) {
                $return .= '('.  $hp->purify($row_newrel['release_version'], CODENDI_PURIFIER_CONVERT_HTML)  .')&nbsp;'.
                    '<A href="/projects/'. $row_newrel['unix_group_name'] .'/">'.
                     $hp->purify(util_unconvert_htmlspecialchars($row_newrel['group_name']), CODENDI_PURIFIER_CONVERT_HTML)  ."</A><BR>\n";
                
                $count++;
                $DONE[$row_newrel['group_id']] = true;
            }
        }
    }

    $return .= '<center><A href="/new/?func=releases">[ '.$Language->getText('include_features_boxes','more').' ]</A></center>';

    return $return;

}

function stats_getprojects_active() {
    $res_count = db_query("SELECT count(*) AS count FROM groups WHERE status='A' AND type='1'");
    if (db_numrows($res_count) > 0) {
        $row_count = db_fetch_array($res_count);
        return $row_count['count'];
    } else {
        return "error";
    }
}

function stats_getusers() {
    $res_count = db_query("SELECT count(*) AS count FROM user WHERE status='A' OR status='R'");
    if (db_numrows($res_count) > 0) {
        $row_count = db_fetch_array($res_count);
        return $row_count['count'];
    } else {
        return "error";
    }
}

function show_sitestats() {
    global $Language;
    $return  = "";
    $return .= $Language->getText('include_features_boxes','hosted_projects').': <B>'.number_format(stats_getprojects_active()).'</B>';
    $return .= '<BR>'.$Language->getText('include_features_boxes','registered_users').': <B>'.number_format(stats_getusers()).'</B>';
    return $return;
}

function show_newest_projects() {
    global $Language;
    $return  = "";
    $hp = Codendi_HTMLPurifier::instance();
    $start_time = strval(time()-(24*3600));
    $limit = 10;
    $sql = new_utils_get_new_projects ($start_time,0,$limit);
    $res_newproj = db_query( $sql );

    if (!$res_newproj || db_numrows($res_newproj) < 1) {
        return db_error();
    } else {
        while ( $row_newproj = db_fetch_array($res_newproj) ) {
            if ( $row_newproj['register_time'] ) {
                $return .= "(" . date("m/d",$row_newproj['register_time'])  . ") "
                    . '<A href="/projects/'. $row_newproj['unix_group_name'] .'/">'
                    .  $hp->purify(util_unconvert_htmlspecialchars($row_newproj['group_name']), CODENDI_PURIFIER_CONVERT_HTML)  ."</A><BR>\n";
            }
        }
        $return .= '<CENTER><A href="/new/?func=projects">[ '.$Language->getText('include_features_boxes','more').' ]</A></CENTER>';
    }
    return $return;
}

function show_highest_ranked_projects() {
    global $Language;
    $return  = "";
    $hp = Codendi_HTMLPurifier::instance();
    //don't take into account test projects and template projects
    $sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,
        project_weekly_metric.ranking,project_weekly_metric.percentile 
        FROM groups,project_weekly_metric 
        WHERE groups.group_id=project_weekly_metric.group_id AND 
        groups.access != '".db_es(Project::ACCESS_PRIVATE)."' AND groups.type=1 AND groups.status='A' AND groups.type=1
        ORDER BY ranking ASC LIMIT 20";
    $result=db_query($sql);
    if (!$result || db_numrows($result) < 1) {
        return db_error();
    } else {
    	$rank=1;
        while ($row=db_fetch_array($result)) {
            $return .= '<B>'.$rank.'. </B>'
                .' <A HREF="/projects/'.$row['unix_group_name'].
                '/">'. $hp->purify(util_unconvert_htmlspecialchars($row['group_name']), CODENDI_PURIFIER_CONVERT_HTML) .'</A><BR>';
            $rank++;
        }
    }
    return $return;
}

?>
