#!/bin/bash
#
# Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

#
# Create the scrum template during setup.sh
#
set -e

import_archive="$(mktemp -d)/"
function cleanup {
    rm -rf "$import_archive"
}
trap cleanup EXIT

cp -r /usr/share/tuleap/tools/utils/setup_templates/scrum/* "$import_archive"

/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/setup_templates/merge_xml_files.php \
    /usr/share/tuleap/tools/utils/setup_templates/scrum/project.xml \
    /usr/share/tuleap/plugins/agiledashboard/www/resources/scrum_dashboard_template.xml \
    "$import_archive/project.xml"

/usr/share/tuleap/src/utils/tuleap import-project-xml \
    -u admin \
    --type=template \
    -i "$import_archive" \
    -m "$import_archive/mapping.csv"
