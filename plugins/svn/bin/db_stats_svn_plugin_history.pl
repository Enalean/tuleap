#!/usr/bin/perl

#use strict;

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
}

##
## Set begin and end times (in epoch seconds) of day to be run
## Either specified on the command line, or auto-calculated
## to run yesterday's data.
##

my ($query,$repopath, $group_name);
my %svn_access = ();
my %svn_access_by_repository = ();

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

print "Caching group information from groups table.\n" if $verbose;
$sql = " SELECT groups.unix_group_name, plugin_svn_repositories.id, plugin_svn_repositories.name
         FROM plugin_svn_repositories INNER JOIN groups ON (groups.group_id = plugin_svn_repositories.project_id)";
$res = $dbh->prepare($sql);
$res->execute();

while ( $temp = $res->fetchrow_arrayref() ) {
    $project_name    = ${$temp}[0];
    $repository_id   = ${$temp}[1];
    $repository_name = ${$temp}[2];

    $groups{$project_name}{$repository_name} = $repository_id;
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

    $_ =~ m/^([\d\.]+)\s-\s(.+)\s\[(.+)\]\s(.+)\s(\d\d\d)\s\"(.*)\"/;

    $ip       = $1;
    $user     = $2;
    $date     = $3;
    $repopath = $4;
    $code     = $5;
    $svncom   = $6;

  if ( $repopath =~ m:/svnplugin/([^ /]+)/([^ /]+):) {
    $project_name    = $1;
    $repository_name = $2;

    $repository_id = $groups{$project_name}{$repository_name};

    if ( $repository_id == 0 ) {
      print STDERR "db_stats_svn_plugin history.pl: repository $n2 not found in project $1. Skipping. \n" if $verbose;
      next;
    }

    if ($user ne '-') {
        if ($use_ldap) {
            # LDAP users
            $ldap_id = ldap_get_ldap_id_from_login($user);
            if( $ldap_id == -1 ) {
                print STDERR "db_stats_svn_history.pl: Faild to find \'$user\' in LDAP\n" if $verbose;
                next;
            }
            if (!defined($usersLdapId{$ldap_id})) {
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
            print STDERR "db_stats_svn_history.pl: bad user_name \'$user\' \n" if $verbose;
            next;
        }
    }

    if (index($svncom, "commit") == 0) {
        $svn_access_by_repository{$repository_id}{$user_id}{write} += 1;
    } else {
        $svn_access_by_repository{$repository_id}{$user_id}{read} += 1;
    }

  }
}
close(LOGFILE);

for my $repository_id ( keys %svn_access_by_repository ) {
    for my $user_id ( keys %{$svn_access_by_repository{$repository_id}} ) {

        $nb_write = defined $svn_access_by_repository{$repository_id}{$user_id}{write} ?
                    $svn_access_by_repository{$repository_id}{$user_id}{write} : 0;
        $nb_read  = defined $svn_access_by_repository{$repository_id}{$user_id}{read} ?
                    $svn_access_by_repository{$repository_id}{$user_id}{read} : 0;

        ## test first if we have already a row for group_id, user_id, day_date that contains
        ## info on svn browsing activity.
        $sql_search = "SELECT *
                       FROM plugin_svn_full_history
                       WHERE repository_id = $repository_id
                        AND user_id = $user_id
                        AND day = $day_date";

        $search_res = $dbh->prepare($sql_search);
        $search_res->execute();

        if ($search_res->rows > 0) {
            $sql = "UPDATE plugin_svn_full_history
                    SET svn_write_operations = $nb_write,
                        svn_read_operations = $nb_read
                    WHERE repository_id = $repository_id
                        AND user_id = $user_id
                        AND day = $day_date";
        } else {
            $sql = "INSERT INTO plugin_svn_full_history (repository_id, user_id, day, svn_write_operations, svn_read_operations)
                    VALUES ($repository_id, $user_id, $day_date, $nb_write, $nb_read)";
        }

        $dbh->do($sql);

        #Update the last_access_date in user_access table
        $sql = "UPDATE user_access
                SET last_access_date = $day_begin
                WHERE user_id = $user_id
                    AND last_access_date < $day_begin";

        $dbh->do($sql)
    }
}

print " done.\n" if $verbose;
