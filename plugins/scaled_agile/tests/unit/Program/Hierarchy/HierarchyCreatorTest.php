<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Hierarchy;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;

final class HierarchyCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreateHierarchy(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $program_id = 101;
        $program_tracker_id = 1;
        $team_tracker_id = 20;

        $program_build = \Mockery::mock(BuildProgram::class);
        $hierarchy_build = \Mockery::mock(BuildHierarchy::class);
        $hierarchy_store = \Mockery::mock(HierarchyStore::class);

        $hierarchy_creator = new HierarchyCreator($program_build, $hierarchy_build, $hierarchy_store);

        $program = new Program($program_id);
        $hierarchy = new Hierarchy($program_tracker_id, $team_tracker_id);

        $program_build->shouldReceive('buildProgramProject')->once()->andReturn($program);
        $hierarchy_build->shouldReceive('buildHierarchy')->once()->andReturn($hierarchy);

        $hierarchy_store->shouldReceive('save')->with($hierarchy)->once();

        $hierarchy_creator->create($user, $program_id, $program_tracker_id, $team_tracker_id);
    }
}
