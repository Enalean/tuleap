#!/usr/bin/perl
#
# $Id: stats_http_logparse.pl 3591 2006-09-01 09:52:16Z guerin $
#
use DBI;
use Time::Local;
use POSIX qw( strftime );
require("../include.pl");  # Include all the predefined functions

#######################
##  CONF VARS

	my $verbose = 1;
	my $chronolog_basedir = $codex_log;

##
#######################

my ( $filerel, $query, $rel, %groups, %filerelease, $bytes, $filepath, $group_name, $filename, $files );

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

$file = "$chronolog_basedir/$year/" . sprintf("%02d",$month) . "/http_combined_$year" 
	. sprintf("%02d%02d", $month, $day) . ".log";

print "Running year $year, month $month, day $day from \'$file\'\n" if $verbose;
print "Caching file release information out of the database..." if $verbose;

   ## It's makes things a whole lot faster for us if we cache the filerelease/group info beforehand.
$query  = "SELECT frs_file.file_id,groups.group_id,groups.unix_group_name,frs_file.filename "
	. "FROM frs_file,frs_release,frs_package,groups "
	. "WHERE ( groups.group_id = frs_package.group_id "
	. "AND frs_package.package_id = frs_release.package_id "
	. "AND frs_release.release_id = frs_file.release_id )";
$rel = $dbh->prepare($query);
$rel->execute();
while( $filerel = $rel->fetchrow_arrayref() ) {
	$file_ident = ${$filerel}[2] . ":" . ${$filerel}[3];
	$filerelease{$file_ident} = ${$filerel}[0];
	$groups{${$filerel}[0]} = ${$filerel}[1];
}

print " done.\n" if $verbose;

print "Begining processing for logfile \'$file\'..." if $verbose;			

if ( -f $file ) {
	open(LOGFILE, "< $file" ) || die "Cannot open $file";
} elsif( -f "$file.gz" ) {
	open(LOGFILE, "/usr/bin/gunzip -c $file.gz |" ) || die "Cannot open gunzip pipe for $file.gz";
}

while (<LOGFILE>) {

	$_ =~ m/^([\d\.]+).*\[(.+)\]\s\"GET (.+) HTTP.+(\d\d\d)\s(\d+)/;

	$filepath = $3;
	$code = $4;

	if ( $code =~ m/2\d\d/ ) {


		$filepath =~ m/^\/([^\/]+)\//;
		$basedir = $1;

		if ( $basedir ne "mirrors" && $basedir ne "pub" && $basedir ne "debian" ) {

			$filepath =~ m/\/([^\/]+)$/;
			$filename = $1;

			$file_ident = $basedir . ":" . $filename;

			if ( $filerelease{$file_ident} ) {
				$downloads{$filerelease{$file_ident}}++;
			} 
		}
	}
}
close(LOGFILE);

print " done.\n" if $verbose;

print "Deleting any existing records for day=" . sprintf("%d%02d%02d", $year, $month, $day) . ".\n" if $verbose;

$query = "DELETE FROM stats_http_downloads WHERE day='" . sprintf("%d%02d%02d", $year, $month, $day) . "'";
$dbh->do( $query );

print "Inserting records into database: stats_http_downloads..." if $verbose;

foreach $id ( keys %downloads ) {
	$query  = "INSERT INTO stats_http_downloads (day,filerelease_id,group_id,downloads) ";
	$query .= "VALUES (\'" . sprintf("%d%02d%02d", $year, $month, $day) . "\',\'";
	$query .= $id . "\',\'" . $groups{$id} . "\',\'" . $downloads{$id} . "\')";
	$dbh->do( $query );
}

print " done.\n" if $verbose;

##
## EOF
##
