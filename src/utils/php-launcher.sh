#!/usr/bin/env bash

# Copyright (c) Enalean, 2011-Present. All Rights Reserved.
# Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
#
# Originally written by Manuel Vacelet, 2005
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
#

set -e

function findPHPCLI() {
    local php82_remi_scl='/opt/remi/php82/root/usr/bin/php'
        if [ -x "$php82_remi_scl" ]; then
            echo "$php82_remi_scl"
            return
        fi
    local php81_remi_scl='/opt/remi/php81/root/usr/bin/php'
    if [ -x "$php81_remi_scl" ]; then
        echo "$php81_remi_scl"
        return
    fi
    echo "php"
}


# PHP path and parameters
if [ -z "$PHP" ]; then
    PHP="$(findPHPCLI)"
fi

# Include path is only defined in php.conf (and not php.ini).
# It was also reported that 8MB (default memory limit) is not sufficient in some cases.
if [ -z "$PHP_PARAMS" ]; then
    PHP_PARAMS="-q -d memory_limit=256M"
fi

php_display_errors="-d error_reporting=0"
if [ "$DISPLAY_ERRORS" = true ]; then
    php_display_errors=""
fi

# Finally runs php interpreter
phpscript=$1;
shift;
exec "${PHP}" ${php_display_errors} ${PHP_PARAMS} $phpscript "$@"
