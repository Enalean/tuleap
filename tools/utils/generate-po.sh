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
translated_plugins=(proftpd tracker captcha git docman)

echo "[core] Generating .pot file"
find "$basedir/src" -name "*.php" \
    | grep -v -E '(common/wiki/phpwiki|common/include/lib)' \
    | xargs xgettext \
        --default-domain=core \
        --from-code=UTF-8 \
        --no-location \
        --sort-output \
        --omit-header \
        -o - \
    | sed '/^msgctxt/d' \
    > "$basedir/site-content/tuleap-core.pot"

echo "[core] Merging .pot file into .po files"
find "$basedir/site-content" -name "tuleap-core.po" -exec msgmerge \
    --update \
    "{}" \
    "$basedir/site-content/tuleap-core.pot" \;

index=0
while [ "x${translated_plugins[index]}" != "x" ]
do
    translated_plugin=${translated_plugins[index]}
    path=$basedir/plugins/$translated_plugin
    index=$(( $index + 1 ))

    echo "[$translated_plugin] Generating default .pot file"
    find "$path/include" -name "*.php" \
        | xargs xgettext \
            --keyword="dgettext:1c,2" \
            --default-domain=$translated_plugin \
            --from-code=UTF-8 \
            --omit-header \
            -o - \
        | msggrep \
            --msgctxt \
            --regexp=$translated_plugin \
            - \
        | sed '/^msgctxt/d' \
        > "$path/site-content/tuleap-$translated_plugin-default.pot"

    echo "[$translated_plugin] Generating plural .pot file"
    find "$path/include" -name "*.php" \
        | xargs xgettext \
            --keyword="dngettext:1c,2,3" \
            --default-domain=$translated_plugin \
            --from-code=UTF-8 \
            --omit-header \
            -o - \
        | msggrep \
            --msgctxt \
            --regexp=$translated_plugin \
            - \
        | sed '/^msgctxt/d' \
        > "$path/site-content/tuleap-$translated_plugin-plural.pot"

    echo "[$translated_plugin] Combining .pot files into one"
    msgcat --no-location --sort-output --use-first \
        "$path/site-content/tuleap-$translated_plugin-plural.pot" \
        "$path/site-content/tuleap-$translated_plugin-default.pot" \
        > "$path/site-content/tuleap-$translated_plugin.pot"
    rm "$path/site-content/tuleap-$translated_plugin-default.pot" \
        "$path/site-content/tuleap-$translated_plugin-plural.pot"

    for foreign_dir in $(find "$path/site-content" -mindepth 1 -maxdepth 1 -type d -not -name "en_US");
    do
        lc_messages=$foreign_dir/LC_MESSAGES
        po_file=$lc_messages/tuleap-$translated_plugin.po
        if [ ! -d "$lc_messages" ];
        then
            echo "[$translated_plugin] Creating missing ${po_file/$basedir\//}"
            mkdir -p "$lc_messages"
            echo 'msgid ""' > "$po_file"
            echo 'msgstr ""' >> "$po_file"
            echo '"Content-Type: text/plain; charset=UTF-8\n"' >> "$po_file"
        fi
    done

    echo "[$translated_plugin] Merging .pot file into .po files"
    find "$path/site-content" -name "tuleap-$translated_plugin.po" -exec msgmerge \
        --update \
        "{}" \
        "$path/site-content/tuleap-$translated_plugin.pot" \;
done
