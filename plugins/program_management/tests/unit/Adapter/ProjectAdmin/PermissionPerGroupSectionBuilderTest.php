<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\ProjectAdmin;

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\ProgramService;
use UGroupManager;

final class PermissionPerGroupSectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var PermissionPerGroupSectionBuilder
     */
    private $permission_section_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CanPrioritizeFeaturesDAO
     */
    private $can_prioritize_features_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PermissionPerGroupUGroupFormatter
     */
    private $formatter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UGroupManager
     */
    private $ugroup_manager;

    protected function setUp(): void
    {
        $this->can_prioritize_features_dao = $this->createMock(CanPrioritizeFeaturesDAO::class);
        $this->formatter                   = $this->createMock(PermissionPerGroupUGroupFormatter::class);
        $this->ugroup_manager              = $this->createMock(UGroupManager::class);
        $template_renderer                 = new class extends \TemplateRenderer {
            public function renderToString($template_name, $presenter): string
            {
                return 'Rendered template';
            }
        };

        $this->permission_section_builder = new PermissionPerGroupSectionBuilder(
            $this->can_prioritize_features_dao,
            $this->formatter,
            $this->ugroup_manager,
            $template_renderer
        );
    }

    public function testDisplayPermissionsWhenNoUGroupIsSelected(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('getService')->willReturn(new ProgramService($project, ['rank' => 100]));
        $event = new PermissionPerGroupPaneCollector($project, false);

        $this->can_prioritize_features_dao->method('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->willReturn([4]);
        $this->ugroup_manager->method('getUGroup')->with($project, false)->willReturn(null);
        $this->formatter->method('getFormattedUGroups')->willReturn([['name' => 'Project admin']]);

        $this->permission_section_builder->collectSections($event);

        self::assertNotEmpty($event->getPanes());
    }

    public function testDisplayPermissionsWhenAUGroupIsSelected(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('getService')->willReturn(new ProgramService($project, ['rank' => 100]));
        $event = new PermissionPerGroupPaneCollector($project, 4);

        $this->can_prioritize_features_dao->method('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->willReturn([4]);
        $this->ugroup_manager->method('getUGroup')->with($project, 4)->willReturn(new \ProjectUGroup(['group_id' => 102, 'ugroup_id' => 4]));
        $this->formatter->method('getFormattedUGroups')->willReturn([['name' => 'Project admin']]);

        $this->permission_section_builder->collectSections($event);

        self::assertNotEmpty($event->getPanes());
    }

    public function testDisplayPermissionsWhenTheSelectedUGroupIsNotUsedInThePlan(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('getService')->willReturn(new ProgramService($project, ['rank' => 100]));
        $event = new PermissionPerGroupPaneCollector($project, 4);

        $this->can_prioritize_features_dao->method('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->willReturn([2]);
        $this->ugroup_manager->method('getUGroup')->with($project, 4)->willReturn(new \ProjectUGroup(['group_id' => 102, 'ugroup_id' => 4]));
        $this->formatter->method('getFormattedUGroups')->willReturn([['name' => 'Project admin']]);

        $this->permission_section_builder->collectSections($event);

        self::assertNotEmpty($event->getPanes());
    }

    public function testDoesNotDisplayPermissionsWhenTheServiceIsNotActive(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getService')->willReturn(null);

        $event = new PermissionPerGroupPaneCollector($project, false);

        $this->permission_section_builder->collectSections($event);

        self::assertEmpty($event->getPanes());
    }

    public function testDoesNotDisplayPermissionsWhenNoPlanHasBeenDefinedForTheProject(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);
        $project->method('getService')->willReturn(new ProgramService($project, ['rank' => 100]));

        $this->can_prioritize_features_dao->method('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->willReturn([]);

        $event = new PermissionPerGroupPaneCollector($project, false);

        $this->permission_section_builder->collectSections($event);

        self::assertEmpty($event->getPanes());
    }
}
