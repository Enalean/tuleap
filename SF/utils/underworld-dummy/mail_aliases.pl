#!/usr/bin/perl
#
# $Id$
#
use DBI;

require("../include.pl");  # Include all the predefined functions

$admin_list = ("precision,fusion94,dtype,bigdisk");
$mailman_dir = "/home/mailman";

&db_connect;

@alias_array = open_array_file("aliases.zone");

push @alias_array, "\n\n### Begin Mailing List Aliases ###\n\n";

# Determine the name of the mailman wrapper
# Before 2.1  the name is wrapper, after it's mailman
if ( -x "$mailman_dir/mail/wrapper") {
  $mm_wrapper = "$mailman_dir/mail/wrapper";
} else {
  $mm_wrapper = "$mailman_dir/mail/mailman";
}

# Select mailing list that public or private but not 'Deleted'
$query = "SELECT list_name from mail_group_list where is_public IN (0,1)";
$c = $dbh->prepare($query);
$c->execute();
while(my ($list_name) = $c->fetchrow()) {
  $list_name =~ tr/A-Z/a-z/;
  $list_name =~ s/ //g;
  # Mailman 2.1 aliases
  push @alias_array, sprintf("%-50s%-10s","$list_name:", "\"|$mm_wrapper post $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-admin:", "\"|$mm_wrapper admin $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-bounces:", "\"|$mm_wrapper bounces $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-confirm:", "\"|$mm_wrapper confirm $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-join:", "\"|$mm_wrapper join $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-leave:", "\"|$mm_wrapper leave $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-owner:", "\"|$mm_wrapper owner $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-request:", "\"|$mm_wrapper request $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-subscribe:", "\"|$mm_wrapper subscribe $list_name\"\n");
  push @alias_array, sprintf("%-50s%-10s","$list_name-unsubscribe:", "\"|$mm_wrapper unsubscribe $list_name\"\n");
}




push @alias_array, "\n\n### Begin User Aliases ###\n\n";

$query = "SELECT user_name,email FROM user WHERE status = \"A\"";

$c = $dbh->prepare($query);
$c->execute();
while(($username, $email) = $c->fetchrow()) {
	if ($email) {
		if (!($admin_list =~ /.*$username*./)) {
			push @alias_array, sprintf("%-50s%-10s","$username:", "$email\n");
		}
	}
}

# Retrieve the dummy's home directory
($name,$passwd,$uid,$gid,$quota,$comment,$gcos,$dir,$shell,$expire) = getpwnam("dummy");

write_array_file("$dir/dumps/aliases", @alias_array);
