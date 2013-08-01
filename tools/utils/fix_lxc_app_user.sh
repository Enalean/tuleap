#!/bin/bash

#
# Copyright Enalean (c) 2013. All rights reserved.
#
# Tuleap and Enalean names and logos are registrated trademarks owned by
# Enalean SAS. All other trademarks or names are properties of their respective
# owners.
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
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#

# This file resets the ownership of files
# Usage:
#   fix_lxc_app_user.sh uid gid
#       Where uid and gid is the new id of the app user
#
# Example:
#   fix_lxc_app_user.sh 1000 1000

if [ $# -ne 2 ]
then
    echo "Usage: $0 uid gid"
    exit 1
fi

newuid=$1
newgid=$2

if [ -e /etc/debian_version ]; then
    appuser="www-data"
    service apache2 stop
else
    appuser="codendiadm"
    service httpd stop
fi


olduid=`id -u $appuser`
oldgid=`id -g $appuser`

usermod -u $newuid $appuser
groupmod -g $newgid $appuser
usermod -g $newgid $appuser
find / -uid $olduid -exec chown $appuser {} \;
find / -gid $oldgid -exec chgrp $appuser {} \;

echo "Please reboot"
