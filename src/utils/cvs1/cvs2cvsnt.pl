#!/usr/bin/perl
#
# CodeX: Breaking Down the Barriers to Source Code Sharing
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2006. All Rights Reserved
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# cvs2cvsnt: convert CodeX CVS repositories to CVSNT.
# Simply update the CVSROOT/loginfo file so that the format is accepted by cvsnt.
#
# WARNING: this script backs up all 'loginfo' files and replaces them with a new file


# Make sure umask is properly positioned for the
# entire session.
umask 002;

require("../include.pl");  # Include all the predefined functions and variables

$server_url=$sys_default_domain;
my ($cxname) = get_codex_user();

my $MARKER_BEGIN = "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
my $MARKER_END   = "# END OF NEEDED CODEX BLOCK";


@projects=`ls -1 $cvs_prefix`;
foreach $project (@projects) {
  chomp $project;
  $projectdir="$cvs_prefix/$project";
  print "Processing $projectdir\n";
  if (! -d "$projectdir/CVSROOT")
    {
      next;
    }
  $projectdir=~m|$cvs_prefix/(.*)|;
  $project=$1;
  print "Processing $project\n";
  `mv $projectdir/CVSROOT/loginfo $projectdir/CVSROOT/loginfo.cvs.old`;
  system("echo \"DEFAULT chgrp -f -R  $project $projectdir\" > $projectdir/CVSROOT/loginfo");
  system("echo \"$MARKER_BEGIN\" >> $projectdir/CVSROOT/loginfo");
  system("echo \"ALL $codex_bin_prefix/log_accum -T $project -C $project -U http://$server_url/cvs/viewcvs.php/ -s %{sVv}\" >> $projectdir/CVSROOT/loginfo");
  system("echo \"$MARKER_END\" >> $projectdir/CVSROOT/loginfo");
  system("cd $projectdir/CVSROOT; rcs -q -l loginfo; ci -q -m\"CVSNT migration\" loginfo; co -q loginfo");
  # set group ownership
  system("chown -R $cxname:$project $projectdir");
  system("chmod g+rw $projectdir");

}
