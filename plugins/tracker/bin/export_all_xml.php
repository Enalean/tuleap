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
require_once dirname(__FILE__) .'/../include/Tracker/TrackerManager.class.php';

if ($argc < 2) {
    echo <<< EOT
Usage: $argv[0] project_id filepath.xml
Dump all trackers of a project to XML format

EOT;
    exit(1);
}

$project = ProjectManager::instance()->getProject($argv[1]);
if ($project && !$project->isError()) {
    $xml_content = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <trackers />');
    $tracker_manager = new TrackerManager();
    $xml_element = $tracker_manager->exportToXml($project->getID(), $xml_content);
    $dom = dom_import_simplexml($xml_element)->ownerDocument;
    $dom->formatOutput = true;
    file_put_contents($argv[2], $dom->saveXML());
} else {
    die("*** ERROR: Invalid project_id\n");
}
?>
