<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

use Tuleap\XML\MappingsRegistry;
use Tuleap\Project\XML\Import;

class ProjectXMLImporterTest extends TuleapTestCase {

    private $event_manager;
    private $project_manager;
    private $project;
    private $ugroup_manager;
    private $xml_file_path;
    private $xml_file_path_with_ugroups;
    private $xml_content;
    private $user_manager;
    private $logger;
    private $configuration;

    /** @var ProjectXMLImporter */
    private $xml_importer;

    public function setUp() {
        parent::setUp();

        $this->event_manager     = mock('EventManager');
        $this->project_manager   = mock('ProjectManager');
        $this->project           = stub('Project')->getID()->returns(122);
        $this->ugroup_manager    = mock('UGroupManager');
        $this->user_manager      = stub('UserManager')->getCurrentUser()->returns(mock('PFUser'));
        $this->user_finder       = new XMLImportHelper($this->user_manager);
        $this->logger            = mock('ProjectXMLImporterLogger');
        $this->ugroup_duplicator = mock('Tuleap\Project\UgroupDuplicator');
        $frs_permissions_creator = mock('Tuleap\FRS\FRSPermissionCreator');
        $user_removal            = mock('Tuleap\Project\UserRemover');

        $project_creator = new ProjectCreator(
            $this->project_manager,
            mock('ReferenceManager'),
            $this->user_manager,
            $this->ugroup_duplicator,
            false,
            $frs_permissions_creator,
            mock('Tuleap\Dashboard\Project\ProjectDashboardDuplicator'),
            mock('Tuleap\Service\ServiceCreator')
        );

        $this->xml_importer = new ProjectXMLImporter(
            $this->event_manager,
            $this->project_manager,
            $this->user_manager,
            new XML_RNGValidator(),
            $this->ugroup_manager,
            new XMLImportHelper($this->user_manager),
            mock('ServiceManager'),
            $this->logger,
            $this->ugroup_duplicator,
            $frs_permissions_creator,
            $user_removal,
            $project_creator
        );

        $this->xml_file_path              = dirname(__FILE__).'/_fixtures/fake_project.xml';
        $this->xml_file_path_with_ugroups = dirname(__FILE__).'/_fixtures/fake_project_with_ugroups.xml';
        $this->xml_file_path_with_members = dirname(__FILE__).'/_fixtures/fake_project_with_project_members.xml';

        $this->xml_content = new SimpleXMLElement(file_get_contents($this->xml_file_path));

        $this->mapping_registery = new MappingsRegistry();

        $this->configuration = new Import\ImportConfig();
    }

    public function itAsksToPluginToImportInformationsFromTheGivenXml() {
        stub($this->project_manager)->getValidProjectByShortNameOrId()->returns($this->project);

        expect($this->event_manager)->processEvent(
            Event::IMPORT_XML_PROJECT,
            array(
                'logger'              => $this->logger,
                'project'             => $this->project,
                'xml_content'         => $this->xml_content,
                'extraction_path'     => '',
                'user_finder'         => $this->user_finder,
                'mappings_registery'  => $this->mapping_registery,
                'configuration'       => $this->configuration,
            )
        )->once();

        $this->xml_importer->import($this->configuration, 369, $this->xml_file_path);
    }

    public function itAsksProjectManagerForTheProject() {
        stub($this->project_manager)->getValidProjectByShortNameOrId()->returns($this->project);
        expect($this->project_manager)->getValidProjectByShortNameOrId(122)->once();
        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path);
    }

    public function itStopsIfNoProjectIsFound() {
        stub($this->project_manager)->getValidProjectByShortNameOrId()->throws(new Project_NotFoundException());
        $this->expectException();

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path);
    }

    public function itImportsProjectDataWithUgroups() {
        stub($this->project_manager)->getValidProjectByShortNameOrId()->returns($this->project);
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
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug01','descr01')->at(0)->returns(555);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug02','descr02')->at(1)->returns(556);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug03','descr03')->at(2)->returns(557);

        $ug01 = mock('ProjectUGroup');
        $ug02 = mock('ProjectUGroup');
        $ug03 = mock('ProjectUGroup');

        stub($this->ugroup_manager)->getById(555)->returns($ug01);
        stub($this->ugroup_manager)->getById(556)->returns($ug02);
        stub($this->ugroup_manager)->getById(557)->returns($ug03);

        $ug01->expectCallCount('addUser', 3);
        $ug02->expectCallCount('addUser', 1);
        $ug03->expectCallCount('addUser', 0);

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_ugroups);
    }

    public function itDoesNotImportsExistingUgroups() {
        stub($this->project_manager)->getValidProjectByShortNameOrId()->returns($this->project);
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
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug01','descr01')->at(0)->returns(555);
        expect($this->ugroup_manager)->createEmptyUgroup(122,'ug03','descr03')->at(1)->returns(557);
        $ug01 = mock('ProjectUGroup');
        $ug02 = mock('ProjectUGroup');
        $ug03 = mock('ProjectUGroup');

        stub($this->ugroup_manager)->getById(555)->returns($ug01);
        stub($this->ugroup_manager)->getById(556)->returns($ug02);
        stub($this->ugroup_manager)->getById(557)->returns($ug03);

        $ug01->expectCallCount('addUser', 3);
        $ug02->expectCallCount('addUser', 0);
        $ug03->expectCallCount('addUser', 0);

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_ugroups);
    }

    public function itDoesNotStopIfUserIsAlreadyProjectMember() {
        $user = stub('PFUser')->getId()->returns(101);
        stub($user)->getLdapId()->returns('ldap_01');
        stub($user)->getUserName()->returns('user_01');
        stub($user)->isMember()->returns(true);

        $project_member_ugroup = mock('ProjectUGroup');

        stub($this->ugroup_manager)->getDynamicUGoupByName($this->project, 'project_members')->returns($project_member_ugroup);
        stub($this->project_manager)->getValidProjectByShortNameOrId()->returns($this->project);
        stub($this->user_manager)->getUserByIdentifier('ldapId:ldap_01')->returns($user);

        //No exception must be raised --> nothing to assert

        $this->xml_importer->import($this->configuration, 122, $this->xml_file_path_with_members);
    }
}
