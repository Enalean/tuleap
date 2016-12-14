#!/usr/bin/perl
#
# SourceForge: Breaking Down the Barriers to Open Source Development
# Copyright 1999-2000 (c) The SourceForge Crew
# http:#sourceforge.net
#
# 

use DBI;
use Time::Local;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions

&db_connect;

$last_week= timegm( 0, 0, 0, (gmtime( time() - (86400 * 7) ))[3,4,5] );  
$last_day = strftime("%Y%m%d", gmtime($last_week) );
$this_week = timegm( 0, 0, 0, (gmtime(time()))[3,4,5] );
$this_day = strftime("%Y%m%d", gmtime($this_week) );

print "\nlast_week: $last_week $last_day ";
print "\n\nthis_week: $this_week $this_day\n";

$sql="DROP TABLE IF EXISTS project_counts_weekly_tmp";
$rel = $dbh->prepare($sql);
$rel->execute();


$sql="DROP TABLE IF EXISTS project_metric_weekly_tmp";
$rel = $dbh->prepare($sql);
$rel->execute();


$sql="DROP TABLE IF EXISTS project_metric_weekly_tmp1";
$rel = $dbh->prepare($sql);
$rel->execute();


#create a table to put the aggregates in
$sql="CREATE TABLE project_counts_weekly_tmp (group_id int,type text,count float(8,5))";
$rel = $dbh->prepare($sql);
$rel->execute();

#forum messages
$sql="INSERT INTO project_counts_weekly_tmp 
SELECT forum_group_list.group_id,'forum',log(3*count(forum.msg_id)) AS count 
FROM forum,forum_group_list 
WHERE forum.group_forum_id=forum_group_list.group_forum_id 
AND date > '$last_week' 
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


# artifacts in trackers
$sql="INSERT INTO project_counts_weekly_tmp 
SELECT group_id,'artifacts',log(5*sum(artifact_id)) as count 
FROM artifact, artifact_group_list 
WHERE artifact.group_artifact_id=artifact_group_list.group_artifact_id AND open_date > '$last_week' 
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();

#cvs commits
$sql="INSERT INTO project_counts_weekly_tmp 
SELECT group_id,'cvs',log(sum(cvs_commits_wk)) AS count 
FROM group_cvs_history 
GROUP BY group_id";

# LJ New SQL statement 
# LJ TODO: must compute month*100+day start and end value TODO
#$sql="INSERT INTO project_counts_weekly_tmp
#SELECT group_id,'cvs',log(sum(cvs_commits) + sum(cvs_adds)) AS count
#FROM stats_project
#WHERE ((month*100+day) >= 20001200 and (month*100+day) <= 20010104)
#GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


# svn low level accesses
$sql="INSERT INTO project_counts_weekly_tmp 
SELECT group_id,'svn',log(sum(svn_access_count)) AS count 
FROM group_svn_full_history 
WHERE ( day >= '$last_day' )
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


#developers
#$sql="INSERT INTO project_counts_weekly_tmp 
#SELECT group_id,'developers',log((5*count(*))) AS count FROM user_group GROUP BY group_id";
#$rel = $dbh->prepare($sql);
#$rel->execute();


#file releases
$sql="INSERT INTO project_counts_weekly_tmp 
select frs_package.group_id,'filereleases',log( 5 * COUNT(frs_release.release_id) ) 
FROM frs_release,frs_package
WHERE ( frs_package.package_id = frs_release.package_id AND frs_release.release_date > '$last_week' )
GROUP BY frs_package.group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


#file downloads
$sql="INSERT INTO project_counts_weekly_tmp 
SELECT group_id,'downloads',log(.3 * SUM(downloads)) 
FROM frs_dlstats_group_agg
WHERE ( day >= '$last_day' )
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


#news
$sql="INSERT INTO project_counts_tmp
SELECT group_id,'news',log(10*count(id)) AS count
FROM news_bytes
WHERE is_approved <> 4 AND date > '$last_week'
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


#wiki access
$sql="INSERT INTO project_counts_tmp
SELECT group_id, 'wiki', log(count(user_id)) AS count 
FROM wiki_log
WHERE time > '$last_week'
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();


#docman items (without directories - item_type = 1)
$sql="INSERT INTO project_counts_tmp
SELECT group_id, 'docman', log(5*count(item_id)) AS count
FROM plugin_docman_item
WHERE item_type <> 1 AND update_date > '$last_week'
GROUP BY group_id";

#print "\n\n".$sql;

$rel = $dbh->prepare($sql);
$rel->execute();



#create a new table to insert the final records into
$sql="CREATE TABLE project_metric_weekly_tmp1 (ranking int not null primary key auto_increment,
group_id int not null,
value float (8,5))";
$rel = $dbh->prepare($sql);
$rel->execute();


#insert the rows into the table in order, adding a sequential rank #

$sql="INSERT INTO project_metric_weekly_tmp1 (group_id,value) 
SELECT project_counts_weekly_tmp.group_id,(sum(project_counts_weekly_tmp.count)) AS value 
FROM project_counts_weekly_tmp
GROUP BY group_id ORDER BY value DESC";
$rel = $dbh->prepare($sql);
$rel->execute();


#numrows in the set
$sql="SELECT count(*) FROM project_metric_weekly_tmp1";
$rel = $dbh->prepare($sql);
$rel->execute();
($counts) = $rel->fetchrow();
#print "\n\nCounts: ".$counts;

#create a new table to insert the final records into
$sql="CREATE TABLE project_metric_weekly_tmp (ranking int not null primary key auto_increment,
percentile float(8,2), group_id int not null)";
$rel = $dbh->prepare($sql);
$rel->execute();

$sql="INSERT INTO project_metric_weekly_tmp (ranking,percentile,group_id)
SELECT ranking,(100-(100*((ranking-1)/$counts))),group_id 
FROM project_metric_weekly_tmp1 ORDER BY ranking ASC";
$rel = $dbh->prepare($sql);
$rel->execute();


#print "\n\nSQL: ".$sql;

#create an index
$sql="create index idx_project_metric_weekly_group on project_metric_weekly_tmp(group_id)";
$rel = $dbh->prepare($sql);
$rel->execute();


#drop the old metrics table
$sql="DROP TABLE IF EXISTS project_weekly_metric";
$rel = $dbh->prepare($sql);
$rel->execute();


#move the new ratings to the correct table name
$sql="alter table project_metric_weekly_tmp rename as project_weekly_metric";
$rel = $dbh->prepare($sql);
$rel->execute();
