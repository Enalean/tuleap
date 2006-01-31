#!/usr/bin/perl
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#
# NIGHTLY SCRIPT
#
# Pulls the subversion access logs out of the http log file and push it into
# the database. Remark: the subversion access log available in the http log
# log file gives a low level view of the subversion accesses (HTTP methods:
# PROPFIND, REPORT,...) and we cannot infere from this what the highler
# level operations are (update, checkout.etc...). So for now we just store in the
# DB the fact there were some accesses but we cannot count them.
#
# Written by Laurent Julliard, Xerox Corporation
#

#use strict; # uncomment to check thoroughly

use DBI;
use Time::Local;
use POSIX qw( strftime );
require("../include.pl");  # Include all the predefined functions
&db_connect;

my ($logfile, $sql, $res, $temp, %groups, $group_id, $errors );
my ($sql_del, $res_del, %users, $user_id);

my $verbose = 1;
my $chronolog_basedir = "/home/log";

##
## Set begin and end times (in epoch seconds) of day to be run
## Either specified on the command line, or auto-calculated
## to run yesterday's data.
##

my ($query,$filepath, $group_name);
my %svn_access = ();
my %svn_access_by_group = ();

&db_connect;


if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {
  $day_begin = timegm( 0, 0, 0, $ARGV[2], $ARGV[1] - 1, $ARGV[0] - 1900 );
} else {
  ## go until midnight yesterday.
  $day_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );
}

## Preformat the important date strings.
$year     = strftime("%Y", gmtime( $day_begin ) );
$month    = strftime("%m", gmtime( $day_begin ) );
$day      = strftime("%d", gmtime( $day_begin ) );

# Day YYYYMMDD used in the group_svn_full_history table
$day_date = "$year$month$day";

$file = "$chronolog_basedir/$year/$month/http_combined_$year$month$day.log";

print "Running year $year, month $month, day $day from \'$file\'\n" if $verbose;
print "Beginning Subversion access parsing logfile \'$file\'...\n" if $verbose;			
# Open the log file first
if ( -f $file ) {
  open(LOGFILE, "< $file" ) || die "Cannot open $file";
} elsif( -f "$file.gz" ) {
  open(LOGFILE, "/usr/bin/gunzip -c $file.gz |" ) || die "Cannot open gunzip pipe for $file.gz";
}

# Now that open was succesful make sure that we delete all the rows
# in the group_svn_full_history for that day so that his day is not 
# twice in the table in case of a rerun.
# Now that there exist a new column svn_browse that is not filled by
# this script we need to be a bit more delicate not deleting it.
#$sql_del = "DELETE FROM group_svn_full_history WHERE day='$day_date'";
#$res_del = $dbh->do($sql_del);


## Now, we will pull all of the project ID's and names into a *massive*
## hash, because it will save us some real time in the log processing.
print "Caching group information from groups table.\n" if $verbose;
$sql = "SELECT group_id,unix_group_name FROM groups";
$res = $dbh->prepare($sql);
$res->execute();
while ( $temp = $res->fetchrow_arrayref() ) {
  $groups{${$temp}[1]} = ${$temp}[0];
}

# And we now do the same for users since we log stats about
# users as well in CodeX (See group_svn_full_history table)
print "Caching user information from user table.\n" if $verbose;
$sql = "SELECT user_id,user_name FROM user";
$res = $dbh->prepare($sql);
$res->execute();
while ( $temp = $res->fetchrow_arrayref() ) {
  ${$temp}[1] =~ tr/A-Z/a-z/; # Unix users are lower case only
  $users{${$temp}[1]} = ${$temp}[0];
}

while (<LOGFILE>) {
  chomp($_);

  $_ =~ m/^([\d\.]+)\s.+\s(.+)\s\[(.+)\]\s\"\w+\s(.+)\sHTTP.+(\d\d\d)\s([\d-]+)/;

  $ip   = $1;
  $user = $2;
  $date = $3;
  $filepath = $4;
  $code = $5;
  $size = $6; # can be '-' in some subversion http methods.

  #print "--------------------------------\n";
  #print "line: $_\n";
  #print "file: $filepath\n";

  if ( $filepath =~ m:$svn_prefix/([^ /]+):) {
    $gname = $1;
    $group_id = $groups{$gname};

    #print "User: $user\n";

    if ( $group_id == 0 ) {
      print STDERR "$_";
      print STDERR "db_stats_svn_history.pl: bad unix_group_name \'$group\' \n";
      next;
    }
    $svn_access_by_group{$group_id} += 1;


    if ($user ne '-') {
      $user_id = $users{$user};

      if ( $user_id == 0 ) {
	print STDERR "$_";
	print STDERR "db_stats_svn_history.pl: bad user_name \'$user\' \n";
	next;
      }

      $svn_access{$group_id}{$user_id} += 1;
    }

  } else {
    #print "line rejected:$_\n";
  }
}
close(LOGFILE);

# loop through the group_id/user_id array and insert svn access entries
print "Saving Subversion access in database \'$file\'...\n" if $verbose;
for my $g ( keys %svn_access ) {
  #print "key=$g\n";
			
  $sql = "INSERT INTO stats_project_build_tmp (group_id,stat,value) 
	   VALUES ('" . $g . "'," 
	  . "'svn_access_count','" . $svn_access_by_group{$g} . "')";
  $dbh->do( $sql );

  for my $u ( keys %{$svn_access{$g}} ) {
    #print "\t$u\n";

    ## test first if we have already a row for group_id, user_id, day_date that contains
    ## info on svn browsing activity.
    $sql_search = "SELECT * FROM group_svn_full_history WHERE group_id=$g AND user_id=$u AND day='$day_date'";
    $search_res = $dbh->prepare($sql_search);
    $search_res->execute();
    if ($search_res->rows > 0) {
      $sql = "UPDATE group_svn_full_history SET svn_access_count='$svn_access{$g}{$u}' WHERE group_id=$g AND user_id=$u AND day='$day_date'";
      $dbh->do($sql);
    } else {
      $sql = "INSERT INTO group_svn_full_history (group_id,user_id,day,svn_access_count)
			VALUES ('$g', '$u', '$day_date','$svn_access{$g}{$u}')";
      $dbh->do($sql)|| warn "SQL error in $sql: $!";
    }
    #print "SQL -> $sql\n";
  }
}
print " done.\n" if $verbose;

##
## EOF
##

