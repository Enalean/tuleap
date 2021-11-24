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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class MirroredProgramIncrementTrackerIdentifierCollectionBuilder
{
    /**
     * @no-named-arguments
     */
    public static function buildWithIds(
        int $tracker_id,
        int ...$other_tracker_ids
    ): MirroredProgramIncrementTrackerIdentifierCollection {
        $all_tracker_ids = [$tracker_id, ...$other_tracker_ids];
        $trackers        = [];
        $projects        = [];
        $team_ids        = [];
        $first_team_id   = 101;
        foreach ($all_tracker_ids as $id) {
            $trackers[] = TrackerReferenceStub::withId($id);
            $team_ids[] = $first_team_id;
            $projects[] = ProjectReferenceStub::withId($first_team_id);
            $first_team_id++;
        }
        return MirroredProgramIncrementTrackerIdentifierCollection::fromTeams(
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(...$trackers),
            RetrieveProjectReferenceStub::withProjects(...$projects),
            TeamIdentifierCollectionBuilder::buildWithIds(...$team_ids),
            UserIdentifierStub::buildGenericUser()
        );
    }
}
