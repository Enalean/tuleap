#!/usr/bin/perl
#
# $Id$
#
use DBI;

require("../include.pl");  # Include all the predefined functions

$admin_list = ("precision,fusion94,dtype,bigdisk");

&db_connect;

@alias_array = open_array_file("aliases.zone");

push @alias_array, "\n\n### Begin Mailing List Aliases ###\n\n";

$query = "SELECT list_name from mail_group_list";
$c = $dbh->prepare($query);
$c->execute();
while(my ($list_name) = $c->fetchrow()) {
		$list_name =~ tr/A-Z/a-z/;
		$list_name =~ s/ //g;

		push @alias_array, sprintf("%-50s%-10s","$list_name\@lists.codex.xerox.com:", "\"|/usr/local/mailman/mail/wrapper post $list_name\"\n");
		push @alias_array, sprintf("%-50s%-10s","$list_name-admin\@lists.codex.xerox.com:", "\"|/usr/local/mailman/mail/wrapper mailowner $list_name\"\n");
		push @alias_array, sprintf("%-50s%-10s","$list_name-request\@lists.codex.xerox.com:", "\"|/usr/local/mailman/mail/wrapper mailcmd $list_name\"\n");
}




#push @alias_array, "\n\n### Begin User Aliases ###\n\n";

#$query = "SELECT user_name,email FROM user WHERE status = \"A\"";

#$c = $dbh->prepare($query);
#$c->execute();
#while(($username, $email) = $c->fetchrow()) {
#	if ($email) {
#		if (!($admin_list =~ /.*$username*./)) {
#			push @alias_array, sprintf("%-50s%-10s","$username\@users.sourceforge.net:", "$email\n");
#		}
#	}
#}

write_array_file("/home/dummy/dumps/aliases", @alias_array);
