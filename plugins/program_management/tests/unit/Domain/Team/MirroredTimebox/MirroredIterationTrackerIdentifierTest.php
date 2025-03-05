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
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredIterationTrackerIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 21;
    private RetrieveMirroredIterationTrackerStub $tracker_retriever;

    protected function setUp(): void
    {
        $this->tracker_retriever = RetrieveMirroredIterationTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::TRACKER_ID)
        );
    }

    private function getMirroredIterationTracker(): ?MirroredIterationTrackerIdentifier
    {
        return MirroredIterationTrackerIdentifier::fromTeam(
            $this->tracker_retriever,
            RetrieveProjectReferenceStub::withProjects(ProjectReferenceStub::buildGeneric()),
            TeamIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser(),
        );
    }

    public function testItBuildsFromTeam(): void
    {
        $mirrored_iteration_tracker = $this->getMirroredIterationTracker();
        self::assertNotNull($mirrored_iteration_tracker);
        self::assertSame(self::TRACKER_ID, $mirrored_iteration_tracker->getId());
    }

    public function testItReturnsNullWhenTeamHasNoVisibleRootPlanning(): void
    {
        $this->tracker_retriever = RetrieveMirroredIterationTrackerStub::withNoVisibleRootPlanning();
        self::assertNull($this->getMirroredIterationTracker());
    }

    public function testItReturnsNullWhenTeamHasNoVisibleSecondPlanning(): void
    {
        $this->tracker_retriever = RetrieveMirroredIterationTrackerStub::withNoVisibleSecondPlanning();
        self::assertNull($this->getMirroredIterationTracker());
    }
}
