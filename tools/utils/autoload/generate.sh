#!/bin/bash

#
# Copyright (c) Enalean, 2012. All Rights Reserved.
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

# generate autoload.php file
#
# Usage: tools/utils/autoload/generate.sh <directory>
#
# Example:
# $ tools/utils/autoload/generate.sh plugins/agiledashboard


basedir=`pwd`
directory=$1

cd $directory

# generate the autoload file
phpab --compat -o autoload.php .

# check that there isn't any require_once used based on the result of phpab
inclusions=`phpab -s -t ../../../tools/utils/autoload/filelist.tpl . | cut -d"'" -f2 | sed 's#^.*/##' | tr "\\n" '|' | sed 's/|$//'`
cd $basedir
lines_to_delete=`grep --exclude=autoload.php -rnE "require.*('|\"|/)($inclusions)" $directory | cut -d: -f1,2`
previous_file=''
for line in $lines_to_delete; do
    file=`echo $line | cut -d: -f1`
    line_number=`echo $line | cut -d: -f2`
    if [ "$previous_file" = "" -o "$previous_file" != "$file" ]; then
        if [ "$previous_file" != "" -a "$delete_command" != "" ]; then
            sed -i "$delete_command" $previous_file
        fi
        previous_file=$file
        delete_command=''
    fi
    delete_command="$delete_command $line_number d;"
done

