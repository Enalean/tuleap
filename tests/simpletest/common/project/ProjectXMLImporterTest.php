<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
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

class ProjectXMLImporterTest extends TuleapTestCase {

    private $event_manager;
    private $project_manager;
    private $project;
    private $ugroup_manager;
    private $xml_file_path;
    private $xml_file_path_with_ugroups;
    private $xml_content;
    private $user_manager;

    /** @var ProjectXMLImporter */
    private $xml_importer;

    public function setUp() {
        parent::setUp();

        $this->event_manager   = mock('EventManager');
        $this->project_manager = mock('ProjectManager');
        $this->project         = stub('Project')->getID()->returns(122);
        $this->ugroup_manager  = mock('UGroupManager');
        $this->user_manager    = mock('UserManager');
        $this->user_finder     = new XMLImportHelper($this->user_manager);

        $this->xml_importer    = new ProjectXMLImporter(
            $this->event_manager,
            $this->project_manager,
            new XML_RNGValidator(),
            $this->ugroup_manager,
            new XMLImportHelper($this->user_manager),
            mock('ProjectXMLImporterLogger')
        );

        $this->xml_file_path              = dirname(__FILE__).'/_fixtures/fake_project.xml';
        $this->xml_file_path_with_ugroups = dirname(__FILE__).'/_fixtures/fake_project_with_ugroups.xml';

        $this->xml_content = new SimpleXMLElement(file_get_contents($this->xml_file_path));
    }

    public function itAsksToPluginToImportInformationsFromTheGivenXml() {
        stub($this->project_manager)->getProject()->returns($this->project);

        expect($this->event_manager)->processEvent(
            Event::IMPORT_XML_PROJECT,
            array(
                'project'         => $this->project,
                'xml_content'     => $this->xml_content,
                'extraction_path' => '',
                'user_finder'     => $this->user_finder,
            )
        )->once();

        $this->xml_importer->import(369, $this->xml_file_path);
    }

    public function itAsksProjectManagerForTheProject() {
        expect($this->project_manager)->getProject(122)->once();
        $this->expectException();
        $this->xml_importer->import(122, $this->xml_file_path);
    }

    public function itStopsIfNoProjectIsFound() {
        $this->expectException();

        $this->xml_importer->import(122, $this->xml_file_path);
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

    public function itImportsProjectDataWithUgroups() {
        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->ugroup_manager)->getUGroupByName()->returns(false);

        $user_01 = aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $user_02 = aUser()->withLdapId('ldap_02')->withUserName('user_02')->withId(102)->build();
        $user_03 = aUser()->withLdapId('ldap_03')->withUserName('user_03')->withId(103)->build();
        $user_04 = aUser()->withUserName('user_04')->withId(104)->build();

        stub($this->user_manager)->getUserByIdentifier('ldapId:ldap_01')->returns($user_01);
        stub($this->user_manager)->getUserByIdentifier('ldapId:ldap_02')->returns($user_02);
        stub($this->user_manager)->getUserByIdentifier('ldapId:ldap_03')->returns($user_03);
        stub($this->user_manager)->getUserByIdentifier('user_04')->returns($user_04);

        $this->ugroup_manager->expectCallCount('createEmptyUgroup', 3);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug01','descr01')->at(0);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug02','descr02')->at(1);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug03','descr03')->at(2);

        $this->ugroup_manager->expectCallCount('addUserToUgroup', 4);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 101)->at(0);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 102)->at(1);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 104)->at(2);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 103)->at(3);

        $this->xml_importer->import(122, $this->xml_file_path_with_ugroups);
    }

    public function itDoesNotImportsExistingUgroups() {
        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->ugroup_manager)->getUGroupByName($this->project, 'ug01')->returns(false);
        stub($this->ugroup_manager)->getUGroupByName($this->project, 'ug02')->returns(true);
        stub($this->ugroup_manager)->getUGroupByName($this->project, 'ug03')->returns(false);

        $user_01 = aUser()->withLdapId('ldap_01')->withUserName('user_01')->withId(101)->build();
        $user_02 = aUser()->withLdapId('ldap_02')->withUserName('user_02')->withId(102)->build();
        $user_04 = aUser()->withUserName('user_04')->withId(104)->build();

        stub($this->user_manager)->getUserByIdentifier('ldapId:ldap_01')->returns($user_01);
        stub($this->user_manager)->getUserByIdentifier('ldapId:ldap_02')->returns($user_02);
        stub($this->user_manager)->getUserByIdentifier('user_04')->returns($user_04);

        $this->ugroup_manager->expectCallCount('createEmptyUgroup', 2);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug01','descr01')->at(0);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug03','descr03')->at(1);

        $this->ugroup_manager->expectCallCount('addUserToUgroup', 3);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 101)->at(0);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 102)->at(1);
        expect($this->ugroup_manager)->addUserToUgroup(122, '*', 104)->at(2);

        $this->xml_importer->import(122, $this->xml_file_path_with_ugroups);
    }
}
