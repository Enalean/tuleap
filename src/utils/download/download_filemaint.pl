#!/usr/bin/perl -w
#
# 
#
#use strict;
require("../include.pl");  # Include all the predefined functions

#
# variable dec
my ($deleting_files, $deleting_files_work, $file, $project, $time, $delete_dir);

#  location of the download/upload directories
$delete_dir = $ftp_frs_dir_prefix ."/DELETED";


# list of files to be deleted
$deleting_files = $ftp_incoming_dir ."/.delete_files";
$deleting_files_work = $ftp_incoming_dir ."/.delete_files.work";


#move the list of files to delete to a temp work file
print `/bin/mv -f $deleting_files $deleting_files_work`;
print `/bin/touch $deleting_files`;
my $codex_user = &get_codex_user();
print `/bin/chown $codex_user $deleting_files`;


#
#  move all files in the .delete_files
#
open(WAITING_FILES, "< $deleting_files_work" ) || die "Cannot open $deleting_files_work";
FILE:
while (<WAITING_FILES>) {

	($file, $project, $time) = split("::", $_);

	if ((!-f "$ftp_frs_dir_prefix/$project/$file") && (!-d "$ftp_frs_dir_prefix/$project/$file")) {
		print "$ftp_frs_dir_prefix/$project/$file doesn't exist\n";
		next FILE
	} else {
	  my (@subdirs, $endfile, $dirs);
	  @subdirs = split("/", $file);
	  $endfile = pop(@subdirs);
	  $" = '/';
          $dirs = "@subdirs";
          print `/bin/mkdir -p $delete_dir/$project/$dirs`;
	  
	  $filename = "$ftp_frs_dir_prefix/$project/$file";
	  $last_modified = (stat($filename))[9];
	  $last_accessed = (stat($filename))[8];
	  $last_ctime = (stat($filename))[10];

	  #make sure that since the deletion of the file nobody has submitted a new file with 
	  #the same filename
	  if (($last_modified >= $time) || ($last_accessed >= $time) || ($last_ctime >= $time)) {
	    print "don't delete file $project/$file (modified since deletion)\n";
	  } else {
	    print "deleting file $project/$file\n";
	    print `/bin/mv -f $ftp_frs_dir_prefix/$project/$file $delete_dir/$project/$file-$time` ;
	  } 
	}
}
close(WAITING_FILES);

#
# delete all files under DELETE that are older than 7 days
#

print `find $delete_dir -type f -mtime +7 -exec rm {} \\;`;
print `find $delete_dir -mindepth 1 -type d -empty -exec rm -R {} \\;`;


##
## EOF
##
