#!/usr/bin/perl 
#
# $Id: db_stats_site_nightly.pl 1317 2005-01-14 10:51:24Z guerin $
#

use DBI;
use Time::Local;
use POSIX qw( strftime );

require("../include.pl");
$dbh = &db_connect();

my ($sql, $rel, $day_begin, $day_end, $mon, $week, $day);
my $verbose = 1;


if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {

        $day_begin = timegm( 0, 0, 0, $ARGV[2], $ARGV[1] - 1, $ARGV[0] - 1900 );
        $day_end = timegm( 0, 0, 0, (gmtime( $day_begin + 86400 ))[3,4,5] );

} else {

           ## Start at midnight last night.
        $day_end = timegm( 0, 0, 0, (gmtime( time() ))[3,4,5] );
           ## go until midnight yesterday.
        $day_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );

}

   ## Preformat the important date strings.
$year   = strftime("%Y", gmtime( $day_begin ) );
$mon    = strftime("%Y%m", gmtime( $day_begin ) );
$month  = strftime("%m", gmtime( $day_begin ) );
$week   = strftime("%U", gmtime( $day_begin ) );    ## GNU ext.
$day    = strftime("%d", gmtime( $day_begin ) );
print "Running week $week, day $day month $month year $year ($mon)\n" if $verbose;


##
## And now, we calculate the agregate stats for the site.
##

## site_views
## 

$sql	= "SELECT count FROM stats_agg_pages_by_day WHERE (day='$mon$day')";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($site_views) = ($rel->fetchrow_array)[0];

## subdomain_views
##
$sql	= "SELECT SUM(subdomain_views) FROM stats_project WHERE ( month='$mon' AND day='$day' ) GROUP BY month,day";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($subdomain_views) = ($rel->fetchrow_array)[0];

## downloads 
##
$sql	= "SELECT SUM(downloads) FROM frs_dlstats_group_agg WHERE ( day='$mon$day' ) GROUP BY day";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($downloads) = ($rel->fetchrow_array)[0];

## uniq_users
##
$sql	= "SELECT COUNT(DISTINCT(user_id)) FROM session WHERE (time < $day_end AND time > $day_begin)";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($uniq_users) = ($rel->fetchrow_array)[0];

## sessions
##
$sql	= "SELECT COUNT(session_hash) FROM session WHERE (time < $day_end AND time > $day_begin)";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($sessions) = ($rel->fetchrow_array)[0];

## total_users
##
$sql	= "SELECT COUNT(user_id) FROM user WHERE add_date < $day_end AND ( status='A' OR status='R')";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($total_users) = ($rel->fetchrow_array)[0]; 

## new_users
##
$sql	= "SELECT COUNT(user_id) FROM user WHERE ( add_date < $day_end AND add_date > $day_begin )";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($new_users) = ($rel->fetchrow_array)[0]; 

## new_projects
##
$sql	= "SELECT COUNT(group_id) FROM groups WHERE ( register_time < $day_end AND register_time > $day_begin )";
($rel = $dbh->prepare($sql))->execute() || die "SQL error: $!";
($new_projects) = ($rel->fetchrow_array)[0]; 



##
## Merge the nightly site info back into the live stat table.
##

$sql	= "DELETE FROM stats_site WHERE (month='$mon' AND day='$day')";
$rel = $dbh->do($sql) || die("SQL error: $!");

$sql	= "INSERT INTO stats_site VALUES "
	. "('$mon','$week','$day',"
	. "'$site_views','$subdomain_views','$downloads','$uniq_users',"
	. "'$sessions','$total_users','$new_users','$new_projects')";
$rel = $dbh->do($sql) || die("SQL error: $!");
print "Wrote back new data to stats_site...\n" if $verbose;

##
## EOF
##
