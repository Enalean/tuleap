<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CheckProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;

final class ProgramIncrementChecker implements CheckProgramIncrement
{
    private \Tracker_ArtifactFactory $artifact_factory;
    private VerifyIsProgramIncrementTracker $verify_is_program_increment_tracker;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        VerifyIsProgramIncrementTracker $verify_is_program_increment_tracker
    ) {
        $this->artifact_factory                    = $artifact_factory;
        $this->verify_is_program_increment_tracker = $verify_is_program_increment_tracker;
    }

    /**
     * @throws ProgramIncrementNotFoundException
     */
    public function checkIsAProgramIncrement(int $program_increment_id, \PFUser $user): void
    {
        $program_increment = $this->artifact_factory->getArtifactById($program_increment_id);

        if (
            ! $program_increment ||
            ! $program_increment->userCanView($user) ||
            ! $this->verify_is_program_increment_tracker->isProgramIncrementTracker($program_increment->getTrackerId())
        ) {
            throw new ProgramIncrementNotFoundException($program_increment_id);
        }
    }
}
