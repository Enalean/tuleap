#!/usr/bin/perl
#
# $Id$
#
# dump_database.pl - script to dump data from the database to flat files so the ofher perl
#		     scripts can process it without needing to access the database.
use DBI;

require("../include.pl");  # Include all the predefined functions

my $user_array = ();

&db_connect;

# Dump the User Table information
my $query = "select unix_uid, unix_status, user_name, shell, unix_pw, realname from user where unix_status != \"N\"";
my $query = "select user.unix_uid, user.unix_status, user.user_name, user.shell, user.unix_pw, user.realname from user,intel_agreement where user.unix_status != 'N' AND user.user_id=intel_agreement.user_id AND intel_agreement.is_approved='1'";
my $c = $dbh->prepare($query);
$c->execute();
	
while(my ($id, $status, $username, $shell, $passwd, $realname) = $c->fetchrow()) {
	$home_dir = $homedir_prefix.$username;

	$userlist = "$id:$status:$username:$shell:$passwd:$realname\n";

	push @user_array, $userlist;
}

# Now write out the files
write_array_file($file_dir."ia64_dump", @user_array);
