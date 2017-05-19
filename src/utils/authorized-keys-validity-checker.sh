#!/usr/bin/env bash

# Copyright (c) Enalean, 2017. All Rights Reserved.
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


set -e

if [ -z "$1" ]; then
    (>&2 echo "Usage: $0 path_to_existing_authorized_keys_file")
    exit 1
fi

TEMPORARY_KEY_FILE="$(mktemp)"

delete_temporary_key_file() {
    rm -f "$TEMPORARY_KEY_FILE"
}
trap delete_temporary_key_file EXIT

while IFS='' read -r line || [[ -n "$line" ]]
do
    echo "$line" > "$TEMPORARY_KEY_FILE"
    ssh-keygen -l -f "$TEMPORARY_KEY_FILE" > /dev/null 2>&1
done < "$1"