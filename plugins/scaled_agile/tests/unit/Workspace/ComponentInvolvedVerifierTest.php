<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Workspace;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;

final class ComponentInvolvedVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramStore
     */
    private $program_store;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TeamStore
     */
    private $team_store;
    /**
     * @var ComponentInvolvedVerifier
     */
    private $component_involved_verifier;

    protected function setUp(): void
    {
        $this->program_store               = \Mockery::mock(ProgramStore::class);
        $this->team_store                  = \Mockery::mock(TeamStore::class);
        $this->component_involved_verifier = new ComponentInvolvedVerifier($this->team_store, $this->program_store);
    }

    public function testNotConsideredAsInvolvedInAScaledAgileWorkspaceWhenItIsNeitherATeamOrProgramProject(): void
    {
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturn(false);
        $this->team_store->shouldReceive('isATeam')->andReturn(false);
        self::assertFalse($this->component_involved_verifier->isInvolvedInAScaledAgileWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAScaledAgileWorkspaceWhenItIsATeamProject(): void
    {
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturn(false);
        $this->team_store->shouldReceive('isATeam')->andReturn(true);
        self::assertTrue($this->component_involved_verifier->isInvolvedInAScaledAgileWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAScaledAgileWorkspaceWhenItIsAProgramProject(): void
    {
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturn(true);
        $this->team_store->shouldReceive('isATeam')->andReturn(false);
        self::assertTrue($this->component_involved_verifier->isInvolvedInAScaledAgileWorkspace($this->buildProjectData()));
    }

    private function buildProjectData(): ProjectData
    {
        return new ProjectData(12, 'Name', 'Public name');
    }
}
