<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveStatusValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTimeframeValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTitleValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveUri;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\VerifyUserCanUpdateTimebox;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class IterationsRetriever
{
    public function __construct(
        private VerifyIsProgramIncrement $verify_is_program_increment,
        private VerifyIsVisibleArtifact $verify_is_visible_artifact,
        private SearchIterations $search_iterations,
        private RetrieveStatusValueUserCanSee $retrieve_status,
        private RetrieveTitleValueUserCanSee $retrieve_title,
        private RetrieveTimeframeValueUserCanSee $retrieve_timeframe,
        private RetrieveUri $retrieve_uri,
        private RetrieveCrossRef $retrieve_cross_ref,
        private VerifyUserCanUpdateTimebox $user_can_update_verifier,
    ) {
    }

    /**
     * @return Iteration[]
     * @throws ProgramIncrementNotFoundException
     */
    public function retrieveIterations(int $program_increment_id, UserIdentifier $user_identifier): array
    {
        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->verify_is_program_increment,
            $this->verify_is_visible_artifact,
            $program_increment_id,
            $user_identifier
        );

        $iterations_identifier = IterationIdentifier::buildCollectionFromProgramIncrement(
            $this->search_iterations,
            $this->verify_is_visible_artifact,
            $program_increment,
            $user_identifier
        );

        $iteration_list = [];
        foreach ($iterations_identifier as $iteration_identifier) {
            $iteration = Iteration::build(
                $this->retrieve_status,
                $this->retrieve_title,
                $this->retrieve_timeframe,
                $this->retrieve_uri,
                $this->retrieve_cross_ref,
                $this->user_can_update_verifier,
                $user_identifier,
                $iteration_identifier
            );
            if ($iteration) {
                $iteration_list[] = $iteration;
            }
        }

        return $this->sortIterationCollectionByDateFromMostToLessRecent($iteration_list);
    }

    /**
     * @param Iteration[] $iterations
     * @return Iteration[]
     */
    private function sortIterationCollectionByDateFromMostToLessRecent(array $iterations): array
    {
        usort($iterations, static function (Iteration $a, Iteration $b) {
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

        return $iterations;
    }
}
