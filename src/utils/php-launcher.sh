#!/bin/bash

# Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

# PHP path and parameters
if [ -z "$PHP" ]; then
    PHP="/opt/remi/php56/root/usr/bin/php"
    if [ ! -x "$PHP" ]; then
        if hash php 2> /dev/null; then
            PHP="php"
        fi
    fi
fi

if [ -f "/usr/share/tuleap/VERSION" ]; then
    APP_PATH="/usr/share/tuleap"
else
    APP_PATH="/usr/share/codendi"
fi

PEAR_PATH="/usr/share/pear"

# Include path is only defined in php.conf (and not php.ini).
# It was also reported that 8MB (default memory limit) is not sufficient in some cases.
if [ -z "$PHP_PARAMS" ]; then
    PHP_PARAMS="-q -d include_path=/usr/share/php:/usr/share/pear:/usr/share/tuleap/src/www/include:/usr/share/tuleap/src:/usr/share/codendi/src/www/include:/usr/share/codendi/src:/usr/share/jpgraph:. -d memory_limit=256M"
fi

php_display_errors="-d error_reporting=0"
if [ "$DISPLAY_ERRORS" = true ]; then
    php_display_errors=""
fi

# Finally runs php interpretor
phpscript=$1;
shift;
exec "${PHP}" ${php_display_errors} ${PHP_PARAMS} $phpscript "$@"
