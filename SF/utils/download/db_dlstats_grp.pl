#!/usr/bin/perl
#
# $Id$
#
use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

# doing this for all days for now
my $query = "SELECT day FROM frs_dlstats_agg GROUP BY day";
my $rel = $dbh->prepare($query);
$rel->execute();

# for each day
while(my ($day) = $rel->fetchrow()) {
	print "Processing day $day...\n";
	undef(%daydl);

	my $query = "SELECT (frs_dlstats_agg.downloads_http + frs_dlstats_agg.downloads_ftp) "
		."AS downloads, filerelease.group_id FROM filerelease,frs_dlstats_agg "
		."WHERE filerelease.filerelease_id=frs_dlstats_agg.file_id AND day=$day";
	my $reldb = $dbh->prepare($query);
	$reldb->execute();

	while (my ($downloads, $group_id) = $reldb->fetchrow()) {
		$daydl{$group_id} += $downloads;
	}

	#drop previous rows
	my $query = "DELETE FROM frs_dlstats_group_agg WHERE day=$day";
	my $reldel = $dbh->prepare($query);
	$reldel->execute();

	while (($keygrp,$valdl) = each (%daydl)) {
		my $query = "INSERT INTO frs_dlstats_group_agg (group_id,day,downloads) "
			."VALUES (".$keygrp.",".$day.",".$valdl.")";
		my $relins = $dbh->prepare($query);
		$relins->execute();
	}
}
