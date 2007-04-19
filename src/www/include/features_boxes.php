<?php
  //
  // SourceForge: Breaking Down the Barriers to Open Source Development
  // Copyright 1999-2000 (c) The SourceForge Crew
  // http://sourceforge.net
  //
  // $Id: features_boxes.php 3567 2006-08-25 14:11:17Z schneide $

require_once('www/project/admin/permissions.php');
require_once('www/new/new_utils.php');
require_once('www/stats/site_stats_utils.php');

function show_features_boxes() {
    GLOBAL $HTML,$Language;
    $return  = "";
    $return .= $HTML->box1_top($GLOBALS['sys_name'].' '.$Language->getText('include_features_boxes','stats'),0);
    $return .= show_sitestats();
    $return .= $HTML->box1_middle($Language->getText('include_features_boxes','top_download_yesterday'));
    $return .= show_top_downloads();
    $return .= $HTML->box1_middle($Language->getText('include_features_boxes','newest_releases').' <A href="/export/rss_sfnewreleases.php" title="'.$Language->getText('include_features_boxes','newest_releases').' '.$Language->getText('include_features_boxes','rss_format').'">['.$Language->getText('include_features_boxes','xml').']</A>');
    $return .= show_newest_releases();
    $return .= $HTML->box1_middle($Language->getText('include_features_boxes','newest_projects').' <A href="/export/rss_sfprojects.php?type=rss&option=newest" title="'.$Language->getText('include_features_boxes','newest_projects').' '.$Language->getText('include_features_boxes','rss_format').'">['.$Language->getText('include_features_boxes','xml').']</A>');
    $return .= show_newest_projects();
    $return .= $HTML->box1_middle($Language->getText('include_features_boxes','most_active_week'));
    $return .= show_highest_ranked_projects();
    $return .= $HTML->box1_bottom(0);
    return $return;
}

function foundry_features_boxes($group_id) {
    GLOBAL $HTML,$Language;
    $return  = "";
    $comma_sep_groups=$GLOBALS['foundry']->getProjectsCommaSep();

    $return .= $HTML->box1_top($Language->getText('include_features_boxes','most_active'),0);
    $return .= foundry_active_projects($comma_sep_groups);
    $return .= $HTML->box1_middle($Language->getText('include_features_boxes','top_downloads'));
    $return .= foundry_top_downloads($comma_sep_groups);
    $return .= $HTML->box1_middle($Language->getText('include_features_boxes','featured_projects'));
    $return .= foundry_featured_projects($group_id);
    $return .= $HTML->box1_bottom(0);
    return $return;
}

function foundry_active_projects($comma_sep_groups) {
    $return  = "";
    $sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,".
        "project_weekly_metric.ranking,project_weekly_metric.percentile ".
        "FROM groups,project_weekly_metric ".
        "WHERE groups.group_id=project_weekly_metric.group_id AND ".
        "groups.is_public=1 AND groups.type=1 ".
        "AND project_weekly_metric.group_id IN ($comma_sep_groups) ".
        "ORDER BY ranking ASC LIMIT 20";
    $result=db_query($sql);
    if (!$result || db_numrows($result) < 1) {
        return '';//db_error();
    } else {
        while ($row=db_fetch_array($result)) {
            $return .= '<B>( '.$row['percentile'].'% )</B>'
                .' <A HREF="/projects/'.$row['unix_group_name'].
                '/">'.$row['group_name'].'</A><BR>';
        }
        $return .= '<CENTER><A href="/top/mostactive.php?type=week">[ More ]</A></CENTER>';
    }
    return $return;
}

function foundry_featured_projects($group_id) {
    global $Language;
    $return  = "";
    $sql="SELECT groups.group_name,groups.unix_group_name,".
        "groups.group_id,foundry_preferred_projects.rank ".
        "FROM groups,foundry_preferred_projects ".
        "WHERE foundry_preferred_projects.group_id=groups.group_id ".
        "AND foundry_preferred_projects.foundry_id='$group_id' ".
        "ORDER BY rank ASC";

    $res_grp=db_query($sql);
    $rows=db_numrows($res_grp);

    if (!$res_grp || $rows < 1) {
        $return .= $Language->getText('include_features_boxes','no_projects');
        //		$return .= db_error();
    } else {
        for ($i=0; $i<$rows; $i++) {
            $return .= '<A href="/projects/'. 
                strtolower(db_result($res_grp,$i,'unix_group_name')) .'/">'. 
                db_result($res_grp,$i,'group_name') .'</A><BR>';
        }
    }
    return $return;
}

function foundry_top_downloads($comma_sep_groups) {
    global $Language;
    $return  = "";
    $return .= "<B>".$Language->getText('include_features_boxes','downloads_yesterday').":</B>\n";
	
#get yesterdays day
    $yesterday = date("Ymd",time()-(3600*24));
	
    $res_topdown = db_query("SELECT groups.group_id,"
                            ."groups.group_name,"
                            ."groups.unix_group_name,"
                            ."frs_dlstats_group_agg.downloads "
                            ."FROM frs_dlstats_group_agg,groups WHERE day='$yesterday' "
                            ."AND frs_dlstats_group_agg.group_id=groups.group_id "
                            ."AND frs_dlstats_group_agg.group_id IN ($comma_sep_groups) "
                            ."AND groups.type=1 "
                            ."ORDER BY downloads DESC LIMIT 10");

    if (!$res_topdown || db_numrows($res_topdown) < 1) {
        //return db_error();
        return ""; 
    } else {
        // print each one
        while ($row_topdown = db_fetch_array($res_topdown)) {
            if ($row_topdown['downloads'] > 0) 
                $return .= "<BR><A href=\"/projects/$row_topdown[unix_group_name]/\">"
                    . "$row_topdown[group_name]</A> ($row_topdown[downloads])\n";
        }
    }
    //$return .= '<P align="center"><A href="/top/">[ '.$Language->getText('include_features_boxes','more').' ]</A>';
	
    return $return; 

}

