#!/usr/bin/perl
# Modified 9 Nov. 2000 by John Stidd to eliminate www.geocrawler.com interaction
# 
#
# mailing_lists_create.pl - Creates mailing lists based off of the file dump
require("include.pl");  # Include all the predefined functions and variables

my $list_file = $dump_dir . "/list_dump";
my ($list_name, $list_admin, $list_admin_email, $list_password, $list_is_public, $list_status, $list_desc);

my $list_dir;

print ("\n\n    Processing Mailing Lists\n\n");
# Open up all the files that we need.
if(! -f $list_file) {
    print "No mailing-lists available\n";
    exit 1;
}
@listfile_array = open_array_file($list_file);

#
# Loop through @listfile_array and deal w/ users.
#
while ($ln = pop(@listfile_array)) {

  chop $ln; #remove newline
  ($list_name, $list_admin, $list_admin_email, $list_password, $list_is_public, $list_status, $list_desc) = split(":", $ln);

  $list_dir = "$mailman_list_dir/$list_name";

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

    system("$mailman_bin_dir/newlist -q $list_name $list_admin_email $list_password >/dev/null");

     # Define encoding. See SR #764
    system("echo \"# coding=ISO-8859-1\n\" > $tmp_dir/send_reminders.in");

     # Setup the description and deactivate monthly reminders by default
    system("echo \"send_reminders = 0\n\" >> $tmp_dir/send_reminders.in");
    system("echo \"description = '$list_desc'\n\" >> $tmp_dir/send_reminders.in");

    system("$mailman_bin_dir/config_list -i $tmp_dir/send_reminders.in $list_name");

  } elsif ( -d $list_dir && $list_is_public == 9 ) {
     # Delete the mailing list if asked to and the mailing exists (archive deleted as well)
     print ("Deleting Mailing List: $list_name\n");
     system("$mailman_bin_dir/rmlist -a $list_name >/dev/null");
  }
}
