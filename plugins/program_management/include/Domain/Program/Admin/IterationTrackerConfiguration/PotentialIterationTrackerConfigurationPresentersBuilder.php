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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\IterationTrackerConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;

final class PotentialIterationTrackerConfigurationPresentersBuilder
{
    private RetrieveIterationTracker $iteration_tracker_retriever;

    public function __construct(RetrieveIterationTracker $program_increment_tracker_retriever)
    {
        $this->iteration_tracker_retriever = $program_increment_tracker_retriever;
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildPotentialIterationConfigurationPresenters(
        ProgramForAdministrationIdentifier $program_id,
        PotentialTrackerCollection $all_potential_trackers
    ): array {
        $iteration_tracker_id         = $this->iteration_tracker_retriever->getIterationTrackerId($program_id->id);
        $potential_tracker_presenters = [];

        foreach ($all_potential_trackers->trackers_reference as $potential_tracker) {
            $selected                       = $potential_tracker->id === $iteration_tracker_id;
            $potential_tracker_presenters[] = new ProgramSelectOptionConfigurationPresenter(
                $potential_tracker->id,
                $potential_tracker->label,
                $selected
            );
        }

        return $potential_tracker_presenters;
    }
}
