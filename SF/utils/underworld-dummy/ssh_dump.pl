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
$query = "SELECT user_name,authorized_keys FROM user WHERE authorized_keys != \"\"";
$c = $dbh->prepare($query);
$c->execute();
while(my ($username, $ssh_key) = $c->fetchrow()) {

	$new_list = "$username:$ssh_key\n";

	push @ssh_array, $new_list;
}


# Now write out the files
write_array_file($file_dir."ssh_dump", @ssh_array);
