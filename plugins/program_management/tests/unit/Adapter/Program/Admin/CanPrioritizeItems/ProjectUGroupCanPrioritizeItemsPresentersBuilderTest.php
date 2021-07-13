<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectUGroupCanPrioritizeItemsPresentersBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\UGroupManager
     */
    private $ugroup_manager;
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->project_manager = $this->createStub(\ProjectManager::class);
        $this->ugroup_manager  = $this->createStub(\UGroupManager::class);
        $project               = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_manager->method('getProject')->willReturn($project);
        $this->program = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            UserTestBuilder::aUser()->build(),
            $project
        );
    }


    public function testBuildPresenterWithDynamicAndStaticProjectUGroup(): void
    {
        $ugroup_3 = new \ProjectUGroup(['ugroup_id' => 3]);
        $ugroup_4 = new \ProjectUGroup(['ugroup_id' => 105]);

        $this->ugroup_manager->method('getUGroups')->willReturn([$ugroup_3, $ugroup_4]);

        $builder = new ProjectUGroupCanPrioritizeItemsPresentersBuilder(
            $this->ugroup_manager,
            $this->project_manager,
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds(3)
        );

        $presenters = $builder->buildProjectUgroupCanPrioritizeItemsPresenters($this->program);

        self::assertCount(2, $presenters);
        self::assertSame('101_3', $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(105, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }
}
