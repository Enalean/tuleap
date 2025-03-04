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

use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredProgramIncrementTrackerIdentifierCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TRACKER_ID  = 92;
    private const SECOND_TRACKER_ID = 90;
    private RetrieveMirroredProgramIncrementTrackerStub $tracker_retriever;

    protected function setUp(): void
    {
        $this->tracker_retriever = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::FIRST_TRACKER_ID),
            TrackerReferenceStub::withId(self::SECOND_TRACKER_ID),
        );
    }

    /**
     * @throws TeamHasNoMirroredProgramIncrementTrackerException
     */
    private function getCollection(): MirroredProgramIncrementTrackerIdentifierCollection
    {
        $first_team_id  = 108;
        $second_team_id = 103;
        return MirroredProgramIncrementTrackerIdentifierCollection::fromTeams(
            $this->tracker_retriever,
            RetrieveProjectReferenceStub::withProjects(
                ProjectReferenceStub::withId($first_team_id),
                ProjectReferenceStub::withId($second_team_id),
            ),
            TeamIdentifierCollectionBuilder::buildWithIds($first_team_id, $second_team_id),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFromTeamsCollection(): void
    {
        $collection  = $this->getCollection();
        $tracker_ids = array_map(
            static fn(MirroredProgramIncrementTrackerIdentifier $identifier) => $identifier->getId(),
            $collection->getTrackers()
        );
        self::assertContains(self::FIRST_TRACKER_ID, $tracker_ids);
        self::assertContains(self::SECOND_TRACKER_ID, $tracker_ids);
    }

    public function testItThrowsExceptionWhenOneTeamHasNoVisibleRootPlanning(): void
    {
        $this->tracker_retriever = RetrieveMirroredProgramIncrementTrackerStub::withNoRootPlanning();
        $this->expectException(TeamHasNoMirroredProgramIncrementTrackerException::class);
        $this->getCollection();
    }
}
