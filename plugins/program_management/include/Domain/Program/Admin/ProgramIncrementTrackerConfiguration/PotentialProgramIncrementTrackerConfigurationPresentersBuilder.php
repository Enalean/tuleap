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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\ProgramIncrementTrackerConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\ProgramTracker;

final class PotentialProgramIncrementTrackerConfigurationPresentersBuilder
{
    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildPotentialProgramIncrementTrackerPresenters(
        ProgramForAdministrationIdentifier $program,
        ?ProgramTracker $program_increment_tracker,
        PotentialTrackerCollection $all_potential_trackers
    ): array {
        $potential_tracker_presenters = [];

        foreach ($all_potential_trackers->trackers_reference as $potential_tracker) {
            $selected = $program_increment_tracker && $potential_tracker->id === $program_increment_tracker->getTrackerId();

            $potential_tracker_presenters[] = new ProgramSelectOptionConfigurationPresenter(
                $potential_tracker->id,
                $potential_tracker->label,
                $selected
            );
        }

        return $potential_tracker_presenters;
    }
}
