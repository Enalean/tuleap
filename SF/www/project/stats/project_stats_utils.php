<?php

$Language->loadLanguageMsg('project/project');

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


function stats_get_sql_query($group_id) {
    $grp=project_get_object($group_id);
    $sql  = "SELECT month,week,day,COUNT(day),MAX(developers) as developers ,AVG(group_ranking),AVG(group_metric)";
    if ($grp->usesFile())
        $sql .= ", SUM(downloads)";
    if ($grp->usesHomePage())
        $sql .= ", SUM(site_views + subdomain_views)";
    if ($grp->usesForum())
        $sql .= ", SUM(msg_posted)";
    if ($grp->usesBugs())
        $sql .= ", SUM(bugs_opened),SUM(bugs_closed)";
    if ($grp->usesSupport())
        $sql .= ", SUM(support_opened),SUM(support_closed)";
    if ($grp->usesPm())
        $sql .= ", SUM(tasks_opened),SUM(tasks_closed)";
    if ($grp->usesTracker())
        $sql .= ", SUM(artifacts_opened),SUM(artifacts_closed)";
    if ($grp->usesPatch())
        $sql .= ", SUM(patches_opened),SUM(patches_closed)";
    if ($grp->usesCVS())
        $sql .= ", SUM(cvs_commits),SUM(cvs_adds)";
    if ($grp->usesSVN())
        $sql .= ", SUM(svn_access_count)";
    $sql .= "FROM stats_project ";
    return $sql;
}  

function stats_get_table_service_header($group_id) {
    global $Language;
    $grp=project_get_object($group_id);
    $text ='';
    if ($grp->usesHomePage())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','page_views').'</TD>';
    if ($grp->usesFile())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','downloads').'</TD>';
    if ($grp->usesBugs())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','bugs').'</TD>';
    if ($grp->usesSupport())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','support').'</TD>';
    if ($grp->usesPm())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','tasks').'</TD>';
    if ($grp->usesTracker())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','artifacts').'</TD>';
    if ($grp->usesPatch())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','patches').'</TD>';
    if ($grp->usesCVS())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','cvs').'</TD>';
    if ($grp->usesSVN())
        $text .=  '<TD class="boxtitle">'.$Language->getText('project_stats_index','svn').'</TD>';
    return $text; 
}

function stats_get_table_service_rows($group_id,$row) {
    $grp=project_get_object($group_id);
    $text ='';
    if ($grp->usesHomePage())
        $text .= '<TD align="center">' . number_format( $row["SUM(site_views + subdomain_views)"] ) . '</TD>';
    if ($grp->usesFile())
        $text .= '<TD align="center">' . number_format( $row["SUM(downloads)"] ) . '</TD>';
    if ($grp->usesBugs())
        $text .= '<TD align="center">' . $row["SUM(bugs_opened)"] . " ( " . $row["SUM(bugs_closed)"] . ' )</TD>';
    if ($grp->usesSupport())
        $text .= '<TD align="center">' . $row["SUM(support_opened)"] . " ( " . $row["SUM(support_closed)"] . ' )</TD>';
    if ($grp->usesPm())
        $text .= '<TD align="center">' . $row["SUM(tasks_opened)"] . " ( " . $row["SUM(tasks_closed)"] . ' )</TD>';
    if ($grp->usesTracker())
        $text .= '<TD align="center">' . $row["SUM(artifacts_opened)"] . " ( " . $row["SUM(artifacts_closed)"] . ' )</TD>';
    if ($grp->usesPatch())
        $text .= '<TD align="center">' . $row["SUM(patches_opened)"] . " ( " . $row["SUM(patches_closed)"] . ' )</TD>';
    if ($grp->usesCVS())
        $text .= '<TD align="center">' . $row["SUM(cvs_commits)"] . '</TD>';
    if ($grp->usesSVN())
        $text .= '<TD align="center">' . $row["SUM(svn_access_count)"] . '</TD>';
    return $text;
}  

