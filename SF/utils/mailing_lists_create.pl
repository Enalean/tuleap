#!/usr/bin/perl
#
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
	($listadmin, $listname, $listpassword, $liststatus) = split(":", $ln);

	$list_dir = "$mailman_dir/lists/$listname";

	if (! -d $list_dir) {
		print ("Creating Mailing List: $listname\n");

		system("$mailman_dir/bin/newlist $listname $listadmin\@users.sourceforge.net $listpassword >/dev/null 2>&1");

		system("echo \"archiver\@db.geocrawler.com\" | $mailman_dir/bin/add_members --welcome-msg=n --non-digest-members-file - $listname >/dev/null 2>&1");

		system("cd ~/logs ; /usr/bin/wget http://www.geocrawler.com/addsourceforge.php3?addlist=$listname&status=$status >/dev/null 2>&1");
	}
}
