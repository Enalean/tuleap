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

namespace Tuleap\ProgramManagement\Domain\Workspace;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;
use Tuleap\ProgramManagement\Domain\Project;

final class ComponentInvolvedVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
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

    public function testNotConsideredAsInvolvedInAProgramWorkspaceWhenItIsNeitherATeamOrProgramProject(): void
    {
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturn(false);
        $this->team_store->shouldReceive('isATeam')->andReturn(false);
        self::assertFalse($this->component_involved_verifier->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAProgramWorkspaceWhenItIsATeamProject(): void
    {
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturn(false);
        $this->team_store->shouldReceive('isATeam')->andReturn(true);
        self::assertTrue($this->component_involved_verifier->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAProgramWorkspaceWhenItIsAProgramProject(): void
    {
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturn(true);
        $this->team_store->shouldReceive('isATeam')->andReturn(false);
        self::assertTrue($this->component_involved_verifier->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    private function buildProjectData(): Project
    {
        return new Project(12, 'Name', 'Public name');
    }
}
