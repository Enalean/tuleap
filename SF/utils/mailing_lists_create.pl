#!/usr/bin/perl
# Modified 9 Nov. 2000 by John Stidd to eliminate www.geocrawler.com interaction
# $Id$
#
# mailing_lists_create.pl - Creates mailing lists based off of the file dump
require("include.pl");  # Include all the predefined functions and variables

my $mailman_dir = "/usr/local/mailman";

my $list_file = $file_dir . "list_dump";
my ($list_name, $list_admin, $list_admin_email, $list_password, $list_is_public, $list_status, $list_desc);

my $list_dir;

# load local.inc variables
&load_local_config();

# Open up all the files that we need.
@listfile_array = open_array_file($list_file);

#
# Loop through @listfile_array and deal w/ users.
#
print ("\n\n    Processing Mailing Lists\n\n");
while ($ln = pop(@listfile_array)) {

  chop $ln; #remove newline
  ($list_name, $list_admin, $list_admin_email, $list_password, $list_is_public, $list_status, $list_desc) = split(":", $ln);

  $list_dir = "$mailman_dir/lists/$list_name";

  # if the email of the administrator is empty then forge it with the
  # admin user name and the domain name
  if ($list_admin_email == "") {
    # remove port number in default domain
    ($domain,$port) = split(":",$sys_default_domain);
    $list_admin_email = $list_admin."@".$domain;
  }

  # restore columns after line split. Escape single quote.
  $list_desc =~ s/&#58;/:/g;
  $list_desc =~ s/'/\\'/g;

  if (! -d $list_dir && $list_is_public != 9 ) {
    # Create the list if it doesn't exist and status is not 'Deleted'
    print ("Creating Mailing List: $list_name\n");

    system("$mailman_dir/bin/newlist -q $list_name $list_admin_email $list_password >/dev/null");

     # Setup the description and deactivate monthly reminders by default
    system("echo \"send_reminders = 0\n\" > /tmp/send_reminders.in");
    system("echo \"description = '$list_desc'\n\" >> /tmp/send_reminders.in");

    system("$mailman_dir/bin/config_list -i /tmp/send_reminders.in $list_name");

  } elsif ( -d $list_dir && $list_is_public == 9 ) {
     # Delete the mailing list if asked to and the mailing exists (archive deleted as well)
     print ("Deleting Mailing List: $list_name\n");
     system("$mailman_dir/bin/rmlist -a $list_name >/dev/null");
  }
}
