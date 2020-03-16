<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration
 */

use Mockery as M;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Test\Builders as B;

final class ProjectXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $event_manager;
    private $ugroup_manager;
    private $project;
    private $xml_exporter;
    /**
     * @var string
     */
    private $export_dir;
    private $user;
    private $options;
    private $archive;

    protected function setUp() : void
    {
        $this->event_manager  = M::spy(EventManager::class);
        $this->ugroup_manager = M::spy(UGroupManager::class);
        $xml_validator        = new XML_RNGValidator();
        $user_xml_exporter    = new UserXMLExporter(M::spy(UserManager::class), M::spy(UserXMLExportedCollection::class));
        $this->project        = M::spy(Project::class, ['getPublicName' => 'Project01']);

        $this->xml_exporter   = new ProjectXMLExporter(
            $this->event_manager,
            $this->ugroup_manager,
            $xml_validator,
            $user_xml_exporter,
            M::mock(SynchronizedProjectMembershipDetector::class, ['isSynchronizedWithProjectMembers' => false]),
            M::spy(\Psr\Log\LoggerInterface::class)
        );

        $this->options = array(
            'tracker_id' => 10
        );
        $this->export_dir = "__fixtures";

        $this->archive = M::spy(\Tuleap\Project\XML\Export\ArchiveInterface::class);
        $this->user    = M::spy(PFUser::class);
    }

    public function testItExportsStaticUgroupsForTheGivenProject() : void
    {
        $user_01 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_02 = B\UserTestBuilder::aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_03 = B\UserTestBuilder::aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_04 = B\UserTestBuilder::aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_members = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => 'ugroup_01',
                'getMembers' => [$user_01, $user_02, $user_04],
                'getTranslatedDescription' => 'descr01'
            ]
        );

        $project_ugroup_members2 = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => 'ugroup_02',
                'getMembers' => [$user_03],
                'getTranslatedDescription' => 'descr02'
            ]
        );

        $project_ugroup_members3 = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => 'ugroup_03',
                'getMembers' => [],
                'getTranslatedDescription' => 'descr03'
            ]
        );

        $project_ugroup_dynamic = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => 'ugroup_dynamic',
                'getMembers' => [],
            ]
        );

        $this->ugroup_manager->shouldReceive('getProjectAdminsUGroup')->with($this->project)->andReturns($project_ugroup_dynamic);
        $this->ugroup_manager->shouldReceive('getProjectMembersUGroup')->with($this->project)->andReturns($project_ugroup_dynamic);
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->andReturns(array(
            $project_ugroup_members,
            $project_ugroup_members2,
            $project_ugroup_members3,
        ));

        $this->project->shouldReceive('getServices')->andReturns([]);
        $this->event_manager->shouldReceive('processEvent')->once();

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertNotNull($xml_objet->ugroups);
        $this->assertNotNull($xml_objet->ugroups->ugroup[0]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[1]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[2]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[3]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[4]);

        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]['name'], 'ugroup_01');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]['description'], 'descr01');
        $this->assertNotNull($xml_objet->ugroups->ugroup[2]->members);
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]->members->member[0], 'ldap_01');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]->members->member[0]['format'], 'ldap');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]->members->member[1], 'ldap_02');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]->members->member[1]['format'], 'ldap');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]->members->member[2], 'user_04');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[2]->members->member[2]['format'], 'username');

        $this->assertEquals((string) $xml_objet->ugroups->ugroup[3]['name'], 'ugroup_02');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[3]['description'], 'descr02');
        $this->assertNotNull($xml_objet->ugroups->ugroup[3]->members);
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[3]->members->member[0], 'ldap_03');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[3]->members->member[0]['format'], 'ldap');

        $this->assertEquals((string) $xml_objet->ugroups->ugroup[4]['name'], 'ugroup_03');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[4]['description'], 'descr03');
        $this->assertNotNull($xml_objet->ugroups->ugroup[4]->members);
        $this->assertNull($xml_objet->ugroups->ugroup[4]->members->member[0]);
    }

    public function testItExportsDynamicUgroupsForTheGivenProject() : void
    {
        $user_admin_1 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_1 = B\UserTestBuilder::aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_2 = B\UserTestBuilder::aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_3 = B\UserTestBuilder::aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_project_admins = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN],
                'getMembers' => [$user_admin_1],
            ]
        );

        $project_ugroup_project_members = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
                'getMembers' => [$user_1, $user_2, $user_3],
            ]
        );

        $this->ugroup_manager->shouldReceive('getProjectAdminsUGroup')->with($this->project)->andReturns($project_ugroup_project_admins);
        $this->ugroup_manager->shouldReceive('getProjectMembersUGroup')->with($this->project)->andReturns($project_ugroup_project_members);

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->andReturns([]);

        $this->project->shouldReceive('getServices')->andReturns(array());

        $this->event_manager->shouldReceive('processEvent')->once();

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertNotNull($xml_objet->ugroups);
        $this->assertNotNull($xml_objet->ugroups->ugroup[0]);
        $this->assertNotNull($xml_objet->ugroups->ugroup[1]);

        $this->assertEquals(
            (string) $xml_objet->ugroups->ugroup[0]['name'],
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN]
        );
        $this->assertNotNull($xml_objet->ugroups->ugroup[0]->members);
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[0]->members->member[0], 'ldap_01');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[0]->members->member[0]['format'], 'ldap');

        $this->assertEquals(
            (string) $xml_objet->ugroups->ugroup[1]['name'],
            ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS]
        );
        $this->assertNotNull($xml_objet->ugroups->ugroup[1]->members);
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[1]->members->member[0], 'ldap_02');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[1]->members->member[0]['format'], 'ldap');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[1]->members->member[1], 'ldap_03');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[1]->members->member[1]['format'], 'ldap');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[1]->members->member[2], 'user_04');
        $this->assertEquals((string) $xml_objet->ugroups->ugroup[1]->members->member[2]['format'], 'username');
    }

    public function testItExportsProjectInfo() : void
    {
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

        $this->project->shouldReceive('getUnixName')->andReturns('myproject');
        $this->project->shouldReceive('getDescription')->andReturns('my short desc');
        $this->project->shouldReceive('getServices')->andReturns(array($service_01, $service_02));
        $this->project->shouldReceive('getAccess')->andReturns('public');
        $project_ugroup_dynamic = M::spy(ProjectUGroup::class, ['getNormalizedName' => 'ugroup_dynamic']);
        $project_ugroup_dynamic->shouldReceive('getMembers')->andReturns(array());
        $this->ugroup_manager->shouldReceive('getProjectAdminsUGroup')->with($this->project)->andReturns($project_ugroup_dynamic);
        $this->ugroup_manager->shouldReceive('getProjectMembersUGroup')->with($this->project)->andReturns($project_ugroup_dynamic);
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->andReturns(array());

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertEquals((string) $xml_objet['unix-name'], 'myproject');
        $this->assertEquals((string) $xml_objet['full-name'], 'Project01');
        $this->assertEquals((string) $xml_objet['description'], 'my short desc');
        $this->assertEquals((string) $xml_objet['access'], 'public');

        $this->assertNotNull($xml_objet->services);
        $this->assertEquals((string) $xml_objet->services->service[0]['enabled'], '1');
        $this->assertEquals((string) $xml_objet->services->service[0]['shortname'], 's01');
        $this->assertEquals((string) $xml_objet->services->service[1]['enabled'], '0');
        $this->assertEquals((string) $xml_objet->services->service[1]['shortname'], 's02');
    }
}