// stats_project_daily
function stats_project_daily( $group_id, $span = 7 ) {
    global $Language;

    if (! $span ) { 
        $span = 7;
    }
    
    $grp=project_get_object($group_id);
    
    // Get information about the date $span days ago 
    $begin_date = localtime( (time() - ($span * 86400)), 1);
    $year = $begin_date["tm_year"] + 1900;
    $month = sprintf("%02d", $begin_date["tm_mon"] + 1);
    $day = $begin_date["tm_mday"];
    
    $sql  = stats_get_sql_query($group_id);
    $sql .= "WHERE ( (( month = " . $year . $month . " AND day >= " . $day . " ) OR ";
    $sql .= "( month > " . $year . $month . " )) AND group_id = " . $group_id . " ) ";
    $sql .= "GROUP BY month,day ORDER BY month DESC, day DESC";

    // Executions will continue until morale improves.
    $res = db_query( $sql);

    // if there are any days, we have valid data.
    if ( ($valid_days = db_numrows( $res )) > 1 ) {
        
        print '<P><B>'.$Language->getText('project_stats_index','stats_for_past_x_days',$valid_days).'</B></P>';

        print	'<P><TABLE width="100%" cellpadding=2 cellspacing=1 border=0>'
            . '<TR class="boxtable">'
            . '<TD class="boxtitle">'.$Language->getText('project_admin_utils','date').'</TD>'
            . '<TD class="boxtitle">'.$Language->getText('project_stats_index','rank').'</TD>';
        print stats_get_table_service_header($group_id);
        print '</TR>' . "\n";
        while ( $row = db_fetch_array($res) ) {
            print	'<TR class="' . util_get_alt_row_color($i++) . '">'
                . '<TD align="center">' . gmstrftime("%e %b %Y", gmmktime(0,0,0,substr($row["month"],4,2),$row["day"],substr($row["month"],0,4)) ) . '</TD>'
                //. '<TD>' . $row["month"] . " " . $row["day"] . '</TD>'
                . '<TD align="center">' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>';
            print stats_get_table_service_rows($group_id,$row);
            print '</TR>' . "\n";
        }
        
        print '</TABLE>';
        
    } else {
        echo $Language->getText('project_stats_index','proj_not_exist_on_date');
    }
    
}


// stats_project_weekly
function stats_project_weekly( $group_id, $span = 8 ) {
    global $Language;

    $grp=project_get_object($group_id);

    if (! $span ) { 
        $span = 8;
    }

    // Get information about the date $span weeks ago 
    $begin_date = localtime( (time() - ($span * 7 * 86400)), 1);
    $week = gmstrftime("%U", (time() - ($span * 7 * 86400)) );
    $year = $begin_date["tm_year"] + 1900;
    $month = sprintf("%02d", $begin_date["tm_mon"] + 1);
    $day = $begin_date["tm_mday"];

    $sql  = stats_get_sql_query($group_id);
    $sql .= "WHERE ( (( month > " . $year . "00 AND week > " . $week . " ) OR ( month > " . $year . $month . "))";
    $sql .= "AND group_id = " . $group_id . " ) ";
    $sql .= "GROUP BY week ORDER BY month DESC, week DESC";

    // Executions will continue until morale improves.
    $res = db_query( $sql );

    // if there are any weeks, we have valid data.
    if ( ($valid_weeks = db_numrows( $res )) > 1 ) {

        print '<P><B>'.$Language->getText('project_stats_index','stats_for_past_x_weeks',($valid_weeks - 1));

        print	'<P><TABLE width="100%" cellpadding=2 cellspacing=1 border=0>'
            . '<TR class="boxtable">'
            . '<TD class="boxtitle">'.$Language->getText('project_stats_index','week').'</TD>'
            . '<TD class="boxtitle">'.$Language->getText('project_stats_index','rank').'</TD>';
        print stats_get_table_service_header($group_id);
        print '</TR>' . "\n";

        $today = time();

        while ( $row = db_fetch_array($res) ) {
            $w_begin = $w_end = 0;
            list($w_begin, $w_end) = week_to_dates($row["week"]);
            //if ( $w_end > $today ) {
            //	$w_end = $today;
            //}

            print	'<TR class="' . util_get_alt_row_color($i++) . '">'
                . '<TD align="center">' . $row["week"] . "&nbsp;(" . gmstrftime("%D", $w_begin) . " -> " . strftime("%D", $w_end) . ') </TD>'
                . '<TD align="center">' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>';
            print stats_get_table_service_rows($group_id,$row);
            print '</TR>' . "\n";
        }

        print '</TABLE>';

    } else {
        echo $Language->getText('project_stats_index','proj_not_exist_on_date');
    }

}


