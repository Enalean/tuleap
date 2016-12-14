#!/usr/bin/perl
#
# 
#
# use strict;
use DBI;
use Time::Local;
use POSIX qw( strftime );

require("../include.pl");
&db_connect();

my ($sql, $rel);
my ($day_begin, $day_end, $mday, $year, $mon, $week, $day);
my $verbose = 1;

##
## Set begin and end times (in epoch seconds) of day to be run
## Either specified on the command line, or auto-calculated
## to run yesterday's data.
##
if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {

	$day_begin = timegm( 0, 0, 0, $ARGV[2], $ARGV[1] - 1, $ARGV[0] - 1900 );
	$day_end = timegm( 0, 0, 0, (gmtime( $day_begin + 86400 ))[3,4,5] );

} else {

	   ## Start at midnight last night.
	$day_end = timegm( 0, 0, 0, (gmtime( time() ))[3,4,5] );
	   ## go until midnight yesterday.
	$day_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );

	print "$day_begin $day_end \n";
}

   ## Preformat the important date strings.
$year	= strftime("%Y", gmtime( $day_begin ) );
$mon	= strftime("%m", gmtime( $day_begin ) );
$week	= strftime("%U", gmtime( $day_begin ) );    ## GNU ext.
$day	= strftime("%d", gmtime( $day_begin ) );
print "Running week $week, day $day month $mon year $year \n" if $verbose;



##
## Now we're going to pull in every column...
##

## group_ranking
$sql = "INSERT INTO stats_project_build_tmp 
	SELECT group_id,'group_ranking',ranking 
	FROM project_metric";
$rel = $dbh->prepare($sql)->execute();
print "Inserted group_ranking from project_metric...\n" if $verbose;

## group_metric
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'group_metric',percentile 
	FROM project_metric";
$rel = $dbh->prepare($sql)->execute();
print "Inserted percentile from project_metric...\n" if $verbose;

## developers
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'developers',COUNT(user_id) 
	FROM user_group 
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Inserted developers from user_group...\n" if $verbose;

## file_releases
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'file_releases',COUNT(release_id) 
	FROM frs_release,frs_package
	WHERE ( frs_release.release_date > $day_begin AND frs_release.release_date < $day_end 
		AND frs_release.package_id = frs_package.package_id )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert file_releases from frs_release,frs_package...\n" if $verbose;

## downloads
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'downloads',downloads
	FROM frs_dlstats_group_agg 
	WHERE ( day = '$year$mon$day' )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert downloads from frs_dlstats_group_agg...\n" if $verbose;

if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {
	## register_time
	$sql = "INSERT INTO stats_project_build_tmp
		SELECT group_id,'register_time',register_time 
		FROM groups
		GROUP BY group_id";
	$rel = $dbh->prepare($sql)->execute();
	print "Insert register_time from groups...\n" if $verbose;

} 

print "Postponed: subdomain_views need to be inserted later from the project server logs...\n" if $verbose;

