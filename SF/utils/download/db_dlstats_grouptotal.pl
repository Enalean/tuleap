#!/usr/bin/perl
#
# $Id$
#
use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

# doing this for all days for now
my $query = "SELECT group_id, SUM(downloads) AS downloads "
	."FROM frs_dlstats_group_agg GROUP BY group_id";
my $rel = $dbh->prepare($query);
$rel->execute();

my $query = "DELETE FROM frs_dlstats_grouptotal_agg";
my $reldel = $dbh->prepare($query);
$reldel->execute();

# for each day
while(my ($group_id,$downloads) = $rel->fetchrow()) {
	my $query = "INSERT INTO frs_dlstats_grouptotal_agg (group_id,downloads) "
		."VALUES (".$group_id.",".$downloads.")";
	my $reldb = $dbh->prepare($query);
	$reldb->execute();
}
