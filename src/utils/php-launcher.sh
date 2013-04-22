#!/bin/bash

# Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
#
# Originally written by Manuel Vacelet, 2005
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
# along with Codendi; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

set -e

# PHP path and parameters
if [ -z "$PHP" ]; then
    PHP="/usr/bin/php"
fi
# Include path is only defined in php.conf (and not php.ini).
# It was also reported that 8MB (default memory limit) is not sufficient in some cases.
if [ -z "$PHP_PARAMS" ]; then
    PHP_PARAMS="-q -d include_path=/usr/share/pear:/usr/share/codendi/src/www/include:/usr/share/codendi/src:. -d memory_limit=256M -d allow_call_time_pass_reference=On -d display_errors=On"
fi

# Common functions
error() {
    echo "Error $@"
    exit 1
}

# Check if CODENDI_LOCAL_INC variable exists in the environement
# or set a default value
if [ ! -f "${CODENDI_LOCAL_INC}" ]; then
    CODENDI_LOCAL_INC="/etc/codendi/conf/local.inc"
fi
if [ ! -f "${CODENDI_LOCAL_INC}" ]; then
    error "No valid CODENDI_LOCAL_INC found. Please update your environnement."
fi
export CODENDI_LOCAL_INC

# Finaly runs php interpretor
phpscript=$1;
shift;
exec "${PHP}" ${PHP_PARAMS} $phpscript "$@"
