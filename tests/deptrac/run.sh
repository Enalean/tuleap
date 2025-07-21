#!/usr/bin/env bash

#
# Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
#
#  This file is a part of Tuleap.
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
#

set -euo pipefail

script="$(realpath "$0")"
root_path="$(dirname "$script")/../../"

function checkThatPluginAreDefinedInYmlFile() {
   plugins_dir="plugins"
   yaml_file="tests/deptrac/core_and_plugins_skip_uncovered.yml"

   folders=$(find "$plugins_dir" -mindepth 1 -maxdepth 1 -type d -printf "%f\n")
   yaml_content=$(cat "$yaml_file")
   for folder in $folders; do
    if [[ $yaml_content != *"$folder"* ]]; then
        echo "The plugin '$folder' is NOT defined in deptrac yaml_file '$yaml_file'."
        exit 1
    fi
   done
}

function execDeptrac() {
    local config_file_path="$1"
    local config_file_name
    config_file_name="$(basename "$config_file_path")"
    local args=()
    if [[ -n "${CI_REPORT_OUTPUT_PATH:-}" ]]; then
        args+=(--no-progress --no-interaction --formatter=junit --output="$CI_REPORT_OUTPUT_PATH/${config_file_name%.*}_$(date +%s).xml")
    fi
    if ! [[ "${config_file_name%.*}" == *"skip_uncovered" ]]; then
        args+=(--fail-on-uncovered --report-uncovered)
    fi

    echo "Processing $config_file_path"
    "${PHP:-php}" -d opcache.preload="" "$root_path"/src/vendor/bin/deptrac analyse --config-file="$config_file_path" "${args[@]}"
}

pushd "$root_path" > /dev/null

checkThatPluginAreDefinedInYmlFile

find ./"${SEARCH_PATH:-}" -type f -wholename '*/tests/deptrac/*.yml' -print0 | while IFS= read -r -d '' deptrac_config; do
    execDeptrac "$deptrac_config"
done || exit 1
popd > /dev/null
