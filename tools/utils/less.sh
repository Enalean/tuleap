#!/bin/bash
#
# Copyright (c) Enalean, 2013. All rights reserved
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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/
#

mode=$1
basedir=$2

# Find the path of this directory
if [ -f "$0" ]; then
    mydir=$(dirname $(readlink -f $0))
else
    mydir=$(dirname $(readlink -f $(which $0)))
fi

. $mydir/less_utils.sh

clean_process()
{
    kill -9 `ps aux | grep inotifywait | grep -v grep | awk '{print $2}'`
}

less()
{
    less_path=$(echo "$1/plugins" "$1/src")
    find $less_path -type f -name "*.less" -not -wholename "*/node_modules/*" -not -wholename "*/vendor/*" -not -wholename "*/angular/*" -not -wholename "*/js/kanban/*" -not -wholename "*/js/planning-v2/*" -not -wholename "*/src/www/guidelines/*" -o -wholename "*/src/www/guidelines/_css/*.less" | while read -r file; do
        compile_less $file
    done
}

less_dev()
{
    trap clean_process SIGINT
    echo "LESS watches established for $1 directory..."
    while read file; do
        compile_less $file
    done < <(inotifywait -rqm $1 -e modify,moved_to --format "%w%f")
}

if [[ $mode == "watch" ]]; then
    less_dev $basedir
else
    less $basedir
fi
