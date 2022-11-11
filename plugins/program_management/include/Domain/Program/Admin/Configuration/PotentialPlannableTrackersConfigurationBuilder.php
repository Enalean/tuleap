<?php
/**
 * Copyright (c) Enalean 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class PotentialPlannableTrackersConfigurationBuilder
{
    private RetrievePlannableTrackers $plannable_trackers_retriever;

    public function __construct(RetrievePlannableTrackers $plannable_trackers_retriever)
    {
        $this->plannable_trackers_retriever = $plannable_trackers_retriever;
    }

    /**
     * @return ProgramSelectOptionConfiguration[]
     */
    public function buildPotentialPlannableTracker(ProgramForAdministrationIdentifier $program, PotentialTrackerCollection $all_potential_trackers): array
    {
        $plannable_tracker_ids = $this->plannable_trackers_retriever->getPlannableTrackersOfProgram($program->id);

        $potential_tracker = [];

        foreach ($all_potential_trackers->trackers_reference as $tracker_reference) {
            $selected = $this->isSelected($tracker_reference, $plannable_tracker_ids);

            $potential_tracker[] = new ProgramSelectOptionConfiguration(
                $tracker_reference->getId(),
                $tracker_reference->getLabel(),
                $selected
            );
        }

        return $potential_tracker;
    }

    /**
     * @param TrackerReference[] $plannable_tracker_ids
     */
    private function isSelected(TrackerReference $tracker_reference, array $plannable_tracker_ids): bool
    {
        foreach ($plannable_tracker_ids as $tracker_id) {
            if ($tracker_reference->getId() === $tracker_id->getId()) {
                return true;
            }
        }

        return false;
    }
}
