#!/usr/bin/perl
# Modified 9 Nov. 2000 by John Stidd to eliminate www.geocrawler.com interaction
# $Id$
#
# mailing_lists_create.pl - Creates mailing lists based off of the file dump
require("include.pl");  # Include all the predefined functions and variables

my $mailman_dir = "/usr/local/mailman";

my $list_file = $file_dir . "list_dump";
my ($listname, $listadmin, $listpassword, $list_dir, $liststatus);

# Open up all the files that we need.
@listfile_array = open_array_file($list_file);

#
# Loop through @listfile_array and deal w/ users.
#
print ("\n\n    Processing Mailing Lists\n\n");
while ($ln = pop(@listfile_array)) {
  # LJ new field added to get the email of the admin
  # Lj we do not use email aliases on CodeX

	($list_name, $list_admin, $list_admin_email, $list_password, $list_status) = split(":", $ln);

	$list_dir = "$mailman_dir/lists/$list_name";

	if (! -d $list_dir) {
		print ("Creating Mailing List: $list_name\n");

		system("$mailman_dir/bin/newlist $list_name $list_admin_email $list_password >/dev/null 2>&1");

		# Deactivate monthly reminders by default
		system("echo \"send_reminders = 0\n\" > /tmp/send_reminders.in");
		system("$mailman_dir/bin/config_list -i /tmp/send_reminders.in $list_name");

#		system("echo \"archiver\@db.geocrawler.com\" | $mailman_dir/bin/add_members --welcome-msg=n --non-digest-members-file - $listname >/dev/null 2>&1");

#		system("cd ~/logs ; /usr/bin/wget http://www.geocrawler.com/addsourceforge.php3?addlist=$listname&status=$status >/dev/null 2>&1");
	}
}
