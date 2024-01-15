<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Project;

use EventManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use ProjectXMLExporter;
use Psr\Log\NullLogger;
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

class ProjectXMLExporterWithSynchronizedUGroupsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EventManager&MockObject $event_manager;
    private UGroupManager&MockObject $ugroup_manager;
    private Project $project;
    private ProjectXMLExporter $xml_exporter;
    private string $export_dir;
    private PFUser $user;
    private ExportOptions $options;
    private ArchiveInterface&MockObject $archive;
    private SynchronizedProjectMembershipDetector&MockObject $synch_detector;

    protected function setUp(): void
    {
        $this->event_manager  = $this->createMock(EventManager::class);
        $this->ugroup_manager = $this->createMock(UGroupManager::class);
        $xml_validator        = new XML_RNGValidator();
        $user_xml_exporter    = new UserXMLExporter($this->createMock(UserManager::class), $this->createPartialMock(UserXMLExportedCollection::class, []));
        $this->project        = B\ProjectTestBuilder::aProject()
            ->withPublicName('Project01')
            ->withStatusActive()
            ->withUnixName('project01')
            ->withDescription('Project 01')
            ->withAccess(Project::ACCESS_PRIVATE)
            ->withoutServices()
            ->build();
        $this->synch_detector = $this->createMock(SynchronizedProjectMembershipDetector::class);

        $dashboard_exporter = $this->createMock(DashboardXMLExporter::class);
        $dashboard_exporter->method('exportDashboards');

        $this->xml_exporter = new ProjectXMLExporter(
            $this->event_manager,
            $this->ugroup_manager,
            $xml_validator,
            $user_xml_exporter,
            $dashboard_exporter,
            $this->synch_detector,
            new NullLogger()
        );

        $this->options    = new ExportOptions("", false, ['tracker_id' => 10]);
        $this->export_dir = "__fixtures";

        $this->archive = $this->createMock(\Tuleap\Project\XML\Export\ArchiveInterface::class);
        $this->user    = B\UserTestBuilder::buildWithDefaults();
    }

    public function testItExportsThatUserGroupsAreSynchronizedWithProjectMembers(): void
    {
        $this->synch_detector->method('isSynchronizedWithProjectMembers')->with($this->project)->willReturn(true);

        $user_1 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();

        $project_ugroup_project_admins = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withUsers($user_1)
            ->withDescription('Project admin')
            ->build();

        $project_ugroup_project_members = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_MEMBERS)
            ->withUsers($user_1)
            ->withDescription('Project members')
            ->build();

        $this->ugroup_manager->method('getProjectAdminsUGroup')->with($this->project)->willReturn($project_ugroup_project_admins);
        $this->ugroup_manager->method('getProjectMembersUGroup')->with($this->project)->willReturn($project_ugroup_project_members);

        $this->ugroup_manager->method('getStaticUGroups')->willReturn([]);

        $this->event_manager->expects(self::once())->method('processEvent');

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        self::assertNotNull($xml_objet->ugroups);
        self::assertEquals('synchronized', (string) $xml_objet->ugroups['mode']);
    }

    public function testItExportsThatUserGroupsAreNotSynchronizedWithProjectMembers(): void
    {
        $this->synch_detector->method('isSynchronizedWithProjectMembers')->with($this->project)->willReturn(false);

        $user_1 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();

        $project_ugroup_project_admins = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withUsers($user_1)
            ->withDescription('Project admin')
            ->build();

        $project_ugroup_project_members = B\ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_MEMBERS)
            ->withUsers($user_1)
            ->withDescription('Project members')
            ->build();

        $this->ugroup_manager->method('getProjectAdminsUGroup')->with($this->project)->willReturn($project_ugroup_project_admins);
        $this->ugroup_manager->method('getProjectMembersUGroup')->with($this->project)->willReturn($project_ugroup_project_members);

        $this->ugroup_manager->method('getStaticUGroups')->willReturn([]);

        $this->event_manager->method('processEvent');

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        self::assertNotNull($xml_objet->ugroups);
        self::assertTrue(! isset($xml_objet->ugroups['mode']));
    }
}
