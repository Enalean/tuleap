#!/bin/sh

# 
# Copyright (c) Enalean, 2012. All Rights Reserved.
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

# Augment version number of each merged plugin and update the changelog
# printout the list of plugins for the main ChangeLog (to be copy-pasted by hand)

# Usage: 
# $ tools/utils/prepare_changelog.sh "Default changelog message to add in each plugins"

default_changelog_message=$1
modified_plugins=`git status --porcelain | grep "^M  plugins/" | awk -F' ' '{print $2}' | cut -d/ -f2 | uniq`
prepend() {
    echo "0a\n$1\n.\nw" | ed -s $2
}
for p in $modified_plugins ; do
    major_version=`cat plugins/$p/VERSION | sed -r "s|(\.[0-9]+)$||"`
    minor_version=`cat plugins/$p/VERSION | sed -r "s|([0-9]+\.)+||"`
    minor_version=`expr 1 + $minor_version`
    version="$major_version.$minor_version"
    echo "    * $p: $version"
    echo $version > plugins/$p/VERSION
    touch plugins/$p/ChangeLog
    prepend "" plugins/$p/ChangeLog
    prepend "    * $default_changelog_message" plugins/$p/ChangeLog
    prepend "Version $version" plugins/$p/ChangeLog
done
