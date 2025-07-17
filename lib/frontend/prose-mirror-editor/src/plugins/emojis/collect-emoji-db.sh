#!/usr/bin/env bash
# Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

# This script takes list of emoji from unicode here: https://unicode.org/Public/emoji/16.0/emoji-test.txt
# You can find more details here: https://unicode.org/emoji/charts/full-emoji-list.html

set -euo pipefail

SOURCE_FILE='https://unicode.org/Public/emoji/16.0/emoji-test.txt'
OUTPUT_FILE="$(dirname "$0")/unicode-db.json"

rm -f "$OUTPUT_FILE"

SOURCE_CONTENT="$(curl $SOURCE_FILE)"

echo '{' >> "$OUTPUT_FILE"
echo "$SOURCE_CONTENT" | while IFS= read -r line ; do
    if [[ $line =~ ^# ]]; then
        continue
    fi
    if [ -z "$line" ]; then
        continue
    fi
    if [[ $line != *"fully-qualified"* ]]; then
        continue
    fi

    echo "$line" | awk '{ print $2 }' FS="# " | awk '{ emoji = $1; $1=$2=""; print "\""$0"\": \""emoji"\"," }' >> "$OUTPUT_FILE"
done
echo '}' >> "$OUTPUT_FILE"

echo "Please remove the last , at the end of $OUTPUT_FILE"
