#!/usr/bin/perl
#
# $Id$
#
use DBI;
use File::Copy;

require("../include.pl");  # Include all the predefined functions

&db_connect;

# grab Table information
my $query = "SELECT groups.unix_group_name, filerelease.unix_partition, "
	. "filerelease.filename, filerelease.status, filerelease.filerelease_id, filerelease.old_filename "
	. "FROM groups,filerelease WHERE "
	. "filerelease.unix_box='remission' AND groups.group_id=filerelease.group_id";

# if the quick option is selected, then only do for modified files;

if ($ARGV[0] eq "quick") {
	print "Executing in quick mode.\n";
	$query .= " AND (filerelease.status='E' OR filerelease.status='M' OR filerelease.status='N')" ;
}
if (($ARGV[0] eq "verbose") or ($ARGV[1] eq "verbose")) {
	print "Executing in verbose mode.\n";
	$mode_v = 1;
}

my $c = $dbh->prepare($query);
$c->execute();

while(my ($unix_group_name,$unix_partition,$filename,$status,$filerelease_id,$old_filename) = $c->fetchrow()) {
	$fullpath = "/home/ftp/pub/sourceforge/$unix_group_name/";
	$newpath = "/home/ftp/incoming/";
	$newfilename = "${newpath}$filename";
	$fullfilename = "${fullpath}$filename";
	$fullfilenameold = "${fullpath}$old_filename";
	$delfilename = "${fullpath}~$filename";

	######### ACTIVE FILES
	if ($status eq 'A') {
		# verify file exists in the right location
		if (!(-f "$fullfilename")) {
			print "[ERROR] - A Not Found - $fullfilename\n";
		}
	}

	######### DELETED FILES
	elsif ($status eq 'D') {
		if (!(-f "$delfilename")) {
			print "[ERROR] - D Not Found - $delfilename\n";
			#fix it?
			if (-f "$fullfilename") {
				$command = "mv $fullfilename $delfilename";
				if (system($command)) {
					print "[ERROR] - Failed - $command\n";
				} else {
					print "[MOVE D FIX] - $command\n";
				}
			}
		} 
	}

	######### NEW FILES
	elsif ($status eq 'N') {
		if (-f "$newfilename") {
			$command = "mv $newfilename $fullfilename";
			if (system($command)) {
				print "[ERROR] - Failed - $command\n";
			} else {
				my $statusquery = "UPDATE filerelease SET status='A' WHERE filerelease_id=$filerelease_id";
				my $stat = $dbh->prepare($statusquery);
				$stat->execute();
				print "[MOVE NEW] - $command\n";
			}
		} else {
			print "[ERROR] - N Not Found - $newfilename\n";
		}
	}

	######### FILE CHANGE, PENDING ACTIVE
	elsif ($status eq 'M') {
		if (-f "$fullfilenameold") {
			$command = "mv $fullfilenameold $fullfilename";
			if (system($command)) {
				print "[ERROR] - Failed - $command\n";
			} else {
				my $statusquery = "UPDATE filerelease SET status='A' WHERE filerelease_id=$filerelease_id";
				my $stat = $dbh->prepare($statusquery);
				$stat->execute();
				print "[MOVE] - $command\n";
			}
		} else {
			print "[ERROR] - M Old Not Found - $fullfilenameold";
		}
	}

	######### FILE CHANGE, PENDING DELETE
	elsif ($status eq 'E') {
		if (-f "$fullfilenameold") {
			$command = "mv $fullfilenameold $delfilename";
			if (system($command)) {
				print "[ERROR] - Failed - $command\n";
			} else {
				my $statusquery = "UPDATE filerelease SET status='D' WHERE filerelease_id=$filerelease_id";
				my $stat = $dbh->prepare($statusquery);
				$stat->execute();
				print "[MOVE] - $command\n";
			}
		} elsif (-f "$fullfilename") {
			$command = "mv $fullfilename $delfilename";
			if (system($command)) {
				print "[ERROR] - Failed - $command\n";
			} else {
				my $statusquery = "UPDATE filerelease SET status='D' WHERE filerelease_id=$filerelease_id";
				my $stat = $dbh->prepare($statusquery);
				$stat->execute();
				print "[MOVE] - $command\n";
			}
		} else {
			print "[ERROR] - E Old Not Found - $fullfilenameold";
		}
	}
}
