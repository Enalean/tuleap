#!/usr/bin/perl
#
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Codendi.
#
# Codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Codendi. If not, see <http://www.gnu.org/licenses/>.
#
#
# cvs2cvsnt: convert Codendi CVS repositories to CVSNT.
# Simply update the CVSROOT/loginfo file so that the format is accepted by cvsnt.
#
# WARNING: this script backs up all 'loginfo' files and replaces them with a new file


# Make sure umask is properly positioned for the
# entire session.
umask 002;

require("../include.pl");  # Include all the predefined functions and variables

$server_url=$sys_default_domain;
my ($cxname) = get_codendi_user();

my $MARKER_BEGIN = "# !!! Codendi Specific !!! DO NOT REMOVE (NEEDED CODENDI MARKER)";
my $MARKER_END   = "# END OF NEEDED CODENDI BLOCK";


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
  system("echo \"ALL sudo -u codendiadm - E $codendi_bin_prefix/log_accum -T $project -C $project -U http://$server_url/cvs/viewvc.php/ -s %{sVv}\" >> $projectdir/CVSROOT/loginfo");
  system("echo \"$MARKER_END\" >> $projectdir/CVSROOT/loginfo");
  system("cd $projectdir/CVSROOT; rcs -q -l loginfo; ci -q -m\"CVSNT migration\" loginfo; co -q loginfo");
  # set group ownership
  system("chown -R $cxname:$project $projectdir");
  system("chmod g+rw $projectdir");

}
