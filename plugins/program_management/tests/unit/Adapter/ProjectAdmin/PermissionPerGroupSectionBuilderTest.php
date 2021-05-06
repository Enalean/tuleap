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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\ProgramService;
use UGroupManager;

final class PermissionPerGroupSectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CanPrioritizeFeaturesDAO
     */
    private $can_prioritize_features_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PermissionPerGroupUGroupFormatter
     */
    private $formatter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerGroupSectionBuilder
     */
    private $permission_section_builder;

    protected function setUp(): void
    {
        $this->can_prioritize_features_dao = \Mockery::mock(CanPrioritizeFeaturesDAO::class);
        $this->formatter                   = \Mockery::mock(PermissionPerGroupUGroupFormatter::class);
        $this->ugroup_manager              = \Mockery::mock(UGroupManager::class);
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
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $project->shouldReceive('getService')->andReturn(new ProgramService($project, ['rank' => 100]));
        $event = new PermissionPerGroupPaneCollector($project, false);

        $this->can_prioritize_features_dao->shouldReceive('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->andReturn([4]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, false)->andReturn(null);
        $this->formatter->shouldReceive('getFormattedUGroups')->andReturn([['name' => 'Project admin']]);

        $this->permission_section_builder->collectSections($event);

        self::assertNotEmpty($event->getPanes());
    }

    public function testDisplayPermissionsWhenAUGroupIsSelected(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $project->shouldReceive('getService')->andReturn(new ProgramService($project, ['rank' => 100]));
        $event = new PermissionPerGroupPaneCollector($project, 4);

        $this->can_prioritize_features_dao->shouldReceive('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->andReturn([4]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, 4)->andReturn(new \ProjectUGroup(['group_id' => 102, 'ugroup_id' => 4]));
        $this->formatter->shouldReceive('getFormattedUGroups')->andReturn([['name' => 'Project admin']]);

        $this->permission_section_builder->collectSections($event);

        self::assertNotEmpty($event->getPanes());
    }

    public function testDisplayPermissionsWhenTheSelectedUGroupIsNotUsedInThePlan(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $project->shouldReceive('getService')->andReturn(new ProgramService($project, ['rank' => 100]));
        $event = new PermissionPerGroupPaneCollector($project, 4);

        $this->can_prioritize_features_dao->shouldReceive('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->andReturn([2]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($project, 4)->andReturn(new \ProjectUGroup(['group_id' => 102, 'ugroup_id' => 4]));
        $this->formatter->shouldReceive('getFormattedUGroups')->andReturn([['name' => 'Project admin']]);

        $this->permission_section_builder->collectSections($event);

        self::assertNotEmpty($event->getPanes());
    }

    public function testDoesNotDisplayPermissionsWhenTheServiceIsNotActive(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getService')->andReturn(null);

        $event = new PermissionPerGroupPaneCollector($project, false);

        $this->permission_section_builder->collectSections($event);

        self::assertEmpty($event->getPanes());
    }

    public function testDoesNotDisplayPermissionsWhenNoPlanHasBeenDefinedForTheProject(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);
        $project->shouldReceive('getService')->andReturn(new ProgramService($project, ['rank' => 100]));

        $this->can_prioritize_features_dao->shouldReceive('searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID')->andReturn([]);

        $event = new PermissionPerGroupPaneCollector($project, false);

        $this->permission_section_builder->collectSections($event);

        self::assertEmpty($event->getPanes());
    }
}
