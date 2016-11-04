#!/usr/bin/env bash
#
# Copyright (c) Enalean, 2016. All rights reserved
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
tuleap_path_source=$2

get_files_to_build() {
    local tuleap_source_path=$1
    find "$tuleap_path_source/src" "$tuleap_path_source/plugins" -type f -name "*.scss" \
        -not -name "_*.scss" \
        -not -wholename "*/node_modules/*" \
        -not -wholename "*/vendor/*" \
        -not -wholename "*/angular/*" \
        -not -wholename "*/js/kanban/*" \
        -not -wholename "*/js/planning-v2/*" \
        -not -wholename "*/src/www/themes/common/tlp/*"
}

get_css_file() {
    local sass_file=$1
    echo "${sass_file%.*}.css"
}

build_file_to_css() {
    local sass_file=$1
    local css_file=$2

    echo "Building $sass_file"
    sass --scss --style compressed --sourcemap=none "$sass_file":"$css_file"
}

build_dev_file_to_css() {
    local sass_file=$1
    local css_file=$2

    echo "Building $sass_file"
    sass --scss --style expanded --sourcemap=file "$sass_file":"$css_file"
}

build() {
    local tuleap_source_path=$1
    local build=$2
    local sass_file

    for sass_file in $(get_files_to_build "$tuleap_path_source")
    do
        $build "$sass_file" "$(get_css_file "$sass_file")"
    done
}

if [[ ${mode} == "dev" ]]; then
    build "$tuleap_path_source" build_dev_file_to_css
else
    build "$tuleap_path_source" build_file_to_css
fi
