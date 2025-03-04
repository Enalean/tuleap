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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanLinkToProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserCanPlanInProgramIncrementVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 274;
    private UserIdentifierStub $user;
    private UserCanPrioritize $user_can_prioritize;
    private ProgramIncrementIdentifier $program_increment;
    private VerifyUserCanUpdateTimeboxStub $update_verifier;
    private VerifyUserCanLinkToProgramIncrementStub $link_verifier;
    private SearchVisibleTeamsOfProgramStub $team_searcher;
    private BuildProgramStub $program_builder;

    protected function setUp(): void
    {
        $this->update_verifier = VerifyUserCanUpdateTimeboxStub::withAllowed();
        $this->link_verifier   = VerifyUserCanLinkToProgramIncrementStub::withAllowed();

        $this->user                = UserIdentifierStub::buildGenericUser();
        $this->user_can_prioritize = UserCanPrioritize::fromUser(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $this->user,
            ProgramIdentifierBuilder::build(),
            null
        );
        $this->program_increment   = ProgramIncrementIdentifierBuilder::buildWithIdAndUser(
            self::PROGRAM_INCREMENT_ID,
            $this->user
        );
        $this->team_searcher       = SearchVisibleTeamsOfProgramStub::withTeamIds(139, 173);
        $this->program_builder     = BuildProgramStub::stubValidProgram();
    }

    private function getVerifier(): UserCanPlanInProgramIncrementVerifier
    {
        return new UserCanPlanInProgramIncrementVerifier(
            $this->update_verifier,
            RetrieveProgramIncrementTrackerStub::withValidTracker(90),
            $this->link_verifier,
            RetrieveProgramOfProgramIncrementStub::withProgram(141),
            $this->program_builder,
            $this->team_searcher
        );
    }

    public function testUserCanPlan(): void
    {
        self::assertTrue($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenUserCannotUpdateProgramIncrement(): void
    {
        $this->update_verifier = VerifyUserCanUpdateTimeboxStub::withDenied();
        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenUserCannotUpdateArtifactLinkOfProgramIncrement(): void
    {
        $this->link_verifier = VerifyUserCanLinkToProgramIncrementStub::withDenied();
        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenCannotRetrieveProgramOfProgramIncrement(): void
    {
        $this->program_builder = BuildProgramStub::stubInvalidProgramAccess();
        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenOneTeamIsNotVisible(): void
    {
        $this->team_searcher = SearchVisibleTeamsOfProgramStub::withNotVisibleTeam();
        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCanPlanAndPrioritize(): void
    {
        self::assertTrue(
            $this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize)
        );
    }

    public function testUserCannotPlanAndPrioritizeWhenUserCannotUpdateProgramIncrement(): void
    {
        $this->update_verifier = VerifyUserCanUpdateTimeboxStub::withDenied();
        self::assertFalse(
            $this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize)
        );
    }

    public function testUserCannotPlanAndPrioritizeWhenUserCannotUpdateArtifactLinkOfProgramIncrement(): void
    {
        $this->link_verifier = VerifyUserCanLinkToProgramIncrementStub::withDenied();
        self::assertFalse(
            $this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize)
        );
    }

    public function testUserCannotPlanAndPrioritizeWhenCannotRetrieveProgramOfProgramIncrement(): void
    {
        $this->program_builder = BuildProgramStub::stubInvalidProgramAccess();
        self::assertFalse(
            $this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize)
        );
    }

    public function testUserCannotPlanAndPrioritizeWhenOneTeamIsNotVisible(): void
    {
        $this->team_searcher = SearchVisibleTeamsOfProgramStub::withNotVisibleTeam();
        self::assertFalse(
            $this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize)
        );
    }
}
