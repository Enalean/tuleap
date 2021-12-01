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
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class MirroredProgramIncrementTrackerIdentifierCollection
{
    /**
     * @param MirroredProgramIncrementTrackerIdentifier[] $trackers
     */
    private function __construct(private array $trackers)
    {
    }

    /**
     * @throws TeamHasNoMirroredProgramIncrementTrackerException
     */
    public static function fromTeams(
        RetrieveMirroredProgramIncrementTracker $tracker_retriever,
        RetrieveProjectReference $project_retriever,
        TeamIdentifierCollection $teams,
        UserIdentifier $user,
    ): self {
        $trackers = [];
        foreach ($teams->getTeams() as $team) {
            $tracker = MirroredProgramIncrementTrackerIdentifier::fromTeam(
                $tracker_retriever,
                $project_retriever,
                $team,
                $user
            );
            if (! $tracker) {
                throw new TeamHasNoMirroredProgramIncrementTrackerException($team);
            }
            $trackers[] = $tracker;
        }
        return new self($trackers);
    }

    /**
     * @return MirroredProgramIncrementTrackerIdentifier[]
     */
    public function getTrackers(): array
    {
        return $this->trackers;
    }
}
