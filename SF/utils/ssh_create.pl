#!/usr/bin/perl
#
# $Id$
#
# ssh_create.pl - Dumps SSH authorized_keys into users homedirs on the cvs server.
#

require("include.pl");  # Include all the predefined functions and variables

my @ssh_key_file = open_array_file("/home/dummy/dumps/ssh_dump");
my ($username, $ssh_keys, $ssh_dir);

print("\n\n	Processing Users\n\n");
while ($ln = pop(@ssh_key_file)) {
	chop($ln);

	($username, $ssh_key) = split(":", $ln);

	$ssh_key =~ s/\#\#\#/\n/g;
	$username =~ tr/[A-Z]/[a-z]/;

	push @user_authorized_keys, $ssh_key . "\n";

	$ssh_dir = "/home/users/$username/.ssh";

	if (! -d $ssh_dir) {
		mkdir $ssh_dir, 0755;
	}

	print("Writing authorized_keys for $username: ");

	write_array_file("$ssh_dir/authorized_keys", @user_authorized_keys);
	system("chown $username:$username ~$username");
	system("chown $username:$username $ssh_dir");
	system("chmod 0644 $ssh_dir/authorized_keys");
	system("chown $username:$username $ssh_dir/authorized_keys");

	print ("Done\n");

	undef @user_authorized_keys;
}
