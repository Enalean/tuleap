#!/usr/bin/perl -UT

#
# Copyright (c) STMicroelectronics, 2005. All Rights Reserved.

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
 #
 # $Id$
 #

# Taint mode enabled

# mailing-list name should contain only alphabetical characters, '-' and '.' 
sub validate_listname {
    my $arg = shift;
    my $listname = "";
    my $match = 0;

    # special system mailing-lists should not be included
    my @exclude_ml = ("crolles.codex.system", "grenoble.codex.system");

    if($arg =~ /^([-.\w]+)$/) {
        foreach (@exclude_ml) {
        	if ($_ eq $1) {
    			$match = 1;
        	}
    	}
        if ($match == 0) {
	        $listname = $1;
	    }
    }
    return $listname;
}

use strict;

$ENV{'PATH'} = '/usr/bin:/bin';

my $logfile = "/var/log/codendi/forumml_hook.log";

my $listname = validate_listname($ARGV[0]);
if($listname eq "") {
    exit 1;
}

# get mail from STDIN, store it in a temporary file, then pass it to php script
my $range = 100;
my $random = int(rand($range));
my $temp = "mail_tmp_".$random."_".time();
my $path = "/var/run/forumml/".$temp;
open(OUT, ">>$path");
while (defined($_ = <STDIN>)) {
    print OUT $_;
}
close(OUT);

open STDOUT, ">>", $logfile or die "cannot append to '$logfile': $!\n";
open STDERR, ">&STDOUT" or die "cannot append STDERR to STDOUT: $!\n";

# store mail in ForumML DB
exec "/usr/share/codendi/src/utils/php-launcher.sh  /usr/share/codendi/plugins/forumml/bin/mail_2_DB.php $listname 1 $temp";

close STDOUT;
close STDERR;
