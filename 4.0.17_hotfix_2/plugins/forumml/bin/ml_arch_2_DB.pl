#!/usr/bin/perl

#
# Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
#
# Originally written by Mohamed CHAARI, 2007
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


=pod

This script aims at achieving the migration of archives, of all _active_ mailing-lists, to the ForumML database.
Only projects that enabled ForumML plugin are concerned by this migration.

=cut

# Search if there are lists we shouldn't treat
my $conf = '/etc/codendi/plugins/forumml/etc/forumml.inc';
my %excluded_list;
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
	$excluded_list{$list} = 0;
    }
}

# Get PHP_PARAMS variable from php-laucher.sh
my $PHP_PARAMS="";
open(PHP_LAUNCHER, "</usr/share/codendi/src/utils/php-launcher.sh");
while (<PHP_LAUNCHER>) {
    if (m/^[ ]*PHP_PARAMS="(.*)"$/) {
	$PHP_PARAMS=$1
    }
}
close(PHP_LAUNCHER);

#use strict;
use DBI;

require "/usr/share/codendi/src/utils/include.pl";
&db_connect;

# get all active mailing-lists
my $query = "SELECT list_name, group_id FROM mail_group_list WHERE status = 1";
my $req = $dbh->prepare($query);
$req->execute();
while (my ($list_name,$group_id) = $req->fetchrow()) {
    if(! exists $excluded_list{$list_name}) {
	print "Processing ".$list_name." mailing-list ... \n";
	system("/usr/bin/php $PHP_PARAMS /usr/share/codendi/plugins/forumml/bin/mail_2_DB.php $list_name 2");
    }
}
