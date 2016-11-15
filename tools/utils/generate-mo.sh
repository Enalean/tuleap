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

for f in $(find "$basedir/site-content" -name "tuleap-core.po"); do
    locale_dir=$(dirname "$f")
    msgfmt -o "$locale_dir/tuleap-core.mo" "$f"
done

for f in $(find "$basedir/plugins/proftpd/site-content" -name "tuleap-proftpd.po"); do
    locale_dir=$(dirname "$f")
    msgfmt -o "$locale_dir/tuleap-proftpd.mo" "$f"
done
