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
        ($day, $month, $year) = (gmtime(timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] )))[3,4,5];
        $year += 1900;
        $month += 1;
}

$today = sprintf("%04d%02d%02d", $year, $month, $day);
print "Running year $year, month $month, day $day.\n" if $verbose;

##
## POPULATE THE frs_dlstats_group_agg TABLE.
##
$sql	= "SELECT group_id,SUM(downloads) FROM stats_http_downloads "
	. "WHERE ( day = '$today' ) GROUP BY group_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

$sql	= "SELECT group_id,SUM(downloads) FROM stats_ftp_downloads "
	. "WHERE ( day = '$today' ) GROUP BY group_id";
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
$sql	= "SELECT filerelease_id,SUM(downloads) FROM stats_http_downloads "
	. "WHERE ( day = '$today' ) GROUP BY filerelease_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1]; 
}

$sql	= "SELECT filerelease_id,SUM(downloads) FROM stats_ftp_downloads "
	. "WHERE ( day = '$today' ) GROUP BY filerelease_id";
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
	print "stats_nightly_filerelease.pl: THE TRANSER STATS DID NOT AGREE!! FIX ME!!\n";
}
$sql	= "UPDATE stats_site SET downloads='$total_xfers' WHERE (month='" . sprintf("%04d%02d", $year, $month) . "' AND day='$day') ";
$rel = $dbh->do($sql) || die "SQL parse error: $!";

##
## EOF
##
