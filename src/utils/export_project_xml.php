#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once 'common/project/ProjectXMLExporter.class.php';

if ($argc < 1) {
    echo <<< EOT
Usage: $argv[0] project_id
Dump a project structure to XML format

EOT;
    exit(1);
}

$project = ProjectManager::instance()->getProject($argv[1]);
if ($project && !$project->isError()) {
    $xml_exporter = new ProjectXMLExporter();
    $xml_element = $xml_exporter->export($project);
    $dom = dom_import_simplexml($xml_element)->ownerDocument;
    $dom->formatOutput = true;
    echo $dom->saveXML();
} else {
    die("*** ERROR: Invalid project_id\n");
}
?>
