<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


function show_features_boxes() {
	GLOBAL $HTML;
	$return .= $HTML->box1_top('CodeX Statistics',0);
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= show_sitestats();
	$return .= '</font>';
	$return .= $HTML->box1_middle('Top Project Downloads');
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= show_top_downloads();
	$return .= '</font>';
	$return .= $HTML->box1_middle('Newest Projects');
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= show_newest_projects();
	$return .= '</font>';
	$return .= $HTML->box1_middle('Most Active This Week');
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= show_highest_ranked_projects();
	$return .= '</font>';
	$return .= $HTML->box1_bottom(0);
	return $return;
}

function foundry_features_boxes($group_id) {
	GLOBAL $HTML;
	$comma_sep_groups=$GLOBALS['foundry']->getProjectsCommaSep();

	$return .= $HTML->box1_top('Most Active',0);
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= foundry_active_projects($comma_sep_groups);
	$return .= '</font>';
	$return .= $HTML->box1_middle('Top Downloads');
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= foundry_top_downloads($comma_sep_groups);
	$return .= '</font>';
	$return .= $HTML->box1_middle('Featured Projects');
	$return .= '<font face="arial, helvetica" size="2">';
	$return .= foundry_featured_projects($group_id);
	$return .= '</font>';
	$return .= $HTML->box1_bottom(0);
	return $return;
}

function foundry_active_projects($comma_sep_groups) {
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
		$return .= '<BR><CENTER><A href="/top/mostactive.php?type=week">[ More ]</A></CENTER>';
	}
	return $return;
}

function foundry_featured_projects($group_id) {
	$sql="SELECT groups.group_name,groups.unix_group_name,".
		"groups.group_id,foundry_preferred_projects.rank ".
		"FROM groups,foundry_preferred_projects ".
		"WHERE foundry_preferred_projects.group_id=groups.group_id ".
		"AND foundry_preferred_projects.foundry_id='$group_id' ".
		"ORDER BY rank ASC";

	$res_grp=db_query($sql);
	$rows=db_numrows($res_grp);

	if (!$res_grp || $rows < 1) {
		$return .= 'No Projects';
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

	$return .= "<B>Downloads Yesterday:</B>\n";
	
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
	//$return .= '<P align="center"><A href="/top/">[ More ]</A>';
	
	return $return; 

}

function show_top_downloads() {
	$return .= "<B>Downloads Yesterday:</B>\n";	

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
			$return .= "<BR>($row_topdown[downloads]) <A href=\"/projects/$row_topdown[unix_group_name]/\">"
			. "$row_topdown[group_name]</A>\n";
	}
	$return .= '<P align="center"><A href="/top/">[ More ]</A>';
	
	return $return;

}


function stats_getprojects_active() {
	$res_count = db_query("SELECT count(*) AS count FROM groups WHERE status='A'");
	if (db_numrows($res_count) > 0) {
		$row_count = db_fetch_array($res_count);
		return $row_count['count'];
	} else {
		return "error";
	}
}

function stats_getprojects_total() {
	$res_count = db_query("SELECT count(*) AS count FROM groups WHERE status='A' OR status='H'");
	if (db_numrows($res_count) > 0) {
		$row_count = db_fetch_array($res_count);
		return $row_count['count'];
	} else {
		return "error";
	}
}

function stats_getusers() {
	$res_count = db_query("SELECT count(*) AS count FROM user WHERE status='A'");
	if (db_numrows($res_count) > 0) {
		$row_count = db_fetch_array($res_count);
		return $row_count['count'];
	} else {
		return "error";
	}
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
	$return .= 'Hosted Projects: <B>'.number_format(stats_getprojects_active()).'</B>';
	$return .= '<BR>Registered Users: <B>'.number_format(stats_getusers()).'</B>';
	$return .= '<BR>Files Downloaded: <B>'.number_format(stats_downloads_total()).'</B>';
	$return .= '<BR>Pages Viewed: <B>'.number_format(stats_getpageviews_total()).'</B><BR>&nbsp;';
	return $return;
}

function show_newest_projects() {
	$sql =	"SELECT group_id,unix_group_name,group_name,register_time FROM groups " .
		"WHERE is_public=1 AND status='A' AND type=1 " .
		"AND register_time < " . strval(time()-(24*3600)) . " " . 
		"ORDER BY register_time DESC LIMIT 10";
	$res_newproj = db_query( $sql );

	if (!$res_newproj || db_numrows($res_newproj) < 1) {
		return db_error();
	} else {
		while ( $row_newproj = db_fetch_array($res_newproj) ) {
			if ( $row_newproj['register_time'] ) {
				$return .= "(" . date("m/d",$row_newproj['register_time'])  . ") "
				. "<A href=\"/projects/$row_newproj[unix_group_name]/\">"
				. "$row_newproj[group_name]</A><BR>";
			}
		}
		$return .= '<BR><CENTER><A href="/new/">[ More ]</A></CENTER>';
	}
	return $return;
}

function show_highest_ranked_projects() {
	$sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,".
		"project_weekly_metric.ranking,project_weekly_metric.percentile ".
		"FROM groups,project_weekly_metric ".
		"WHERE groups.group_id=project_weekly_metric.group_id AND ".
		"groups.is_public=1 AND groups.type=1 ".
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
		$return .= '<BR><CENTER><A href="/top/mostactive.php?type=week">[ More ]</A></CENTER>';
	}
	return $return;
}

?>
