#!/usr/bin/perl
#
# $Id$
#
# cvs2cvsnt: convert CodeX CVS repositories to CVSNT.
# Simply update the CVSROOT/loginfo file so that the format is accepted by cvsnt.
#
# WARNING: this script backs up all 'loginfo' files and replaces them with a new file


# Make sure umask is properly positioned for the
# entire session.
umask 002;

require("../include.pl");  # Include all the predefined functions and variables
load_local_config();


$cvsroot_path='/cvsroot';
$server_url=$sys_default_domain;
my ($cxname) = get_codex_user();

my $MARKER_BEGIN = "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
my $MARKER_END   = "# END OF NEEDED CODEX BLOCK";


@projects=`ls -1 $cvsroot_path`;
foreach $project (@projects) {
  chomp $project;
  $projectdir="$cvsroot_path/$project";
  print "Processing $projectdir\n";
  if (! -d "$projectdir/CVSROOT")
    {
      next;
    }
  $projectdir=~m|$cvsroot_path/(.*)|;
  $project=$1;
  print "Processing $project\n";
  `mv $projectdir/CVSROOT/loginfo $projectdir/CVSROOT/loginfo.cvs.old`;
  system("echo \"DEFAULT chgrp -f -R  $project $projectdir\" > $projectdir/CVSROOT/loginfo");
  system("echo \"$MARKER_BEGIN\" >> $projectdir/CVSROOT/loginfo");
  system("echo \"ALL /usr/local/bin/log_accum -T $project -C $project -U http://$server_url/cvs/viewcvs.php/ -s %{sVv}\" >> $projectdir/CVSROOT/loginfo");
  system("echo \"$MARKER_END\" >> $projectdir/CVSROOT/loginfo");
  system("cd $projectdir/CVSROOT; rcs -q -l loginfo; ci -q -m\"CVSNT migration\" loginfo; co -q loginfo");
  # set group ownership, sourceforge user
  system("chown -R $cxname:$project $projectdir");
  system("chmod g+rw $projectdir");

}
