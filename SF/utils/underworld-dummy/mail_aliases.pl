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

		push @alias_array, sprintf("%-50s%-10s","$list_name:", "\"|/usr/local/mailman/mail/wrapper post $list_name\"\n");
		push @alias_array, sprintf("%-50s%-10s","$list_name-admin:", "\"|/usr/local/mailman/mail/wrapper mailowner $list_name\"\n");
		push @alias_array, sprintf("%-50s%-10s","$list_name-request:", "\"|/usr/local/mailman/mail/wrapper mailcmd $list_name\"\n");
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
