<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold the identifier of a Mirrored Iteration Tracker.
 * A Mirrored Iteration Tracker is the Milestone tracker of the second-level planning
 * of a Team project. For example, a Sprint tracker.
 * @psalm-immutable
 */
final class MirroredIterationTrackerIdentifier implements TrackerIdentifier
{
    private function __construct(private int $id)
    {
    }

    public static function fromTeam(
        RetrieveMirroredIterationTracker $tracker_retriever,
        RetrieveProjectReference $project_retriever,
        TeamIdentifier $team,
        UserIdentifier $user,
    ): ?self {
        $team_project               = $project_retriever->buildFromId($team->getId());
        $mirrored_iteration_tracker = $tracker_retriever->retrieveSecondPlanningMilestoneTracker(
            $team_project,
            $user,
            null
        );
        if (! $mirrored_iteration_tracker) {
            return null;
        }
        return new self($mirrored_iteration_tracker->getId());
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
