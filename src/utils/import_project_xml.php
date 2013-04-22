<?php
// #!/usr/share/codendi/src/utils/php-launcher.sh

/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'pre.php';
require_once 'common/project/ProjectXMLImporter.class.php';

if ($argc < 3) {
    echo <<< EOT
Usage: $argv[0] project_id xml
Create a project trackers, agiledashbiard and cardwall from XML format

EOT;
    exit(1);
}

$xml = file_get_contents($argv[2], "r");

$project = ProjectManager::instance()->getProject($argv[1]);
$user = UserManager::instance()->forceLogin('admin');

$user = UserManager::instance()->forceLogin('admin', 'siteadmin');

if ($project && !$project->isError() && !$project->isDeleted()) {
    $xml_element = new SimpleXMLElement($xml);

    $xml_importer = new ProjectXMLImporter(EventManager::instance());
    $xml_importer->import($project, $xml_element);
} else {
    echo "*** ERROR: Invalid project_id\n";
    exit(1);
}
?>
