#!/usr/bin/perl
#
# $Id$
#
use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

#once per year
opendir DIRYEAR, "/home/log";
@diryear = grep /\d\d\d\d/, readdir DIRYEAR;
foreach $logyear (@diryear) {
	#now per month
	opendir DIRMONTH, "/home/log/$logyear";
	@dirmonth = grep /\d\d/, readdir DIRMONTH;
	foreach $logmonth (@dirmonth) {
		#now per combined_log
		opendir DIRENTRY, "/home/log/$logyear/$logmonth";
		@combinedlogs = grep /combined.*\.log$/, readdir DIRENTRY;
		foreach $combinedlog (@combinedlogs) {
			print "Processing $combinedlog...\n";
			if ($combinedlog =~ /\d\d\d\d\d\d(\d\d)/) {
				$logday = $1;
			} else { 
				die ("Cannot find day in logfilename.");
			}

			undef %ct_file;
			undef %ct_group;
			
			$LOGFILE = "/home/log/$logyear/$logmonth/$combinedlog";

			open LOGFILE or die "Cannot open $LOGFILE";
			while (<LOGFILE>) {
				use integer;
				$logline = $_;
				if ($logline =~ /((\d|\.)+).*\[(\d+)\/(\w+)\/(\d+):(\d+):(\d+):(\d+)\s.*GET \/((?:\w|-)+)\/(.+)\sHTTP.+\s200\s(\d+)/ && !($9 eq 'mirrors') && !($9 eq 'pub') && !($9 eq 'debian')) {
					$grp = $9;
			                $file = $10;
					$bytes = $11;
					$ip = $1;

					# Get time diff
					$mday = $3;
					if ($4 eq 'Jan') { $mon = "01"; }
					elsif ($4 eq 'Feb') { $mon = "02"; }
					elsif ($4 eq 'Mar') { $mon = "03"; }
					elsif ($4 eq 'Apr') { $mon = "04"; }
					elsif ($4 eq 'May') { $mon = "05"; }
					elsif ($4 eq 'Jun') { $mon = "06"; }
					elsif ($4 eq 'Jul') { $mon = "07"; }
					elsif ($4 eq 'Aug') { $mon = "08"; }
					elsif ($4 eq 'Sep') { $mon = "09"; }
					elsif ($4 eq 'Oct') { $mon = "10"; }
					elsif ($4 eq 'Nov') { $mon = "11"; }
					elsif ($4 eq 'Dec') { $mon = "12"; }
					$year = $5;
					$hour = $6;
					$min = $7;
					$sec = $8;

					$grp =~ s/%([0-9a-fA-F][0-9a-fA-F])/pack("C", hex($1))/eg;
					$file =~ s/%([0-9a-fA-F][0-9a-fA-F])/pack("C", hex($1))/eg;

					$ct_group{$grp}++;
					$ct_file{$grp}{$file}++;
				} #end per line of good regexed logfile
			} # end while processing logfile

			#delete all rows for this day
			my $query = "DELETE FROM frs_dlstats_agg WHERE day="
				.$logyear.$logmonth.$logday;
			my $rel = $dbh->prepare($query);
			$rel->execute();

			#now output the database rows
			while (($keygrp,$valgrp) = each (%ct_group)) {
				while (($keyfile,$valfile) = each (%{$ct_file{$keygrp}})) {
					#get fileid
					my $query = "SELECT filerelease.filerelease_id FROM filerelease,groups WHERE filerelease.group_id=groups.group_id AND groups.unix_group_name='$keygrp' AND filerelease.filename='$keyfile'";
					my $rel = $dbh->prepare($query);
					$rel->execute();
					($filerelease_id) = $rel->fetchrow();

					my $query = "INSERT INTO frs_dlstats_agg "
						."(file_id,day,downloads_http) VALUES "
						."(".$filerelease_id.",".$logyear.$logmonth.$logday.","
						.$valfile.")";
					if ($filerelease_id > 0) {
						my $rel = $dbh->prepare($query);
						$rel->execute();
					}
				}
			}
		}
	}
}
