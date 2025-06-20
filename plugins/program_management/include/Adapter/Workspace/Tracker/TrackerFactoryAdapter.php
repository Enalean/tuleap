<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\SearchTrackersOfProgram;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class TrackerFactoryAdapter implements SearchTrackersOfProgram, RetrieveFullTracker, RetrieveFullTrackerFromId
{
    public function __construct(private \TrackerFactory $tracker_factory)
    {
    }

    /**
     * @return TrackerReference[]
     */
    public function searchAllTrackersOfProgram(ProgramForAdministrationIdentifier $program): array
    {
        $trackers           = $this->tracker_factory->getTrackersByGroupId($program->id);
        $tracker_references = [];

        foreach ($trackers as $tracker) {
            $tracker_references[] = TrackerReferenceProxy::fromTracker($tracker);
        }

        return $tracker_references;
    }

    public function getTrackerFromId(int $tracker_id): ?\Tuleap\Tracker\Tracker
    {
        return $this->tracker_factory->getTrackerById($tracker_id);
    }

    public function getNonNullTracker(TrackerIdentifier $tracker_identifier): \Tuleap\Tracker\Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_identifier->getId());
        if (! $tracker) {
            throw new TrackerNotFoundException($tracker_identifier->getId());
        }
        return $tracker;
    }
}
