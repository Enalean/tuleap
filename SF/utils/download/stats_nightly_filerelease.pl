#!/usr/bin/perl 
#
# $Id$
#
use DBI;
use Time::Local;
require("../include.pl");  # Include all the predefined functions

my $verbose = 1;

&db_connect;

if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {
  ## Set params manually, so we can run
  ## regressive log parses.
  $year = $ARGV[0];
  $month = $ARGV[1];
  $day = $ARGV[2];
} else {
  ## Otherwise, we just parse the logs for yesterday.
  ## compute the date for yesterday as well as the Unix time for begin and end of
  ## the day

  ## We also need the time at 0:00:00 and 23:59:59 for yesterday
  $time_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );
  $time_end   = timegm( 59, 59, 23,(gmtime( time() - 86400 ))[3,4,5] );


  ($day, $month, $year) = (gmtime($time_begin))[3,4,5];
  $year += 1900;
  $month += 1;

}

$today = sprintf("%04d%02d%02d", $year, $month, $day);
print "Running year $year, month $month, day $day.\n" if $verbose;

##
## POPULATE THE frs_dlstats_group_agg TABLE.
##

# Count all the downloads through direct HTTP access (group by project)
$sql	= "SELECT group_id,SUM(downloads) FROM stats_http_downloads "
	. "WHERE ( day = '$today' ) GROUP BY group_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

# Count all the downloads through direct HTTP access (group by project)
$sql	= "SELECT group_id,SUM(downloads) FROM stats_ftp_downloads "
	. "WHERE ( day = '$today' ) GROUP BY group_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

# Count all the downloads through the CodeX Web Frontend  (group by project)
# (this used to be counted through HTTP downloads but access is now
# managed thorugh a PHP script and there is special table storing download information
$sql    = "SELECT frs_package.group_id AS group_id,COUNT(*) "
        ."FROM frs_package,frs_release, frs_file, filedownload_log "
        ."WHERE filedownload_log.filerelease_id = frs_file.file_id "
        ."AND (filedownload_log.time >  $time_begin AND filedownload_log.time <= $time_end) "
        ."AND frs_file.release_id = frs_release.release_id "
        ."AND frs_release.package_id = frs_package.package_id  GROUP BY group_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

$sql = "DELETE FROM frs_dlstats_group_agg WHERE day='$today'";
$rel = $dbh->do($sql) || die "SQL parse error: $!";
foreach $group_id ( keys %downloads ) {
	$xfers = $downloads{$group_id};
	$total_xfers += $xfers;
	$sql  = "INSERT INTO frs_dlstats_group_agg VALUES ('$group_id','$today','$xfers')";
	$rel = $dbh->do($sql) || die "SQL parse error: $!";
}


## do some housekeeping before the next set.
%downloads = {};
$first_xfers = $total_xfers;
$total_xfers = 0;


##
## POPULATE THE frs_dlstats_file_agg TABLE.
##

# Count all the downloads through direct HTTP access (group by file)
$sql	= "SELECT filerelease_id,SUM(downloads) FROM stats_http_downloads "
	. "WHERE ( day = '$today' ) GROUP BY filerelease_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

# Count all the downloads through direct FTP access (group by file)
$sql	= "SELECT filerelease_id,SUM(downloads) FROM stats_ftp_downloads "
	. "WHERE ( day = '$today' ) GROUP BY filerelease_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

# Count all the downloads through the CodeX Web Frontend  (group by file)
# (this used to be counted through HTTP downloads but access is now
# monitored on CodeX and there is special table storing download information
$sql	= " SELECT filerelease_id,COUNT(*) AS downloads FROM filedownload_log "
	. "WHERE ( time >= $time_begin AND time <= $time_end) GROUP BY filerelease_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1];
}

$sql = "DELETE FROM frs_dlstats_file_agg WHERE day='$today'";
$rel = $dbh->do($sql) || die "SQL parse error: $!";
foreach $file_id ( keys %downloads ) {
	$xfers = $downloads{$file_id};
	$total_xfers += $xfers;
	$sql  = "INSERT INTO frs_dlstats_file_agg VALUES ('$file_id','$today','$xfers')";
	$rel = $dbh->do($sql) || die "SQL parse error: $!";
}


##
## POPULATE THE downloads ROW OF THE stats_site TABLE
##

if ( $total_xfers != $first_xfers ) {
  # Sanity check: sum by file must equal sum by project because file releases
  # belong to the same project
  print "stats_nightly_filerelease.pl: THE TRANSER STATS DID NOT AGREE!! FIX ME!!\n";
}

$sql	= "UPDATE stats_site SET downloads='$total_xfers' WHERE (month='" . sprintf("%04d%02d", $year, $month) . "' AND day='$day') ";
$rel = $dbh->do($sql) || die "SQL parse error: $!";

##
## EOF
##
