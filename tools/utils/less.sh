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

clean_process()
{
    kill -9 `ps aux | grep inotifywait | grep -v grep | awk '{print $2}'`
}

compile_less()
{
    recess_options="--format compact --zeroUnits false --strictPropertyOrder false --noUnderscores false --noOverqualifying false --noIDs false --noUniversalSelectors false"
    filename=$(basename "$1")
    extension="${filename##*.}"
    filename="${filename%.*}"
    path=$(dirname "$1")

    if [[ 'less' == $extension ]]; then
        basename "$path" | grep -qE "bootstrap|utils"
        can_compile_to_css=$?

        if [[ $can_compile_to_css == 1 ]]; then
            echo "Compiling $1"
            recess --compile "$path/$filename.less" > "$path/$filename.css"
            nb_generated_lines=$(wc -l "$path/$filename.css" | awk '{print $1}')

            if [[ $nb_generated_lines == 0 ]]; then
                recess $recess_options "$path/$filename.less"
            fi
        fi
    fi
}

less()
{
    less_path=$(echo "$1/plugins" "$1/src")
    find $less_path -type f -name "*.less" | while read -r file; do
        compile_less $file
    done

    recess --compile --compress "$1/src/www/themes/common/css/bootstrap-2.3.2/bootstrap.less" > "$1/src/www/themes/common/css/bootstrap-2.3.2.min.css"
    recess --compile --compress "$1/src/www/themes/common/css/bootstrap-2.3.2/responsive.less" > "$1/src/www/themes/common/css/bootstrap-responsive-2.3.2.min.css"
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
