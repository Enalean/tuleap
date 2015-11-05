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

basedir=$1

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

find "$basedir/site-content" -name "tuleap-core.po" -exec msgmerge \
    --update \
    "{}" \
    "$basedir/site-content/tuleap-core.pot" \;

find "$basedir/plugins/proftpd" -name "*.php" \
    | xargs xgettext \
        --keyword="dgettext:1c,2" \
        --default-domain=proftpd \
        --from-code=UTF-8 \
        --no-location \
        --sort-output \
        --omit-header \
        -o - \
    | msggrep \
        --msgctxt \
        --regexp=proftpd \
        --no-location \
        --sort-output \
        - \
    | sed '/^msgctxt/d' \
    > "$basedir/plugins/proftpd/site-content/tuleap-proftpd.pot"

find "$basedir/plugins/proftpd/site-content" -name "tuleap-proftpd.po" -exec msgmerge \
    --update \
    "{}" \
    "$basedir/plugins/proftpd/site-content/tuleap-proftpd.pot" \;
