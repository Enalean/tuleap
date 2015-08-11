#!/bin/bash
#
# Copyright (c) Enalean, 2015. All rights reserved
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

set_user_id()
{
    if [ -n "$USER_ID" ]; then
        chown $USER_ID:$USER_ID $1;
    fi
}

compile_less()
{
    recess_options="$recess_options --format compact --zeroUnits false --strictPropertyOrder false --noUnderscores false --noOverqualifying false --noIDs false --noUniversalSelectors false"
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
            set_user_id "$path/$filename.css"
            nb_generated_lines=$(wc -l "$path/$filename.css" | awk '{print $1}')

            if [[ $nb_generated_lines == 0 ]]; then
                recess $recess_options "$path/$filename.less"
            fi

            if [[ $(echo "$filename" | grep 'FlamingParrot_') ]]; then
                echo "Splitting $filename.css for IE9"
                blessc "$path/$filename.css" "$path/$filename-IE9.css"
            fi
        fi
    fi
}
