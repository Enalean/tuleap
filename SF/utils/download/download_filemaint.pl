#!/usr/bin/perl -w
#
# $Id$
#
use strict;
#
# variable dec
my ($incoming_dir, $waiting_files, $waiting_files_work, $deleting_files, $deleting_files_work, $file, $project, $project_dir);


#  location of the download/upload directories
$project_dir="/home/ftp/pub/sourceforge";
$incoming_dir="/home/ftp/incoming";

#list of files to be moved to their project home
#$waiting_files="$incoming_dir/.waiting_files";
#$waiting_files_work="$incoming_dir/$waiting_files.work";

# list of files to be deleted
$deleting_files="$incoming_dir/.delete_files";
$deleting_files_work="$incoming_dir/.delete_files.work";

#move the waiting file to a temp work file
#print `/bin/mv -f $waiting_files $waiting_files_work`;

#create a new waiting files dir so an exploit can't be uploaded
#print `/bin/touch $waiting_files`;
#print `/bin/chown nobody:nobody $waiting_files`;

#move the list of files to delete to a temp work file
print `/bin/mv -f $deleting_files $deleting_files_work`;
print `/bin/touch $deleting_files`;
print `/bin/chown nobody:nobody $deleting_files`;

#
# move all files in .waiting_files to the project directory
#
#open(WAITING_FILES, "< $waiting_files_work" ) || die "Cannot open $waiting_files_work";
#FILE:
#while (<WAITING_FILES>) {

#	($file, $project) = split("::", $_);
	
#	if (!-f "$incoming_dir/$file") {
#		print "File doesn't exists\n";
#		next FILE
#	} else {
#		print `/bin/mv $waiting_files/.$file $project_dir/$project/$file`;
		#
		#  need to remove the period from the file....
		#
#	}
#}
#close(WAITING_FILES);

#
#  delete all files in the .delete_files
#
open(WAITING_FILES, "< $deleting_files_work" ) || die "Cannot open $deleting_files_work";
FILE:
while (<WAITING_FILES>) {

	($file, $project) = split("::", $_);

	if (!-f "$project_dir/$project/$file") {
		print "File doesn't exist: $project_dir/$project/$file\n";
		next FILE
	} else {
		print `/bin/rm -f $project_dir/$project/$file`;
	}       
}       
close(WAITING_FILES);

##
## EOF
##
