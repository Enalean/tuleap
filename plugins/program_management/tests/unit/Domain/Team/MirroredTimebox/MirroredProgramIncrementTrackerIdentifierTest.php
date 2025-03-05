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

use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredProgramIncrementTrackerIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 2;
    private RetrieveMirroredProgramIncrementTrackerStub $tracker_retriever;

    protected function setUp(): void
    {
        $this->tracker_retriever = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::TRACKER_ID),
        );
    }

    private function getTracker(): ?MirroredProgramIncrementTrackerIdentifier
    {
        $team_id = 189;
        return MirroredProgramIncrementTrackerIdentifier::fromTeam(
            $this->tracker_retriever,
            RetrieveProjectReferenceStub::withProjects(ProjectReferenceStub::withId($team_id)),
            TeamIdentifierBuilder::buildWithId($team_id),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFromATeam(): void
    {
        $tracker = $this->getTracker();
        self::assertNotNull($tracker);
        self::assertSame(self::TRACKER_ID, $tracker->getId());
    }

    public function testItReturnsNullWhenTeamHasNoVisibleRootPlanning(): void
    {
        $this->tracker_retriever = RetrieveMirroredProgramIncrementTrackerStub::withNoRootPlanning();
        self::assertNull($this->getTracker());
    }
}
