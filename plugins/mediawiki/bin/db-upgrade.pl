#!/usr/bin/perl -w
#
# $Id: db-upgrade.pl,v 1.1.1.1 2005/09/22 17:32:56 rhertzog Exp $
#
# Debian-specific script to upgrade the database between releases
# Roland Mas <lolando@debian.org>

use strict ;
use diagnostics ;

use DBI ;
use MIME::Base64 ;
use HTML::Entities ;

use vars qw/$dbh @reqlist $query/ ;
use vars qw/$sys_default_domain $sys_cvs_host
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
    my ($sth, @array, $version, $path, $target) ;

    &create_metadata_table ("0") ;

    $version = &get_db_version ;
    $target = "0.1" ;
    if (is_lesser $version, $target) {
	my @filelist = ( "/usr/share/gforge/plugins/$pluginname/lib/$pluginname-init.sql" ) ;

	foreach my $file (@filelist) {
	    debug "Processing $file" ;
	    @reqlist = @{ &parse_sql_file ($file) } ;

	    foreach my $s (@reqlist) {
		$query = $s ;
		# debug $query ;
		$sth = $dbh->prepare ($query) ;
		$sth->execute () ;
		$sth->finish () ;
	    }
	}
	@reqlist = () ;

	&update_db_version ($target) ;
	debug "Committing." ;
	$dbh->commit () ;
    }

    debug "It seems your database install/upgrade went well and smoothly.  That's cool." ;
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

sub is_lesser ( $$ ) {
    my $v1 = shift || 0 ;
    my $v2 = shift || 0 ;

    my $rc = system "dpkg --compare-versions $v1 lt $v2" ;

    return (! $rc) ;
}

sub is_greater ( $$ ) {
    my $v1 = shift || 0 ;
    my $v2 = shift || 0 ;

    my $rc = system "dpkg --compare-versions $v1 gt $v2" ;

    return (! $rc) ;
}

sub debug ( $ ) {
    my $v = shift ;
    chomp $v ;
    print STDERR "$v\n" ;
}

sub create_metadata_table ( $ ) {
    my $v = shift || "0" ;
    my $tablename = "plugin_" .$pluginname . "_meta_data" ;
    # Do we have the metadata table?

    $query = "SELECT count(*) FROM pg_class WHERE relname = '$tablename' and relkind = 'r'";
    # debug $query ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    # Let's create this table if we have it not

    if ($array [0] == 0) {
	debug "Creating $tablename table." ;
	$query = "CREATE TABLE $tablename (key varchar primary key, value text not null)" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }

    $query = "SELECT count(*) FROM $tablename WHERE key = 'db-version'";
    # debug $query ;
    $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    # Empty table?  We'll have to fill it up a bit

    if ($array [0] == 0) {
	debug "Inserting first data into $tablename table." ;
	$query = "INSERT INTO $tablename (key, value) VALUES ('db-version', '$v')" ;
	# debug $query ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	$sth->finish () ;
    }
}

sub update_db_version ( $ ) {
    my $v = shift or die "Not enough arguments" ;
    my $tablename = "plugin_" .$pluginname . "_meta_data" ;

    debug "Updating $tablename table." ;
    $query = "UPDATE $tablename SET value = '$v' WHERE key = 'db-version'" ;
    # debug $query ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    $sth->finish () ;
}

sub get_db_version () {
    my $tablename = "plugin_" .$pluginname . "_meta_data" ;

    $query = "SELECT value FROM $tablename WHERE key = 'db-version'" ;
    # debug $query ;
    my $sth = $dbh->prepare ($query) ;
    $sth->execute () ;
    my @array = $sth->fetchrow_array () ;
    $sth->finish () ;

    my $version = $array [0] ;

    return $version ;
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

sub bump_sequence_to ( $$ ) {
    my ($sth, @array, $seqname, $targetvalue) ;

    $seqname = shift ;
    $targetvalue = shift ;

    do {
	$query = "select nextval ('$seqname')" ;
	$sth = $dbh->prepare ($query) ;
	$sth->execute () ;
	@array = $sth->fetchrow_array () ;
	$sth->finish () ;
    } until $array[0] >= $targetvalue ;
}
