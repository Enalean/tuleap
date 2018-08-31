#!/usr/bin/perl
# Copyright (c) Enalean, 2017-2018. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#

#use strict;
#use warnings;
use Getopt::Std;
use Carp;
use DBI;
use DBI qw(:sql_types);

# Svnlook path.
my $svnlook = "/usr/bin/svnlook";

$utils_path = $ENV{'CODENDI_UTILS_PREFIX'} || "/usr/share/tuleap/src/utils";
require $utils_path."/include.pl";
require $utils_path."/group.pl";
&db_connect;

#arg parsing
my %options = ();
getopts('r:l:p:q', \%options);

my $from_rev         = '';
my $to_rev           = '';
my $repository       = '';
my $quiet            = 0;

($from_rev,$to_rev)     = split(/:/, $options{r}) if defined $options{r};
$repository             = $options{p} if defined $options{p};
$quiet                  = $options{q};

$from_rev               = int $from_rev;
$to_rev                 = int $to_rev;

if ( ! -d $repository  ) {
   die( '[ERROR] Repository directory does not exist' );
}

# Check rev range

if ( !$from_rev ) {
    # retrieve the group_id
    my $gname = $repository;
    $gname =~ s|.*/||; # Remove everything until the last slash
    my $group_id = &set_group_info_from_name($gname);

    my $q_max_rev = "SELECT MAX(revision) AS r FROM svn_commits c WHERE group_id=?";
    my $c_max_rev = $dbh->prepare($q_max_rev);
    $c_max_rev->bind_param(1, $group_id, SQL_INTEGER);
    my $r_max_rev = $c_max_rev->execute();
    if ($r_max_rev && ($c_max_rev->rows eq 1)) {
	my $row = $c_max_rev->fetchrow_hashref;
	$from_rev = $row->{'r'} + 1;
    } else {
	$from_rev = 1;
    }
}

if ( !$to_rev ) {
    my @svnlooklines = &read_from_process($svnlook, 'youngest', $repository);
    $to_rev = shift @svnlooklines;
}

if ( !$from_rev || !$to_rev) {
    die( &usage );
}

if ($from_rev > $to_rev) {
    print STDERR "No revision to import (DB: ".($from_rev-1).", svn: $to_rev)\n";
    exit 0;
}

if (!$quiet) {
    my $answer;
    print STDERR '[WARNING] Notification will be sent, check you deactivate it before running this script, continue ? [y/N]: ';
    chomp($answer = <STDIN>);
    close(STDIN);
    if ( $answer ne 'y' ) {
	die('Aborted');
    }
}

print "Import $repository from $from_rev to $to_rev\n";
for ( my $i=$from_rev; $i<$to_rev+1; $i++ ) {
    print 'Processing revision '."$i\n";
    `perl /usr/share/codendi/src/utils/svn/commit-email.pl $repository $i`;
}

##
# FUNCTIONS
##

sub usage {
    return "Usage : $0 [-r rev1:rev2] [-q] -p repo_sys_path
  -p repo      Path to the repository
  -r rev1:rev2 Specify the revisions to import (Optionnal)
  -q           Quiet, do not ask questions (Optionnal)

By default (without -r option) $0 tries to find the right range of revisions
to import depending of what stand in the subversion repository and in the DB.";
}

# Start a child process safely without using /bin/sh.
sub safe_read_from_pipe
{
  unless (@_)
    {
      croak "$0: safe_read_from_pipe passed no arguments.\n";
    }

  my $pid = open(SAFE_READ, '-|');
  unless (defined $pid)
    {
      die "$0: cannot fork: $!\n";
    }
  unless ($pid)
    {
      open(STDERR, ">&STDOUT")
        or die "$0: cannot dup STDOUT: $!\n";
      exec(@_)
        or die "$0: cannot exec `@_': $!\n";
    }
  my @output;
  while (<SAFE_READ>)
    {
      s/[\r\n]+$//;
      push(@output, $_);
    }
  close(SAFE_READ);
  my $result = $?;
  my $exit   = $result >> 8;
  my $signal = $result & 127;
  my $cd     = $result & 128 ? "with core dump" : "";
  if ($signal or $cd)
    {
      warn "$0: pipe from `@_' failed $cd: exit=$exit signal=$signal\n";
    }
  if (wantarray)
    {
      return ($result, @output);
    }
  else
    {
      return $result;
    }
}

# Use safe_read_from_pipe to start a child process safely and return
# the output if it succeeded or an error message followed by the output
# if it failed.
sub read_from_process
{
  unless (@_)
    {
      croak "$0: read_from_process passed no arguments.\n";
    }
  my ($status, @output) = &safe_read_from_pipe(@_);
  if ($status)
    {
      return ("$0: `@_' failed with this output:", @output);
    }
  else
    {
      return @output;
    }
}

1;
