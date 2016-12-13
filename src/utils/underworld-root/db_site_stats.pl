#!/usr/bin/perl
#
# 
#
use DBI;
use Time::Local;
require("../include.pl");  # Include all the predefined functions

&db_connect;

   ## Define yesterday.
($day, $month, $year) = (gmtime(timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] )))[3,4,5];
   ## Fix the braindead localtime formatting assumptions.
$year += 1900;
$month += 1;
   ## Now format our date nicely for the database.
$yesterday_formatted = sprintf("%04d%02d%02d", $year, $month, $day);


   ## shuffle the activity log tables - we keep 3 days of data
# Create table first, so that the process will not go further if the disk is full
$sql = "CREATE TABLE activity_log_new (
	day int(11) DEFAULT '0' NOT NULL,
	hour int(11) DEFAULT '0' NOT NULL,
	group_id int(11) DEFAULT '0' NOT NULL,
	browser varchar(8) DEFAULT 'OTHER' NOT NULL,
	ver float(10,2) DEFAULT '0.00' NOT NULL,
	platform varchar(8) DEFAULT 'OTHER' NOT NULL,
	time int(11) DEFAULT '0' NOT NULL,
	page text,
	type int(11) DEFAULT '0' NOT NULL,
	KEY idx_activity_log_day (day),
	KEY idx_activity_log_group (group_id),
	KEY type_idx (type)
)";
$rel = $dbh->do($sql);
if (!$rel) {
  # Create table failed. No more space on device?
  print "** CREATE TABLE failed for activity_log_new. Disk full? **\n";
  exit 1;
}

$sql = "DROP TABLE IF EXISTS activity_log_old_old";
$rel = $dbh->do($sql);

$sql = "ALTER TABLE activity_log_old RENAME AS activity_log_old_old";
$rel = $dbh->do($sql);

$sql = "ALTER TABLE activity_log RENAME AS activity_log_old";
$rel = $dbh->do($sql);

$sql = "ALTER TABLE activity_log_new RENAME AS activity_log";
$rel = $dbh->do($sql);



   ## Define today.
($day, $month, $year) = (gmtime(timegm( 0, 0, 0, (gmtime(time()))[3,4,5] )))[3,4,5];
   ## Fix the braindead localtime formatting assumptions.
$year += 1900;
$month += 1;
   ## Now format our date nicely for the database.
$today_formatted = sprintf("%04d%02d%02d", $year, $month, $day);

   ## Cleanup any spillover, so that the activity log always contains exactly 24 hours worth of data.
$sql = "INSERT INTO activity_log SELECT * FROM activity_log_old WHERE day='$today_formatted'";
$rel = $dbh->do($sql);
$sql = "DELETE FROM activity_log_old WHERE day='$today_formatted'";
$rel = $dbh->do($sql);

exit;

##
## EOF
##