function show_top_downloads() {
    global $Language;
    $return  = "";
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
                            ."AND filedownload_log.time > $start_time "
                            ."AND filedownload_log.time < $end_time "
                            ."GROUP BY groups.group_id "
                            ."ORDER BY downloads DESC LIMIT 10");
	
    // print each one
    while ($row_topdown = db_fetch_array($res_topdown)) {
        if ($row_topdown['downloads'] > 0) 
            $return .= "($row_topdown[downloads]) <A href=\"/projects/$row_topdown[unix_group_name]/\">"
                . "$row_topdown[group_name]</A><BR>\n";
    }
    $return .= '<center><A href="/top/">[ '.$Language->getText('include_features_boxes','more').' ]</A></center>';
	
    return $return;

}

function show_newest_releases() {
    global $Language;
    $return  = "";
    // Fetch releases that are no more than 3 months old
    $start_time = strval(time() - 3*30*24*3600);
    $query	= new_utils_get_new_releases_short($start_time);

    $res_newrel = db_query($query);

    // print each one but only show one release per project
    $count = 0;
    $DONE  = array();
    while ( ($row_newrel = db_fetch_array($res_newrel)) && ($count < 10)) {
	
	if ( !isset($DONE[$row_newrel['group_id']])) { 
	    if ((!permission_exist("PACKAGE_READ",$row_newrel['package_id'] ))&&
		(!permission_exist("RELEASE_READ",$row_newrel['release_id'] ))) {
                $return .= "($row_newrel[release_version])&nbsp;".
                    "<A href=\"/projects/$row_newrel[unix_group_name]/\">".
                    "$row_newrel[group_name]</A><BR>\n";
                
                $count++;
                $DONE[$row_newrel['group_id']] = true;
            }
        }
    }

    $return .= '<center><A href="/new/?func=releases">[ '.$Language->getText('include_features_boxes','more').' ]</A></center>';
	
    return $return;

}



function stats_getpageviews_total() {
    $res_count = db_query("SELECT SUM(site_views) AS site, SUM(subdomain_views) AS subdomain FROM stats_site");
    if (db_numrows($res_count) > 0) {
        $row_count = db_fetch_array($res_count);
        return ($row_count['site'] + $row_count['subdomain']);
    } else {
        return "error";
    }
}

function stats_downloads_total() {
    $res_count = db_query("SELECT SUM(downloads) AS downloads FROM stats_site");
    if (db_numrows($res_count) > 0) {
        $row_count = db_fetch_array($res_count);
        return $row_count['downloads'];
    } else {
        return "error";
    }
}

function show_sitestats() {
    global $Language;
    $return  = "";
    $return .= $Language->getText('include_features_boxes','hosted_projects').': <B>'.number_format(stats_getprojects_active()).'</B>';
    $return .= '<BR>'.$Language->getText('include_features_boxes','registered_users').': <B>'.number_format(stats_getusers()).'</B>';
    $return .= '<BR>'.$Language->getText('include_features_boxes','files_download').': <B>'.number_format(stats_downloads_total()).'</B>';
    $return .= '<BR>'.$Language->getText('include_features_boxes','pages_viewed').': <B>'.number_format(stats_getpageviews_total()).'</B>&nbsp;';
    return $return;
}

function show_newest_projects() {
    global $Language;
    $return  = "";
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
                    . "<A href=\"/projects/$row_newproj[unix_group_name]/\">"
                    . "$row_newproj[group_name]</A><BR>\n";
            }
        }
        $return .= '<CENTER><A href="/new/?func=projects">[ '.$Language->getText('include_features_boxes','more').' ]</A></CENTER>';
    }
    return $return;
}

function show_highest_ranked_projects() {
    global $Language;
    $return  = "";
    //don't take into account test projects and template projects
    $sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,".
        "project_weekly_metric.ranking,project_weekly_metric.percentile ".
        "FROM groups,project_weekly_metric ".
        "WHERE groups.group_id=project_weekly_metric.group_id AND ".
        "groups.is_public=1 AND groups.type=1 AND groups.status='A' AND groups.type=1 ".
        "ORDER BY ranking ASC LIMIT 20";
    $result=db_query($sql);
    if (!$result || db_numrows($result) < 1) {
        return db_error();
    } else {
        while ($row=db_fetch_array($result)) {
            $return .= '<B>( '.$row['percentile'].'% )</B>'
                .' <A HREF="/projects/'.$row['unix_group_name'].
                '/">'.$row['group_name'].'</A><BR>';
        }
        $return .= '<CENTER><A href="/top/mostactive.php?type=week">[ '.$Language->getText('include_features_boxes','more').' ]</A></CENTER>';
    }
    return $return;
}

?>
