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

declare(strict_types=1);

namespace Tuleap\Project;

use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use ProjectXMLExporter;
use Psr\Log\NullLogger;
use Service;
use Tuleap\Dashboard\Project\DashboardXMLExporter;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\Export\ExportOptions;
use Tuleap\Test\Builders as B;
use UGroupManager;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;

final class ProjectXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EventManager&MockObject $event_manager;
    private UGroupManager&MockObject $ugroup_manager;
    private \Project $project;
    private ProjectXMLExporter $xml_exporter;
    private string $export_dir;
    private \PFUser $user;
    private ExportOptions $options;
    private ArchiveInterface&MockObject $archive;
    private DashboardXMLExporter&MockObject $dashboard_exporter;

    protected function setUp(): void
    {
        $this->event_manager  = $this->createMock(EventManager::class);
        $this->ugroup_manager = $this->createMock(UGroupManager::class);
        $xml_validator        = new XML_RNGValidator();
        $user_xml_exporter    = new UserXMLExporter($this->createMock(UserManager::class), $this->createPartialMock(UserXMLExportedCollection::class, []));
        $this->project        = B\ProjectTestBuilder::aProject()
            ->withPublicName('Project01')
            ->withUnixName('project01')
            ->withDescription('Wonderfull project')
            ->withAccess(\Project::ACCESS_PRIVATE)
            ->withIcon('😬')
            ->withoutServices()
            ->build();

        $this->dashboard_exporter = $this->createMock(DashboardXMLExporter::class);

        $membership_detector = $this->createMock(SynchronizedProjectMembershipDetector::class);
        $membership_detector->method('isSynchronizedWithProjectMembers')->willReturn(false);
        $this->xml_exporter = new ProjectXMLExporter(
            $this->event_manager,
            $this->ugroup_manager,
            $xml_validator,
            $user_xml_exporter,
            $this->dashboard_exporter,
            $membership_detector,
            new NullLogger()
        );

        $this->options    = new ExportOptions(
            "",
            false,
            ['tracker_id' => 10]
        );
        $this->export_dir = "__fixtures";

        $this->archive = $this->createMock(ArchiveInterface::class);
        $this->user    = B\UserTestBuilder::buildWithDefaults();
    }

    public function testItDoesNotExportUsersIfWeOnlyWantProjectStructure(): void
    {
        $user_01 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_02 = B\UserTestBuilder::aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_03 = B\UserTestBuilder::aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_04 = B\UserTestBuilder::aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_project_admins  = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withUsers($user_01)
            ->withDescription('descr01')
            ->build();
        $project_ugroup_project_members = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_MEMBERS)
            ->withUsers($user_01, $user_02, $user_03)
            ->withDescription('Project members')
            ->build();
        $project_ugroup_static          = B\ProjectUGroupTestBuilder::aCustomUserGroup(103)
            ->withName('Developers')
            ->withUsers($user_01, $user_04)
            ->withDescription('Developers')
            ->build();

        $this->ugroup_manager->method('getProjectAdminsUGroup')->with($this->project)->willReturn($project_ugroup_project_admins);
        $this->ugroup_manager->method('getProjectMembersUGroup')->with($this->project)->willReturn($project_ugroup_project_members);
        $this->ugroup_manager->method('getStaticUGroups')->willReturn([$project_ugroup_static]);

        $this->event_manager->method('processEvent');
        $this->dashboard_exporter->method('exportDashboards');

        $xml = $this->xml_exporter->export(
            $this->project,
            new ExportOptions(ExportOptions::MODE_STRUCTURE, false, []),
            $this->user,
            $this->archive,
            $this->export_dir
        );

        $xml_objet = simplexml_load_string($xml);

        self::assertNotNull($xml_objet->ugroups);
        self::assertNotNull($xml_objet->ugroups->ugroup[0]);
        self::assertNotNull($xml_objet->ugroups->ugroup[1]);
        self::assertNotNull($xml_objet->ugroups->ugroup[2]);

        self::assertCount(0, $xml_objet->ugroups->ugroup[0]->members->member);
        self::assertCount(0, $xml_objet->ugroups->ugroup[1]->members->member);
        self::assertCount(0, $xml_objet->ugroups->ugroup[2]->members->member);
    }

    public function testItExportsStaticUgroupsForTheGivenProject(): void
    {
        $user_01 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_02 = B\UserTestBuilder::aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_03 = B\UserTestBuilder::aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_04 = B\UserTestBuilder::aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_members = B\ProjectUGroupTestBuilder::aCustomUserGroup(101)
            ->withName('ugroup_01')
            ->withUsers($user_01, $user_02, $user_04)
            ->withDescription('descr01')
            ->build();

        $project_ugroup_members2 = B\ProjectUGroupTestBuilder::aCustomUserGroup(102)
            ->withName('ugroup_02')
            ->withUsers($user_03)
            ->withDescription('descr02')
            ->build();

        $project_ugroup_members3 = B\ProjectUGroupTestBuilder::aCustomUserGroup(103)
            ->withName('ugroup_03')
            ->withDescription('descr03')
            ->build();

        $project_ugroup_dynamic = B\ProjectUGroupTestBuilder::aCustomUserGroup(104)
            ->withName('ugroup_dynamic')
            ->withDescription('dynamic')
            ->build();

        $this->ugroup_manager->method('getProjectAdminsUGroup')->with($this->project)->willReturn($project_ugroup_dynamic);
        $this->ugroup_manager->method('getProjectMembersUGroup')->with($this->project)->willReturn($project_ugroup_dynamic);
        $this->ugroup_manager->method('getStaticUGroups')->willReturn([
            $project_ugroup_members,
            $project_ugroup_members2,
            $project_ugroup_members3,
        ]);

        $this->event_manager->expects(self::once())->method('processEvent');
        $this->dashboard_exporter->method('exportDashboards');

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        self::assertNotNull($xml_objet->ugroups);
        self::assertNotNull($xml_objet->ugroups->ugroup[0]);
        self::assertNotNull($xml_objet->ugroups->ugroup[1]);
        self::assertNotNull($xml_objet->ugroups->ugroup[2]);
        self::assertNotNull($xml_objet->ugroups->ugroup[3]);
        self::assertNotNull($xml_objet->ugroups->ugroup[4]);

        self::assertEquals('ugroup_01', (string) $xml_objet->ugroups->ugroup[2]['name']);
        self::assertEquals('descr01', (string) $xml_objet->ugroups->ugroup[2]['description']);
        self::assertNotNull($xml_objet->ugroups->ugroup[2]->members);
        self::assertEquals('ldap_01', (string) $xml_objet->ugroups->ugroup[2]->members->member[0]);
        self::assertEquals('ldap', (string) $xml_objet->ugroups->ugroup[2]->members->member[0]['format']);
        self::assertEquals('ldap_02', (string) $xml_objet->ugroups->ugroup[2]->members->member[1]);
        self::assertEquals('ldap', (string) $xml_objet->ugroups->ugroup[2]->members->member[1]['format']);
        self::assertEquals('user_04', (string) $xml_objet->ugroups->ugroup[2]->members->member[2]);
        self::assertEquals('username', (string) $xml_objet->ugroups->ugroup[2]->members->member[2]['format']);

        self::assertEquals('ugroup_02', (string) $xml_objet->ugroups->ugroup[3]['name']);
        self::assertEquals('descr02', (string) $xml_objet->ugroups->ugroup[3]['description']);
        self::assertNotNull($xml_objet->ugroups->ugroup[3]->members);
        self::assertEquals('ldap_03', (string) $xml_objet->ugroups->ugroup[3]->members->member[0]);
        self::assertEquals('ldap', (string) $xml_objet->ugroups->ugroup[3]->members->member[0]['format']);

        self::assertEquals('ugroup_03', (string) $xml_objet->ugroups->ugroup[4]['name']);
        self::assertEquals('descr03', (string) $xml_objet->ugroups->ugroup[4]['description']);
        self::assertNotNull($xml_objet->ugroups->ugroup[4]->members);
        self::assertNull($xml_objet->ugroups->ugroup[4]->members->member[0]);

        $attrs = $xml_objet->attributes();
        self::assertEquals("😬", (string) $attrs['icon-codepoint']);
    }

    public function testItExportsDynamicUgroupsForTheGivenProject(): void
    {
        $user_admin_1 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_1       = B\UserTestBuilder::aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();
        $user_2       = B\UserTestBuilder::aUser()->withId(103)->withLdapId('ldap_03')->withUserName('user_03')->build();
        $user_3       = B\UserTestBuilder::aUser()->withId(104)->withUserName('user_04')->build();

        $project_ugroup_project_admins = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withUsers($user_admin_1)
            ->withDescription('Project admin')
            ->build();

        $project_ugroup_project_members = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_MEMBERS)
            ->withUsers($user_1, $user_2, $user_3)
            ->withDescription('Project member')
            ->build();

        $this->ugroup_manager->method('getProjectAdminsUGroup')->with($this->project)->willReturn($project_ugroup_project_admins);
        $this->ugroup_manager->method('getProjectMembersUGroup')->with($this->project)->willReturn($project_ugroup_project_members);

        $this->ugroup_manager->method('getStaticUGroups')->willReturn([]);

        $this->event_manager->expects(self::once())->method('processEvent');

        $this->dashboard_exporter->method('exportDashboards');


        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        self::assertNotNull($xml_objet->ugroups);
        self::assertNotNull($xml_objet->ugroups->ugroup[0]);
        self::assertNotNull($xml_objet->ugroups->ugroup[1]);

        self::assertEquals(
            ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_ADMIN],
            (string) $xml_objet->ugroups->ugroup[0]['name']
        );
        self::assertNotNull($xml_objet->ugroups->ugroup[0]->members);
        self::assertEquals('ldap_01', (string) $xml_objet->ugroups->ugroup[0]->members->member[0]);
        self::assertEquals('ldap', (string) $xml_objet->ugroups->ugroup[0]->members->member[0]['format']);

        self::assertEquals(
            ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            (string) $xml_objet->ugroups->ugroup[1]['name']
        );
        self::assertNotNull($xml_objet->ugroups->ugroup[1]->members);
        self::assertEquals('ldap_02', (string) $xml_objet->ugroups->ugroup[1]->members->member[0]);
        self::assertEquals('ldap', (string) $xml_objet->ugroups->ugroup[1]->members->member[0]['format']);
        self::assertEquals('ldap_03', (string) $xml_objet->ugroups->ugroup[1]->members->member[1]);
        self::assertEquals('ldap', (string) $xml_objet->ugroups->ugroup[1]->members->member[1]['format']);
        self::assertEquals('user_04', (string) $xml_objet->ugroups->ugroup[1]->members->member[2]);
        self::assertEquals('username', (string) $xml_objet->ugroups->ugroup[1]->members->member[2]['format']);
    }

    public function testItExportsProjectInfo(): void
    {
        $data_01 = [
            'is_used'    => true,
            'short_name' => 's01',
        ];

        $data_02 = [
            'is_used'    => false,
            'short_name' => 's02',
        ];

        $service_01 = new Service($this->project, $data_01);
        $service_02 = new Service($this->project, $data_02);

        $project                = B\ProjectTestBuilder::aProject()
            ->withPublicName('Project01')
            ->withUnixName('myproject')
            ->withDescription('my short desc')
            ->withServices($service_01, $service_02)
            ->withAccess(\Project::ACCESS_PUBLIC)
            ->build();
        $project_ugroup_dynamic = B\ProjectUGroupTestBuilder::aCustomUserGroup(101)
            ->withName('ugroup_dynamic')
            ->withDescription('dynamic')
            ->build();
        $this->ugroup_manager->method('getProjectAdminsUGroup')->with($project)->willReturn($project_ugroup_dynamic);
        $this->ugroup_manager->method('getProjectMembersUGroup')->with($project)->willReturn($project_ugroup_dynamic);
        $this->ugroup_manager->method('getStaticUGroups')->willReturn([]);
        $this->event_manager->expects(self::once())->method('processEvent');


        $this->dashboard_exporter->method('exportDashboards');

        $xml       = $this->xml_exporter->export($project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        self::assertEquals('myproject', (string) $xml_objet['unix-name']);
        self::assertEquals('Project01', (string) $xml_objet['full-name']);
        self::assertEquals('my short desc', (string) $xml_objet['description']);
        self::assertEquals('public', (string) $xml_objet['access']);

        self::assertNotNull($xml_objet->services);
        self::assertEquals('1', (string) $xml_objet->services->service[0]['enabled']);
        self::assertEquals('s01', (string) $xml_objet->services->service[0]['shortname']);
        self::assertEquals('0', (string) $xml_objet->services->service[1]['enabled']);
        self::assertEquals('s02', (string) $xml_objet->services->service[1]['shortname']);
    }

    public function testItThrowExceptionIfProjectIsSuspended(): void
    {
        $project = B\ProjectTestBuilder::aProject()->withStatusSuspended()->build();
        self::expectException(ProjectIsInactiveException::class);
        $this->xml_exporter->export($project, $this->options, $this->user, $this->archive, $this->export_dir);
    }
}
