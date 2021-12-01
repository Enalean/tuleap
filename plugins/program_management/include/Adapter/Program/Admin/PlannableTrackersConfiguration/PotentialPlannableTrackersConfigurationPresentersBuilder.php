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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\PlannableTrackersConfiguration;

use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;

final class PotentialPlannableTrackersConfigurationPresentersBuilder
{
    private RetrievePlannableTrackers $plannable_trackers_retriever;

    public function __construct(
        RetrievePlannableTrackers $plannable_trackers_retriever,
    ) {
        $this->plannable_trackers_retriever = $plannable_trackers_retriever;
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildPotentialPlannableTrackerPresenters(ProgramForAdministrationIdentifier $program, PotentialTrackerCollection $all_potential_trackers): array
    {
        $plannable_tracker_ids = $this->plannable_trackers_retriever->getPlannableTrackersOfProgram($program->id);

        $potential_tracker_presenters = [];

        foreach ($all_potential_trackers->trackers_reference as $potential_tracker) {
            $selected = \in_array($potential_tracker->getId(), $plannable_tracker_ids);

            $potential_tracker_presenters[] = new ProgramSelectOptionConfigurationPresenter(
                $potential_tracker->getId(),
                $potential_tracker->getLabel(),
                $selected
            );
        }

        return $potential_tracker_presenters;
    }
}
