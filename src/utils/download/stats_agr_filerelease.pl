#!/usr/bin/perl 
#
# 
#
# Accumulate file downloads for all time :
#   - by project (group_id) in frs_dlstats_grouptotal_agg
#   - by file release (filerelease_id) in frs_dlstats_filetotal_agg
#
use DBI;
require("../include.pl");  # Include all the predefined functions

my $verbose = 1;

   ## if params were passed, we don't need to be running the agregates.
if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {
	print "Skipping the agregate build...\n" if $verbose;
	exit;
}


&db_connect;

##
## Begin by collecting universal data into RAM.
##
$sql	= "SELECT group_id FROM groups WHERE status='A'";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while ( @tmp_ar = $rel->fetchrow_array() ) {
	push( @groups, $tmp_ar[0] );
}

##
## CREATE THE frs_dlstats_grouptotal_agg TABLE.
##
$sql	= "DROP TABLE IF EXISTS frs_dlstats_grouptotal_agg_tmp";
$rel = $dbh->do($sql) || die "SQL parse error: $!";

   ## create the temp table;
$sql	= "CREATE TABLE frs_dlstats_grouptotal_agg_tmp ( "
	. "group_id int(11) DEFAULT '0' NOT NULL,"
	. "downloads int(11) DEFAULT '0' NOT NULL,"
	. "KEY idx_stats_agr_tmp_gid (group_id)"
	. ")";
$rel = $dbh->do($sql) || die "SQL parse error: $!";

$sql = "SELECT frs_package.group_id AS group_id, COUNT(*) AS downloads "
        ."FROM filedownload_log,frs_file,frs_release,frs_package "
	."WHERE frs_file.file_id=filedownload_log.filerelease_id AND "
        ."frs_file.release_id=frs_release.release_id AND "
        ."frs_release.package_id=frs_package.package_id GROUP BY group_id";
$rel = $dbh->prepare($sql) || die "SQL parse error: $!";
$rel->execute() || die "SQL execute error: $!";
while(@tmp_ar = $rel->fetchrow_array()) {
	$downloads{ $tmp_ar[0] } += $tmp_ar[1];
}

foreach $group_id ( @groups ) {
	$xfers = $downloads{$group_id};

	$sql  = "INSERT INTO frs_dlstats_grouptotal_agg_tmp VALUES ('$group_id','$xfers')";
	$rel = $dbh->do($sql) || die "SQL parse error: $!";
}

   ## Drop the old agregate table
$sql="DROP TABLE IF EXISTS frs_dlstats_grouptotal_agg";
$rel = $dbh->do($sql) || die "SQL parse error: $!";
   ## Relocate the new table to take it's place.
$sql="ALTER TABLE frs_dlstats_grouptotal_agg_tmp RENAME AS frs_dlstats_grouptotal_agg";
$rel = $dbh->do($sql) || die "SQL parse error: $!";



##
## CREATE THE frs_dlstats_filetotal_agg TABLE.
##
$sql	= "DROP TABLE IF EXISTS frs_dlstats_filetotal_agg_tmp";
$rel = $dbh->do($sql) || die "SQL parse error: $!";

   ## create the temp table;
$sql	= "CREATE TABLE frs_dlstats_filetotal_agg_tmp ( "
	. "file_id int(11) DEFAULT '0' NOT NULL,"
	. "downloads int(11) DEFAULT '0' NOT NULL,"
	. "KEY idx_stats_agr_tmp_fid (file_id)"
	. ")";
$rel = $dbh->do($sql) || die "SQL parse error: $!";

my $sql = "SELECT filerelease_id, COUNT(*) "
	."FROM filedownload_log GROUP BY filerelease_id";
my $rel = $dbh->prepare($sql);
$rel->execute();
while( @tmp_ar = $rel->fetchrow_array() ) {
  $filedownloads{$tmp_ar[0]} += $tmp_ar[1];
}

foreach $file_id ( keys %filedownloads ) {
	$xfers = $filedownloads{$file_id};

	$sql  = "INSERT INTO frs_dlstats_filetotal_agg_tmp VALUES ('$file_id','$xfers')";
	$rel = $dbh->do($sql) || die "SQL parse error: $!";
}

   ## Drop the old agregate table
$sql="DROP TABLE IF EXISTS frs_dlstats_filetotal_agg";
$rel = $dbh->do($sql) || die "SQL parse error: $!";
   ## Relocate the new table to take it's place.
$sql="ALTER TABLE frs_dlstats_filetotal_agg_tmp RENAME AS frs_dlstats_filetotal_agg";
$rel = $dbh->do($sql) || die "SQL parse error: $!";


##
## EOF
##