// stats_project_monthly
function stats_project_monthly( $group_id, $span = 4 ) {
    global $Language;

    $grp=project_get_object($group_id);

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

    $sql  = stats_get_sql_query($group_id);
    $sql .= "WHERE ( month > " . $year . sprintf("%02d", $month) . " AND group_id = " . $group_id . " ) ";
    $sql .= "GROUP BY month ORDER BY month DESC";

    // Executions will continue until morale improves.
    $res = db_query( $sql );

    // if there are any weeks, we have valid data.
    if ( ($valid_months = db_numrows( $res )) > 1 ) {

        print '<P><B>'.$Language->getText('project_stats_index','stats_for_past_x_months',$valid_months).'</B></P>';

        print	'<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>'
            . '<TR class="boxtable">'
            . '<TD class="boxtitle">'.$Language->getText('project_stats_index','month').'</TD>'
            . '<TD class="boxtitle">'.$Language->getText('project_stats_index','rank').'</TD>';
        print stats_get_table_service_header($group_id);
        print '</TR>' . "\n";

        while ( $row = db_fetch_array($res) ) {
            print	'<TR class="' . util_get_alt_row_color($i++) . '">'
                . '<TD align="center">' . gmstrftime("%B %Y", mktime(0,0,1,substr($row["month"],4,2),1,substr($row["month"],0,4)) ) . '</TD>'
                . '<TD align="center">' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>';
            print stats_get_table_service_rows($group_id,$row);
            print '</TR>' . "\n";
        }

        print '</TABLE>';

    } else {
        echo $Language->getText('project_stats_index','proj_not_exist_on_date');
    }
}


// stats_site_alltime
function stats_site_agregate( $group_id ) {
    global $Language;

    $grp=project_get_object($group_id);

    $sql  = stats_get_sql_query($group_id);
    $sql .= "WHERE group_id = " . $group_id . " ";
    $sql .= "GROUP BY group_id ";
    $sql .= "ORDER BY month DESC, day DESC";

    // Executions will continue until morale improves.
    $res = db_query( $sql );
    $row = db_fetch_array($res);

    print '<P><B>'.$Language->getText('project_stats_index','stats_for_all_time').'</B></P>';
    
    print '<P><TABLE width="100%" cellpadding=2 cellspacing=1 border=0>'
        . '<TR class="boxtable">'
        . '<TD class="boxtitle">'.$Language->getText('project_stats_index','lifespan').'</TD>'
        . '<TD class="boxtitle">'.$Language->getText('project_stats_index','rank').'</TD>'
        . '<TD class="boxtitle">'.$Language->getText('project_stats_index','developers').'</TD>';
        print stats_get_table_service_header($group_id);
        print '</TR>' . "\n";

    print	'<TR class="' . util_get_alt_row_color(0) . '">'
        . '<TD align="center">' . $row["COUNT(day)"] . ' '.$Language->getText('project_stats_index','ddays').' </TD>'
        . '<TD align="center">' . sprintf("%d", $row["AVG(group_ranking)"]) . " ( " . sprintf("%0.2f", $row["AVG(group_metric)"]) . ' ) </TD>'
        . '<TD align="center">' . $row["developers"] . '</TD>';
    print stats_get_table_service_rows($group_id,$row);
    print '</TR>' . "\n";

    print '</TABLE>';
}


?>
