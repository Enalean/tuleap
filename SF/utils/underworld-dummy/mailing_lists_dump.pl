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
# LJ email column extracted as well to get the e-mail address
$query = "SELECT user.user_name, user.email, mail_group_list.list_name, mail_group_list.password, mail_group_list.is_public, mail_group_list.status , mail_group_list.description FROM mail_group_list,user WHERE mail_group_list.list_admin=user.user_id";
$c = $dbh->prepare($query);
$c->execute();
while(my ($list_admin, $list_admin_email, $list_name, $list_password, $list_is_public, $list_status, $list_desc) = $c->fetchrow()) {

  # replace ':' in the description with HTML entities &#58;
  $list_desc =~ s/:/&#58;/g;
  $new_list = "$list_name:$list_admin:$list_admin_email:$list_password:$list_is_public:$list_status:$list_desc\n";

	push @list_array, $new_list;
}

# Now write out the files
write_array_file($dump_dir."/list_dump", @list_array);
