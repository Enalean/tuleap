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
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramStub;

final class ComponentInvolvedVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TeamStore
     */
    private $team_store;
    private VerifyIsProgram $program_verifier;

    protected function setUp(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withValidProgram();
        $this->team_store       = \Mockery::mock(TeamStore::class);
    }

    private function getVerifier(): ComponentInvolvedVerifier
    {
        return new ComponentInvolvedVerifier($this->team_store, $this->program_verifier);
    }

    public function testNotConsideredAsInvolvedInAProgramWorkspaceWhenItIsNeitherATeamOrProgramProject(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->team_store->shouldReceive('isATeam')->andReturn(false);
        self::assertFalse($this->getVerifier()->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAProgramWorkspaceWhenItIsATeamProject(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->team_store->shouldReceive('isATeam')->andReturn(true);
        self::assertTrue($this->getVerifier()->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAProgramWorkspaceWhenItIsAProgramProject(): void
    {
        $this->team_store->shouldReceive('isATeam')->andReturn(false);
        self::assertTrue($this->getVerifier()->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    private function buildProjectData(): Project
    {
        return new Project(12, 'Name', 'Public name');
    }
}
