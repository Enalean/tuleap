<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackersIds;

final class PlannableTrackersRetriever implements RetrievePlannableTrackers
{
    public function __construct(public RetrievePlannableTrackersIds $plan_dao, public \TrackerFactory $tracker_factory)
    {
    }

    #[\Override]
    public function getPlannableTrackersOfProgram(int $program_id): array
    {
        $tracker_reference_list    = [];
        $plannable_tracker_id_list = $this->plan_dao->getPlannableTrackersIdOfProgram($program_id);
        foreach ($plannable_tracker_id_list as $plannable_tracker_id) {
            $tracker = $this->tracker_factory->getTrackerById($plannable_tracker_id);
            if (! $tracker) {
                continue;
            }
            $tracker_reference_list[] = TrackerReferenceProxy::fromTracker($tracker);
        }

        return $tracker_reference_list;
    }
}
