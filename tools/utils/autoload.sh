#!/bin/bash
#
# Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

echo "Running autoload in dev mode"
declare -A AUTOLOAD_EXCLUDES
AUTOLOAD_EXCLUDES["tests"]=1
AUTOLOAD_EXCLUDES["template"]=1

for section in `git status --porcelain src/ | sed s/^...// | cut -d'/' -f 1`; do
    echo "Generate core"
    (cd src/common; phpab -q --compat -o autoload.php --exclude "./wiki/phpwiki/*" .)
    break
done

for section in `git status --porcelain tests/ | sed s/^...// | cut -d'/' -f 1`; do
    echo "Generate tests"
    (cd tests/lib; phpab  -q --compat -o autoload.php .)
    break
done

for plugin in `git status --porcelain plugins/ | sed s/^...// | cut -d'/' -f 2 | sort -u`; do
    if [[ ${AUTOLOAD_EXCLUDES[$plugin]} ]];
    then
        continue;
    fi

    echo "Generate plugin $plugin";
    (cd "plugins/$plugin/include"; phpab -q --compat -o autoload.php .)
done