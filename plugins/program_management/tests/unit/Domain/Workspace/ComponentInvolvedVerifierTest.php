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

use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ComponentInvolvedVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VerifyIsTeam $team_verifier;
    private VerifyIsProgram $program_verifier;

    protected function setUp(): void
    {
        $this->team_verifier    = VerifyIsTeamStub::withValidTeam();
        $this->program_verifier = VerifyIsProgramStub::withValidProgram();
    }

    private function getVerifier(): ComponentInvolvedVerifier
    {
        return new ComponentInvolvedVerifier($this->team_verifier, $this->program_verifier);
    }

    public function testNotConsideredAsInvolvedInAProgramWorkspaceWhenItIsNeitherATeamOrProgramProject(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->team_verifier    = VerifyIsTeamStub::withNotValidTeam();
        self::assertFalse($this->getVerifier()->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAProgramWorkspaceWhenItIsATeamProject(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        self::assertTrue($this->getVerifier()->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    public function testIsConsideredAsInvolvedInAProgramWorkspaceWhenItIsAProgramProject(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withNotValidTeam();
        self::assertTrue($this->getVerifier()->isInvolvedInAProgramWorkspace($this->buildProjectData()));
    }

    private function buildProjectData(): ProjectReference
    {
        return ProjectReferenceStub::withValues(12, 'Public name', 'Name', '');
    }
}
