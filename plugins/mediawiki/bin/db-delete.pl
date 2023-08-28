#!/usr/bin/perl -w
#
# $Id: db-delete.pl,v 1.1.1.1 2005/09/22 17:32:56 rhertzog Exp $
#
# Debian-specific script to delete plugin-specific tables
# Roland Mas <lolando@debian.org>

use strict ;
use diagnostics ;

use DBI ;
use MIME::Base64 ;
use HTML::Entities ;

use vars qw/$dbh @reqlist $query/ ;
use vars qw/$sys_default_domain
    $sys_shell_host $sys_users_host $sys_docs_host
    $sys_dns1_host $sys_dns2_host $FTPINCOMING_DIR $FTPFILES_DIR
    $sys_urlroot $sf_cache_dir $sys_name
    $sys_news_group $sys_dbhost $sys_dbname $sys_dbuser $sys_dbpasswd
    $sys_ldap_base_dn $sys_ldap_host $admin_login $admin_password
    $domain_name
    $skill_list/ ;
use vars qw/$pluginname/ ;

sub is_lesser ( $$ ) ;
sub is_greater ( $$ ) ;
sub debug ( $ ) ;
sub parse_sql_file ( $ ) ;

require ("/usr/share/gforge/lib/include.pl") ; # Include a few predefined functions
require ("/usr/share/gforge/lib/sqlparser.pm") ; # Our magic SQL parser

debug "You'll see some debugging info during this installation." ;
debug "Do not worry unless told otherwise." ;

&db_connect ;

# debug "Connected to the database OK." ;

$pluginname = "mediawiki" ;

$dbh->{AutoCommit} = 0;
$dbh->{RaiseError} = 1;
eval {
    my ($sth, @array, $version, $action, $path, $target, $rname) ;

    my $pattern = "plugin_" . $pluginname . '_%' ;

    $query = "SELECT relname FROM pg_class WHERE relname LIKE '$pattern' AND relkind='v'" ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    while (@array = $sth->fetchrow_array ()) {
	$rname = $array [0] ;
	&drop_view_if_exists ($rname) ;
    }
    $sth->finish () ;

    $query = "SELECT relname FROM pg_class WHERE relname LIKE '$pattern' AND relkind='r'" ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    while (@array = $sth->fetchrow_array ()) {
	$rname = $array [0] ;
	&drop_table_if_exists ($rname) ;
    }
    $sth->finish () ;

    $query = "SELECT relname FROM pg_class WHERE relname LIKE '$pattern' AND relkind='i'" ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    while (@array = $sth->fetchrow_array ()) {
	$rname = $array [0] ;
	&drop_index_if_exists ($rname) ;
    }
    $sth->finish () ;

    $query = "SELECT relname FROM pg_class WHERE relname LIKE '$pattern' AND relkind='S'" ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    while (@array = $sth->fetchrow_array ()) {
	$rname = $array [0] ;
	&drop_sequence_if_exists ($rname) ;
    }
    $sth->finish () ;

    $dbh->commit ();


    debug "It seems your database deletion went well and smoothly.  That's cool." ;
    debug "Please enjoy using Debian GForge." ;

    # There should be a commit at the end of every block above.
    # If there is not, then it might be symptomatic of a problem.
    # For safety, we roll back.
    $dbh->rollback ();
};

if ($@) {
    warn "Transaction aborted because $@" ;
    debug "Transaction aborted because $@" ;
    debug "Last SQL query was:\n$query\n(end of query)" ;
    $dbh->rollback ;
    debug "Please report this bug on the Debian bug-tracking system." ;
    debug "Please include the previous messages as well to help debugging." ;
    debug "You should not worry too much about this," ;
    debug "your DB is still in a consistent state and should be usable." ;
    exit 1 ;
}

$dbh->rollback ;
$dbh->disconnect ;

sub debug ( $ ) {
    my $v = shift ;
    chomp $v ;
    print STDERR "$v\n" ;
}

sub drop_table_if_exists ( $ ) {
    my $tname = shift or die  "Not enough arguments" ;
    $query = "SELECT count(*) FROM pg_class WHERE relname='$tname' AND relkind='r'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    if ($array [0] != 0) {
	# debug "Dropping table $tname" ;
	$query = "DROP TABLE $tname" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub drop_sequence_if_exists ( $ ) {
    my $sname = shift or die  "Not enough arguments" ;
    $query = "SELECT count(*) FROM pg_class WHERE relname='$sname' AND relkind='S'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    if ($array [0] != 0) {
	# debug "Dropping sequence $sname" ;
	$query = "DROP SEQUENCE $sname" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub drop_index_if_exists ( $ ) {
    my $iname = shift or die  "Not enough arguments" ;
    $query = "SELECT count(*) FROM pg_class WHERE relname='$iname' AND relkind='i'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    if ($array [0] != 0) {
	# debug "Dropping index $iname" ;
	$query = "DROP INDEX $iname" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub drop_view_if_exists ( $ ) {
    my $iname = shift or die  "Not enough arguments" ;
    $query = "SELECT count(*) FROM pg_class WHERE relname='$iname' AND relkind='v'" ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    if ($array [0] != 0) {
	# debug "Dropping view $iname" ;
	$query = "DROP VIEW $iname" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}
