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

# less-dev usage: less-dev /directory/to/watch

# clean inotifywait process on quit
clean_process()
{
    kill -9 `ps aux | grep inotifywait | grep -v grep | awk '{print $2}'`
}
trap clean_process SIGINT

LESS_PATH=$1
echo "LESS watches established for $LESS_PATH directory..."
while read file; do
    filename=$(basename "$file")
    extension="${filename##*.}"
    filename="${filename%.*}"
    path=$(dirname "$file")

    if [[ 'less' == $extension ]]; then
        echo "Compiling $file"
        # Comments are striped by plessc from css files but we need to keep the license comment at the top of the file
        head -n 100 $file | grep -iP '^/\*\*(\n|.)*?copyright(\n|.)*?\n\s?\*/' > "$path/$filename.css"
        # Append the compiled css after the license comment
        plessc "$path/$filename.less" >> "$path/$filename.css"
    fi
done < <(inotifywait -rqm $LESS_PATH -e modify,moved_to --format "%w%f")