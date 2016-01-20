#!/usr/bin/perl
#
# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
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

my ($logfile, $sql, $res, $temp, %groups, $group_id, $errors, %svn_ldap_groups);
my ($sql_del, $res_del, %users, $user_id);

my $verbose = 0;
my $chronolog_basedir = $codendi_log;
my $use_ldap = 0;

# Test whether LDAP plugin is enabled or not
my $query_plugin_ldap = "SELECT NULL FROM plugin WHERE name='ldap' AND available=1";
my $c_plugin_ldap     = $dbh->prepare($query_plugin_ldap);
my $res_plugin_ldap   = $c_plugin_ldap->execute();
if ($res_plugin_ldap && ($c_plugin_ldap->rows > 0)) {
    $use_ldap = 1;

    use Net::LDAP;
    use Cwd; # needed by ldap account auto create

    require("../ldap.pl");

    # Load LDAP config
    my $ldapIncFile = $sys_custompluginsroot.'/ldap/etc/ldap.inc';
    &load_local_config($ldapIncFile);
    &ldap_connect;

    # Cache the projects that uses LDAP to authenticate svn users instead of
    # codex crendentials.
    print "Caching ldap powered svn repositories.\n" if $verbose;
    my $query_ldap = "SELECT g.group_id".
        " FROM groups g JOIN plugin_ldap_svn_repository svnrep USING (group_id)".
        " WHERE svnrep.ldap_auth = 1";
    my $res_ldap = $dbh->prepare($query_ldap);
    $res_ldap->execute();
    while(my ($group_id) = $res_ldap->fetchrow()) {
        $svn_ldap_groups{$group_id} = 1;
    }
}

##
## Set begin and end times (in epoch seconds) of day to be run
## Either specified on the command line, or auto-calculated
## to run yesterday's data.
##

my ($query,$repopath, $group_name);
my %svn_access = ();
my %svn_access_by_group = ();

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

$file = "$chronolog_basedir/$year/$month/svn_$year$month$day.log";

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
# users as well in Codendi (See group_svn_full_history table)
print "Caching user information from user table.\n" if $verbose;
$sql = "SELECT user_id,user_name,ldap_id FROM user";
$res = $dbh->prepare($sql);
$res->execute();
while ( $temp = $res->fetchrow_arrayref() ) {
  ${$temp}[1] =~ tr/A-Z/a-z/; # Unix users are lower case only
  $users{${$temp}[1]} = ${$temp}[0];
  $usersLdapId{${$temp}[2]} = ${$temp}[0];
}

while (<LOGFILE>) {
  chomp($_);

  $_ =~ m/^([\d\.]+)\s-\s(.+)\s\[(.+)\]\s(.+)\s(\d\d\d)\s\".*\"/;

  $ip   = $1;
  $user = $2;
  $date = $3;
  $repopath = $4;
  $code = $5;
  $svncom = $6; 

  #print "--------------------------------\n";
  #print "line: $_\n";
  #print "file: $repopath\n";

  if ( $repopath =~ m:/svnroot/([^ /]+):) {
    $gname = $1;
    $group_id = $groups{$gname};

    #print "User: $user\n";

    if ( $group_id == 0 ) {
      #print STDERR "$_";
      print STDERR "db_stats_svn_history.pl: bad unix_group_name \'$group\' \n" if $verbose;
      next;
    }
    $svn_access_by_group{$group_id} += 1;

    if ($user ne '-') {

	if(defined($svn_ldap_groups{$group_id})) {
	    # LDAP users
	    #print "Find Ldap user: $user\n";
	    $ldap_id = ldap_get_ldap_id_from_login($user);
	    if( $ldap_id == -1 ) {
		#print STDERR "$_";
		print STDERR "db_stats_svn_history.pl: Faild to find \'$user\' in LDAP\n" if $verbose;
		next;
	    }
            if(!defined($usersLdapId{$ldap_id})) {
                # No user with this ldap Id, create a new account
                $user_id = ldap_account_create_auto($ldap_id);
            } else {
                $user_id = $usersLdapId{$ldap_id};
            }
	} else {
	    # CodeX users
	    $user_id = $users{$user};
	}

	if ( $user_id == 0 ) {
	    #print STDERR "$_";
	    print STDERR "db_stats_svn_history.pl: bad user_name \'$user\' \n" if $verbose;
	    next;
	}
	
	#print "$user_id acceded to $group_id\n";
	$svn_access{$group_id}{$user_id} += 1;
    }

  } else {
    #print "line rejected:$_\n";
  }
}
close(LOGFILE);
#exit 1;

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
    #Update the last_access_date in user_access table
    $sql = "Update user_access set last_access_date = $day_begin WHERE user_id=$u AND last_access_date < $day_begin";
    $dbh->do($sql)|| warn "SQL error in $sql: $!";
    #print "SQL -> $sql\n";
  }
}
print " done.\n" if $verbose;

##
## EOF
##

