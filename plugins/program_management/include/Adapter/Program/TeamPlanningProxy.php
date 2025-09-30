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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Planning;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TeamPlanning;
use Tuleap\ProgramManagement\Domain\TrackerReference;

/**
 * @psalm-immutable
 */
final class TeamPlanningProxy implements TeamPlanning
{
    private function __construct(
        private TrackerReference $planning_tracker,
        private int $id,
        private string $name,
        private array $backlog_tracker_ids,
    ) {
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[\Override]
    public function getPlanningTracker(): TrackerReference
    {
        return $this->planning_tracker;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getPlannableTrackerIds(): array
    {
        return $this->backlog_tracker_ids;
    }

    public static function fromPlanning(Planning $root_planning): self
    {
        return new self(
            TrackerReferenceProxy::fromTracker($root_planning->getPlanningTracker()),
            $root_planning->getId(),
            $root_planning->getName(),
            $root_planning->getBacklogTrackersIds()
        );
    }
}
