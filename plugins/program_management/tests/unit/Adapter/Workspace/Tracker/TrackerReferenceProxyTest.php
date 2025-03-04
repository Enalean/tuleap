<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker;

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerReferenceProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID   = 12;
    private const LABEL        = 'sarcosporid';
    private const PROJECT_ID   = 165;
    private const PROJECT_NAME = 'interregna';

    private function getProject(): \Project
    {
        return ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withPublicName(self::PROJECT_NAME)
            ->build();
    }

    private function getTracker(): \Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withName(self::LABEL)
            ->withProject($this->getProject())
            ->build();
    }

    private function getReferenceFromTracker(): TrackerReference
    {
        return TrackerReferenceProxy::fromTracker($this->getTracker());
    }

    public function testItBuildsFromTracker(): void
    {
        $reference = $this->getReferenceFromTracker();
        self::assertSame(self::TRACKER_ID, $reference->getId());
        self::assertSame(self::LABEL, $reference->getLabel());
        self::assertSame(self::PROJECT_ID, $reference->getProjectId());
        self::assertSame(self::PROJECT_NAME, $reference->getProjectLabel());
    }

    private function getReferenceFromIterationTracker(): TrackerReference
    {
        return TrackerReferenceProxy::fromIterationTracker(
            RetrieveFullTrackerStub::withTracker($this->getTracker()),
            IterationTrackerIdentifierBuilder::buildWithId(self::TRACKER_ID)
        );
    }

    public function testItBuildsFromIterationTracker(): void
    {
        $reference = $this->getReferenceFromIterationTracker();
        self::assertSame(self::TRACKER_ID, $reference->getId());
        self::assertSame(self::LABEL, $reference->getLabel());
        self::assertSame(self::PROJECT_ID, $reference->getProjectId());
        self::assertSame(self::PROJECT_NAME, $reference->getProjectLabel());
    }
}
