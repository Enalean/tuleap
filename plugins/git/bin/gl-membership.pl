#!/usr/bin/perl -UT

# Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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
# along with Tuleap; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

use strict;
use warnings;

# Set default path (required by taint mode)
$ENV{'PATH'} = '/usr/bin:/bin';

# Validate first arg
my $username = '';
if ($#ARGV == 0 && $ARGV[0] =~ /^([A-Za-z0-9-_.]+)$/) {
    $username = $1;
} else {
    print "Usage: gl-membership.pl username\n";
    exit 1;
}

# get PHP_PARAMS variable from php-laucher.sh
my $PHP_PARAMS="";
open(PHP_LAUNCHER, "</usr/share/tuleap/src/utils/php-launcher.sh");
while (<PHP_LAUNCHER>) {
    if (m/^[ ]*PHP_PARAMS="(.*)"$/) {
        $PHP_PARAMS=$1
    }
}
close(PHP_LAUNCHER);

exec "/opt/remi/php73/root/usr/bin/php -d error_reporting=0 $PHP_PARAMS /usr/share/tuleap/plugins/git/bin/gl-membership.php $username";