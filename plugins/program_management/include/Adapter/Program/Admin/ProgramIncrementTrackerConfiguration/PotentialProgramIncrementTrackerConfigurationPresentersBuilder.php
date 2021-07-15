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
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\ProgramTracker;

final class PotentialProgramIncrementTrackerConfigurationPresentersBuilder implements BuildPotentialProgramIncrementTrackerConfigurationPresenters
{
    private \TrackerFactory $tracker_factory;

    public function __construct(\TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildPotentialProgramIncrementTrackerPresenters(int $program_id, ?ProgramTracker $program_increment_tracker): array
    {
        $all_trackers = $this->tracker_factory->getTrackersByGroupId($program_id);

        $potential_tracker_presenters = [];

        foreach ($all_trackers as $potential_tracker) {
            $selected = $program_increment_tracker && $potential_tracker->getId() === $program_increment_tracker->getTrackerId();

            $potential_tracker_presenters[] = new ProgramSelectOptionConfigurationPresenter(
                $potential_tracker->getId(),
                $potential_tracker->getName(),
                $selected
            );
        }

        return $potential_tracker_presenters;
    }
}
