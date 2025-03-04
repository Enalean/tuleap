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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectFromTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID    = 219;
    private const PROJECT_ID    = 288;
    private const PROJECT_LABEL = 'My project';
    private TrackerReference $tracker_reference;

    protected function setUp(): void
    {
        $this->tracker_reference = TrackerReferenceStub::withId(self::TRACKER_ID);
    }

    private function getRetriever(): ProjectFromTrackerRetriever
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withPublicName(self::PROJECT_LABEL)
            ->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject($project)
            ->build();
        return new ProjectFromTrackerRetriever(RetrieveFullTrackerStub::withTracker($tracker));
    }

    public function testItRetrievesProjectReference(): void
    {
        $project_reference = $this->getRetriever()->fromTrackerReference($this->tracker_reference);
        self::assertSame(self::PROJECT_ID, $project_reference->getId());
        self::assertSame(self::PROJECT_LABEL, $project_reference->getProjectLabel());
    }
}
