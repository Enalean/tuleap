#!/bin/sh

#
# Copyright (c) Enalean, 2014. All Rights Reserved.
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

set -e

scriptdir=$(dirname $0)
tuleap_base_dir="$scriptdir/../../.."
phplauncher="$tuleap_base_dir/src/utils/php-launcher.sh"
user=$1
project_id=$2
tv3_id=$3
name=$4
description=$5
itemname=$6

cleanup() {
    rm -f $1
}

# Ensure produced files are only readable by owner
umask 077

TMPFILE=`mktemp -t tv3-export.XXXXXXXXXX` && {
    # Keep tmpfile name by remove it
    # We need a tmp file name but it should not exists 
    # mktemp create a temp unique filename, so we just delete
    # it to reuse the name.
    rm $TMPFILE

    # Create structure
    tv5_id=$($phplauncher $tuleap_base_dir/plugins/tracker/bin/create_tracker_structure_from_tv3.php $user $project_id $tv3_id $name $description $itemname)

    # Export data
    $phplauncher $tuleap_base_dir/src/utils/TrackerV3-data-exporter.php $tv3_id $TMPFILE
    if [ $? -ne 0 ]; then
        cleanup $TMPFILE
        exit 1
    fi

    # Import data
    $phplauncher $tuleap_base_dir/plugins/tracker/bin/import_artifacts_xml.php $user $tv5_id $TMPFILE
    if [ $? -ne 0 ]; then
        cleanup $TMPFILE
        exit 1
    fi

    cleanup $TMPFILE
}
