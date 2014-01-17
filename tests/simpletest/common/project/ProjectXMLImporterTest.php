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

require_once 'common/project/ProjectXMLImporter.class.php';

class ProjectXMLImporterTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->event_manager   = mock('EventManager');
        $this->project_manager = mock('ProjectManager');
        $this->project         = mock('Project');

        $this->xml_file_path   = dirname(__FILE__).'/_fixtures/fake_project.xml';
        $this->xml_content     = new SimpleXMLElement(file_get_contents($this->xml_file_path));
        $this->xml_importer    = new ProjectXMLImporter($this->event_manager, $this->project_manager);
    }

    public function itAsksToPluginToImportInformationsFromTheGivenXml() {
        stub($this->project_manager)->getProject()->returns($this->project);

        expect($this->event_manager)->processEvent(Event::IMPORT_XML_PROJECT, array('project' => $this->project, 'xml_content' => $this->xml_content))->once();

        $this->xml_importer->import(369, $this->xml_file_path);
    }

    public function itAsksProjectManagerForTheProject() {
        expect($this->project_manager)->getProject(122)->once();
        $this->expectException();
        $this->xml_importer->import(122, $this->xml_file_path);
    }

    public function itStopsIfNoProjectIsFound() {
        $this->expectException();

        $this->xml_importer->import(122, $this->xml_content);
    }

    public function itStopsIfProjectIsError() {
        stub($this->project_manager)->getProject()->returns(stub('Project')->isError()->returns(true));
        $this->expectException();

        $this->xml_importer->import(122, $this->xml_file_path);
    }

    public function itStopsIfProjectIsDeleted() {
        stub($this->project_manager)->getProject()->returns(stub('Project')->isDeleted()->returns(true));
        $this->expectException();

        $this->xml_importer->import(122, $this->xml_file_path);
    }
}
?>
