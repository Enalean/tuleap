#!/usr/bin/perl
#
# $Id$
#
# mailing_list_dump.pl - Script to suck data outta the database to be processed on the mail
#                        mail server to create mailing lists
use DBI;

require("../include.pl");  # Include all the predefined functions

my $list_array = ();

&db_connect;

# Dump the Table information
$query = "SELECT user.user_name,mail_group_list.list_name,mail_group_list.password,mail_group_list.is_public FROM mail_group_list,user WHERE mail_group_list.list_admin=user.user_id";
$c = $dbh->prepare($query);
$c->execute();
while(my ($list_name, $list_admin, $password, $status) = $c->fetchrow()) {

	$new_list = "$list_name:$list_admin:$password:$status\n";

	push @list_array, $new_list;
}


# Now write out the files
write_array_file($file_dir."list_dump", @list_array);
