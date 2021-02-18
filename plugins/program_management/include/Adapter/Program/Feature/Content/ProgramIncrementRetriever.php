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

use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\Plan\BuildTracker;

class ProgramIncrementRetriever implements RetrieveProgramIncrement
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var BuildTracker
     */
    private $build_tracker;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        BuildTracker $build_tracker
    ) {
        $this->artifact_factory = $artifact_factory;
        $this->build_tracker    = $build_tracker;
    }

    /**
     * @throws ProgramIncrementNotFoundException
     * @throws ProgramTrackerException
     */
    public function retrieveProgramIncrement(int $program_increment_id, \PFUser $user): PlannedProgramIncrement
    {
        $program_increment = $this->artifact_factory->getArtifactById($program_increment_id);

        if (! $program_increment || ! $program_increment->userCanView($user)) {
            throw new ProgramIncrementNotFoundException();
        }

        $this->build_tracker->buildProgramIncrementTracker(
            $program_increment->getTrackerId(),
            (int) $program_increment->getTracker()->getGroupId()
        );

        return new PlannedProgramIncrement($program_increment->getId());
    }
}
