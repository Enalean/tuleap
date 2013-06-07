#!/usr/bin/perl -UT

# Copyright (c) Enalean 2013. All Rights Reserved.
#
# This file is a part of Tuleap.
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
# along with Tuleap; If not, see <http://www.gnu.org/licenses/>.


use strict;
use warnings;

# Set default path (required by taint mode)
$ENV{'PATH'} = '/usr/bin:/bin';

# get PHP_PARAMS variable from php-laucher.sh
my $PHP_PARAMS="";
open(PHP_LAUNCHER, "</usr/share/codendi/src/utils/php-launcher.sh");
while (<PHP_LAUNCHER>) {
    if (m/^[ ]*PHP_PARAMS="(.*)"$/) {
        $PHP_PARAMS=$1
    }
}
close(PHP_LAUNCHER);
exec "/usr/bin/php $PHP_PARAMS /usr/share/codendi/plugins/git/hooks/post-receive.php $ARGV[0] $ARGV[1] $ARGV[2] $ARGV[3] $ARGV[4]";
