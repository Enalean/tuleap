<?php
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

require_once 'common/project/ProjectXMLExporter.class.php';

class ProjectXMLExporterTest extends TuleapTestCase {

    public function itAsksToPluginToExportStuffForTheGivenProject() {
        $event_manager = mock('EventManager');
        $project       = mock('Project');
        $xml_exporter  = new ProjectXMLExporter($event_manager);
        $xml_element   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                               <project />');

        expect($event_manager)->processEvent(Event::EXPORT_XML_PROJECT, array('project' => $project, 'into_xml' => $xml_element))->once();

        $xml_exporter->export($project, $xml_element);
    }
}
?>
