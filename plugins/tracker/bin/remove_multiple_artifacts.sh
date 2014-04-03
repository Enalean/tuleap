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
user_name=$1
tracker_id=$2
first_artifact_id=$3
last_artifact_id=$4

$phplauncher $tuleap_base_dir/plugins/tracker/bin/remove_multiple_artifacts.php $user_name $tracker_id $first_artifact_id $last_artifact_id