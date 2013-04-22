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
        $this->event_manager = mock('EventManager');
        $this->user_manager  = mock('UserManager');
        $this->xml_importer  = new ProjectXMLImporter($this->event_manager, $this->user_manager);
        $this->xml_content   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                               <project />');
        $this->super_user = mock('PFUser');
        stub($this->super_user)->isSuperUser()->returns(true);
        stub($this->super_user)->isActive()->returns(true);

        $this->mere_user = mock('PFUser');
    }

    public function itAsksToPluginToImportInformationsFromTheGivenXml() {
        stub($this->user_manager)->forceLogin()->returns($this->super_user);
        $project       = mock('Project');

        expect($this->event_manager)->processEvent(Event::IMPORT_XML_PROJECT, array('project' => $project, 'xml_content' => $this->xml_content))->once();

        $this->xml_importer->import($project, 'good_user', $this->xml_content);
    }

    public function itStopsIfGivenUserIsNotSiteAdmin() {
        expect($this->user_manager)->forceLogin('bad user')->once();
        stub($this->user_manager)->forceLogin()->returns($this->mere_user);

        $this->expectException();
        expect($this->event_manager)->processEvent()->never();
        $this->xml_importer->import(mock('Project'), 'bad user', $this->xml_content);
    }
}
?>
