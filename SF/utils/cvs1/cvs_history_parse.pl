#!/usr/bin/perl
##
## cvs_history_parse.pl 
##
## NIGHTLY SCRIPT
##
## Recurses through the /cvsroot directory tree and parses each projects
## '~/CVSROOT/history' file, building agregate stats on the number of 
## checkouts, commits, and adds to each project over the past 24 hours.
##
##
## $Id$ 
##
use strict;
use Time::Local;
use POSIX qw( strftime );

my ($year, $month, $day, $day_begin, $day_end);
my ($group, $histline, $daily_log_file, $key, $verbose);
my $verbose = 0;
my $base_log_dir = "/usr/local/boa/htdocs/cvslogs";

$|=0 if $verbose;

   ## Set the time to collect stats for
if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {

        $day_begin = timegm( 0, 0, 0, $ARGV[2], $ARGV[1] - 1, $ARGV[0] - 1900 );
        $day_end = timegm( 0, 0, 0, (gmtime( $day_begin + 86400 ))[3,4,5] );
	
	$year = $ARGV[0];
	$month = $ARGV[1];
	$day = $ARGV[2];

} else {

           ## Start at midnight last night.
        $day_end = timegm( 0, 0, 0, (gmtime( time() ))[3,4,5] );
           ## go until midnight yesterday.
        $day_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );

	$year	= strftime("%Y", gmtime( $day_begin ) );
	$month	= strftime("%m", gmtime( $day_begin ) );
	$day	= strftime("%d", gmtime( $day_begin ) );

}

print "Parsing cvs logs looking for traffic on day $day, month $month, year $year.\n" if $verbose;

if ( -d $base_log_dir ) {
	$daily_log_file = $base_log_dir . "/" . sprintf("%04d", $year);
	if ( ! -d $daily_log_file ) {
		print "Making dest dir \'$daily_log_file\'\n";
		mkdir( $daily_log_file, 0755 ) || die("Could not mkdir $daily_log_file");
	} 
	$daily_log_file .= "/" . sprintf("%02d", $month);
	if ( ! -d $daily_log_file ) {
		print "Making dest dir \'$daily_log_file\'\n";
		mkdir( $daily_log_file, 0755 ) || die("Could not mkdir $daily_log_file");
	}
	$daily_log_file .= "/cvs_traffic_" . sprintf("%04d%02d%02d",$year,$month,$day) . ".log";
} else {
	die("Base log directory \'$base_log_dir\' does not exist!");
}

open(DAYS_LOG, "> $daily_log_file") || die "Unable to open the log file \'$daily_log_file\'";
print "Opened log file at \'$daily_log_file\' for writing...\n";
print "Running tree at /cvsroot/\n";

chdir( "/cvsroot" ) || die("Unable to make /cvsroot the working directory.\n");
foreach $group ( glob("*") ) {
	
	next if ( ! -d "$group" );

	my ($cvs_co, $cvs_commit, $cvs_add, %usr_commit, %usr_add );

	open(HISTORY, "< /cvsroot/$group/CVSROOT/history") or print "E::Unable to open history for $group\n";
	while ( <HISTORY> ) {
		my ($time_parsed, $type, $cvstime, $user, $curdir, $module, $rev, $file );
 
		   ## Split the cvs history entry into it's 6 fields.
		($cvstime,$user,$curdir,$module,$rev,$file) = split(/\|/, $_, 6 );

		$type = substr($cvstime, 0, 1);
		$time_parsed = hex( substr($cvstime, 1, 8) );

		   ## If the entry was made in the past 24 hours 
		   ## (i.e. - since the last run of this script...)
		if ( ($time_parsed > $day_begin) && ($time_parsed < $day_end) ) {

			   ## log commits
			if ( $type eq "M" ) {
				$cvs_commit++;
				$usr_commit{$user}++;
				next;
			}

			   ## log adds
			if ( $type eq "A" ) {
				$cvs_add++;
				$usr_add{$user}++;
				next;
			}

			   ## log checkouts
			if ( $type eq "O" ) {
				$cvs_co++;
				## we don't care about checkouts on a per-user
				## most of them will be anon anyhow.
				next;
			}
		
		} elsif ( $time_parsed > $day_end ) {
			if ( $verbose >= 2 ) {
				print "Short circuting execution, parsed date exceeded current threshold.\n";
			}
			last;
		}

	}
	close( HISTORY );

	   ## Now, we'll print all of the results for that project, in the following format:
	   ## (G|U|E)::proj_name::user_name::checkouts::commits::adds
	   ## If 'G', then record is group statistics, and field 2 is a space...
	   ## If 'U', then record is per-user stats, and field 2 is the user name...
	   ## If 'E', then record is an error, and field 1 is a description, there are no other fields.
	if ( $cvs_co || $cvs_commit || $cvs_add ) {
		print DAYS_LOG "G::" . $group . ":: ::" . ($cvs_co?$cvs_co:"0") . "::"
			. ($cvs_commit?$cvs_commit:"0") . "::" . ($cvs_add?$cvs_add:"0") . "\n";
	
		foreach $key ( keys %usr_commit ) {
	
			print DAYS_LOG "U::" . $group . "::" . $key . "::0::" . ($usr_commit{$key}?$usr_commit{$key}:"0") 
				. "::" . ($usr_add{$key}?$usr_add{$key}:"0") . "\n";
		}
	}
}
print "Done processing cvs history file for this date.\n" if $verbose;

##
## EOF
##
