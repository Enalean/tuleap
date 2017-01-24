#!/bin/sh

#
# Copyright (c) Enalean, 2012, 2013, 2014, 2016, 2017. All Rights Reserved.
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

# Augment version number of each merged plugin

# Usage:
# $ tools/utils/changelogs/generate.sh

new_tuleap_version=$(awk -F'.' '{OFS="."; if ($3 == '99') { $NF=$NF+1; print} else { $2=$2+1; print $1, $2 }}' VERSION)
echo $new_tuleap_version > VERSION

search_modified_added_or_deleted_files_in_git_staging_area() {
    path=$1
    git status --porcelain | awk -F' ' '($1 == "M" || $1 == "D" || $1 == "A") {print $2}' | grep "$path"
}

modified_plugins=$(search_modified_added_or_deleted_files_in_git_staging_area "plugins/" | cut -d/ -f1,2 | uniq)
modified_themes=$(search_modified_added_or_deleted_files_in_git_staging_area "src/www/themes/" | cut -d/ -f3,4 | uniq)
modified_api=$(search_modified_added_or_deleted_files_in_git_staging_area "src/www/api/" | cut -d/ -f3,4 | uniq)

for item in $modified_plugins $modified_themes $modified_api; do

    item_type=$(echo $item | cut -d/ -f1)
    item_name=$(echo $item | cut -d/ -f2)
    path=$item

    case "$item_type" in
	"themes")
            if [ "$item_name" = 'common' ]; then
		# common theme does not have a version but since Experimental theme
		# depends strongly on it, increase the later one instead
		item_name='FlamingParrot'
		item="themes/$item_name"
            fi

            path="src/www/$item"
	    ;;

	"api")
	    item_name="REST API"
	    path="src/www/api"
	    ;;
    esac

    if [ ! -f $path/VERSION ]; then
	echo "No VERSION found for $path, skip..."
	continue
    fi

    version=$(awk -F'.' '{OFS="."; $NF=$NF+1; print}' "$path/VERSION")
    echo "    * $item_name: $version"
    echo $version > $path/VERSION
done
