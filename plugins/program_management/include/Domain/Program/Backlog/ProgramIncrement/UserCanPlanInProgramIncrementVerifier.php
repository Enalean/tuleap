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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\VerifyUserCanUpdateTimebox;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class UserCanPlanInProgramIncrementVerifier
{
    public function __construct(
        private VerifyUserCanUpdateTimebox $update_verifier,
        private RetrieveProgramIncrementTracker $tracker_retriever,
        private VerifyUserCanLinkToProgramIncrement $link_verifier,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder,
        private SearchVisibleTeamsOfProgram $teams_searcher,
    ) {
    }

    public function userCanPlan(
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): bool {
        if (! $this->update_verifier->canUserUpdate($program_increment, $user)) {
            return false;
        }
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromProgramIncrement(
            $this->tracker_retriever,
            $program_increment
        );

        if (! $this->link_verifier->canUserLinkToProgramIncrement($program_increment_tracker, $user)) {
            return false;
        }
        try {
            $program = ProgramIdentifier::fromProgramIncrement(
                $this->program_retriever,
                $this->program_builder,
                $program_increment,
                $user
            );
        } catch (ProgramAccessException | ProjectIsNotAProgramException) {
            return false;
        }

        try {
            TeamIdentifierCollection::fromProgram($this->teams_searcher, $program, $user);
        } catch (ProgramHasNoTeamException | TeamIsNotVisibleException) {
            return false;
        }
        return true;
    }

    /**
     * Check that user can update Program Increment and that User has Prioritize permission
     * (implicit check done by UserCanPrioritize object).
     * This method is a reading help
     */
    public function userCanPlanAndPrioritize(
        ProgramIncrementIdentifier $program_increment,
        UserCanPrioritize $user,
    ): bool {
        return $this->userCanPlan($program_increment, $user);
    }
}
