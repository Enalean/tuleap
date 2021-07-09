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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramIncrementTrackerConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramIncrementTrackerConfiguration\BuildPotentialProgramIncrementTrackerConfigurationPresenters;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramTrackerConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;

final class PotentialProgramIncrementTrackerConfigurationPresentersBuilder implements BuildPotentialProgramIncrementTrackerConfigurationPresenters
{
    private \TrackerFactory $tracker_factory;
    private RetrieveProgramIncrementTracker $program_increment_tracker_retriever;

    public function __construct(
        \TrackerFactory $tracker_factory,
        RetrieveProgramIncrementTracker $program_increment_tracker_retriever
    ) {
        $this->tracker_factory                     = $tracker_factory;
        $this->program_increment_tracker_retriever = $program_increment_tracker_retriever;
    }

    /**
     * @return ProgramTrackerConfigurationPresenter[]
     */
    public function buildPotentialProgramIncrementTrackerPresenters(int $program_id): array
    {
        $all_trackers                 = $this->tracker_factory->getTrackersByGroupId($program_id);
        $program_increment_tracker_id = $this->program_increment_tracker_retriever->getProgramIncrementTrackerId($program_id);

        $potential_tracker_presenters = [];

        foreach ($all_trackers as $potential_tracker) {
            $selected = $potential_tracker->getId() === $program_increment_tracker_id;

            $potential_tracker_presenters[] = new ProgramTrackerConfigurationPresenter(
                $potential_tracker->getId(),
                $potential_tracker->getName(),
                $selected
            );
        }

        return $potential_tracker_presenters;
    }
}
