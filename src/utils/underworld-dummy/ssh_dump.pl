#!/usr/bin/perl
#
# $Id$
#
# ssh_dump.pl - Script to suck data outta the database to be processed by ssh_create.pl
#
use DBI;

require("../include.pl");  # Include all the predefined functions

my $ssh_array = ();

&db_connect;

# Dump the Table information
# This query is too lax - we must strip out all null values
# and also none unix active  users
#$query = "SELECT user_name,authorized_keys FROM user WHERE authorized_keys != \"\"";
$query = "SELECT user_name,authorized_keys FROM user WHERE unix_status = \"A\" and authorized_keys != \"\" and authorized_keys IS NOT NULL";
$c = $dbh->prepare($query);
$c->execute();
while(my ($username, $ssh_key) = $c->fetchrow()) {

	$new_list = "$username:$ssh_key\n";

	push @ssh_array, $new_list;
}


# Now write out the files
write_array_file($dump_dir."/ssh_dump", @ssh_array);
