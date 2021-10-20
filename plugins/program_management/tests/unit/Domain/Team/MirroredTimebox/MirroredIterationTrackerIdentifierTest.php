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

use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class MirroredIterationTrackerIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 21;
    private RetrieveMirroredIterationTrackerStub $tracker_retriever;
    private ProjectReference $team_project;
    private UserIdentifierStub $user;

    protected function setUp(): void
    {
        $this->tracker_retriever = RetrieveMirroredIterationTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::TRACKER_ID)
        );
        $this->team_project      = ProjectReferenceStub::buildGeneric();
        $this->user              = UserIdentifierStub::buildGenericUser();
    }

    public function testItBuildsFromTeam(): void
    {
        $mirrored_iteration_tracker = MirroredIterationTrackerIdentifier::fromTeam(
            $this->tracker_retriever,
            $this->team_project,
            $this->user
        );
        self::assertNotNull($mirrored_iteration_tracker);
        self::assertSame(self::TRACKER_ID, $mirrored_iteration_tracker->getId());
    }

    public function testItReturnsNullWhenTeamHasNoVisibleRootPlanning(): void
    {
        self::assertNull(
            MirroredIterationTrackerIdentifier::fromTeam(
                RetrieveMirroredIterationTrackerStub::withNoVisibleRootPlanning(),
                $this->team_project,
                $this->user
            )
        );
    }

    public function testItReturnsNullWhenTeamHasNoVisibleSecondPlanning(): void
    {
        self::assertNull(
            MirroredIterationTrackerIdentifier::fromTeam(
                RetrieveMirroredIterationTrackerStub::withNoVisibleSecondPlanning(),
                $this->team_project,
                $this->user
            )
        );
    }
}
