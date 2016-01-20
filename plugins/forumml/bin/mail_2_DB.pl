#!/usr/bin/perl -UT

# Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
#
# Originally written by Jean-Philippe Giola, 2005
#
# This file is a part of codendi.
#
# codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with codendi; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

# Taint mode enabled

# mailing-list name should contain only alphabetical characters, '-' and '.' 
sub validate_listname {
    my $arg = shift;
    my $listname = "";
    my $match = 0;

    if($arg =~ /^([-.\w]+)$/) {
        if ($match == 0) {
	    $listname = $1;
	}
    }
    return $listname;
}

use strict;

# Set default path (required by taint mode)
$ENV{'PATH'} = '/usr/bin:/bin';

# Hook log file
my $logfile = "/var/log/codendi/forumml_hook.log";

# Redirect outputs
open STDOUT, ">>", $logfile or die "cannot append to '$logfile': $!\n";
open STDERR, ">&STDOUT" or die "cannot append STDERR to STDOUT: $!\n";

# Search if there are lists we shouldn't treat
my $conf = '/etc/codendi/plugins/forumml/etc/forumml.inc';
if (-f $conf) {
    # Get the variable defined in forumml.inc
    my @exc_lists;
    open(FORUMML_INC, "<$conf");
    while (<FORUMML_INC>) {
	if (m/^\$forumml_excluded_lists[ ]*=[ ]*"(.*)"[ ]*;[ ]*$/) {
	    @exc_lists = split(/[ ]*,[ ]*/, $1);
	}
    }
    close(FORUMML_INC);

    # Test if given list is excluded or not
    foreach my $list (@exc_lists) {
	if ($list eq $ARGV[0]) {
	    exit 2;
	}
    }
}

# First argument is mandatory (list name)
my $listname = $ARGV[0];
chomp($listname);
if($listname eq "") {
    exit 1
}

# Get mail from STDIN, store it in a temporary file, then pass it to php script
my $range = 100;
my $random = int(rand($range));
my $temp = "mail_tmp_".$random."_".time();
my $path = "/var/run/forumml/".$temp;
open(OUT, ">>$path");
while (defined($_ = <STDIN>)) {
    print OUT $_;
}
close(OUT);

# Get PHP_PARAMS variable from php-laucher.sh
my $PHP_PARAMS="";
open(PHP_LAUNCHER, "</usr/share/codendi/src/utils/php-launcher.sh");
while (<PHP_LAUNCHER>) {
    if (m/^[ ]*PHP_PARAMS="(.*)"$/) {
	$PHP_PARAMS=$1
    }
}
close(PHP_LAUNCHER);

# get PHP_PARAMS variable from php-laucher.sh
my $PHP_PARAMS="";
open(PHP_LAUNCHER, "</usr/share/codendi/src/utils/php-launcher.sh");
while (<PHP_LAUNCHER>) {
    if (m/^[ ]*PHP_PARAMS="(.*)"$/) {
	$PHP_PARAMS=$1
    }
}
close(PHP_LAUNCHER);

# store mail in ForumML DB
exec "/usr/bin/php $PHP_PARAMS /usr/share/codendi/plugins/forumml/bin/mail_2_DB.php $listname 1 $temp";

close STDOUT;
close STDERR;
