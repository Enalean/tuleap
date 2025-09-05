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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchProgramIncrementsOfProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramIncrementsSearcher implements SearchOpenProgramIncrements
{
    public function __construct(
        private BuildProgram $build_program,
        private SearchProgramIncrementsOfProgram $program_increments_searcher,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveProgramIncrement $program_increment_retriever,
    ) {
    }

    /**
     * @return ProgramIncrement[]
     *
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     */
    #[\Override]
    public function searchOpenProgramIncrements(int $potential_program_id, UserIdentifier $user): array
    {
        $program = ProgramIdentifier::fromId($this->build_program, $potential_program_id, $user);

        $program_increment_ids = $this->program_increments_searcher->searchOpenProgramIncrements($program);

        $program_increments = [];
        foreach ($program_increment_ids as $id) {
            try {
                $identifier = ProgramIncrementIdentifier::fromId(
                    $this->program_increment_verifier,
                    $this->visibility_verifier,
                    $id,
                    $user
                );
            } catch (ProgramIncrementNotFoundException $e) {
                continue;
            }
            $program_increment = $this->program_increment_retriever->retrieveProgramIncrementById($user, $identifier);
            if ($program_increment !== null) {
                $program_increments[] = $program_increment;
            }
        }

        $this->sortProgramIncrementByStartDate($program_increments);

        return $program_increments;
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
