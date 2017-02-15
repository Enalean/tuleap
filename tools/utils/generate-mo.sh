#!/bin/bash
#
# Copyright (c) Enalean, 2015 - 2017. All rights reserved
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

basedir=$1
translated_plugins=(proftpd tracker captcha git)

echo "[core] Generating .mo file"
for f in $(find "$basedir/site-content" -name "tuleap-core.po"); do
    locale_dir=$(dirname "$f")
    msgfmt -o "$locale_dir/tuleap-core.mo" "$f"
done

index=0
while [ "x${translated_plugins[index]}" != "x" ]
do
    translated_plugin=${translated_plugins[index]}
    index=$(( $index + 1 ))

    echo "[$translated_plugin] Generating .mo file"
    for f in $(find "$basedir/plugins/$translated_plugin/site-content" -name "tuleap-$translated_plugin.po"); do
        locale_dir=$(dirname "$f")
        msgfmt -o "$locale_dir/tuleap-$translated_plugin.mo" "$f"
    done
done
