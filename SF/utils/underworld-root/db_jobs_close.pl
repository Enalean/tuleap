#!/usr/bin/perl
#
#  Sets as closed jobs that are older than 14 days.
#  by q@sourceforge.net 19/05/2000
# 

use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

$two_weeks=(time()-1209600);

#unix_time_stamp() is a mysql function to return seconds since epoch.
#1209600 is 60*60*24*14 seconds (14 days)

my $query = "UPDATE people_job SET status_id = '3' where date < '$two_weeks'";
my $rel = $dbh->prepare($query);
$rel->execute();

