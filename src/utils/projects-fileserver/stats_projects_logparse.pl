#!/usr/bin/perl
#
# 
#
use DBI;
use Time::Local;
use POSIX qw( strftime );
require("../include.pl");  # Include all the predefined functions

#######################
##  CONF VARS

	my $verbose = 1;
	my $chronolog_basedir = $codendi_log;
	my @webservers = ();

##
#######################

my ( $filerel, $query, $rel, %groups, %filerelease, $bytes, $filepath, $group_name, $filename, $files );

# Note that db_connect triggered the reading of the Codendi config file
&db_connect;

# get the host name
($hostname) = split(/\./,$sys_fullname);
push @webservers, $hostname;

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

print "----- Starting $0 ------\n";
print "Running year $year, month $month, day $day.\n" if $verbose;

   ## It's makes things a whole lot faster for us if we cache the filerelease/group info beforehand.
print "Caching group information out of the database..." if $verbose;
$query = "SELECT group_id,unix_group_name,http_domain FROM groups";
$rel = $dbh->prepare( $query );
$rel->execute();
while( $info = $rel->fetchrow_arrayref() ) {
	$group{${$info}[2]} = ${$info}[0];
}
print " done.\n" if $verbose;

foreach $server ( @webservers ) {

# LJ Make the file naming and directory structure consistent with the 
# LJ stats script in the utils/downloads/ subdir
# LJ	$file = "$chronolog_basedir/$server/$year/" . sprintf("%02d",$month) . "/" . sprintf("%02d",$day) . "/access_log"; 

	$file = "$chronolog_basedir/$year/" . sprintf("%02d",$month) ."/vhosts-access_$year". sprintf("%02d%02d", $month, $day) .".log"; 

	if ( -f $file ) {
		open(LOGFILE, "< $file" ) || die "Cannot open $file";
	} elsif( -f "$file.gz" ) {
		$file .= ".gz";
		open(LOGFILE, "/usr/bin/gunzip -c $file |" ) || die "Cannot open gunzip pipe for $file";
	} else {
		print STDERR "HELP! I couldn't find a log file at $file!\n";
		next;
	}

	print "Begining processing for logfile \'$file\'..." if $verbose;

	while (<LOGFILE>) {
		chomp($_);
		$lines++;
	
		   ## 1=ip  2=date 3=file_uri 4=return_code 5=bytes 6=referer 7=browser 8=domain
		$_ =~ m/^([\d\.]+).*\[(.+)\]\s\"GET (.+) HTTP.+\" (\d\d\d) (\d+|\-) \"([^\"]+)\" \"([^\"]+)\"\ *(.*)$/;

		$filepath = $3;
		$code = $4;
		$host = $8;

		next if ( !($filepath || $code) );

		if ( $code =~ m/(2|3)\d\d/ ) {

			   ## strip off any GET params.
			$filepath =~ s/\?.*$//;

			   ## We'll have our pageview filter to allow -> deny.
			if ( $filepath =~ m/\/$/ || $filepath !~ m/\.(gif|png|jpg|jpeg|css)$/ ) {
				if ( $group{$host} && $host ) {
					$page_views{$group{$host}}++;
				} 
				$valid_hits++;
			} 
		}
	}
	close(LOGFILE);

	print " done.\nProcessed $lines lines of file, with $valid_hits page views.\n" if $verbose;
	$total_views += $valid_hits;
	$lines = $valid_hits = 0;
}

if ( $total_views ) {
	print "Inserting/Updating records into database: stats_project..." if $verbose;

	foreach $id ( keys %page_views ) {
		$sql  = "UPDATE stats_project SET subdomain_views = $page_views{$id} ";
		$sql .= "WHERE ( group_id = '$id' AND month = '" . sprintf("%04d%02d", $year, $month) . "' AND day = '$day' )";
		$dbh->do( $sql ) || die "SQL error: $!";
	}
	print " done.\n" if $verbose;

	print "Completed insertion of $total_views subdomain page views into the database.\n" if $verbose;
} else {
	print "There were no valid page views found on this day!  (Parse error or no logfile?)\n" if $verbose;
}

##
## EOF
##
