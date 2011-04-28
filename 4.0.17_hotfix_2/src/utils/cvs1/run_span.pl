#!/usr/bin/perl 
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

use Time::Local;

$script		= "cvs_history_parse.pl";
$span		= $ARGV[0];
$year		= $ARGV[1];
$month		= $ARGV[2];
$day		= $ARGV[3];

$| = 0;
print "Processing $span day span from $month/$day/$year ...\n";

for ( $i = 1; $i <= $span; $i++ ) {

	$command = "perl $script $year $month $day";
	print STDERR "Running \'$command\' from the current directory...\n";
	print STDERR `$command`;

	($year,$month,$day) = (gmtime( timegm(0,0,0,$day + 1,$month - 1,$year - 1900) ))[5,4,3];
	$year += 1900;
	$month += 1;
}

