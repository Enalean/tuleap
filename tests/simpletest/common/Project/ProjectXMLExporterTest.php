<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All Rights Reserved.
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

class ProjectXMLExporterTest extends TuleapTestCase {

    private $event_manager;
    private $ugroup_manager;
    private $project;
    private $xml_exporter;
    /**
     * @var string
     */
    private $export_dir;

    public function setUp() {
        $this->event_manager  = mock('EventManager');
        $this->ugroup_manager = mock('UGroupManager');
        $xml_validator        = new XML_RNGValidator();
        $user_xml_exporter    = new UserXMLExporter(mock('UserManager'), mock('UserXMLExportedCollection'));
        $this->project        = stub('Project')->getPublicName()->returns('Project01');

        $this->xml_exporter   = new ProjectXMLExporter(
            $this->event_manager,
            $this->ugroup_manager,
            $xml_validator,
            $user_xml_exporter,
            mock('ProjectXMLExporterLogger')
        );

        $this->options = array(
            'tracker_id' => 10
        );
        $this->export_dir = "__fixtures";

        $this->archive = mock('Tuleap\Project\XML\Export\ArchiveInterface');
        $this->user    = mock('PFUser');
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function itExportsStaticUgroupsForTheGivenProject() {
        $user_01 = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_02 = aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_03 = aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_04 = aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_members = stub('ProjectUGroup')->getNormalizedName()->returns('ugroup_01');
        stub($project_ugroup_members)->getMembers()->returns(array($user_01, $user_02, $user_04));
        stub($project_ugroup_members)->getTranslatedDescription()->returns("descr01");

        $project_ugroup_members2 = stub('ProjectUGroup')->getNormalizedName()->returns('ugroup_02');
        stub($project_ugroup_members2)->getMembers()->returns(array($user_03));
        stub($project_ugroup_members2)->getTranslatedDescription()->returns("descr02");

        $project_ugroup_members3 = stub('ProjectUGroup')->getNormalizedName()->returns('ugroup_03');
        stub($project_ugroup_members3)->getMembers()->returns(array());
        stub($project_ugroup_members3)->getTranslatedDescription()->returns("descr03");


        $project_ugroup_dynamic = stub('ProjectUGroup')->getNormalizedName()->returns('ugroup_dynamic');
        stub($project_ugroup_dynamic)->getMembers()->returns(array());
        stub($this->ugroup_manager)->getUGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns($project_ugroup_dynamic);
        stub($this->ugroup_manager)->getUGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns($project_ugroup_dynamic);
        stub($this->ugroup_manager)->getStaticUGroups()->returns(array(
            $project_ugroup_members,
            $project_ugroup_members2,
            $project_ugroup_members3,
        ));

        stub($this->project)->getServices()->returns(array());

        expect($this->event_manager)->processEvent(
            Event::EXPORT_XML_PROJECT,
            '*'
        )->once();

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertNotNull($xml_objet->ugroups);
        $this->assertNotNull($xml_objet->ugroups->ugroup[0]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[1]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[2]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[3]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[4]);

        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]['name'], 'ugroup_01');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]['description'], 'descr01');
        $this->assertNotNull($xml_objet->ugroups->ugroup[2]->members);
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]->members->member[0], 'ldap_01');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]->members->member[0]['format'], 'ldap');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]->members->member[1], 'ldap_02');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]->members->member[1]['format'], 'ldap');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]->members->member[2], 'user_04');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[2]->members->member[2]['format'], 'username');

        $this->assertEqual((string)$xml_objet->ugroups->ugroup[3]['name'], 'ugroup_02');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[3]['description'], 'descr02');
        $this->assertNotNull($xml_objet->ugroups->ugroup[3]->members);
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[3]->members->member[0], 'ldap_03');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[3]->members->member[0]['format'], 'ldap');

        $this->assertEqual((string)$xml_objet->ugroups->ugroup[4]['name'], 'ugroup_03');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[4]['description'], 'descr03');
        $this->assertNotNull($xml_objet->ugroups->ugroup[4]->members);
        $this->assertNull($xml_objet->ugroups->ugroup[4]->members->member[0]);
    }

    public function itExportsDynamicUgroupsForTheGivenProject() {
        $user_admin_1 = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_1 = aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_2 = aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_3 = aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_project_admins = stub('ProjectUGroup')->getNormalizedName()->returns(
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN]
        );
        stub($project_ugroup_project_admins)->getMembers()->returns(array($user_admin_1));

        $project_ugroup_project_members = stub('ProjectUGroup')->getNormalizedName()->returns(
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS]
        );
        stub($project_ugroup_project_members)->getMembers()->returns(array($user_1, $user_2, $user_3));

        stub($this->ugroup_manager)->getUGroup($this->project, ProjectUGroup::PROJECT_ADMIN)->returns($project_ugroup_project_admins);
        stub($this->ugroup_manager)->getUGroup($this->project, ProjectUGroup::PROJECT_MEMBERS)->returns($project_ugroup_project_members);
        stub($this->ugroup_manager)->getStaticUGroups()->returns(array());

        stub($this->project)->getServices()->returns(array());

        expect($this->event_manager)->processEvent(
            Event::EXPORT_XML_PROJECT,
            '*'
        )->once();

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertNotNull($xml_objet->ugroups);
        $this->assertNotNull($xml_objet->ugroups->ugroup[0]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[1]);

        $this->assertEqual(
            (string)$xml_objet->ugroups->ugroup[0]['name'],
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN]
        );
        $this->assertNotNull($xml_objet->ugroups->ugroup[0]->members);
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[0]->members->member[0], 'ldap_01');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[0]->members->member[0]['format'], 'ldap');

        $this->assertEqual(
            (string)$xml_objet->ugroups->ugroup[1]['name'],
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS]
        );
        $this->assertNotNull($xml_objet->ugroups->ugroup[1]->members);
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[1]->members->member[0], 'ldap_02');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[1]->members->member[0]['format'], 'ldap');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[1]->members->member[1], 'ldap_03');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[1]->members->member[1]['format'], 'ldap');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[1]->members->member[2], 'user_04');
        $this->assertEqual((string)$xml_objet->ugroups->ugroup[1]->members->member[2]['format'], 'username');
    }

    public function itExportsProjectInfo() {
        $data_01 = array(
            'is_used'    => true,
            'short_name' => 's01'
        );

        $data_02 = array(
            'is_used'    => false,
            'short_name' => 's02'
        );

        $service_01 = new Service($this->project, $data_01);
        $service_02 = new Service($this->project, $data_02);

        stub($this->project)->getUnixName()->returns('myproject');
        stub($this->project)->getDescription()->returns('my short desc');
        stub($this->project)->getServices()->returns(array($service_01, $service_02));
        stub($this->project)->isPublic()->returns(true);
        $project_ugroup_dynamic = stub('ProjectUGroup')->getNormalizedName()->returns('ugroup_dynamic');
        stub($project_ugroup_dynamic)->getMembers()->returns(array());
        stub($this->ugroup_manager)->getUGroup()->returns($project_ugroup_dynamic);
        stub($this->ugroup_manager)->getStaticUGroups()->returns(array());

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertEqual((string)$xml_objet['unix-name'], 'myproject');
        $this->assertEqual((string)$xml_objet['full-name'], 'Project01');
        $this->assertEqual((string)$xml_objet['description'], 'my short desc');
        $this->assertEqual((string)$xml_objet['access'], 'public');

        $this->assertNotNull($xml_objet->services);
        $this->assertEqual((string)$xml_objet->services->service[0]['enabled'], '1');
        $this->assertEqual((string)$xml_objet->services->service[0]['shortname'], 's01');
        $this->assertEqual((string)$xml_objet->services->service[1]['enabled'], '');
        $this->assertEqual((string)$xml_objet->services->service[1]['shortname'], 's02');
    }
}