## msg_posted
$sql = "INSERT INTO stats_project_build_tmp
	SELECT forum_group_list.group_id,'msg_posted',COUNT(forum.msg_id)
	FROM forum_group_list, forum
	WHERE ( forum_group_list.group_forum_id = forum.group_forum_id 
		AND forum.date > $day_begin AND forum.date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert msg_posted from forum_group_list and forum...\n" if $verbose;

## msg_uniq_auth
$sql = "INSERT INTO stats_project_build_tmp
 	SELECT forum_group_list.group_id,'msg_uniq_auth',COUNT( DISTINCT(forum.posted_by) )
 	FROM forum_group_list, forum
 	WHERE ( forum_group_list.group_forum_id = forum.group_forum_id 
		AND forum.date > $day_begin AND forum.date < $day_end )
 	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert msg_uniq_auth from forum_group_list and forum...\n" if $verbose;

## bugs_opened
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'bugs_opened',COUNT(bug_id) 
	FROM bug
	WHERE ( date > $day_begin AND date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert bugs_opened from bug...\n" if $verbose;

## bugs_closed
$sql = "INSERT INTO stats_project_build_tmp 
	SELECT group_id,'bugs_closed',COUNT(bug_id) 
	FROM bug
	WHERE ( close_date > $day_begin AND close_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert bugs_closed from bug...\n" if $verbose;

## support_opened
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'support_opened',COUNT(support_id) 
	FROM support
	WHERE ( open_date > $day_begin AND open_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert support_opened from support...\n" if $verbose;

## support_closed
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'support_closed',COUNT(support_id) 
	FROM support
	WHERE ( close_date > $day_begin AND close_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert support_closed from support...\n" if $verbose;

## patches_opened
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'patches_opened',COUNT(patch_id) 
	FROM patch
	WHERE ( open_date > $day_begin AND open_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert patches_opened from patch...\n" if $verbose;

## patches_closed
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_id,'patches_closed',COUNT(patch_id) 
	FROM patch
	WHERE ( close_date > $day_begin AND close_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert patches_closed from patch...\n" if $verbose;

## tasks_opened
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_project_id as group_id,'tasks_opened',
		COUNT(project_task_id) 
	FROM project_task
	WHERE ( start_date > $day_begin AND start_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert tasks_opened from project_task...\n" if $verbose;

## tasks_closed
$sql = "INSERT INTO stats_project_build_tmp
	SELECT group_project_id as group_id,'tasks_closed',
		COUNT(project_task_id) 
	FROM project_task
	WHERE ( end_date > $day_begin AND end_date < $day_end )
	GROUP BY group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert tasks_closed from project_task...\n" if $verbose;

## artifacts_opened
$sql = "INSERT INTO stats_project_build_tmp
	SELECT artifact_group_list.group_id,'artifacts_opened',
		COUNT(artifact.artifact_id) 
	FROM artifact_group_list, artifact
	WHERE ( open_date > $day_begin AND open_date < $day_end AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
	GROUP BY artifact_group_list.group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert artifacts_opened from project_task...\n" if $verbose;

## artifacts_closed
$sql = "INSERT INTO stats_project_build_tmp
	SELECT artifact_group_list.group_id,'artifacts_closed',
		COUNT(artifact.artifact_id) 
	FROM artifact_group_list, artifact
	WHERE ( close_date > $day_begin AND close_date < $day_end AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
	GROUP BY artifact_group_list.group_id";
$rel = $dbh->prepare($sql)->execute();
print "Insert artifacts_closed from project_task...\n" if $verbose;

##
## Create the daily tmp table for the update.
##
$sql="DROP TABLE IF EXISTS stats_project_tmp";
$rel = $dbh->prepare($sql)->execute();
print "Dropping stats_project_tmp in preparation...\n" if $verbose;

$sql = "CREATE TABLE stats_project_tmp ( 
        month           int(11) DEFAULT '0' NOT NULL,
	week		int(11)	DEFAULT '0' NOT NULL,
        day             int(11) DEFAULT '0' NOT NULL,
        group_id        int(11) DEFAULT '0' NOT NULL,
        group_ranking   int(11) DEFAULT '0' NOT NULL,
        group_metric    float(8,5) DEFAULT '0' NOT NULL,
        developers      smallint(6) DEFAULT '0' NOT NULL,
        file_releases   smallint(6) DEFAULT '0' NOT NULL,
        downloads       int(11) DEFAULT '0' NOT NULL,
        site_views      int(11) DEFAULT '0' NOT NULL,
        subdomain_views int(11) DEFAULT '0' NOT NULL,
        msg_posted      smallint(6) DEFAULT '0' NOT NULL,
        msg_uniq_auth   smallint(6) DEFAULT '0' NOT NULL,
        bugs_opened     smallint(6) DEFAULT '0' NOT NULL,
        bugs_closed     smallint(6) DEFAULT '0' NOT NULL,
        support_opened  smallint(6) DEFAULT '0' NOT NULL,
        support_closed  smallint(6) DEFAULT '0' NOT NULL,
        patches_opened  smallint(6) DEFAULT '0' NOT NULL,
        patches_closed  smallint(6) DEFAULT '0' NOT NULL,
        tasks_opened    smallint(6) DEFAULT '0' NOT NULL,
        tasks_closed    smallint(6) DEFAULT '0' NOT NULL,
        cvs_checkouts   smallint(6) DEFAULT '0' NOT NULL,
        cvs_commits     smallint(6) DEFAULT '0' NOT NULL,
        cvs_adds        smallint(6) DEFAULT '0' NOT NULL,
        svn_commits     smallint(6) DEFAULT '0' NOT NULL,
        svn_adds        smallint(6) DEFAULT '0' NOT NULL,
        svn_deletes   smallint(6) DEFAULT '0' NOT NULL,
        svn_checkouts   smallint(6) DEFAULT '0' NOT NULL,
        svn_access_count       smallint(6) DEFAULT '0' NOT NULL,
        artifacts_opened     smallint(6) DEFAULT '0' NOT NULL,
        artifacts_closed     smallint(6) DEFAULT '0' NOT NULL,
        KEY idx_project_log_group (group_id)
)";
$rel = $dbh->prepare($sql)->execute();
print "Created stats_project_tmp for agregation...\n" if $verbose;

##
## Populate the stats_archive_project_tmp table the old
## fashioned way. (It's cleaner/faster than making the 3! tmp tables
## needed to merge the stats_project_build_tmp into the 
## stats_archive_project_tmp with MySQL.. if you can
## believe that.)
##

my (%stat_data, $group_id, $column, $value, @ar);

$sql = "SELECT DISTINCT group_id FROM stats_project_build_tmp";
$rel = $dbh->prepare($sql);
$rel->execute() or die "db_archive_stats_update.pl: Failed to run agregates.\n";

while ( @ar = $rel->fetchrow_array ) {
	$group_id = $ar[0];
	$stat_data{$group_id} = {};
	$stat_data{$group_id}{"month"} = "$year$mon";
	$stat_data{$group_id}{"week"} = $week;
	$stat_data{$group_id}{"day"} = $day;
}
print "Begining collation of " . $rel->rows . " project records..." if $verbose;
$rel->finish();


foreach $group_id ( keys %stat_data ) {
	
	$sql = "SELECT * FROM stats_project_build_tmp WHERE group_id=$group_id";
	$rel = $dbh->prepare($sql);
	$rel->execute();
	while ( ($column, $value) = ($rel->fetchrow_array)[1,2] ) {
		$stat_data{$group_id}{$column} = $value;
	}
	$rel->finish();

	if ( $stat_data{$group_id}{"register_time"} < $day_end ) {

		delete $stat_data{$group_id}{"register_time"};
		$sql  = "INSERT INTO stats_project_tmp SET ";
		$sql .= "group_id=$group_id,";
		$sql .= join( ",",  
			map { "$_\=\'$stat_data{$group_id}{$_}\'" } (keys %{$stat_data{$group_id}}) 
			);
		$rel = $dbh->prepare($sql);
		$rel->execute();
	}
}
print "Finished.\n" if $verbose;


##
## Drop the tmp table.
##
$sql = "DROP TABLE IF EXISTS stats_project_build_tmp";
$rel = $dbh->prepare($sql)->execute();
print "Dropped stats_project_build_tmp...\n" if $verbose;


##
## Build the rest of the indexes on the temp table before we merge
## back into the live table. (to reduce locking time on live table)
##

$sql = "CREATE INDEX idx_project_stats_day 
	on stats_project_tmp(day)";
$rel = $dbh->prepare($sql)->execute();

$sql = "CREATE INDEX idx_project_stats_week
	on stats_project_tmp(week)";
$rel = $dbh->prepare($sql)->execute();

$sql = "CREATE INDEX idx_project_stats_month
	on stats_project_tmp(month)";
$rel = $dbh->prepare($sql)->execute();
print "Added further indexes to stats_project_tmp...\n" if $verbose;

##
## Merge tmp table back into the live stat table
##
$sql = "DELETE FROM stats_project WHERE month='$year$mon' AND day='$day'";
$rel = $dbh->prepare($sql)->execute();
print "Cleared Old data from stats_project...\n" if $verbose;

$sql = "INSERT INTO stats_project
	SELECT * FROM stats_project_tmp";
$rel = $dbh->prepare($sql)->execute();
print "Wrote back new data to stats_project...\n" if $verbose;

print "Done.\n" if $verbose;
exit;

##
## EOF
##
