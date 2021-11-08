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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchOpenProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveStatusValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTimeframeValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTitleValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveUri;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\VerifyUserCanUpdateTimebox;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanPlanInProgramIncrement;
use Tuleap\Tracker\Artifact\Artifact;

final class ProgramIncrementsRetriever implements RetrieveProgramIncrements
{
    public function __construct(
        private SearchOpenProgramIncrement $program_increments_dao,
        private \Tracker_ArtifactFactory $artifact_factory,
        private RetrieveUser $user_manager_adapter,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveStatusValueUserCanSee $retrieve_status,
        private RetrieveTitleValueUserCanSee $retrieve_title,
        private RetrieveTimeframeValueUserCanSee $retrieve_timeframe,
        private RetrieveUri $retrieve_uri,
        private RetrieveCrossRef $retrieve_cross_ref,
        private VerifyUserCanUpdateTimebox $user_can_update_verifier,
        private VerifyUserCanPlanInProgramIncrement $user_can_plan,
    ) {
    }

    /**
     * @return ProgramIncrement[]
     */
    public function retrieveOpenProgramIncrements(ProgramIdentifier $program, UserIdentifier $user_identifier): array
    {
        $user                        = $this->user_manager_adapter->getUserWithId($user_identifier);
        $program_increment_rows      = $this->program_increments_dao->searchOpenProgramIncrements($program->getId());
        $program_increment_artifacts = [];

        foreach ($program_increment_rows as $program_increment_row) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $program_increment_row['id']);
            if ($artifact !== null) {
                $program_increment_artifacts[] = $artifact;
            }
        }

        $program_increments = [];
        foreach ($program_increment_artifacts as $program_increment_artifact) {
            $program_increment = $this->getProgramIncrementFromArtifact($user, $program_increment_artifact);
            if ($program_increment !== null) {
                $program_increments[] = $program_increment;
            }
        }

        $this->sortProgramIncrementByStartDate($program_increments);

        return $program_increments;
    }

    public function retrieveProgramIncrementById(UserIdentifier $user_identifier, ProgramIncrementIdentifier $increment_identifier): ?ProgramIncrement
    {
        $user     = $this->user_manager_adapter->getUserWithId($user_identifier);
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $increment_identifier->getId());
        if (! $artifact) {
            return null;
        }
        return $this->getProgramIncrementFromArtifact($user, $artifact);
    }

    private function getProgramIncrementFromArtifact(
        \PFUser $user,
        Artifact $program_increment_artifact
    ): ?ProgramIncrement {
        $user_identifier              = UserProxy::buildFromPFUser($user);
        $program_increment_identifier = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $program_increment_artifact->getId(),
            $user_identifier
        );

        return ProgramIncrement::build(
            $this->retrieve_status,
            $this->retrieve_title,
            $this->retrieve_timeframe,
            $this->retrieve_uri,
            $this->retrieve_cross_ref,
            $this->user_can_update_verifier,
            $this->user_can_plan,
            $user_identifier,
            $program_increment_identifier,
        );
    }

    /**
     * @param ProgramIncrement[] $program_increments
     */
    private function sortProgramIncrementByStartDate(array &$program_increments): void
    {
        usort($program_increments, function (ProgramIncrement $a, ProgramIncrement $b) {
            if ($a->start_date === $b->start_date) {
                return 0;
            }
            if ($a->start_date === null) {
                return -1;
            }
            if ($b->start_date === null) {
                return 1;
            }

            return $a->start_date > $b->start_date ? -1 : 1;
        });
    }
}
