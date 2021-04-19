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

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveProgramIncrement;

class ProgramIncrementRetriever implements RetrieveProgramIncrement
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ProgramIncrementsDAO
     */
    private $program_increments_dao;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        ProgramIncrementsDAO $program_increments_dao
    ) {
        $this->artifact_factory       = $artifact_factory;
        $this->program_increments_dao = $program_increments_dao;
    }

    /**
     * @throws ProgramIncrementNotFoundException
     */
    public function retrieveProgramIncrement(int $program_increment_id, \PFUser $user): PlannedProgramIncrement
    {
        $program_increment = $this->artifact_factory->getArtifactById($program_increment_id);

        if (
            ! $program_increment ||
            ! $program_increment->userCanView($user) ||
            ! $this->program_increments_dao->isProgramIncrementTracker($program_increment->getTrackerId())
        ) {
            throw new ProgramIncrementNotFoundException();
        }

        return new PlannedProgramIncrement($program_increment->getId());
    }
}
