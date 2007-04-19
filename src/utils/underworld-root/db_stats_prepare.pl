#!/usr/bin/perl
#
# 
#
use DBI;

my $verbose = 1;

require("../include.pl");
$dbh = &db_connect();

## 
## Drop the tmp table.
##      
$sql = "DROP TABLE IF EXISTS stats_project_build_tmp";
$rel = $dbh->prepare($sql)->execute();
print "Dropped stats_project_build_tmp...\n" if $verbose;

##
## Create a temporary table to hold all of our stats
## for agregation at the end.
##      
$sql = "CREATE TABLE stats_project_build_tmp (
        group_id int NOT NULL,
        stat char(24) NOT NULL,
        value int NOT NULL DEFAULT '0',
        KEY idx_archive_build_group (group_id),
        KEY idx_archive_build_stat (stat)
        )";
$rel = $dbh->prepare($sql)->execute();
print "Created stats_project_build_tmp...\n" if $verbose;

